package orchestrator

import (
	"bytes"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"net/url"
	"os"
	"strconv"
	"strings"
	"time"

	"github.com/jmoiron/sqlx"
)

type Runner struct {
	DB              *sqlx.DB
	Enabled         bool
	PollInterval    time.Duration
	BaseURL         string
	Token           string
	MaxAttempts     int
	RetryBackoffSec int
	HTTPClient      *http.Client
}

type scheduleRow struct {
	ID              int64  `db:"id"`
	JobName         string `db:"job_name"`
	IntervalSeconds int    `db:"interval_seconds"`
	PayloadJSON     string `db:"payload_json"`
}

type eventRow struct {
	ID          int64  `db:"id"`
	EventType   string `db:"event_type"`
	AggregateID int64  `db:"aggregate_id"`
	PayloadJSON string `db:"payload_json"`
	Attempts    int    `db:"attempts"`
}

func NewRunnerFromEnv(db *sqlx.DB) *Runner {
	enabled := parseBool(os.Getenv("GM_ORCHESTRATOR_ENABLED"), false)
	pollMs := parseInt(os.Getenv("GM_ORCHESTRATOR_POLL_MS"), 1000)
	baseURL := strings.TrimRight(strings.TrimSpace(os.Getenv("GM_ORCHESTRATOR_INTERNAL_BASE_URL")), "/")
	if baseURL == "" {
		baseURL = "http://nginx"
	}

	return &Runner{
		DB:              db,
		Enabled:         enabled,
		PollInterval:    time.Duration(max(250, pollMs)) * time.Millisecond,
		BaseURL:         baseURL,
		Token:           strings.TrimSpace(os.Getenv("GM_ORCHESTRATOR_TOKEN")),
		MaxAttempts:     max(1, parseInt(os.Getenv("GM_ORCHESTRATOR_RETRY_MAX"), 8)),
		RetryBackoffSec: max(5, parseInt(os.Getenv("GM_ORCHESTRATOR_RETRY_BACKOFF_SEC"), 30)),
		HTTPClient: &http.Client{
			Timeout: 20 * time.Second,
		},
	}
}

func (r *Runner) Start() {
	if !r.Enabled {
		log.Println("[orchestrator] disabled")
		return
	}
	if r.Token == "" {
		log.Println("[orchestrator] disabled: missing GM_ORCHESTRATOR_TOKEN")
		return
	}

	log.Printf("[orchestrator] enabled poll=%s base=%s", r.PollInterval, r.BaseURL)
	ticker := time.NewTicker(r.PollInterval)
	defer ticker.Stop()

	for range ticker.C {
		r.processSchedules()
		r.processEvents()
	}
}

func (r *Runner) processSchedules() {
	now := time.Now().Unix()
	rows := []scheduleRow{}
	err := r.DB.Select(&rows, `
		SELECT id, job_name, interval_seconds, COALESCE(payload_json, '') AS payload_json
		FROM orchestrator_schedule
		WHERE enabled = 1
		  AND next_run_at <= ?
		ORDER BY next_run_at, id
		LIMIT 10
	`, now)
	if err != nil {
		log.Printf("[orchestrator] schedule select error: %v", err)
		return
	}

	for _, row := range rows {
		nextRun := now + int64(max(30, row.IntervalSeconds))
		res, upErr := r.DB.Exec(`
			UPDATE orchestrator_schedule
			   SET next_run_at = ?, updated_at = ?
			 WHERE id = ?
			   AND enabled = 1
			   AND next_run_at <= ?
		`, nextRun, now, row.ID, now)
		if upErr != nil {
			log.Printf("[orchestrator] schedule claim error job=%s id=%d err=%v", row.JobName, row.ID, upErr)
			continue
		}
		affected, _ := res.RowsAffected()
		if affected != 1 {
			continue
		}

		runID := r.startRun("schedule", row.ID, row.JobName, fmt.Sprintf("schedule:%d:%d", row.ID, now), now)
		metrics, execErr := r.execScheduleJob(row)
		if execErr != nil {
			log.Printf("[orchestrator] schedule job error job=%s id=%d err=%v", row.JobName, row.ID, execErr)
			r.finishRun(runID, "failed", execErr.Error(), metrics, time.Now().Unix())
			_, _ = r.DB.Exec(
				"UPDATE orchestrator_schedule SET next_run_at=?, updated_at=? WHERE id=?",
				now+int64(r.RetryBackoffSec),
				time.Now().Unix(),
				row.ID,
			)
			continue
		}

		_, _ = r.DB.Exec("UPDATE orchestrator_schedule SET last_run_at=?, updated_at=? WHERE id=?", time.Now().Unix(), time.Now().Unix(), row.ID)
		r.finishRun(runID, "ok", "", metrics, time.Now().Unix())
	}
}

func (r *Runner) processEvents() {
	now := time.Now().Unix()
	rows := []eventRow{}
	err := r.DB.Select(&rows, `
		SELECT id, event_type, COALESCE(aggregate_id, 0) AS aggregate_id, COALESCE(payload_json, '') AS payload_json, attempts
		FROM orchestrator_event
		WHERE status = 'pending'
		  AND available_at <= ?
		ORDER BY available_at, id
		LIMIT 20
	`, now)
	if err != nil {
		log.Printf("[orchestrator] event select error: %v", err)
		return
	}

	for _, row := range rows {
		res, upErr := r.DB.Exec(`
			UPDATE orchestrator_event
			   SET status = 'processing',
			       attempts = attempts + 1
			 WHERE id = ?
			   AND status = 'pending'
			   AND available_at <= ?
		`, row.ID, now)
		if upErr != nil {
			log.Printf("[orchestrator] event claim error id=%d err=%v", row.ID, upErr)
			continue
		}
		affected, _ := res.RowsAffected()
		if affected != 1 {
			continue
		}

		attempt := row.Attempts + 1
		runID := r.startRun("event", row.ID, row.EventType, fmt.Sprintf("event:%d:%d", row.ID, attempt), now)
		metrics, execErr := r.execEvent(row)
		if execErr != nil {
			log.Printf("[orchestrator] event error type=%s id=%d attempt=%d err=%v", row.EventType, row.ID, attempt, execErr)
			r.finishRun(runID, "failed", execErr.Error(), metrics, time.Now().Unix())

			if attempt >= r.MaxAttempts {
				_, _ = r.DB.Exec(`
					UPDATE orchestrator_event
					   SET status='failed',
					       processed_at=?,
					       last_error=?
					 WHERE id=?
				`, time.Now().Unix(), limitErr(execErr.Error()), row.ID)
				continue
			}

			_, _ = r.DB.Exec(`
				UPDATE orchestrator_event
				   SET status='pending',
				       available_at=?,
				       last_error=?
				 WHERE id=?
			`, time.Now().Unix()+int64(r.RetryBackoffSec), limitErr(execErr.Error()), row.ID)
			continue
		}

		_, _ = r.DB.Exec(`
			UPDATE orchestrator_event
			   SET status='done',
			       processed_at=?,
			       last_error=NULL
			 WHERE id=?
		`, time.Now().Unix(), row.ID)
		r.finishRun(runID, "ok", "", metrics, time.Now().Unix())
	}
}

// jobEndpoint maps job names to their internal PHP endpoint paths.
var jobEndpoint = map[string]string{
	// market
	"market.resolve_auctions": "/orchestrator/market-resolve",
	"economy.market_refresh":  "/orchestrator/market-refresh",
	// fixtures
	"game.run_fixtures": "/orchestrator/run-fixtures",
	// notifications
	"notification.dispatch":  "/orchestrator/notification-dispatch",
	"notification.pre_match": "/orchestrator/pre-match-notifications",
	"economy.daily_digest":   "/orchestrator/daily-digest",
	// economy
	"economy.pay_wages":        "/orchestrator/pay-wages",
	"economy.daily_training":   "/orchestrator/daily-training",
	"economy.weekly_recovery":  "/orchestrator/weekly-recovery",
	"economy.loan_returns":     "/orchestrator/loan-returns",
	"economy.auto_rollover":    "/orchestrator/auto-rollover",
	"economy.expansion_pool":   "/orchestrator/expansion-pool",
	"economy.expire_friendlies": "/orchestrator/expire-friendlies",
	// game
	"game.cpu_formations": "/orchestrator/cpu-formations",
	"scouting.process":    "/orchestrator/scouting",
}

func (r *Runner) execScheduleJob(row scheduleRow) (map[string]any, error) {
	endpoint, ok := jobEndpoint[row.JobName]
	if !ok {
		return map[string]any{"job": row.JobName}, fmt.Errorf("unknown schedule job: %s", row.JobName)
	}
	out, err := r.callInternal(endpoint, nil)
	return map[string]any{"response": out}, err
}

func (r *Runner) execEvent(row eventRow) (map[string]any, error) {
	switch row.EventType {
	case "friendly.start_now":
		values := url.Values{}
		values.Set("id", strconv.FormatInt(row.AggregateID, 10))

		payload := map[string]any{}
		if row.PayloadJSON != "" {
			_ = json.Unmarshal([]byte(row.PayloadJSON), &payload)
			if challengeID, ok := payload["challenge_id"]; ok {
				switch v := challengeID.(type) {
				case float64:
					values.Set("id", strconv.FormatInt(int64(v), 10))
				case int64:
					values.Set("id", strconv.FormatInt(v, 10))
				case string:
					values.Set("id", v)
				}
			}
		}

		out, err := r.callInternal("/orchestrator/friendly-start-now", values)
		return map[string]any{"response": out}, err
	default:
		return map[string]any{"event": row.EventType}, fmt.Errorf("unknown event type: %s", row.EventType)
	}
}

func (r *Runner) callInternal(path string, form url.Values) (string, error) {
	endpoint := r.BaseURL + path
	if form == nil {
		form = url.Values{}
	}
	req, err := http.NewRequest(http.MethodPost, endpoint, bytes.NewBufferString(form.Encode()))
	if err != nil {
		return "", err
	}
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("X-Orchestrator-Token", r.Token)

	resp, err := r.HTTPClient.Do(req)
	if err != nil {
		return "", err
	}
	defer resp.Body.Close()
	body, _ := io.ReadAll(io.LimitReader(resp.Body, 1024*16))
	raw := string(body)
	if resp.StatusCode < 200 || resp.StatusCode >= 300 {
		return raw, fmt.Errorf("http %d: %s", resp.StatusCode, raw)
	}
	return raw, nil
}

func (r *Runner) startRun(sourceType string, sourceID int64, jobName, idem string, now int64) int64 {
	res, err := r.DB.Exec(`
		INSERT INTO orchestrator_run
		(source_type, source_id, job_name, idempotency_key, status, started_at)
		VALUES (?, ?, ?, ?, 'running', ?)
	`, sourceType, sourceID, jobName, idem, now)
	if err != nil {
		log.Printf("[orchestrator] startRun insert error: %v", err)
		return 0
	}
	id, _ := res.LastInsertId()
	return id
}

func (r *Runner) finishRun(runID int64, status, errText string, metrics map[string]any, finishedAt int64) {
	if runID <= 0 {
		return
	}
	metricsJSON, _ := json.Marshal(metrics)
	_, err := r.DB.Exec(`
		UPDATE orchestrator_run
		   SET status=?, finished_at=?, error_text=?, metrics_json=?
		 WHERE id=?
	`, status, finishedAt, nullable(limitErr(errText)), nullable(string(metricsJSON)), runID)
	if err != nil {
		log.Printf("[orchestrator] finishRun update error: %v", err)
	}
}

func parseBool(raw string, def bool) bool {
	if strings.TrimSpace(raw) == "" {
		return def
	}
	switch strings.ToLower(strings.TrimSpace(raw)) {
	case "1", "true", "yes", "on":
		return true
	case "0", "false", "no", "off":
		return false
	default:
		return def
	}
}

func parseInt(raw string, def int) int {
	raw = strings.TrimSpace(raw)
	if raw == "" {
		return def
	}
	n, err := strconv.Atoi(raw)
	if err != nil {
		return def
	}
	return n
}

func max(a, b int) int {
	if a > b {
		return a
	}
	return b
}

func nullable(v string) any {
	if strings.TrimSpace(v) == "" {
		return nil
	}
	return v
}

func limitErr(msg string) string {
	msg = strings.TrimSpace(msg)
	if len(msg) > 255 {
		return msg[:255]
	}
	return msg
}
