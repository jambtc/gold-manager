package main

import (
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"net/http"
	"os"
	"strconv"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/jmoiron/sqlx"
	"gold-manager-worker/engine"
	"gold-manager-worker/orchestrator"
	"gold-manager-worker/stream"
)

var db *sqlx.DB
var matchEngine *engine.MatchEngine
var sseHub *stream.Hub
var orchestratorRunner *orchestrator.Runner

func initDB() {
	host := os.Getenv("DB_HOST")
	name := os.Getenv("DB_NAME")
	user := os.Getenv("DB_USER")
	pass := os.Getenv("DB_PASSWORD")

	dsn := fmt.Sprintf(
		"%s:%s@tcp(%s:3306)/%s?parseTime=true&charset=utf8mb4&collation=utf8mb4_unicode_ci",
		user,
		pass,
		host,
		name,
	)

	var err error
	db, err = sqlx.Connect("mysql", dsn)
	if err != nil {
		log.Fatalf("Error connecting to database: %v", err)
	}

	db.SetMaxOpenConns(20)
	db.SetMaxIdleConns(10)
	db.SetConnMaxLifetime(time.Minute * 5)

	log.Println("Successfully connected to MariaDB")

	sseHub = stream.NewHub()
	matchEngine = &engine.MatchEngine{DB: db, Hub: sseHub}
	orchestratorRunner = orchestrator.NewRunnerFromEnv(db)
}

func main() {
	log.Println("Starting Gold Manager Live Engine Worker with SSE Support...")
	initDB()

	// Start SSE Server in a goroutine
	go func() {
		log.Println("Starting SSE server on :8080")
		http.Handle("/stream/", sseHub)
		if err := http.ListenAndServe(":8080", nil); err != nil {
			log.Fatalf("SSE server failed: %v", err)
		}
	}()

	// On startup: finalize zombie fixtures (status=0/1 but match_state at minute>=90)
	finalizeZombieFixtures()

	// SIP-0077 slice-1 orchestrator loop (schedule + event consumer)
	go orchestratorRunner.Start()

	// Main Polling Loop
	for {
		processActiveMatches()
		time.Sleep(5 * time.Second)
	}
}

func processActiveMatches() {
	preMatchSeconds := 15
	if raw := os.Getenv("GM_PREMATCH_SECONDS"); raw != "" {
		if parsed, err := strconv.Atoi(raw); err == nil && parsed >= 0 {
			preMatchSeconds = parsed
		}
	}

	var fixtures []struct {
		ID     int `db:"id"`
		Status int `db:"status"`
	}

	err := db.Select(&fixtures, `
		SELECT id, status
		FROM fixture
		WHERE (status = 1 AND match_date <= UNIX_TIMESTAMP())
		   OR (status = 0 AND match_date <= UNIX_TIMESTAMP())
		ORDER BY match_date, id
		LIMIT 64
	`)
	if err != nil {
		log.Printf("Error fetching active fixtures: %v", err)
		return
	}

	for _, f := range fixtures {
		if err := ensureCPUFormationsForFixture(f.ID); err != nil {
			log.Printf("Error ensuring CPU formations for fixture %d: %v", f.ID, err)
		}

		if f.Status == 0 {
			tx, err := db.Begin()
			if err != nil {
				log.Printf("Error opening start transaction for match %d: %v", f.ID, err)
				continue
			}
			if _, err := tx.Exec("DELETE FROM match_event WHERE fixture_id = ?", f.ID); err != nil {
				tx.Rollback()
				log.Printf("Error cleaning events for scheduled match %d: %v", f.ID, err)
				continue
			}
			if _, err := tx.Exec("DELETE FROM match_state WHERE fixture_id = ?", f.ID); err != nil {
				tx.Rollback()
				log.Printf("Error cleaning state for scheduled match %d: %v", f.ID, err)
				continue
			}

			preMatch, buildErr := matchEngine.BuildPreMatchPayload(f.ID)
			if buildErr != nil {
				tx.Rollback()
				log.Printf("Error building pre-match payload for match %d: %v", f.ID, buildErr)
				continue
			}

			detailBytes, _ := json.Marshal(map[string]interface{}{
				"description":     preMatch.Fallback,
				"weather":         preMatch.Weather,
				"field_condition": preMatch.FieldCondition,
				"spectators":      preMatch.Spectators,
			})
			res, err := tx.Exec(
				"INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, 0, 'pre_match', 'home', ?)",
				f.ID,
				string(detailBytes),
			)
			if err != nil {
				tx.Rollback()
				log.Printf("Error writing pre-match event for match %d: %v", f.ID, err)
				continue
			}
			preEventID, _ := res.LastInsertId()

			if _, err := tx.Exec(
				"UPDATE fixture SET status = 1, home_score = 0, away_score = 0, match_date = UNIX_TIMESTAMP() + ? WHERE id = ? AND status = 0",
				preMatchSeconds,
				f.ID,
			); err != nil {
				tx.Rollback()
				log.Printf("Error starting scheduled match %d: %v", f.ID, err)
				continue
			}
			if err := tx.Commit(); err != nil {
				log.Printf("Error committing scheduled match start %d: %v", f.ID, err)
				continue
			}
			log.Printf("Started pre-match for fixture ID: %d (kickoff in %ds)", f.ID, preMatchSeconds)
			matchEngine.BroadcastPreMatchEvent(f.ID, preEventID, preMatch)
			go matchEngine.EnrichPreMatchAsync(f.ID, preEventID, preMatch)
			continue
		}

		log.Printf("Simulating match ID: %d", f.ID)
		err := matchEngine.RunTick(f.ID)
		if err != nil {
			log.Printf("Error in simulation tick for match %d: %v", f.ID, err)
		}
	}
}

// finalizeZombieFixtures sets status=2 for fixtures that are stuck in status 0/1
// but whose match_state is already at minute>=90 (Go worker crashed before full_time tx).
func finalizeZombieFixtures() {
	type zombie struct {
		FixtureID int `db:"fixture_id"`
		HomeScore int `db:"home_score"`
		AwayScore int `db:"away_score"`
	}
	var rows []zombie
	err := db.Select(&rows, `
		SELECT ms.fixture_id, ms.home_score, ms.away_score
		FROM match_state ms
		JOIN fixture f ON f.id = ms.fixture_id
		WHERE ms.current_minute >= 90
		  AND f.status IN (0, 1)
	`)
	if err != nil || len(rows) == 0 {
		return
	}
	for _, z := range rows {
		_, err2 := db.Exec(
			"UPDATE fixture SET status=2, home_score=?, away_score=? WHERE id=? AND status IN (0,1)",
			z.HomeScore, z.AwayScore, z.FixtureID,
		)
		if err2 != nil {
			log.Printf("finalizeZombie: fixture %d error: %v", z.FixtureID, err2)
		} else {
			log.Printf("finalizeZombie: fixture %d finalized %d-%d", z.FixtureID, z.HomeScore, z.AwayScore)
		}
	}
}

func ensureCPUFormationsForFixture(fixtureID int) error {
	type row struct {
		HomeTeamID int `db:"home_team_id"`
		AwayTeamID int `db:"away_team_id"`
		HomeIsCPU  int `db:"home_is_cpu"`
		AwayIsCPU  int `db:"away_is_cpu"`
	}

	var r row
	if err := db.Get(&r, `
		SELECT f.home_team_id, f.away_team_id,
		       COALESCE(th.is_cpu, 0) AS home_is_cpu,
		       COALESCE(ta.is_cpu, 0) AS away_is_cpu
		FROM fixture f
		LEFT JOIN team th ON th.id = f.home_team_id
		LEFT JOIN team ta ON ta.id = f.away_team_id
		WHERE f.id = ?
	`, fixtureID); err != nil {
		return err
	}

	if r.HomeIsCPU == 1 {
		if err := ensureCPUActiveFormation(r.HomeTeamID); err != nil {
			return fmt.Errorf("home team %d: %w", r.HomeTeamID, err)
		}
	}
	if r.AwayIsCPU == 1 {
		if err := ensureCPUActiveFormation(r.AwayTeamID); err != nil {
			return fmt.Errorf("away team %d: %w", r.AwayTeamID, err)
		}
	}

	return nil
}

func ensureCPUActiveFormation(teamID int) error {
	var formationID int
	err := db.Get(&formationID, "SELECT id FROM formation WHERE team_id = ? AND is_active = 1 ORDER BY id ASC LIMIT 1", teamID)
	if err != nil {
		if err != sql.ErrNoRows {
			return err
		}

		res, insErr := db.Exec(`
			INSERT INTO formation (team_id, name, is_active, tactic, marking, offside_trap, trained_tactic, effort, created_at, updated_at)
			VALUES (?, 'Auto 4-4-2', 1, 'balanced', 'zone', 1, NULL, 3, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
		`, teamID)
		if insErr != nil {
			return insErr
		}
		lastID, _ := res.LastInsertId()
		formationID = int(lastID)
	}

	var starters int
	if err := db.Get(&starters, `
		SELECT COUNT(*) FROM formation_slot
		WHERE formation_id = ?
		  AND (((zone = 10) OR ((zone BETWEEN 20 AND 103) AND (MOD(zone,10) BETWEEN 1 AND 3))) OR (zone BETWEEN 1 AND 63) OR (zone BETWEEN 1001 AND 1063))
		  AND player_id IS NOT NULL
	`, formationID); err != nil {
		return err
	}
	if starters >= 11 {
		return nil
	}

	type playerRow struct {
		ID       int    `db:"id"`
		Position string `db:"position"`
		Skill    int    `db:"general_skill"`
	}
	players := []playerRow{}
	if err := db.Select(&players, `
		SELECT id, position, general_skill
		FROM player
		WHERE team_id = ?
		ORDER BY general_skill DESC, id ASC
	`, teamID); err != nil {
		return err
	}

	if len(players) == 0 {
		_, _ = db.Exec("DELETE FROM formation_slot WHERE formation_id = ?", formationID)
		return nil
	}

	type slotTpl struct {
		Zone int
		Role string
	}
	template := []slotTpl{
		{Zone: 10, Role: "GK"},
		{Zone: 31, Role: "DF"}, {Zone: 22, Role: "DF"}, {Zone: 32, Role: "DF"}, {Zone: 33, Role: "DF"},
		{Zone: 61, Role: "MF"}, {Zone: 62, Role: "MF"}, {Zone: 72, Role: "MF"}, {Zone: 63, Role: "MF"},
		{Zone: 82, Role: "FW"}, {Zone: 92, Role: "FW"},
	}

	used := make(map[int]bool)
	assignments := make([]struct {
		Zone     int
		PlayerID int
	}, 0, 11)

	pickByRole := func(role string) (int, bool) {
		for _, p := range players {
			if used[p.ID] {
				continue
			}
			if p.Position == role {
				return p.ID, true
			}
		}
		return 0, false
	}
	pickAny := func() (int, bool) {
		for _, p := range players {
			if !used[p.ID] {
				return p.ID, true
			}
		}
		return 0, false
	}

	for _, slot := range template {
		playerID, ok := pickByRole(slot.Role)
		if !ok {
			playerID, ok = pickAny()
		}
		if !ok {
			break
		}
		used[playerID] = true
		assignments = append(assignments, struct {
			Zone     int
			PlayerID int
		}{Zone: slot.Zone, PlayerID: playerID})
	}

	tx, err := db.Begin()
	if err != nil {
		return err
	}

	if _, err := tx.Exec("DELETE FROM formation_slot WHERE formation_id = ?", formationID); err != nil {
		tx.Rollback()
		return err
	}
	for _, row := range assignments {
		if _, err := tx.Exec(
			"INSERT INTO formation_slot (formation_id, zone, player_id) VALUES (?, ?, ?)",
			formationID, row.Zone, row.PlayerID,
		); err != nil {
			tx.Rollback()
			return err
		}
	}
	if _, err := tx.Exec(
		"UPDATE formation SET name = 'Auto 4-4-2', tactic = 'balanced', marking = 'zone', offside_trap = 1, updated_at = UNIX_TIMESTAMP() WHERE id = ?",
		formationID,
	); err != nil {
		tx.Rollback()
		return err
	}
	if err := tx.Commit(); err != nil {
		return err
	}

	log.Printf("CPU auto-formation rebuilt for team %d (formation %d, starters %d)", teamID, formationID, len(assignments))
	return nil
}
