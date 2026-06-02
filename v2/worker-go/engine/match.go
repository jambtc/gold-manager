package engine

import (
	"bytes"
	"database/sql"
	"encoding/json"
	"fmt"
	"log"
	"math/rand"
	"net/http"
	"os"
	"sort"
	"strconv"
	"strings"
	"time"

	"github.com/jmoiron/sqlx"
	"gold-manager-worker/models"
	"gold-manager-worker/stream"
)

type MatchEngine struct {
	DB  *sqlx.DB
	Hub *stream.Hub
}

type streamPayload map[string]interface{}

type PreMatchPayload struct {
	HomeName       string
	AwayName       string
	Competition    string
	Stadium        string
	Spectators     int
	Weather        string
	FieldCondition string
	HomeStanding   string
	AwayStanding   string
	Fallback       string
	Prompt         string
}

type commentaryTemplateRow struct {
	ID     int    `db:"id"`
	Text   string `db:"text"`
	Weight int    `db:"weight"`
}

type commentaryMeta struct {
	EventType string
	Minute    int
	TeamSide  string
	Source    string
}

func halfTimePauseTicks() int {
	raw := strings.TrimSpace(os.Getenv("GM_HALF_TIME_TICKS"))
	if raw == "" {
		return 15
	}
	parsed, err := strconv.Atoi(raw)
	if err != nil {
		return 15
	}
	if parsed < 1 {
		return 1
	}
	if parsed > 120 {
		return 120
	}
	return parsed
}

func kickoffSideForFixture(fixtureID int) string {
	if fixtureID%2 == 0 {
		return "away"
	}
	return "home"
}

func oppositeSide(side string) string {
	if side == "away" {
		return "home"
	}
	return "away"
}

func (e *MatchEngine) broadcast(fixtureID int, payload streamPayload) {
	raw, err := json.Marshal(payload)
	if err != nil {
		return
	}
	e.Hub.Broadcast(fixtureID, string(raw))
}

func (e *MatchEngine) broadcastEvent(fixtureID int, eventID int64, minute int, eventType, teamSide, description string, homeScore, awayScore int, suspenseText string) {
	payload := streamPayload{
		"type":        "event",
		"id":          eventID,
		"minute":      minute,
		"event_type":  eventType,
		"team_side":   teamSide,
		"description": description,
		"home_score":  homeScore,
		"away_score":  awayScore,
	}
	if strings.TrimSpace(suspenseText) != "" {
		payload["suspense_text"] = suspenseText
	}
	e.broadcast(fixtureID, payload)
}

func (e *MatchEngine) BroadcastPreMatchEvent(fixtureID int, eventID int64, payload PreMatchPayload) {
	e.broadcast(fixtureID, streamPayload{
		"type":            "event",
		"id":              eventID,
		"minute":          0,
		"event_type":      "pre_match",
		"team_side":       "home",
		"description":     payload.Fallback,
		"weather":         payload.Weather,
		"field_condition": payload.FieldCondition,
		"spectators":      payload.Spectators,
	})
}

func (e *MatchEngine) emitCommentaryEvent(fixtureID int, eventID int64, phase, token, description string, meta commentaryMeta) {
	source := meta.Source
	if source == "" {
		source = "template"
	}
	e.broadcast(fixtureID, streamPayload{
		"type":        "commentary_event",
		"phase":       phase,
		"id":          eventID,
		"token":       token,
		"description": description,
		"source":      source,
		"event_type":  meta.EventType,
		"minute":      meta.Minute,
		"team_side":   meta.TeamSide,
	})
}

func (e *MatchEngine) BuildPreMatchPayload(fixtureID int) (PreMatchPayload, error) {
	var base struct {
		CompetitionID int    `db:"competition_id"`
		Competition   string `db:"competition_name"`
		HomeName      string `db:"home_name"`
		AwayName      string `db:"away_name"`
		Stadium       string `db:"stadium_name"`
		Capacity      int    `db:"capacity"`
	}

	if err := e.DB.Get(&base, `
		SELECT f.competition_id,
		       c.name AS competition_name,
		       ht.name AS home_name,
		       at.name AS away_name,
		       COALESCE(s.name, 'Stadio Comunale') AS stadium_name,
		       COALESCE(s.capacity, 20000) AS capacity
		FROM fixture f
		JOIN competition c ON c.id = f.competition_id
		JOIN team ht ON ht.id = f.home_team_id
		JOIN team at ON at.id = f.away_team_id
		LEFT JOIN stadium s ON s.team_id = f.home_team_id
		WHERE f.id = ?
	`, fixtureID); err != nil {
		return PreMatchPayload{}, err
	}

	lang := e.fixtureLang(fixtureID)
	weather, field := randomWeatherField(lang)
	capacity := base.Capacity
	if capacity < 8000 {
		capacity = 8000
	}
	minSpectators := int(float64(capacity) * 0.62)
	maxSpectators := int(float64(capacity) * 0.97)
	if maxSpectators <= minSpectators {
		maxSpectators = minSpectators + 500
	}
	spectators := rand.Intn(maxSpectators-minSpectators+1) + minSpectators

	homeStanding, awayStanding := e.getStandingContext(base.CompetitionID, fixtureID)
	if homeStanding == "" {
		homeStanding = "classifica non disponibile"
	}
	if awayStanding == "" {
		awayStanding = "classifica non disponibile"
	}

	fallback := fmt.Sprintf(
		"Pre-partita allo %s: %s contro %s in %s. Spalti con circa %d spettatori, meteo %s, %s. "+
			"Classifica: %s; %s. Squadre in campo per il riscaldamento.",
		base.Stadium, base.HomeName, base.AwayName, base.Competition, spectators, weather, field, homeStanding, awayStanding,
	)
	if tpl, ok := e.pickTemplate("pre_match", "", "balanced", 0, fixtureID, map[string]string{
		"home_team":       base.HomeName,
		"away_team":       base.AwayName,
		"stadium":         base.Stadium,
		"competition":     base.Competition,
		"spectators":      fmt.Sprintf("%d", spectators),
		"weather":         weather,
		"field_condition": field,
		"home_rank":       homeStanding,
		"away_rank":       awayStanding,
	}); ok {
		fallback = tpl
	}
	prompt := fmt.Sprintf(
		"Pre-partita reale. Competizione: %s. Stadio: %s. Squadre: %s vs %s. "+
			"Spettatori stimati: %d. Meteo: %s. Campo: %s. Classifica casa: %s. Classifica ospite: %s.",
		base.Competition, base.Stadium, base.HomeName, base.AwayName, spectators, weather, field, homeStanding, awayStanding,
	)

	return PreMatchPayload{
		HomeName:       base.HomeName,
		AwayName:       base.AwayName,
		Competition:    base.Competition,
		Stadium:        base.Stadium,
		Spectators:     spectators,
		Weather:        weather,
		FieldCondition: field,
		HomeStanding:   homeStanding,
		AwayStanding:   awayStanding,
		Fallback:       fallback,
		Prompt:         prompt,
	}, nil
}

func randomWeatherField(lang string) (string, string) {
	type option struct{ weather, field string }
	var options []option
	if lang == "en-US" {
		options = []option{
			{"sunny", "dry and fast pitch"},
			{"cloudy", "regular compact surface"},
			{"gusty", "windy conditions, long balls trickier"},
			{"rainy", "heavy and slippery pitch"},
		}
	} else {
		options = []option{
			{"soleggiato", "terreno asciutto e rapido"},
			{"nuvoloso", "manto regolare e compatto"},
			{"ventoso", "vento a raffiche, palloni lunghi più insidiosi"},
			{"piovoso", "terreno pesante e scivoloso"},
		}
	}
	selected := options[rand.Intn(len(options))]
	return selected.weather, selected.field
}

func (e *MatchEngine) getStandingContext(competitionID int, fixtureID int) (string, string) {
	var homeID, awayID int
	if err := e.DB.QueryRow("SELECT home_team_id, away_team_id FROM fixture WHERE id = ?", fixtureID).Scan(&homeID, &awayID); err != nil {
		return "", ""
	}

	type row struct {
		TeamID int `db:"team_id"`
		Pos    int `db:"pos"`
		Points int `db:"points"`
	}
	var rows []row
	err := e.DB.Select(&rows, `
		SELECT t.team_id, t.pos, t.points
		FROM (
		    SELECT team_id,
		           points,
		           ROW_NUMBER() OVER (
		               ORDER BY points DESC, (goals_for - goals_against) DESC, goals_for DESC, team_id ASC
		           ) AS pos
		    FROM standing
		    WHERE competition_id = ?
		) t
		WHERE t.team_id IN (?, ?)
	`, competitionID, homeID, awayID)
	if err != nil || len(rows) == 0 {
		return "", ""
	}

	var total int
	_ = e.DB.Get(&total, "SELECT COUNT(*) FROM standing WHERE competition_id = ?", competitionID)
	if total <= 0 {
		total = 0
	}

	homeTxt := ""
	awayTxt := ""
	for _, r := range rows {
		txt := fmt.Sprintf("%d° posto con %d punti", r.Pos, r.Points)
		if total > 0 {
			txt = fmt.Sprintf("%d°/%d con %d punti", r.Pos, total, r.Points)
		}
		if r.TeamID == homeID {
			homeTxt = txt
		}
		if r.TeamID == awayID {
			awayTxt = txt
		}
	}

	return homeTxt, awayTxt
}

func (e *MatchEngine) RunTick(fixtureID int) error {
	var state models.MatchState
	err := e.DB.Get(&state, "SELECT * FROM match_state WHERE fixture_id = ?", fixtureID)
	if err != nil {
		if err == sql.ErrNoRows {
			log.Printf("[MATCH %d] match_state missing — creating", fixtureID)
			var homeFormID, awayFormID *int
			_ = e.DB.Get(&homeFormID, "SELECT f.id FROM formation f JOIN fixture fix ON fix.home_team_id = f.team_id WHERE fix.id = ? AND f.is_active = 1 ORDER BY f.updated_at DESC, f.id DESC LIMIT 1", fixtureID)
			_ = e.DB.Get(&awayFormID, "SELECT f.id FROM formation f JOIN fixture fix ON fix.away_team_id = f.team_id WHERE fix.id = ? AND f.is_active = 1 ORDER BY f.updated_at DESC, f.id DESC LIMIT 1", fixtureID)
			_, err = e.DB.Exec("INSERT INTO match_state (fixture_id, current_minute, phase, home_score, away_score, home_formation_id, away_formation_id, pending_home_actions, pending_away_actions, home_subs_used, away_subs_used, half_time_ticks) VALUES (?, 0, 'NOT_STARTED', 0, 0, ?, ?, '[]', '[]', 0, 0, 0)", fixtureID, homeFormID, awayFormID)
			if err != nil {
				return err
			}
			err = e.DB.Get(&state, "SELECT * FROM match_state WHERE fixture_id = ?", fixtureID)
			if err != nil {
				return err
			}
		} else {
			return err
		}
	}

	if state.Phase == "NOT_STARTED" {
		homeFormID, awayFormID, ferr := e.currentActiveFormationIDs(fixtureID)
		if ferr != nil {
			log.Printf("[MATCH %d] warning: cannot refresh formation snapshot before kickoff: %v", fixtureID, ferr)
		} else {
			state.HomeFormationID = homeFormID
			state.AwayFormationID = awayFormID
			_, _ = e.DB.Exec(
				"UPDATE match_state SET home_formation_id = ?, away_formation_id = ? WHERE fixture_id = ?",
				homeFormID,
				awayFormID,
				fixtureID,
			)
		}
		// SIP-0083: snapshot effort_level from active formations
		homeEffort, awayEffort := 50, 50
		if homeFormID != nil {
			_ = e.DB.Get(&homeEffort, "SELECT COALESCE(effort_level, 50) FROM formation WHERE id = ?", *homeFormID)
		}
		if awayFormID != nil {
			_ = e.DB.Get(&awayEffort, "SELECT COALESCE(effort_level, 50) FROM formation WHERE id = ?", *awayFormID)
		}
		_, _ = e.DB.Exec("UPDATE match_state SET home_effort_level = ?, away_effort_level = ? WHERE fixture_id = ?",
			homeEffort, awayEffort, fixtureID)
		state.HomeEffortLevel = homeEffort
		state.AwayEffortLevel = awayEffort
	}

	if state.Phase == "NOT_STARTED" {
		kickoffSide := kickoffSideForFixture(fixtureID)
		kickoffTeam := e.teamName(fixtureID, kickoffSide)
		otherSide := oppositeSide(kickoffSide)
		otherTeam := e.teamName(fixtureID, otherSide)

		state.Phase = "FIRST_HALF"
		if _, err = e.DB.Exec("UPDATE match_state SET phase = 'FIRST_HALF', current_minute = 0 WHERE fixture_id = ?", fixtureID); err != nil {
			return err
		}

		fallback := fmt.Sprintf(
			"Calcio d'inizio: %s muove il primo pallone contro %s.",
			kickoffTeam,
			otherTeam,
		)
		detail := fmt.Sprintf(`{"description":%q,"kickoff_team":%q}`, fallback, kickoffTeam)
		res, err2 := e.DB.Exec(
			"INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, 0, 'kickoff', ?, ?)",
			fixtureID,
			kickoffSide,
			detail,
		)
		if err2 == nil {
			eventID, _ := res.LastInsertId()
			e.broadcastEvent(fixtureID, eventID, 0, "kickoff", kickoffSide, fallback, state.HomeScore, state.AwayScore, "")
		}

		// SIP-0080: kickoff notification for both managers
		koHome := e.teamName(fixtureID, "home")
		koAway := e.teamName(fixtureID, "away")
		koTitle := fmt.Sprintf("Calcio d'inizio: %s vs %s", koHome, koAway)
		koTg := fmt.Sprintf("🏁 <b>Calcio d'inizio</b>: %s vs %s", koHome, koAway)
		go e.insertMatchNotification(e.teamUserID(fixtureID, "home"), "match", "🏁", koTitle, "", koTg)
		go e.insertMatchNotification(e.teamUserID(fixtureID, "away"), "match", "🏁", koTitle, "", koTg)

		return nil
	}

	// Pending manager commands
	var commands []models.MatchCommand
	if err2 := e.DB.Select(&commands, "SELECT * FROM match_command WHERE fixture_id = ? AND executed_at_minute IS NULL", fixtureID); err2 == nil {
		for _, cmd := range commands {
			e.ProcessCommand(fixtureID, &state, cmd)
		}
	}

	// Half-time
	if state.Phase == "HALF_TIME" {
		pauseTicks := halfTimePauseTicks()
		state.HalfTimeTicks++
		e.DB.Exec("UPDATE match_state SET half_time_ticks = ? WHERE fixture_id = ?", state.HalfTimeTicks, fixtureID)
		if state.HalfTimeTicks < pauseTicks {
			return nil
		}
		state.Phase = "SECOND_HALF"
		state.CurrentMinute = 46
		tx, err := e.DB.Begin()
		if err != nil {
			return err
		}
		if _, err = tx.Exec("UPDATE match_state SET phase = 'SECOND_HALF', current_minute = 46, half_time_ticks = ? WHERE fixture_id = ?", state.HalfTimeTicks, fixtureID); err != nil {
			tx.Rollback()
			return err
		}
		firstKickoffSide := kickoffSideForFixture(fixtureID)
		secondKickoffSide := oppositeSide(firstKickoffSide)
		secondKickoffTeam := e.teamName(fixtureID, secondKickoffSide)
		fallback := fmt.Sprintf("Fischio d'inizio del secondo tempo! Si riparte: palla a %s.", secondKickoffTeam)
		res, err2 := tx.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, 45, 'second_half_start', ?, ?)",
			fixtureID, secondKickoffSide, fmt.Sprintf(`{"description":%q,"kickoff_team":%q}`, fallback, secondKickoffTeam))
		if err2 != nil {
			tx.Rollback()
			return err2
		}
		if err = tx.Commit(); err != nil {
			return err
		}
		eventID, _ := res.LastInsertId()
		e.broadcastEvent(fixtureID, eventID, 45, "second_half_start", secondKickoffSide, fallback, state.HomeScore, state.AwayScore, "")
		return nil
	}

	if state.CurrentMinute == 45 && state.Phase != "HALF_TIME" {
		state.Phase = "HALF_TIME"
		state.HalfTimeTicks = 0
		log.Printf("[MATCH %d] --- HALF TIME ---", fixtureID)
		tx, err := e.DB.Begin()
		if err != nil {
			return err
		}
		if _, err = tx.Exec("UPDATE match_state SET phase = 'HALF_TIME', current_minute = 45, half_time_ticks = 0 WHERE fixture_id = ?", fixtureID); err != nil {
			tx.Rollback()
			return err
		}
		fallback := e.buildHalfTimeFallback(fixtureID, state.HomeScore, state.AwayScore)
		detail := fmt.Sprintf(`{"home":%d,"away":%d,"description":%q}`, state.HomeScore, state.AwayScore, fallback)
		res, err2 := tx.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, 45, 'half_time', 'home', ?)", fixtureID, detail)
		if err2 != nil {
			tx.Rollback()
			return err2
		}
		if err = tx.Commit(); err != nil {
			return err
		}
		eventID, _ := res.LastInsertId()
		e.broadcastEvent(fixtureID, eventID, 45, "half_time", "home", fallback, state.HomeScore, state.AwayScore, "")

		// SIP-0080: halftime in-app notification for both managers
		htHome := e.teamName(fixtureID, "home")
		htAway := e.teamName(fixtureID, "away")
		htTitle := fmt.Sprintf("Intervallo: %s %d–%d %s", htHome, state.HomeScore, state.AwayScore, htAway)
		htTg := fmt.Sprintf("🔔 <b>Intervallo:</b> %s %d–%d %s", htHome, state.HomeScore, state.AwayScore, htAway)
		go e.insertMatchNotification(e.teamUserID(fixtureID, "home"), "match", "🔔", htTitle, "", htTg)
		go e.insertMatchNotification(e.teamUserID(fixtureID, "away"), "match", "🔔", htTitle, "", htTg)

		prompt := fmt.Sprintf(
			"Intervallo: %s %d-%d %s. Commento telecronista calcio.",
			e.teamName(fixtureID, "home"),
			state.HomeScore,
			state.AwayScore,
			e.teamName(fixtureID, "away"),
		)
		go e.enrichEventAsync(fixtureID, eventID, "half_time", 45, "home", prompt, fallback)
		return nil
	}

	state.CurrentMinute++
	if state.CurrentMinute > 90 {
		return e.EndMatch(fixtureID)
	}

	log.Printf("[MATCH %d] Simulating Minute %d", fixtureID, state.CurrentMinute)

	if handled, err := e.resolvePendingSetPiece(fixtureID, &state); handled || err != nil {
		return err
	}

	// SIP-0037: team trait bonus (average of active XI traits)
	homeLosing := state.HomeScore < state.AwayScore
	awayLosing := state.AwayScore < state.HomeScore
	homeTraits := e.teamTraitsProfile(fixtureID, "home", homeLosing, true)
	awayTraits := e.teamTraitsProfile(fixtureID, "away", awayLosing, false)
	homeBonus := homeTraits.Bonus
	awayBonus := awayTraits.Bonus

	// SIP-0024: half-time morale boost — trailing team +5% in second half.
	if state.Phase == "SECOND_HALF" {
		if homeLosing {
			homeBonus = clampFloat(homeBonus*1.05, 0.80, 1.40)
		}
		if awayLosing {
			awayBonus = clampFloat(awayBonus*1.05, 0.80, 1.40)
		}
	}

	// SIP-0038: tactical modifiers (parity with PHP MatchEngine::resolveTick)
	homeTeamID, _ := e.teamID(fixtureID, "home")
	awayTeamID, _ := e.teamID(fixtureID, "away")
	homeTactics := e.loadTeamTactics(homeTeamID)
	awayTactics := e.loadTeamTactics(awayTeamID)
	homeGoalMod := tacticGoalModifier(homeTactics, awayTactics)
	awayGoalMod := tacticGoalModifier(awayTactics, homeTactics)
	// SIP-0075: set piece skill modifier
	homeSetPieceMod := e.teamSetPieceMod(homeTeamID, fixtureID)
	awaySetPieceMod := e.teamSetPieceMod(awayTeamID, fixtureID)
	homePressureMod := e.pressureGoalMod(fixtureID, "home", state.CurrentMinute, state.HomeScore, state.AwayScore)
	awayPressureMod := e.pressureGoalMod(fixtureID, "away", state.CurrentMinute, state.HomeScore, state.AwayScore)
	// SIP-0073: short opposing GK raises the attacking team's goal threshold
	// Fine-tune parity with PHP engine:
	// - slightly higher base conversion
	// - keep tactical/trait multipliers
	baseGoalThreshold := 3.0 // SIP-0090: calibrated for 2.5–3.0 avg goals/match
	homeGoalThreshold := baseGoalThreshold * homeBonus * homeGoalMod * homeSetPieceMod * homePressureMod * awayTraits.GkHeightMod * effortGoalMod(state.HomeEffortLevel)
	awayGoalThreshold := homeGoalThreshold + baseGoalThreshold*awayBonus*awayGoalMod*awaySetPieceMod*awayPressureMod*homeTraits.GkHeightMod*effortGoalMod(state.AwayEffortLevel)
	homeChanceEnd := awayGoalThreshold + 4.5
	awayChanceEnd := homeChanceEnd + 4.5
	midfieldEnd := awayChanceEnd + 15.0
	attackEnd := midfieldEnd + 10.0
	midfieldSplit := awayChanceEnd + 7.5
	attackSplit := midfieldEnd + 5.0

	chance := rand.Float64() * 100.0

	// attack_side for indicator bar (SIP-0064): home attacks first 50%, away second 50%
	attackSide := "home"
	if chance >= 50.0 {
		attackSide = "away"
	}

	// Suspense phrases (shown before reveal)
	homeSuspense := []string{
		"Conclusione potente dalla distanza...",
		"Azione insistita: tiro in porta!",
		"Percussione di un attaccante verso l'area...",
		"Cross basso, deviazione: pericolo!",
	}
	awaySuspense := []string{
		"L'ospite si fa pericoloso: tiro!",
		"Ripartenza veloce: conclusione!",
		"Cross dalla fascia, il portiere in uscita...",
		"Tiro a giro verso l'angolino...",
	}
	pickSuspense := func(list []string) string { return list[rand.Intn(len(list))] }

	var eventType, teamSide, detail, fallback, suspenseText string
	var eventPlayerID *int

	switch {
	// Home goal threshold
	case chance < homeGoalThreshold:
		state.HomeScore++
		eventType, teamSide = "goal", "home"
		attackSide = "home"
		scorerID, scorer := e.randomPlayerIDName(fixtureID, "home", "FW")
		if scorer == "" {
			scorer = e.randomPlayerName(fixtureID, "home", "FW")
		}
		if scorerID > 0 {
			eventPlayerID = &scorerID
		}
		suspense := pickSuspense(homeSuspense)
		suspenseText = suspense
		fallback = fmt.Sprintf("GOL! %s segna per il %s al minuto %d! Punteggio: %d-%d!", scorer, e.teamName(fixtureID, "home"), state.CurrentMinute, state.HomeScore, state.AwayScore)

		// SIP-0080: in-app goal notifications
		homeName := e.teamName(fixtureID, "home")
		awayName := e.teamName(fixtureID, "away")
		go e.insertMatchNotification(e.teamUserID(fixtureID, "home"), "match", "⚽",
			fmt.Sprintf("GOL! %s segna al minuto %d! %d–%d", scorer, state.CurrentMinute, state.HomeScore, state.AwayScore),
			"", fmt.Sprintf("⚽ <b>GOL!</b> %s al minuto %d! %s %d–%d %s", scorer, state.CurrentMinute, homeName, state.HomeScore, state.AwayScore, awayName))
		go e.insertMatchNotification(e.teamUserID(fixtureID, "away"), "match", "😰",
			fmt.Sprintf("Gol subito al minuto %d: %s %d–%d %s", state.CurrentMinute, homeName, state.HomeScore, state.AwayScore, awayName),
			"", fmt.Sprintf("😰 <b>Gol subito</b> al minuto %d! %s %d–%d %s", state.CurrentMinute, homeName, state.HomeScore, state.AwayScore, awayName))
		gameState := deriveGameState(state.HomeScore, state.AwayScore, "home", state.CurrentMinute)
		if tpl, ok := e.pickTemplate("goal", "home", gameState, state.CurrentMinute, fixtureID, map[string]string{
			"home_team":       e.teamName(fixtureID, "home"),
			"away_team":       e.teamName(fixtureID, "away"),
			"player_attacker": scorer,
			"score_home":      fmt.Sprintf("%d", state.HomeScore),
			"score_away":      fmt.Sprintf("%d", state.AwayScore),
			"minute":          fmt.Sprintf("%d", state.CurrentMinute),
		}); ok {
			fallback = tpl
		}
		detail = fmt.Sprintf(`{"home_score":%d,"away_score":%d,"scorer_name":%q,"description":%q,"suspense_text":%q}`, state.HomeScore, state.AwayScore, scorer, fallback, suspense)

		// Away goal threshold
	case chance < awayGoalThreshold:
		state.AwayScore++
		eventType, teamSide = "goal", "away"
		attackSide = "away"
		scorerID, scorer := e.randomPlayerIDName(fixtureID, "away", "FW")
		if scorer == "" {
			scorer = e.randomPlayerName(fixtureID, "away", "FW")
		}
		if scorerID > 0 {
			eventPlayerID = &scorerID
		}
		suspense := pickSuspense(awaySuspense)
		suspenseText = suspense
		fallback = fmt.Sprintf("GOL! %s segna per il %s al minuto %d! Punteggio: %d-%d!", scorer, e.teamName(fixtureID, "away"), state.CurrentMinute, state.HomeScore, state.AwayScore)

		// SIP-0080: in-app goal notifications
		homeName2 := e.teamName(fixtureID, "home")
		awayName2 := e.teamName(fixtureID, "away")
		go e.insertMatchNotification(e.teamUserID(fixtureID, "away"), "match", "⚽",
			fmt.Sprintf("GOL! %s segna al minuto %d! %d–%d", scorer, state.CurrentMinute, state.HomeScore, state.AwayScore),
			"", fmt.Sprintf("⚽ <b>GOL!</b> %s al minuto %d! %s %d–%d %s", scorer, state.CurrentMinute, homeName2, state.HomeScore, state.AwayScore, awayName2))
		go e.insertMatchNotification(e.teamUserID(fixtureID, "home"), "match", "😰",
			fmt.Sprintf("Gol subito al minuto %d: %s %d–%d %s", state.CurrentMinute, homeName2, state.HomeScore, state.AwayScore, awayName2),
			"", fmt.Sprintf("😰 <b>Gol subito</b> al minuto %d! %s %d–%d %s", state.CurrentMinute, homeName2, state.HomeScore, state.AwayScore, awayName2))
		gameState := deriveGameState(state.HomeScore, state.AwayScore, "away", state.CurrentMinute)
		if tpl, ok := e.pickTemplate("goal", "away", gameState, state.CurrentMinute, fixtureID, map[string]string{
			"home_team":       e.teamName(fixtureID, "home"),
			"away_team":       e.teamName(fixtureID, "away"),
			"player_attacker": scorer,
			"score_home":      fmt.Sprintf("%d", state.HomeScore),
			"score_away":      fmt.Sprintf("%d", state.AwayScore),
			"minute":          fmt.Sprintf("%d", state.CurrentMinute),
		}); ok {
			fallback = tpl
		}
		detail = fmt.Sprintf(`{"home_score":%d,"away_score":%d,"scorer_name":%q,"description":%q,"suspense_text":%q}`, state.HomeScore, state.AwayScore, scorer, fallback, suspense)

		// Home near_miss or gk_save window
	case chance < homeChanceEnd:
		attackSide = "home"
		shooterID, shooter := e.randomPlayerIDName(fixtureID, "home", "FW")
		gkID, gk := e.randomPlayerIDName(fixtureID, "away", "GK")
		suspense := pickSuspense(homeSuspense)
		suspenseText = suspense
		if rand.Intn(2) == 0 {
			eventType, teamSide = "near_miss", "home"
			if shooterID > 0 {
				eventPlayerID = &shooterID
			}
			fallback = fmt.Sprintf("%s calcia ma non trova la porta! Che occasione sprecata.", shooter)
			gameState := deriveGameState(state.HomeScore, state.AwayScore, "home", state.CurrentMinute)
			if tpl, ok := e.pickTemplate("near_miss", "", gameState, state.CurrentMinute, fixtureID, map[string]string{
				"home_team":       e.teamName(fixtureID, "home"),
				"away_team":       e.teamName(fixtureID, "away"),
				"player_attacker": shooter,
				"minute":          fmt.Sprintf("%d", state.CurrentMinute),
			}); ok {
				fallback = tpl
			}
			detail = fmt.Sprintf(`{"description":%q,"suspense_text":%q,"shooter_name":%q}`, fallback, suspense, shooter)
		} else {
			eventType, teamSide = "gk_save", "home"
			if gkID > 0 {
				eventPlayerID = &gkID
			}
			fallback = fmt.Sprintf("%s vola e para! %s non riesce a battere il portiere.", gk, shooter)
			gameState := deriveGameState(state.HomeScore, state.AwayScore, "home", state.CurrentMinute)
			if tpl, ok := e.pickTemplate("gk_save", "", gameState, state.CurrentMinute, fixtureID, map[string]string{
				"home_team":       e.teamName(fixtureID, "home"),
				"away_team":       e.teamName(fixtureID, "away"),
				"player_attacker": shooter,
				"player_gk":       gk,
				"minute":          fmt.Sprintf("%d", state.CurrentMinute),
			}); ok {
				fallback = tpl
			}
			detail = fmt.Sprintf(`{"description":%q,"suspense_text":%q,"shooter_name":%q,"gk_name":%q}`, fallback, suspense, shooter, gk)
		}

	// Away near_miss or gk_save ~4.5%
	case chance < awayChanceEnd:
		attackSide = "away"
		shooterID, shooter := e.randomPlayerIDName(fixtureID, "away", "FW")
		gkID, gk := e.randomPlayerIDName(fixtureID, "home", "GK")
		suspense := pickSuspense(awaySuspense)
		suspenseText = suspense
		if rand.Intn(2) == 0 {
			eventType, teamSide = "near_miss", "away"
			if shooterID > 0 {
				eventPlayerID = &shooterID
			}
			fallback = fmt.Sprintf("Pericolo sventato! %s degli ospiti non inquadra la porta.", shooter)
			gameState := deriveGameState(state.HomeScore, state.AwayScore, "away", state.CurrentMinute)
			if tpl, ok := e.pickTemplate("near_miss", "", gameState, state.CurrentMinute, fixtureID, map[string]string{
				"home_team":       e.teamName(fixtureID, "home"),
				"away_team":       e.teamName(fixtureID, "away"),
				"player_attacker": shooter,
				"minute":          fmt.Sprintf("%d", state.CurrentMinute),
			}); ok {
				fallback = tpl
			}
			detail = fmt.Sprintf(`{"description":%q,"suspense_text":%q,"shooter_name":%q}`, fallback, suspense, shooter)
		} else {
			eventType, teamSide = "gk_save", "away"
			if gkID > 0 {
				eventPlayerID = &gkID
			}
			fallback = fmt.Sprintf("%s risponde presente! Para il tiro di %s.", gk, shooter)
			gameState := deriveGameState(state.HomeScore, state.AwayScore, "away", state.CurrentMinute)
			if tpl, ok := e.pickTemplate("gk_save", "", gameState, state.CurrentMinute, fixtureID, map[string]string{
				"home_team":       e.teamName(fixtureID, "home"),
				"away_team":       e.teamName(fixtureID, "away"),
				"player_attacker": shooter,
				"player_gk":       gk,
				"minute":          fmt.Sprintf("%d", state.CurrentMinute),
			}); ok {
				fallback = tpl
			}
			detail = fmt.Sprintf(`{"description":%q,"suspense_text":%q,"shooter_name":%q,"gk_name":%q}`, fallback, suspense, shooter, gk)
		}

	// Midfield duel ~15%
	case chance < midfieldEnd:
		if chance < midfieldSplit {
			attackSide = "home"
			teamSide = "home"
		} else {
			attackSide = "away"
			teamSide = "away"
		}
		mf := e.randomPlayerName(fixtureID, teamSide, "MF")
		eventType = "midfield_duel"
		midPhrases := []string{
			fmt.Sprintf("%s smista la palla a centrocampo. Le squadre si studiano.", mf),
			"Fase interlocutoria a centrocampo, i ritmi si abbassano.",
			fmt.Sprintf("%s recupera palla e lancia la manovra.", mf),
			"Possesso palla prolungato senza azioni pericolose.",
			fmt.Sprintf("Pressing alto di %s: contrasto vinto.", mf),
		}
		fallback = midPhrases[rand.Intn(len(midPhrases))]
		gameState := deriveGameState(state.HomeScore, state.AwayScore, teamSide, state.CurrentMinute)
		if tpl, ok := e.pickTemplate("midfield_duel", "", gameState, state.CurrentMinute, fixtureID, map[string]string{
			"home_team":       e.teamName(fixtureID, "home"),
			"away_team":       e.teamName(fixtureID, "away"),
			"player_attacker": mf,
			"minute":          fmt.Sprintf("%d", state.CurrentMinute),
		}); ok {
			fallback = tpl
		}
		detail = fmt.Sprintf(`{"description":%q,"attacker_name":%q}`, fallback, mf)

	// Attack attempt ~10%
	case chance < attackEnd:
		if chance < attackSplit {
			attackSide = "home"
			teamSide = "home"
		} else {
			attackSide = "away"
			teamSide = "away"
		}
		att := e.randomPlayerName(fixtureID, teamSide, "FW")
		eventType = "attack_attempt"
		attPhrases := []string{
			fmt.Sprintf("%s accelera ma viene fermato dalla difesa.", att),
			fmt.Sprintf("Percussione di %s: conclusione bloccata.", att),
			fmt.Sprintf("%s prova il tiro dalla distanza — deviato.", att),
			fmt.Sprintf("Cross dalla fascia, %s non arriva di testa.", att),
		}
		fallback = attPhrases[rand.Intn(len(attPhrases))]
		gameState := deriveGameState(state.HomeScore, state.AwayScore, teamSide, state.CurrentMinute)
		if tpl, ok := e.pickTemplate("attack_attempt", "", gameState, state.CurrentMinute, fixtureID, map[string]string{
			"home_team":       e.teamName(fixtureID, "home"),
			"away_team":       e.teamName(fixtureID, "away"),
			"player_attacker": att,
			"minute":          fmt.Sprintf("%d", state.CurrentMinute),
		}); ok {
			fallback = tpl
		}
		detail = fmt.Sprintf(`{"description":%q,"attacker_name":%q}`, fallback, att)
	}

	tx, err := e.DB.Begin()
	if err != nil {
		return err
	}
	if _, err = tx.Exec("UPDATE match_state SET current_minute = ?, home_score = ?, away_score = ? WHERE id = ?", state.CurrentMinute, state.HomeScore, state.AwayScore, state.ID); err != nil {
		tx.Rollback()
		return err
	}

	var eventID int64
	if eventType != "" {
		res, err2 := tx.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, player_id, detail) VALUES (?, ?, ?, ?, ?, ?)",
			fixtureID, state.CurrentMinute, eventType, teamSide, eventPlayerID, detail)
		if err2 != nil {
			tx.Rollback()
			return err2
		}
		eventID, _ = res.LastInsertId()
		e.broadcastEvent(fixtureID, eventID, state.CurrentMinute, eventType, teamSide, fallback, state.HomeScore, state.AwayScore, suspenseText)
	}

	if _, err = tx.Exec("UPDATE fixture SET home_score = ?, away_score = ? WHERE id = ?", state.HomeScore, state.AwayScore, fixtureID); err != nil {
		tx.Rollback()
		return err
	}
	if err = tx.Commit(); err != nil {
		return err
	}

	e.broadcast(fixtureID, streamPayload{"type": "state", "minute": state.CurrentMinute, "home_score": state.HomeScore, "away_score": state.AwayScore, "attack_side": attackSide})

	// Async LLM enrichment for goal events (non-blocking)
	if eventType == "goal" && eventID > 0 {
		activeTeam := e.teamName(fixtureID, teamSide)
		otherTeam := e.teamName(fixtureID, "home")
		if teamSide == "home" {
			otherTeam = e.teamName(fixtureID, "away")
		}
		prompt := fmt.Sprintf("Min %d: GOL %s contro %s. Risultato %d-%d.", state.CurrentMinute, activeTeam, otherTeam, state.HomeScore, state.AwayScore)
		go e.enrichEventAsync(fixtureID, eventID, eventType, state.CurrentMinute, teamSide, prompt, fallback)
	}

	// Corner after near_miss (~30%)
	if eventType == "near_miss" && rand.Float64() < 0.30 {
		e.generateCorner(fixtureID, teamSide, state.CurrentMinute)
	}
	if eventType == "attack_attempt" {
		roll := rand.Float64()
		if roll < 0.03 {
			e.generateSetPieceAward(fixtureID, teamSide, state.CurrentMinute, "penalty")
		} else if roll < 0.13 {
			e.generateSetPieceAward(fixtureID, teamSide, state.CurrentMinute, "freekick")
		}
	}

	// Cards check (~1.5% per team per tick)
	e.resolveGoCards(fixtureID, state.CurrentMinute, homeTraits, awayTraits)

	// Injury check — effort level modifies base probability
	e.resolveGoInjury(fixtureID, state.CurrentMinute, homeTraits, awayTraits, state.HomeEffortLevel, state.AwayEffortLevel)

	// SIP-0083: CPU dynamic effort change in last 20 minutes
	e.maybeCpuAdjustEffort(fixtureID, &state)

	// Pending substitutions from match_command table
	e.processMatchCommands(fixtureID, state.CurrentMinute)

	if state.CurrentMinute >= 90 {
		return e.EndMatch(fixtureID)
	}

	return nil
}

func (e *MatchEngine) currentActiveFormationIDs(fixtureID int) (*int, *int, error) {
	var homeFormID, awayFormID *int
	if err := e.DB.Get(
		&homeFormID,
		"SELECT f.id FROM formation f JOIN fixture fix ON fix.home_team_id = f.team_id WHERE fix.id = ? AND f.is_active = 1 ORDER BY f.updated_at DESC, f.id DESC LIMIT 1",
		fixtureID,
	); err != nil && err != sql.ErrNoRows {
		return nil, nil, err
	}
	if err := e.DB.Get(
		&awayFormID,
		"SELECT f.id FROM formation f JOIN fixture fix ON fix.away_team_id = f.team_id WHERE fix.id = ? AND f.is_active = 1 ORDER BY f.updated_at DESC, f.id DESC LIMIT 1",
		fixtureID,
	); err != nil && err != sql.ErrNoRows {
		return nil, nil, err
	}
	return homeFormID, awayFormID, nil
}

func (e *MatchEngine) generateCorner(fixtureID int, side string, minute int) {
	att := e.randomPlayerName(fixtureID, side, "FW")
	phrases := []string{
		fmt.Sprintf("Calcio d'angolo per %s! %s va a battere dalla bandierina.", e.teamName(fixtureID, side), att),
		"Corner guadagnato! La palla è in angolo.",
		fmt.Sprintf("%s conquista il corner — battuta dalla bandierina.", att),
	}
	desc := phrases[rand.Intn(len(phrases))]
	gameState := deriveGameState(0, 0, side, minute)
	if tpl, ok := e.pickTemplate("corner", "", gameState, minute, fixtureID, map[string]string{
		"home_team":       e.teamName(fixtureID, "home"),
		"away_team":       e.teamName(fixtureID, "away"),
		"player_attacker": att,
		"minute":          fmt.Sprintf("%d", minute),
	}); ok {
		desc = tpl
	}
	det := fmt.Sprintf(`{"description":%q,"attacker_name":%q}`, desc, att)
	res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, ?, 'corner', ?, ?)", fixtureID, minute, side, det)
	if err != nil {
		return
	}
	evID, _ := res.LastInsertId()
	e.broadcastEvent(fixtureID, evID, minute, "corner", side, desc, 0, 0, "")
}

func (e *MatchEngine) generateSetPieceAward(fixtureID int, side string, minute int, setPieceType string) {
	takerID, taker := e.randomPlayerIDName(fixtureID, side, "FW")
	if taker == "" {
		taker = e.randomPlayerName(fixtureID, side, "FW")
	}

	eventType := "freekick"
	desc := fmt.Sprintf("Calcio di punizione per %s: %s sistema il pallone.", e.teamName(fixtureID, side), taker)
	if setPieceType == "penalty" {
		eventType = "penalty_awarded"
		desc = fmt.Sprintf("Rigore per %s! %s prende il pallone e si prepara dal dischetto.", e.teamName(fixtureID, side), taker)
	}

	det := fmt.Sprintf(`{"description":%q,"taker_name":%q,"set_piece":%q}`, desc, taker, setPieceType)
	var playerID interface{} = nil
	if takerID > 0 {
		playerID = takerID
	}
	res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, player_id, detail) VALUES (?, ?, ?, ?, ?, ?)", fixtureID, minute, eventType, side, playerID, det)
	if err != nil {
		return
	}
	evID, _ := res.LastInsertId()
	e.broadcastEvent(fixtureID, evID, minute, eventType, side, desc, 0, 0, "")
}

func (e *MatchEngine) resolvePendingSetPiece(fixtureID int, state *models.MatchState) (bool, error) {
	var last struct {
		ID       int    `db:"id"`
		Minute   int    `db:"minute"`
		Type     string `db:"type"`
		TeamSide string `db:"team_side"`
	}
	err := e.DB.Get(&last, "SELECT id, minute, type, team_side FROM match_event WHERE fixture_id = ? ORDER BY id DESC LIMIT 1", fixtureID)
	if err != nil {
		return false, nil
	}
	if last.Type != "corner" && last.Type != "freekick" && last.Type != "penalty_awarded" {
		return false, nil
	}

	side := last.TeamSide
	opp := oppositeSide(side)
	takerID, taker := e.randomPlayerIDName(fixtureID, side, "FW")
	if taker == "" {
		taker = e.randomPlayerName(fixtureID, side, "FW")
	}
	gkID, gk := e.randomPlayerIDName(fixtureID, opp, "GK")
	if gk == "" {
		gk = e.randomPlayerName(fixtureID, opp, "GK")
	}

	eventType := "attack_attempt"
	eventSide := side
	eventPlayerID := takerID
	description := ""
	detailExtra := ""
	roll := rand.Float64()

	switch last.Type {
	case "penalty_awarded":
		switch {
		case roll < 0.74:
			eventType = "goal"
			if side == "home" {
				state.HomeScore++
			} else {
				state.AwayScore++
			}
			description = fmt.Sprintf("Dal dischetto %s resta freddo e segna! %s %d-%d %s.", taker, e.teamName(fixtureID, "home"), state.HomeScore, state.AwayScore, e.teamName(fixtureID, "away"))
			detailExtra = `,"origin":"penalty","is_penalty":true`
		case roll < 0.90:
			eventType = "gk_save"
			eventSide = side
			eventPlayerID = gkID
			description = fmt.Sprintf("%s intuisce il rigore e para il tiro di %s!", gk, taker)
			detailExtra = `,"origin":"penalty","is_penalty":true`
		default:
			eventType = "penalty_miss"
			description = fmt.Sprintf("%s calcia il rigore ma non trova lo specchio.", taker)
			detailExtra = `,"origin":"penalty","is_penalty":true`
		}
	case "freekick":
		switch {
		case roll < 0.20:
			eventType = "goal"
			if side == "home" {
				state.HomeScore++
			} else {
				state.AwayScore++
			}
			description = fmt.Sprintf("Punizione perfetta di %s: palla oltre la barriera e gol! %s %d-%d %s.", taker, e.teamName(fixtureID, "home"), state.HomeScore, state.AwayScore, e.teamName(fixtureID, "away"))
			detailExtra = `,"origin":"freekick"`
		case roll < 0.46:
			eventType = "gk_save"
			eventPlayerID = gkID
			description = fmt.Sprintf("%s respinge la punizione di %s.", gk, taker)
			detailExtra = `,"origin":"freekick"`
		case roll < 0.76:
			eventType = "near_miss"
			description = fmt.Sprintf("Punizione di %s fuori di poco.", taker)
			detailExtra = `,"origin":"freekick"`
		default:
			description = fmt.Sprintf("Punizione battuta da %s, la difesa libera l'area.", taker)
			detailExtra = `,"origin":"freekick"`
		}
	case "corner":
		switch {
		case roll < 0.16:
			eventType = "goal"
			if side == "home" {
				state.HomeScore++
			} else {
				state.AwayScore++
			}
			description = fmt.Sprintf("Corner tagliato, %s anticipa tutti e segna! %s %d-%d %s.", taker, e.teamName(fixtureID, "home"), state.HomeScore, state.AwayScore, e.teamName(fixtureID, "away"))
			detailExtra = `,"origin":"corner"`
		case roll < 0.40:
			eventType = "gk_save"
			eventPlayerID = gkID
			description = fmt.Sprintf("%s esce sul corner e salva la porta.", gk)
			detailExtra = `,"origin":"corner"`
		case roll < 0.72:
			eventType = "near_miss"
			description = fmt.Sprintf("Corner battuto da %s, colpo di testa fuori di poco.", taker)
			detailExtra = `,"origin":"corner"`
		default:
			description = fmt.Sprintf("Corner di %s, respinta della difesa sul primo palo.", taker)
			detailExtra = `,"origin":"corner"`
		}
	}

	var playerID interface{} = nil
	if eventPlayerID > 0 {
		playerID = eventPlayerID
	}
	detail := fmt.Sprintf(`{"description":%q,"player_attacker":%q,"player_gk":%q,"home_score":%d,"away_score":%d%s}`, description, taker, gk, state.HomeScore, state.AwayScore, detailExtra)

	tx, txErr := e.DB.Begin()
	if txErr != nil {
		return true, txErr
	}
	if _, txErr = tx.Exec("UPDATE match_state SET current_minute = ?, home_score = ?, away_score = ? WHERE id = ?", state.CurrentMinute, state.HomeScore, state.AwayScore, state.ID); txErr != nil {
		tx.Rollback()
		return true, txErr
	}
	res, txErr := tx.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, player_id, detail) VALUES (?, ?, ?, ?, ?, ?)", fixtureID, state.CurrentMinute, eventType, eventSide, playerID, detail)
	if txErr != nil {
		tx.Rollback()
		return true, txErr
	}
	if _, txErr = tx.Exec("UPDATE fixture SET home_score = ?, away_score = ? WHERE id = ?", state.HomeScore, state.AwayScore, fixtureID); txErr != nil {
		tx.Rollback()
		return true, txErr
	}
	if txErr = tx.Commit(); txErr != nil {
		return true, txErr
	}
	eventID, _ := res.LastInsertId()
	e.broadcastEvent(fixtureID, eventID, state.CurrentMinute, eventType, eventSide, description, state.HomeScore, state.AwayScore, "")
	e.broadcast(fixtureID, streamPayload{"type": "state", "minute": state.CurrentMinute, "home_score": state.HomeScore, "away_score": state.AwayScore, "attack_side": side})
	return true, nil
}

func (e *MatchEngine) resolveGoCards(fixtureID, minute int, homeTraits, awayTraits teamTraitProfile) {
	if e.fixtureCompetitionType(fixtureID) == "friendly" {
		return
	}

	teams := map[string]teamTraitProfile{"home": homeTraits, "away": awayTraits}
	for side, traits := range teams {
		baseChance := 0.015 * traits.DisciplineScalar
		if baseChance < 0.005 {
			baseChance = 0.005
		}
		if baseChance > 0.04 {
			baseChance = 0.04
		}
		if rand.Float64() > baseChance {
			continue
		}
		playerID, playerName := e.randomPlayerIDName(fixtureID, side, "")
		if playerID == 0 {
			continue
		}

		// Count yellows this match
		var yellowsThisMatch int
		_ = e.DB.Get(&yellowsThisMatch,
			"SELECT COUNT(*) FROM match_event WHERE fixture_id = ? AND type = 'yellow_card' AND team_side = ? AND player_id = ?",
			fixtureID, side, playerID)

		var evType, desc string
		if yellowsThisMatch >= 1 {
			// second yellow → red
			evType = "red_card"
			desc = fmt.Sprintf("%s espulso per doppio giallo! La squadra rimane in dieci.", playerName)
			_, _ = e.DB.Exec("UPDATE player SET red_cards = red_cards+1, suspended_matches = GREATEST(suspended_matches,1) WHERE id = ?", playerID)
		} else {
			evType = "yellow_card"
			desc = fmt.Sprintf("Cartellino giallo per %s! L'arbitro lo ammonisce.", playerName)
			_, _ = e.DB.Exec("UPDATE player SET yellow_cards = yellow_cards+1 WHERE id = ?", playerID)
		}
		gameState := deriveGameState(0, 0, side, minute)
		if tpl, ok := e.pickTemplate(evType, "", gameState, minute, fixtureID, map[string]string{
			"home_team":       e.teamName(fixtureID, "home"),
			"away_team":       e.teamName(fixtureID, "away"),
			"player_attacker": playerName,
			"minute":          fmt.Sprintf("%d", minute),
		}); ok {
			desc = tpl
		}

		det := fmt.Sprintf(`{"description":%q,"player_name":%q}`, desc, playerName)
		res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, player_id, detail) VALUES (?, ?, ?, ?, ?, ?)",
			fixtureID, minute, evType, side, playerID, det)
		if err != nil {
			continue
		}
		evID, _ := res.LastInsertId()
		e.broadcastEvent(fixtureID, evID, minute, evType, side, desc, 0, 0, "")
	}
}

func (e *MatchEngine) resolveGoInjury(fixtureID, minute int, homeTraits, awayTraits teamTraitProfile, homeEffort, awayEffort int) {
	effortLevels := map[string]int{"home": homeEffort, "away": awayEffort}
	teams := map[string]teamTraitProfile{"home": homeTraits, "away": awayTraits}
	for side, traits := range teams {
		baseChance := 0.0005 * traits.InjuryScalar * effortInjuryMod(effortLevels[side])
		if baseChance < 0.0001 {
			baseChance = 0.0001
		}
		if baseChance > 0.0015 {
			baseChance = 0.0015
		}
		playerID, playerName := e.randomPlayerIDName(fixtureID, side, "")
		if playerID == 0 {
			continue
		}
		vit := e.playerVitals(playerID)
		fatigueScalar := 1.0
		if vit.Freshness > 0 {
			switch {
			case vit.Freshness < 40:
				fatigueScalar *= 1.8
			case vit.Freshness < 60:
				fatigueScalar *= 1.3
			case vit.Freshness > 85:
				fatigueScalar *= 0.8
			}
		}
		if vit.Condition > 0 {
			switch {
			case vit.Condition < 50:
				fatigueScalar *= 1.6
			case vit.Condition < 65:
				fatigueScalar *= 1.2
			case vit.Condition > 85:
				fatigueScalar *= 0.85
			}
		}
		if vit.Form > 0 {
			switch {
			case vit.Form < 50:
				fatigueScalar *= 1.15
			case vit.Form > 80:
				fatigueScalar *= 0.9
			}
		}
		chance := baseChance * fatigueScalar
		if rand.Float64() > chance {
			continue
		}
		weeks := rand.Intn(4) + 1 // 1-4 weeks
		if traits.InjuryScalar <= 0.85 && weeks > 1 {
			weeks--
		}
		if traits.InjuryScalar >= 1.20 && weeks < 4 {
			weeks++
		}
		if vit.Condition > 0 {
			switch {
			case vit.Condition < 40 && weeks < 4:
				weeks++
			case vit.Condition > 85 && weeks > 1:
				weeks--
			}
		}
		if vit.Freshness > 85 && weeks > 1 {
			weeks--
		}
		if weeks < 1 {
			weeks = 1
		}
		if weeks > 4 {
			weeks = 4
		}
		injType := []string{"lieve", "medio", "grave"}[min(weeks/2, 2)]
		_, _ = e.DB.Exec("UPDATE player SET injury_weeks = ?, injury_type = ?, condition = 0 WHERE id = ? AND injury_weeks = 0",
			weeks, injType, playerID)
		desc := fmt.Sprintf("%s si accascia a terra — infortunio %s. Fuori per %d settimane.", playerName, injType, weeks)
		gameState := deriveGameState(0, 0, side, minute)
		if tpl, ok := e.pickTemplate("injury", "", gameState, minute, fixtureID, map[string]string{
			"home_team":       e.teamName(fixtureID, "home"),
			"away_team":       e.teamName(fixtureID, "away"),
			"player_attacker": playerName,
			"injury_type":     injType,
			"minute":          fmt.Sprintf("%d", minute),
		}); ok {
			desc = tpl
		}
		det := fmt.Sprintf(`{"description":%q,"player_name":%q,"injury_type":%q,"injury_weeks":%d}`, desc, playerName, injType, weeks)
		res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, player_id, detail) VALUES (?, ?, 'injury', ?, ?, ?)",
			fixtureID, minute, side, playerID, det)
		if err != nil {
			continue
		}
		evID, _ := res.LastInsertId()
		e.broadcastEvent(fixtureID, evID, minute, "injury", side, desc, 0, 0, "")
	}
}

func min(a, b int) int {
	if a < b {
		return a
	}
	return b
}

func clampFloat(v, min, max float64) float64 {
	if v < min {
		return min
	}
	if v > max {
		return max
	}
	return v
}

func (e *MatchEngine) processMatchCommands(fixtureID, minute int) {
	type cmd struct {
		ID          int    `db:"id"`
		TeamID      int    `db:"team_id"`
		CommandType string `db:"command_type"`
		Payload     string `db:"payload"`
	}
	var cmds []cmd
	_ = e.DB.Select(&cmds,
		"SELECT id, team_id, command_type, payload FROM match_command WHERE fixture_id = ? AND processed = 0 ORDER BY id ASC LIMIT 10",
		fixtureID)

	for _, c := range cmds {
		side := "home"
		var homeTeamID int
		_ = e.DB.Get(&homeTeamID, "SELECT home_team_id FROM fixture WHERE id = ?", fixtureID)
		if c.TeamID != homeTeamID {
			side = "away"
		}

		switch c.CommandType {
		case "substitution":
			var payload struct {
				Out int `json:"out"`
				In  int `json:"in"`
			}
			if err := json.Unmarshal([]byte(c.Payload), &payload); err == nil && payload.Out > 0 && payload.In > 0 {
				outName, inName := "", ""
				_ = e.DB.Get(&outName, "SELECT name FROM player WHERE id = ?", payload.Out)
				_ = e.DB.Get(&inName, "SELECT name FROM player WHERE id = ?", payload.In)
				desc := fmt.Sprintf("Sostituzione: esce %s, entra %s.", outName, inName)
				det := fmt.Sprintf(`{"description":%q,"out":%d,"in":%d,"out_name":%q,"in_name":%q}`, desc, payload.Out, payload.In, outName, inName)
				res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, ?, 'substitution', ?, ?)",
					fixtureID, minute, side, det)
				if err == nil {
					evID, _ := res.LastInsertId()
					e.broadcastEvent(fixtureID, evID, minute, "substitution", side, desc, 0, 0, "")
				}
			}
		case "effort_change":
			// SIP-0083: update live effort level in match_state
			var payload struct {
				EffortLevel int `json:"effort_level"`
			}
			if err := json.Unmarshal([]byte(c.Payload), &payload); err == nil {
				col := "home_effort_level"
				if side == "away" {
					col = "away_effort_level"
				}
				_, _ = e.DB.Exec(fmt.Sprintf("UPDATE match_state SET %s = ? WHERE fixture_id = ?", col), payload.EffortLevel, fixtureID)
				teamName := e.teamName(fixtureID, side)
				desc := fmt.Sprintf("%s modifica l'impegno a %d%%.", teamName, payload.EffortLevel)
				det := fmt.Sprintf(`{"description":%q,"effort_level":%d}`, desc, payload.EffortLevel)
				res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, ?, 'effort_change', ?, ?)",
					fixtureID, minute, side, det)
				if err == nil {
					evID, _ := res.LastInsertId()
					e.broadcastEvent(fixtureID, evID, minute, "effort_change", side, desc, 0, 0, "")
				}
			}
		}
		_, _ = e.DB.Exec("UPDATE match_command SET processed = 1 WHERE id = ?", c.ID)
	}
}

// maybeCpuAdjustEffort: CPU teams may dynamically adjust effort in the last 20 minutes (SIP-0083).
func (e *MatchEngine) maybeCpuAdjustEffort(fixtureID int, state *models.MatchState) {
	if state.CurrentMinute < 70 || state.Phase != "SECOND_HALF" {
		return
	}
	// Only trigger occasionally (~5% of eligible ticks)
	if rand.Float64() > 0.05 {
		return
	}

	for _, side := range []string{"home", "away"} {
		teamID, err := e.teamID(fixtureID, side)
		if err != nil || teamID == 0 {
			continue
		}
		// Only CPU teams (user_id IS NULL)
		var isCPU int
		_ = e.DB.Get(&isCPU, "SELECT is_cpu FROM team WHERE id = ?", teamID)
		if isCPU == 0 {
			continue
		}

		scoreDiff := state.HomeScore - state.AwayScore
		if side == "away" {
			scoreDiff = -scoreDiff
		}
		currentEffortField := "home_effort_level"
		if side == "away" {
			currentEffortField = "away_effort_level"
		}
		currentEffort := state.HomeEffortLevel
		if side == "away" {
			currentEffort = state.AwayEffortLevel
		}

		newEffort := currentEffort
		switch {
		case scoreDiff < 0: // losing → push harder
			if currentEffort < 100 {
				newEffort = currentEffort + 25
				if newEffort > 100 {
					newEffort = 100
				}
			}
		case scoreDiff > 0 && state.CurrentMinute >= 80: // winning late → preserve energy
			if currentEffort > 25 {
				newEffort = currentEffort - 25
			}
		}

		if newEffort == currentEffort {
			continue
		}

		_, _ = e.DB.Exec(fmt.Sprintf("UPDATE match_state SET %s = ? WHERE fixture_id = ?", currentEffortField), newEffort, fixtureID)
		if side == "home" {
			state.HomeEffortLevel = newEffort
		} else {
			state.AwayEffortLevel = newEffort
		}
		teamName := e.teamName(fixtureID, side)
		desc := fmt.Sprintf("(%s alza l'impegno.)", teamName)
		if newEffort < currentEffort {
			desc = fmt.Sprintf("(%s gestisce le energie.)", teamName)
		}
		det := fmt.Sprintf(`{"description":%q,"effort_level":%d}`, desc, newEffort)
		e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, ?, 'effort_change', ?, ?)",
			fixtureID, state.CurrentMinute, side, det)
	}
}

// randomPlayerIDName picks a random player (id + name) from the team.
func (e *MatchEngine) randomPlayerIDName(fixtureID int, side, posGroup string) (int, string) {
	if lineup := e.activeLineupPlayers(fixtureID, side, posGroup); len(lineup) > 0 {
		p := lineup[rand.Intn(len(lineup))]
		return p.ID, p.Name
	}

	teamID, err := e.teamID(fixtureID, side)
	if err != nil || teamID == 0 {
		return 0, ""
	}
	var row struct {
		ID   int    `db:"id"`
		Name string `db:"name"`
	}
	q := "SELECT id, name FROM player WHERE team_id = ? AND position != 'GK' AND injury_weeks = 0 ORDER BY RAND() LIMIT 1"
	args := []interface{}{teamID}
	if posGroup == "GK" {
		q = "SELECT id, name FROM player WHERE team_id = ? AND position = 'GK' AND injury_weeks = 0 LIMIT 1"
	} else if posGroup != "" {
		q = "SELECT id, name FROM player WHERE team_id = ? AND position IN (?, 'FW') AND injury_weeks = 0 ORDER BY general_skill DESC, id ASC LIMIT 1"
		args = append(args, posGroup)
	}
	if err := e.DB.Get(&row, q, args...); err != nil {
		return 0, ""
	}
	return row.ID, row.Name
}

type lineupPlayer struct {
	ID           int    `db:"id"`
	Name         string `db:"name"`
	Position     string `db:"position"`
	GeneralSkill int    `db:"general_skill"`
	Zone         int    `db:"zone"`
}

// activeLineupPlayers returns current on-pitch players from match_state formations.
// It avoids picking bench/reserve names in commentary.
func (e *MatchEngine) activeLineupPlayers(fixtureID int, side, posGroup string) []lineupPlayer {
	formationCol := "ms.home_formation_id"
	if side == "away" {
		formationCol = "ms.away_formation_id"
	}

	where := "AND p.position != 'GK'"
	args := []interface{}{fixtureID}
	switch posGroup {
	case "GK":
		where = "AND p.position = 'GK'"
	case "", "FW", "MF", "DF":
		if posGroup != "" {
			where = "AND p.position = ?"
			args = append(args, posGroup)
		}
	default:
		// Unknown group: keep default outfield filter.
	}

	q := fmt.Sprintf(`
		SELECT p.id, p.name, p.position, p.general_skill, fs.zone
		FROM match_state ms
		JOIN formation_slot fs ON fs.formation_id = %s
		JOIN player p ON p.id = fs.player_id
		WHERE ms.fixture_id = ?
		  AND (((fs.zone = 10) OR ((fs.zone BETWEEN 20 AND 103) AND (MOD(fs.zone,10) BETWEEN 1 AND 3))) OR (fs.zone BETWEEN 1 AND 63) OR (fs.zone BETWEEN 1001 AND 1063))
		  AND fs.player_id IS NOT NULL
		  %s
		ORDER BY p.general_skill DESC, fs.zone ASC, p.id ASC
	`, formationCol, where)

	rows := []lineupPlayer{}
	if err := e.DB.Select(&rows, q, args...); err == nil && len(rows) > 0 {
		return rows
	}

	return nil
}

// fixtureLang reads fixture.language from DB. Returns "it-IT" on any error.
func (e *MatchEngine) fixtureLang(fixtureID int) string {
	var lang string
	if err := e.DB.Get(&lang, "SELECT COALESCE(language,'it-IT') FROM fixture WHERE id=?", fixtureID); err != nil || lang == "" {
		return "it-IT"
	}
	return lang
}

// enrichEventAsync calls Ollama in the background and updates event commentary.
// Never blocks tick loop.
func (e *MatchEngine) enrichEventAsync(
	fixtureID int,
	eventID int64,
	eventType string,
	minute int,
	teamSide string,
	prompt string,
	fallback string,
) {
	lang := e.fixtureLang(fixtureID)
	meta := commentaryMeta{
		EventType: eventType,
		Minute:    minute,
		TeamSide:  teamSide,
		Source:    "llm",
	}
	if !envBool("GM_LLM_ENABLED", true) || !envBool("GM_LLM_MATCH_ENRICH_ENABLED", true) {
		log.Printf("[MATCH %d] LLM skip for %s: GM_LLM_ENABLED=%v GM_LLM_MATCH_ENRICH_ENABLED=%v",
			fixtureID, eventType, envBool("GM_LLM_ENABLED", true), envBool("GM_LLM_MATCH_ENRICH_ENABLED", true))
		if envBool("GM_COMMENTARY_STREAM_ENABLED", true) && strings.TrimSpace(fallback) != "" {
			meta.Source = "template"
			e.streamTemplateText(fixtureID, eventID, fallback, meta)
		}
		return
	}

	streamStarted := false
	text := e.CallOllama(prompt, lang, func(token string) {
		if strings.TrimSpace(token) == "" {
			return
		}
		if !streamStarted {
			streamStarted = true
			e.emitCommentaryEvent(fixtureID, eventID, "start", "", "", meta)
		}
		e.emitCommentaryEvent(fixtureID, eventID, "token", token, "", meta)
	})

	if text == "" || text == fallback {
		if streamStarted {
			e.emitCommentaryEvent(fixtureID, eventID, "done", "", fallback, meta)
		}
		return
	}
	// Update only the description field inside the existing JSON detail
	var existing map[string]interface{}
	row := e.DB.QueryRow("SELECT detail FROM match_event WHERE id = ?", eventID)
	var raw string
	if err := row.Scan(&raw); err == nil {
		if err2 := json.Unmarshal([]byte(raw), &existing); err2 == nil {
			existing["description"] = text
			if updated, err3 := json.Marshal(existing); err3 == nil {
				_, _ = e.DB.Exec("UPDATE match_event SET detail = ? WHERE id = ?", string(updated), eventID)
				e.broadcast(fixtureID, streamPayload{"type": "event_update", "id": eventID, "description": text})
				e.emitCommentaryEvent(fixtureID, eventID, "done", "", text, meta)
			}
		}
	}
}

func (e *MatchEngine) EnrichPreMatchAsync(fixtureID int, eventID int64, payload PreMatchPayload) {
	lang := e.fixtureLang(fixtureID)
	meta := commentaryMeta{
		EventType: "pre_match",
		Minute:    0,
		TeamSide:  "home",
		Source:    "llm",
	}
	if !envBool("GM_LLM_ENABLED", true) || !envBool("GM_LLM_PREMATCH_ENABLED", true) {
		log.Printf("[MATCH %d] LLM skip for pre_match: GM_LLM_ENABLED=%v GM_LLM_PREMATCH_ENABLED=%v",
			fixtureID, envBool("GM_LLM_ENABLED", true), envBool("GM_LLM_PREMATCH_ENABLED", true))
		if envBool("GM_COMMENTARY_STREAM_ENABLED", true) && strings.TrimSpace(payload.Fallback) != "" {
			meta.Source = "template"
			e.streamTemplateText(fixtureID, eventID, payload.Fallback, meta)
		}
		return
	}

	streamStarted := false
	text := e.CallOllamaLong(payload.Prompt, lang, func(token string) {
		if strings.TrimSpace(token) == "" {
			return
		}
		if !streamStarted {
			streamStarted = true
			e.emitCommentaryEvent(fixtureID, eventID, "start", "", "", meta)
		}
		e.emitCommentaryEvent(fixtureID, eventID, "token", token, "", meta)
	})

	if text == "" || text == payload.Fallback {
		if streamStarted {
			e.emitCommentaryEvent(fixtureID, eventID, "done", "", payload.Fallback, meta)
		}
		return
	}

	var existing map[string]interface{}
	row := e.DB.QueryRow("SELECT detail FROM match_event WHERE id = ?", eventID)
	var raw string
	if err := row.Scan(&raw); err == nil {
		if err2 := json.Unmarshal([]byte(raw), &existing); err2 == nil {
			existing["description"] = text
			if _, hasWeather := existing["weather"]; !hasWeather {
				existing["weather"] = payload.Weather
			}
			if _, hasField := existing["field_condition"]; !hasField {
				existing["field_condition"] = payload.FieldCondition
			}
			if _, hasSpectators := existing["spectators"]; !hasSpectators {
				existing["spectators"] = payload.Spectators
			}
			if updated, err3 := json.Marshal(existing); err3 == nil {
				_, _ = e.DB.Exec("UPDATE match_event SET detail = ? WHERE id = ?", string(updated), eventID)
				e.broadcast(fixtureID, streamPayload{
					"type":            "event_update",
					"id":              eventID,
					"description":     text,
					"weather":         payload.Weather,
					"field_condition": payload.FieldCondition,
					"spectators":      payload.Spectators,
				})
				e.emitCommentaryEvent(fixtureID, eventID, "done", "", text, meta)
			}
		}
	}
}

// teamName fetches home or away team name for a fixture.
// randomPlayerName picks a random player name from the team for a given position group.
// posGroup: "FW" | "MF" | "DF" | "" (any outfield)
func (e *MatchEngine) randomPlayerName(fixtureID int, side string, posGroup string) string {
	if lineup := e.activeLineupPlayers(fixtureID, side, posGroup); len(lineup) > 0 {
		return lineup[rand.Intn(len(lineup))].Name
	}

	teamID, err := e.teamID(fixtureID, side)
	if err != nil || teamID == 0 {
		return "un giocatore"
	}
	var names []string
	var q string
	if posGroup == "" {
		q = "SELECT name FROM player WHERE team_id = ? AND position != 'GK' ORDER BY RAND() LIMIT 5"
		_ = e.DB.Select(&names, q, teamID)
	} else if posGroup == "GK" {
		q = "SELECT name FROM player WHERE team_id = ? AND position = 'GK' LIMIT 1"
		_ = e.DB.Select(&names, q, teamID)
	} else {
		q = "SELECT name FROM player WHERE team_id = ? AND position IN (?, 'FW') ORDER BY general_skill DESC LIMIT 5"
		_ = e.DB.Select(&names, q, teamID, posGroup)
	}
	if len(names) == 0 {
		return "un giocatore"
	}
	return names[rand.Intn(len(names))]
}

func (e *MatchEngine) teamName(fixtureID int, side string) string {
	var name string
	col := "home_team_id"
	if side == "away" {
		col = "away_team_id"
	}
	_ = e.DB.Get(&name, "SELECT t.name FROM team t JOIN fixture f ON f."+col+" = t.id WHERE f.id = ?", fixtureID)
	return name
}

func (e *MatchEngine) teamID(fixtureID int, side string) (int, error) {
	var teamID int
	col := "home_team_id"
	if side == "away" {
		col = "away_team_id"
	}
	err := e.DB.Get(&teamID, "SELECT "+col+" FROM fixture WHERE id = ?", fixtureID)
	return teamID, err
}

func (e *MatchEngine) fixtureCompetitionType(fixtureID int) string {
	var competitionType string
	_ = e.DB.Get(&competitionType, `
		SELECT c.type
		FROM fixture f
		JOIN competition c ON c.id = f.competition_id
		WHERE f.id = ?
	`, fixtureID)
	return competitionType
}

// teamUserID returns the user_id of the manager who owns this team (0 if CPU or error).
func (e *MatchEngine) teamUserID(fixtureID int, side string) int {
	teamID, err := e.teamID(fixtureID, side)
	if err != nil || teamID <= 0 {
		return 0
	}
	var userID int
	if err := e.DB.Get(&userID, "SELECT COALESCE(user_id, 0) FROM team WHERE id = ?", teamID); err != nil {
		return 0
	}
	return userID
}

// insertMatchNotification writes a news_item and a notification_delivery row
// directly into the DB (bypassing PHP). The PHP notification/dispatch worker
// picks up the delivery row and sends Telegram asynchronously (SIP-0080).
func (e *MatchEngine) insertMatchNotification(userID int, category, icon, title, body, telegramText string) {
	if userID <= 0 {
		return
	}
	now := time.Now().Unix()

	res, err := e.DB.Exec(`
		INSERT INTO news_item (user_id, category, icon, title, body, link_url, is_read, priority, created_at)
		VALUES (?, ?, ?, ?, ?, '', 0, 0, ?)
	`, userID, category, icon, title, body, now)
	if err != nil {
		log.Printf("[NOTIF] news_item insert failed user=%d: %v", userID, err)
		return
	}
	newsItemID, _ := res.LastInsertId()

	// Check telegram_enabled before inserting delivery row
	var tgEnabled int
	_ = e.DB.Get(&tgEnabled, "SELECT COALESCE(telegram_enabled, 0) FROM user WHERE id = ?", userID)
	if tgEnabled == 0 || telegramText == "" {
		return
	}

	_, err = e.DB.Exec(`
		INSERT INTO notification_delivery (user_id, news_item_id, channel, status, telegram_text, attempts, created_at, updated_at)
		VALUES (?, ?, 'telegram', 'pending', ?, 0, ?, ?)
	`, userID, newsItemID, telegramText, now, now)
	if err != nil {
		log.Printf("[NOTIF] delivery insert failed user=%d: %v", userID, err)
	}
}

type teamTraitProfile struct {
	Bonus            float64
	DisciplineScalar float64
	InjuryScalar     float64
	HasCarismatico   bool
	GkHeightMod      float64 // >1.0 when this team's GK is short → opposing team scores more easily
}

type playerVitals struct {
	Freshness int `db:"freshness"`
	Condition int `db:"condition"`
	Form      int `db:"form"`
}

func (e *MatchEngine) playerVitals(playerID int) playerVitals {
	var vit playerVitals
	_ = e.DB.Get(&vit, "SELECT freshness, condition, form FROM player WHERE id = ?", playerID)
	return vit
}

func (e *MatchEngine) teamTraitsProfile(fixtureID int, side string, isLosing bool, isHome bool) teamTraitProfile {
	profile := teamTraitProfile{
		Bonus:            1.0,
		DisciplineScalar: 1.0,
		InjuryScalar:     1.0,
	}
	teamID, err := e.teamID(fixtureID, side)
	if err != nil || teamID <= 0 {
		return profile
	}

	type traitRow struct {
		Character string `db:"character"`
	}
	rows := []traitRow{}
	err = e.DB.Select(&rows, `
		SELECT COALESCE(p.character, '') AS character
		FROM formation f
		JOIN formation_slot fs ON fs.formation_id = f.id
		JOIN player p ON p.id = fs.player_id
		WHERE f.team_id = ? AND f.is_active = 1
		  AND (((fs.zone = 10) OR ((fs.zone BETWEEN 20 AND 103) AND (MOD(fs.zone,10) BETWEEN 1 AND 3))) OR (fs.zone BETWEEN 1 AND 63) OR (fs.zone BETWEEN 1001 AND 1063))
		  AND fs.player_id IS NOT NULL
	`, teamID)
	if err != nil || len(rows) == 0 {
		rows = []traitRow{}
		err = e.DB.Select(&rows, `
			SELECT COALESCE(character, '') AS character
			FROM player
			WHERE team_id = ?
			ORDER BY general_skill DESC
			LIMIT 11
		`, teamID)
		if err != nil || len(rows) == 0 {
			return profile
		}
	}

	total := 0.0
	count := 0
	for _, row := range rows {
		total += MatchTraitScalar(row.Character, isLosing, isHome)
		count++
		switch normalizeTrait(row.Character) {
		case "carismatico":
			profile.HasCarismatico = true
			profile.Bonus *= 1.01
		case "corretto":
			profile.DisciplineScalar *= 0.70
		case "grintoso":
			profile.DisciplineScalar *= 1.05
		case "irrequieto":
			profile.DisciplineScalar *= 1.15
		case "egoista":
			// SIP-0024: selfish → more fouls/frustration when things don't go their way
			profile.DisciplineScalar *= 1.12
		case "fantasioso":
			// SIP-0024: creative → sometimes reckless
			profile.DisciplineScalar *= 0.97
		case "diligente":
			profile.InjuryScalar *= 0.90
		case "costante":
			profile.InjuryScalar *= 0.95
		}
	}
	if count > 0 {
		avg := total / float64(count)
		profile.Bonus = clampFloat(avg*profile.Bonus, 0.80, 1.25)
	}
	profile.DisciplineScalar = clampFloat(profile.DisciplineScalar, 0.50, 1.50)
	profile.InjuryScalar = clampFloat(profile.InjuryScalar, 0.60, 1.40)

	// SIP-0073: Physical Fitness Index — same formula as PhysicalHelper::teamPfi() in PHP.
	// Parity: clamp [0.92, 1.04], BMI deviation × 0.02 penalty from 1.04 peak.
	type physRow struct {
		HeightCm int    `db:"height_cm"`
		WeightKg int    `db:"weight_kg"`
		Position string `db:"position"`
	}
	var physRows []physRow
	_ = e.DB.Select(&physRows, `
		SELECT COALESCE(p.height_cm, 180) AS height_cm,
		       COALESCE(p.weight_kg, 75)  AS weight_kg,
		       p.position
		FROM formation f
		JOIN formation_slot fs ON fs.formation_id = f.id
		JOIN player p ON p.id = fs.player_id
		WHERE f.team_id = ? AND f.is_active = 1
		  AND (((fs.zone = 10) OR ((fs.zone BETWEEN 20 AND 103) AND (MOD(fs.zone,10) BETWEEN 1 AND 3))) OR (fs.zone BETWEEN 1 AND 63) OR (fs.zone BETWEEN 1001 AND 1063))
		  AND fs.player_id IS NOT NULL
	`, teamID)
	if len(physRows) > 0 {
		pfiSum := 0.0
		for _, pr := range physRows {
			pfiSum += physicalFitnessMod(pr.HeightCm, pr.WeightKg, pr.Position)
		}
		teamPFI := pfiSum / float64(len(physRows))
		profile.Bonus = clampFloat(profile.Bonus*teamPFI, 0.80, 1.25)
	}

	// SIP-0073: GK height penalty — parity with PhysicalHelper::gkHeightPenalty().
	// GkHeightMod > 1.0 means this team's GK is short → opponents score more easily.
	profile.GkHeightMod = 1.0
	var gkHRow struct {
		HeightCm int `db:"height_cm"`
	}
	if qErr := e.DB.Get(&gkHRow, `
		SELECT COALESCE(p.height_cm, 183) AS height_cm
		FROM player p
		WHERE p.team_id = ? AND p.position = 'GK'
		ORDER BY p.general_skill DESC
		LIMIT 1
	`, teamID); qErr == nil && gkHRow.HeightCm < 183 {
		profile.GkHeightMod = 1.0 + float64(183-gkHRow.HeightCm)*0.005
	}

	return profile
}

// physicalFitnessMod returns PFI in [0.92, 1.04].
// Parity: mirrors PhysicalHelper::pfi() in PHP (SIP-0073).
func physicalFitnessMod(heightCm, weightKg int, position string) float64 {
	if heightCm <= 0 {
		return 1.0
	}
	h := float64(heightCm) / 100.0
	bmi := float64(weightKg) / (h * h)
	refBmi := map[string]float64{"GK": 24.0, "DF": 23.5, "MF": 22.5, "FW": 22.0}
	pos := "FW"
	switch {
	case position == "GK" || position == "PO":
		pos = "GK"
	case position == "DF" || position == "DS" || position == "DD" || position == "DC" || position == "D":
		pos = "DF"
	case position == "MF" || position == "CS" || position == "CD" || position == "CC" || position == "C":
		pos = "MF"
	}
	ref := refBmi[pos]
	dev := bmi - ref
	if dev < 0 {
		dev = -dev
	}
	pfi := 1.04 - dev*0.02
	if pfi < 0.92 {
		return 0.92
	}
	if pfi > 1.04 {
		return 1.04
	}
	return pfi
}

// teamTactics holds the 8 tactic levels (0-100) for one team.
type teamTactics struct {
	Pressing    float64
	Contropiede float64
	Possesso    float64
	PallaBassa  float64
	LancioLungo float64
	Catenaccio  float64
	Fuorigioco  float64
}

// loadTeamTactics fetches the latest tactic row for a team.
// Returns sensible defaults if no row exists.
func (e *MatchEngine) loadTeamTactics(teamID int) teamTactics {
	defaults := teamTactics{
		Pressing: 30, Contropiede: 20, Possesso: 40,
		PallaBassa: 30, LancioLungo: 20, Catenaccio: 20,
		Fuorigioco: 10,
	}
	if teamID <= 0 {
		return defaults
	}
	type row struct {
		Pressing    int `db:"pressing"`
		Contropiede int `db:"contropiede"`
		Possesso    int `db:"possesso"`
		PallaBassa  int `db:"palla_bassa"`
		LancioLungo int `db:"lancio_lungo"`
		Catenaccio  int `db:"catenaccio"`
		Fuorigioco  int `db:"fuorigioco"`
	}
	var r row
	err := e.DB.Get(&r,
		"SELECT pressing, contropiede, possesso, palla_bassa, lancio_lungo, catenaccio, fuorigioco FROM training_tactic WHERE team_id = ? ORDER BY season DESC LIMIT 1",
		teamID)
	if err != nil {
		return defaults
	}
	return teamTactics{
		Pressing:    float64(r.Pressing),
		Contropiede: float64(r.Contropiede),
		Possesso:    float64(r.Possesso),
		PallaBassa:  float64(r.PallaBassa),
		LancioLungo: float64(r.LancioLungo),
		Catenaccio:  float64(r.Catenaccio),
		Fuorigioco:  float64(r.Fuorigioco),
	}
}

// tacticGoalModifier returns a goal-probability multiplier in [0.70, 1.40].
// Parity: mirrors PHP MatchEngine::resolveTick() SIP-0038 tactic effects.
// atk = tactics of the team attacking, def = tactics of the defending team.
func tacticGoalModifier(atk, def teamTactics) float64 {
	pressing := atk.Pressing / 100.0
	possesso := atk.Possesso / 100.0
	lancio := atk.LancioLungo / 100.0
	contro := atk.Contropiede / 100.0

	// Conflict penalties (mirrors applyTacticConflicts in PHP)
	if atk.Pressing > 50 && atk.Catenaccio > 50 {
		pressing *= 0.70 // pressing −30% effective
	}
	if atk.LancioLungo > 50 && atk.Possesso > 50 {
		possesso *= 0.60 // possesso −40% effective
	}

	atkMod := 1.0 +
		pressing*0.08 +
		possesso*0.10 +
		lancio*0.10 +
		contro*0.075

	caten := def.Catenaccio / 100.0
	fuo := def.Fuorigioco / 100.0
	palla := def.PallaBassa / 100.0

	defMod := 1.0 +
		caten*0.12 +
		fuo*0.05 + // fine-tune parity: lighter offside defensive suppression
		palla*0.05

	mod := atkMod / defMod
	if mod < 0.70 {
		return 0.70
	}
	if mod > 1.40 {
		return 1.40
	}
	return mod
}

// teamSetPieceMod returns a goal-probability multiplier in [1.0, 1.06] based on the
// best skill_cp in the team's active XI. Parity: PHP FormationRoleHelper::precisionCapForSetPiece (SIP-0075).
func (e *MatchEngine) teamSetPieceMod(teamID, fixtureID int) float64 {
	if teamID <= 0 {
		return 1.0
	}
	var maxSkillCp int
	err := e.DB.Get(&maxSkillCp,
		`SELECT COALESCE(MAX(p.skill_cp), 0)
		 FROM formation f
		 JOIN formation_slot fs ON fs.formation_id = f.id
		 JOIN player p ON p.id = fs.player_id
		 WHERE f.team_id = ? AND f.is_active = 1 AND fs.player_id IS NOT NULL`,
		teamID)
	if err != nil || maxSkillCp == 0 {
		return 1.0
	}
	// 14% of shots are set pieces; skill_cp 100 → +30% precision cap → ~4.2% extra conversion
	return 1.0 + float64(maxSkillCp)/100.0*0.06
}

func (e *MatchEngine) pressureGoalMod(fixtureID int, side string, minute int, homeScore int, awayScore int) float64 {
	var dangerous, attacks, setPieces int
	_ = e.DB.Get(&dangerous,
		`SELECT COUNT(*)
		 FROM match_event
		 WHERE fixture_id = ? AND team_side = ? AND type IN ('near_miss','gk_save')`,
		fixtureID, side)
	_ = e.DB.Get(&attacks,
		`SELECT COUNT(*)
		 FROM match_event
		 WHERE fixture_id = ? AND team_side = ? AND type = 'attack_attempt'`,
		fixtureID, side)
	_ = e.DB.Get(&setPieces,
		`SELECT COUNT(*)
		 FROM match_event
		 WHERE fixture_id = ? AND team_side = ? AND type IN ('corner','freekick','penalty_awarded')`,
		fixtureID, side)

	bonus := float64(dangerous)*0.08 + float64(attacks)*0.02 + float64(setPieces)*0.10
	if minute >= 60 {
		bonus += float64(minute-59) * 0.004
	}
	if homeScore+awayScore == 0 {
		if minute >= 55 {
			bonus += 0.12
		}
		if minute >= 75 {
			bonus += 0.18
		}
	}
	return clampFloat(1.0+bonus, 1.0, 1.95)
}

func (e *MatchEngine) ProcessCommand(fixtureID int, state *models.MatchState, cmd models.MatchCommand) {
	log.Printf("[MATCH %d] Executing Command: %s", fixtureID, cmd.CommandType)
	teamSide := "home"
	var fixHomeTeamID int
	_ = e.DB.Get(&fixHomeTeamID, "SELECT home_team_id FROM fixture WHERE id = ?", fixtureID)
	if cmd.TeamID != fixHomeTeamID {
		teamSide = "away"
	}
	res, err := e.DB.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, ?, ?, ?, ?)",
		fixtureID, state.CurrentMinute, cmd.CommandType, teamSide, cmd.Payload)
	var eventID int64
	if err == nil {
		eventID, _ = res.LastInsertId()
	}
	e.broadcastEvent(fixtureID, eventID, state.CurrentMinute, cmd.CommandType, teamSide, "", state.HomeScore, state.AwayScore, "")
	_, _ = e.DB.Exec("UPDATE match_command SET executed_at_minute = ? WHERE id = ?", state.CurrentMinute, cmd.ID)
}

func (e *MatchEngine) EndMatch(fixtureID int) error {
	var state models.MatchState
	_ = e.DB.Get(&state, "SELECT * FROM match_state WHERE fixture_id = ?", fixtureID)
	var fx struct {
		CompetitionID   int    `db:"competition_id"`
		CompetitionType string `db:"type"`
		HomeTeamID      int    `db:"home_team_id"`
		AwayTeamID      int    `db:"away_team_id"`
	}
	if err := e.DB.Get(&fx, `
		SELECT f.competition_id, c.type, f.home_team_id, f.away_team_id
		FROM fixture f
		JOIN competition c ON c.id = f.competition_id
		WHERE f.id = ?
	`, fixtureID); err != nil {
		return err
	}

	fallback := fmt.Sprintf("Triplice fischio! Finisce qui! %s %d - %s %d.", e.teamName(fixtureID, "home"), state.HomeScore, e.teamName(fixtureID, "away"), state.AwayScore)
	if tpl, ok := e.pickTemplate("full_time", "", deriveGameState(state.HomeScore, state.AwayScore, "home", state.CurrentMinute), state.CurrentMinute, fixtureID, map[string]string{
		"home_team":  e.teamName(fixtureID, "home"),
		"away_team":  e.teamName(fixtureID, "away"),
		"score_home": fmt.Sprintf("%d", state.HomeScore),
		"score_away": fmt.Sprintf("%d", state.AwayScore),
		"minute":     fmt.Sprintf("%d", state.CurrentMinute),
	}); ok {
		fallback = tpl
	}
	detail := fmt.Sprintf(`{"home":%d,"away":%d,"description":%q}`, state.HomeScore, state.AwayScore, fallback)

	tx, err := e.DB.Begin()
	if err != nil {
		return err
	}
	if _, err = tx.Exec("UPDATE fixture SET status = 2 WHERE id = ?", fixtureID); err != nil { // 2 = STATUS_FINISHED
		tx.Rollback()
		return err
	}
	if err = e.savePlayerStatsTx(tx, fixtureID, &state, fx.HomeTeamID, fx.AwayTeamID); err != nil {
		tx.Rollback()
		return err
	}
	if err = e.applyFixtureExperienceTx(tx, fixtureID, &state, fx.CompetitionType == "friendly"); err != nil {
		tx.Rollback()
		return err
	}
	if fx.CompetitionType != "friendly" {
		if err = e.applyStandings(tx, fixtureID, state.HomeScore, state.AwayScore); err != nil {
			tx.Rollback()
			return err
		}
		if err = e.serveSuspensionsTx(tx, fx.HomeTeamID, fx.AwayTeamID); err != nil {
			tx.Rollback()
			return err
		}
	}
	if _, err = tx.Exec("UPDATE match_state SET phase = 'FINISHED' WHERE fixture_id = ?", fixtureID); err != nil {
		tx.Rollback()
		return err
	}
	res, err2 := tx.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, ?, 'full_time', 'home', ?)", fixtureID, state.CurrentMinute, detail)
	if err2 != nil {
		tx.Rollback()
		return err2
	}
	if err = tx.Commit(); err != nil {
		return err
	}
	eventID, _ := res.LastInsertId()
	e.broadcastEvent(fixtureID, eventID, state.CurrentMinute, "full_time", "home", fallback, state.HomeScore, state.AwayScore, "")

	// SIP-0080: full-time notification for both managers
	ftHome := e.teamName(fixtureID, "home")
	ftAway := e.teamName(fixtureID, "away")
	ftTitle := fmt.Sprintf("Finale: %s %d–%d %s", ftHome, state.HomeScore, state.AwayScore, ftAway)
	ftTg := fmt.Sprintf("🏁 <b>Finale</b>: %s %d–%d %s", ftHome, state.HomeScore, state.AwayScore, ftAway)
	go e.insertMatchNotification(e.teamUserID(fixtureID, "home"), "match", "🏁", ftTitle, "", ftTg)
	go e.insertMatchNotification(e.teamUserID(fixtureID, "away"), "match", "🏁", ftTitle, "", ftTg)

	// LLM enrichment for full_time
	prompt := fmt.Sprintf(
		"Partita finita. %s %d-%d %s. Commento finale telecronistra calcio.",
		e.teamName(fixtureID, "home"),
		state.HomeScore,
		state.AwayScore,
		e.teamName(fixtureID, "away"),
	)
	go e.enrichEventAsync(fixtureID, eventID, "full_time", state.CurrentMinute, "home", prompt, fallback)

	return nil
}

func (e *MatchEngine) applyStandings(tx *sql.Tx, fixtureID int, homeScore, awayScore int) error {
	var fixture struct {
		CompetitionID   int
		HomeTeamID      int
		AwayTeamID      int
		CompetitionType string
	}

	err := tx.QueryRow(`
		SELECT f.competition_id, f.home_team_id, f.away_team_id, c.type
		FROM fixture f
		JOIN competition c ON c.id = f.competition_id
		WHERE f.id = ?
	`, fixtureID).Scan(&fixture.CompetitionID, &fixture.HomeTeamID, &fixture.AwayTeamID, &fixture.CompetitionType)
	if err != nil {
		return err
	}

	if fixture.CompetitionType == "friendly" {
		return nil
	}

	if err := updateStandingTx(tx, fixture.CompetitionID, fixture.HomeTeamID, homeScore, awayScore); err != nil {
		return err
	}
	return updateStandingTx(tx, fixture.CompetitionID, fixture.AwayTeamID, awayScore, homeScore)
}

type playerStatRow struct {
	TeamID          int
	Goals           int
	Assists         int
	YellowCards     int
	RedCards        int
	MinutesPlayed   int
	PenaltiesScored int
	PenaltiesMissed int
	Saves           int
	CleanSheet      int
}

func (e *MatchEngine) blankPlayerStatRow(teamID int) *playerStatRow {
	return &playerStatRow{
		TeamID:          teamID,
		Goals:           0,
		Assists:         0,
		YellowCards:     0,
		RedCards:        0,
		MinutesPlayed:   0,
		PenaltiesScored: 0,
		PenaltiesMissed: 0,
		Saves:           0,
		CleanSheet:      0,
	}
}

func onPitchSQL(column string) string {
	// Includes stored-display-zone encoding (offset +1000, range 1001-1063) used by PHP frontend.
	return fmt.Sprintf("(((%s = 10) OR ((%s BETWEEN 20 AND 103) AND (MOD(%s,10) BETWEEN 1 AND 3))) OR (%s BETWEEN 1 AND 63) OR (%s = 64) OR (%s BETWEEN 1001 AND 1063))", column, column, column, column, column, column)
}

func normalizeToCurrentZone(zone int) int {
	if zone == 64 {
		return 10
	}
	// Current zones: GK=10 or row*10+lane where row 2..10, lane 1..3
	if zone == 10 {
		return 10
	}
	row := zone / 10
	lane := zone % 10
	if row >= 2 && row <= 10 && lane >= 1 && lane <= 3 {
		return zone
	}
	// Legacy zones 1..63
	if zone >= 1 && zone <= 63 {
		legacyRow := ((zone - 1) / 7) + 1
		legacyCol := ((zone - 1) % 7) + 1
		newRow := 11 - legacyRow
		newLane := 2
		if legacyCol <= 2 {
			newLane = 1
		} else if legacyCol >= 6 {
			newLane = 3
		}
		if newRow < 2 {
			newRow = 2
		}
		if newRow > 10 {
			newRow = 10
		}
		return newRow*10 + newLane
	}
	return 62
}

func toDisplayZone(zone int) int {
	if zone == 64 || zone == 10 {
		return 64
	}
	n := normalizeToCurrentZone(zone)
	if n == 10 {
		return 64
	}
	// If already current, map row/lane to legacy col/row.
	row := n / 10
	lane := n % 10
	if row >= 2 && row <= 10 && lane >= 1 && lane <= 3 {
		legacyRow := 11 - row
		legacyCol := 4
		if lane == 1 {
			legacyCol = 2
		} else if lane == 3 {
			legacyCol = 6
		}
		return ((legacyRow - 1) * 7) + legacyCol
	}
	if zone >= 1 && zone <= 63 {
		return zone
	}
	return 32
}

func zoneToQuadrant(zone int) string {
	n := normalizeToCurrentZone(zone)
	if n == 10 {
		return "gk"
	}
	row := n / 10
	lane := n % 10
	band := "mid"
	switch {
	case row >= 2 && row <= 4:
		band = "def"
	case row >= 8 && row <= 10:
		band = "att"
	}
	side := "c"
	if lane == 1 {
		side = "l"
	} else if lane == 3 {
		side = "r"
	}
	return band + "_" + side
}

func (e *MatchEngine) savePlayerStatsTx(tx *sql.Tx, fixtureID int, state *models.MatchState, homeTeamID, awayTeamID int) error {
	stats := map[int]*playerStatRow{}
	ensure := func(playerID int, teamID int) *playerStatRow {
		if s, ok := stats[playerID]; ok {
			return s
		}
		s := e.blankPlayerStatRow(teamID)
		stats[playerID] = s
		return s
	}

	// Goals
	type goalRow struct {
		PlayerID *int   `db:"player_id"`
		TeamSide string `db:"team_side"`
		Detail   string `db:"detail"`
	}
	var goals []goalRow
	if err := e.DB.Select(&goals, "SELECT player_id, team_side, detail FROM match_event WHERE fixture_id = ? AND type = 'goal'", fixtureID); err != nil {
		return err
	}
	for _, g := range goals {
		if g.PlayerID == nil || *g.PlayerID <= 0 {
			continue
		}
		teamID := awayTeamID
		if g.TeamSide == "home" {
			teamID = homeTeamID
		}
		row := ensure(*g.PlayerID, teamID)
		row.Goals++
		if strings.Contains(strings.ToLower(g.Detail), `"origin":"penalty"`) || strings.Contains(strings.ToLower(g.Detail), `"reason":"penalty"`) {
			row.PenaltiesScored++
		}
	}

	// Cards. Friendly cards are ignored even if old events exist.
	if e.fixtureCompetitionType(fixtureID) != "friendly" {
		type cardRow struct {
			PlayerID *int   `db:"player_id"`
			TeamSide string `db:"team_side"`
			Type     string `db:"type"`
		}
		var cards []cardRow
		if err := e.DB.Select(&cards, "SELECT player_id, team_side, type FROM match_event WHERE fixture_id = ? AND type IN ('yellow_card','red_card')", fixtureID); err != nil {
			return err
		}
		for _, c := range cards {
			if c.PlayerID == nil || *c.PlayerID <= 0 {
				continue
			}
			teamID := awayTeamID
			if c.TeamSide == "home" {
				teamID = homeTeamID
			}
			row := ensure(*c.PlayerID, teamID)
			if c.Type == "yellow_card" {
				row.YellowCards++
			} else if c.Type == "red_card" {
				row.RedCards++
			}
		}
	}

	// GK saves
	type saveRow struct {
		PlayerID *int `db:"player_id"`
	}
	var saves []saveRow
	if err := e.DB.Select(&saves, "SELECT player_id FROM match_event WHERE fixture_id = ? AND type = 'gk_save' AND player_id IS NOT NULL", fixtureID); err != nil {
		return err
	}
	for _, s := range saves {
		if s.PlayerID == nil || *s.PlayerID <= 0 {
			continue
		}
		var teamID int
		if err := e.DB.Get(&teamID, "SELECT team_id FROM player WHERE id = ? LIMIT 1", *s.PlayerID); err != nil {
			continue
		}
		row := ensure(*s.PlayerID, teamID)
		row.Saves++
	}

	homeMinutes := e.computeMinutesPlayedForSideTx(tx, fixtureID, state.HomeFormationID, homeTeamID, "home")
	awayMinutes := e.computeMinutesPlayedForSideTx(tx, fixtureID, state.AwayFormationID, awayTeamID, "away")
	for pid, mins := range homeMinutes {
		row := ensure(pid, homeTeamID)
		row.MinutesPlayed = mins
	}
	for pid, mins := range awayMinutes {
		row := ensure(pid, awayTeamID)
		row.MinutesPlayed = mins
	}

	// Clean sheet
	for pid, row := range stats {
		if row.MinutesPlayed <= 0 {
			continue
		}
		var p struct {
			Position string `db:"position"`
			TeamID   int    `db:"team_id"`
		}
		if err := e.DB.Get(&p, "SELECT position, team_id FROM player WHERE id = ? LIMIT 1", pid); err != nil {
			continue
		}
		if strings.ToUpper(p.Position) != "GK" {
			continue
		}
		conceded := state.HomeScore
		if p.TeamID == homeTeamID {
			conceded = state.AwayScore
		}
		if conceded == 0 {
			row.CleanSheet = 1
		}
	}

	now := time.Now().Unix()
	for pid, s := range stats {
		if _, err := tx.Exec(`
			INSERT INTO player_stat
			    (fixture_id, player_id, team_id, goals, assists, yellow_cards, red_cards, minutes_played, penalties_scored, penalties_missed, saves, clean_sheet, created_at)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
			    goals = VALUES(goals),
			    assists = VALUES(assists),
			    yellow_cards = VALUES(yellow_cards),
			    red_cards = VALUES(red_cards),
			    minutes_played = VALUES(minutes_played),
			    penalties_scored = VALUES(penalties_scored),
			    penalties_missed = VALUES(penalties_missed),
			    saves = VALUES(saves),
			    clean_sheet = VALUES(clean_sheet)
		`, fixtureID, pid, s.TeamID, s.Goals, s.Assists, s.YellowCards, s.RedCards, s.MinutesPlayed, s.PenaltiesScored, s.PenaltiesMissed, s.Saves, s.CleanSheet, now); err != nil {
			return err
		}
	}
	return nil
}

func (e *MatchEngine) extractLineupPlayerIDsTx(tx *sql.Tx, formationID int, teamID int) []int {
	if formationID > 0 {
		q := fmt.Sprintf("SELECT DISTINCT player_id FROM formation_slot WHERE formation_id = ? AND player_id IS NOT NULL AND %s ORDER BY player_id ASC", onPitchSQL("zone"))
		rows, err := tx.Query(q, formationID)
		if err == nil {
			defer rows.Close()
			out := make([]int, 0, 16)
			for rows.Next() {
				var pid int
				if err2 := rows.Scan(&pid); err2 == nil && pid > 0 {
					out = append(out, pid)
				}
			}
			if len(out) > 0 {
				return out
			}
		}
	}

	fallback := []int{}
	if err := e.DB.Select(&fallback, "SELECT id FROM player WHERE team_id = ? ORDER BY general_skill DESC, id ASC LIMIT 11", teamID); err == nil {
		return fallback
	}
	return []int{}
}

type minuteAction struct {
	Minute int
	Kind   string
	Out    int
	In     int
	Pid    int
}

func (e *MatchEngine) computeMinutesPlayedForSideTx(tx *sql.Tx, fixtureID int, formationIDPtr *int, teamID int, side string) map[int]int {
	formationID := 0
	if formationIDPtr != nil {
		formationID = *formationIDPtr
	}
	finalLineup := e.extractLineupPlayerIDsTx(tx, formationID, teamID)

	type subRow struct {
		Minute int    `db:"minute"`
		Detail string `db:"detail"`
	}
	var subRows []subRow
	_ = e.DB.Select(&subRows, "SELECT minute, detail FROM match_event WHERE fixture_id = ? AND type = 'substitution' AND team_side = ? ORDER BY minute ASC, id ASC", fixtureID, side)

	initial := append([]int{}, finalLineup...)
	for i := len(subRows) - 1; i >= 0; i-- {
		var d map[string]interface{}
		_ = json.Unmarshal([]byte(subRows[i].Detail), &d)
		outID := intFromAny(d["out"])
		inID := intFromAny(d["in"])
		if inID > 0 {
			tmp := make([]int, 0, len(initial))
			for _, id := range initial {
				if id != inID {
					tmp = append(tmp, id)
				}
			}
			initial = tmp
		}
		if outID > 0 && !containsInt(initial, outID) {
			initial = append(initial, outID)
		}
	}

	activeSince := map[int]int{}
	minutes := map[int]int{}
	for _, pid := range initial {
		if pid <= 0 {
			continue
		}
		activeSince[pid] = 0
		minutes[pid] = 0
	}

	actions := make([]minuteAction, 0, len(subRows)+4)
	for _, sub := range subRows {
		var d map[string]interface{}
		_ = json.Unmarshal([]byte(sub.Detail), &d)
		actions = append(actions, minuteAction{
			Minute: clampInt(sub.Minute, 0, 90),
			Kind:   "sub",
			Out:    intFromAny(d["out"]),
			In:     intFromAny(d["in"]),
		})
	}

	type redRow struct {
		Minute   int  `db:"minute"`
		PlayerID *int `db:"player_id"`
	}
	var redRows []redRow
	_ = e.DB.Select(&redRows, "SELECT minute, player_id FROM match_event WHERE fixture_id = ? AND type = 'red_card' AND team_side = ? ORDER BY minute ASC, id ASC", fixtureID, side)
	for _, rr := range redRows {
		if rr.PlayerID == nil || *rr.PlayerID <= 0 {
			continue
		}
		actions = append(actions, minuteAction{
			Minute: clampInt(rr.Minute, 0, 90),
			Kind:   "red",
			Pid:    *rr.PlayerID,
		})
	}

	sort.Slice(actions, func(i, j int) bool {
		if actions[i].Minute == actions[j].Minute {
			return actions[i].Kind < actions[j].Kind
		}
		return actions[i].Minute < actions[j].Minute
	})

	for _, a := range actions {
		m := a.Minute
		if a.Kind == "sub" {
			if a.Out > 0 {
				if since, ok := activeSince[a.Out]; ok {
					minutes[a.Out] += maxInt(0, m-since)
					delete(activeSince, a.Out)
				}
			}
			if a.In > 0 {
				if _, ok := activeSince[a.In]; !ok {
					activeSince[a.In] = m
					if _, ok2 := minutes[a.In]; !ok2 {
						minutes[a.In] = 0
					}
				}
			}
			continue
		}
		if a.Pid > 0 {
			if since, ok := activeSince[a.Pid]; ok {
				minutes[a.Pid] += maxInt(0, m-since)
				delete(activeSince, a.Pid)
			}
		}
	}

	for pid, since := range activeSince {
		minutes[pid] += maxInt(0, 90-since)
	}
	return minutes
}

func intFromAny(v interface{}) int {
	switch t := v.(type) {
	case float64:
		return int(t)
	case int:
		return t
	case int64:
		return int(t)
	case string:
		i, _ := strconv.Atoi(strings.TrimSpace(t))
		return i
	default:
		return 0
	}
}

func containsInt(list []int, v int) bool {
	for _, x := range list {
		if x == v {
			return true
		}
	}
	return false
}

func maxInt(a, b int) int {
	if a > b {
		return a
	}
	return b
}

func clampInt(v, lo, hi int) int {
	if v < lo {
		return lo
	}
	if v > hi {
		return hi
	}
	return v
}

func (e *MatchEngine) applyFixtureExperienceTx(tx *sql.Tx, fixtureID int, state *models.MatchState, isFriendly bool) error {
	type statRow struct {
		PlayerID      int `db:"player_id"`
		MinutesPlayed int `db:"minutes_played"`
	}
	var statRows []statRow
	if err := e.DB.Select(&statRows, "SELECT player_id, minutes_played FROM player_stat WHERE fixture_id = ?", fixtureID); err != nil {
		return err
	}
	if len(statRows) == 0 {
		return nil
	}

	formationIDs := []int{}
	if state.HomeFormationID != nil && *state.HomeFormationID > 0 {
		formationIDs = append(formationIDs, *state.HomeFormationID)
	}
	if state.AwayFormationID != nil && *state.AwayFormationID > 0 {
		formationIDs = append(formationIDs, *state.AwayFormationID)
	}
	if len(formationIDs) == 0 {
		return nil
	}

	q := fmt.Sprintf("SELECT player_id, zone FROM formation_slot WHERE player_id IS NOT NULL AND %s AND formation_id IN (%s)", onPitchSQL("zone"), placeholders(len(formationIDs)))
	args := make([]interface{}, 0, len(formationIDs))
	for _, id := range formationIDs {
		args = append(args, id)
	}
	rows, err := tx.Query(q, args...)
	if err != nil {
		return err
	}
	defer rows.Close()

	playerZones := map[int]int{}
	for rows.Next() {
		var pid, zone int
		if err2 := rows.Scan(&pid, &zone); err2 != nil {
			continue
		}
		if pid > 0 {
			playerZones[pid] = normalizeToCurrentZone(zone)
		}
	}

	for _, row := range statRows {
		if row.PlayerID <= 0 || row.MinutesPlayed <= 0 {
			continue
		}
		zone, ok := playerZones[row.PlayerID]
		if !ok {
			continue
		}

		officialCredits := 0
		friendlyCredits := 0
		if isFriendly {
			if row.MinutesPlayed >= 45 {
				friendlyCredits = 1
			}
		} else {
			if row.MinutesPlayed >= 60 {
				officialCredits = 2
			} else {
				officialCredits = 1
			}
		}
		if officialCredits == 0 && friendlyCredits == 0 {
			continue
		}

		quadrant := zoneToQuadrant(zone)
		displayZone := toDisplayZone(zone)
		if _, err = tx.Exec(`
			INSERT INTO player_quadrant_exp (player_id, quadrant, official_credits, friendly_credits)
			VALUES (?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
			  official_credits = official_credits + VALUES(official_credits),
			  friendly_credits = friendly_credits + VALUES(friendly_credits)
		`, row.PlayerID, quadrant, officialCredits, friendlyCredits); err != nil {
			return err
		}
		if _, err = tx.Exec(`
			INSERT INTO player_cell_exp (player_id, zone, official_credits, friendly_credits)
			VALUES (?, ?, ?, ?)
			ON DUPLICATE KEY UPDATE
			  official_credits = official_credits + VALUES(official_credits),
			  friendly_credits = friendly_credits + VALUES(friendly_credits)
		`, row.PlayerID, displayZone, officialCredits, friendlyCredits); err != nil {
			return err
		}
	}
	return nil
}

func placeholders(n int) string {
	if n <= 0 {
		return ""
	}
	parts := make([]string, n)
	for i := 0; i < n; i++ {
		parts[i] = "?"
	}
	return strings.Join(parts, ",")
}

func (e *MatchEngine) serveSuspensionsTx(tx *sql.Tx, homeTeamID, awayTeamID int) error {
	if _, err := tx.Exec("UPDATE player SET suspended_matches = suspended_matches - 1 WHERE team_id IN (?, ?) AND suspended_matches > 0", homeTeamID, awayTeamID); err != nil {
		return err
	}
	return nil
}

func updateStandingTx(tx *sql.Tx, competitionID int, teamID int, goalsFor int, goalsAgainst int) error {
	won, drawn, lost, points := 0, 0, 0, 0
	if goalsFor > goalsAgainst {
		won = 1
		points = 3
	} else if goalsFor == goalsAgainst {
		drawn = 1
		points = 1
	} else {
		lost = 1
	}

	now := time.Now().Unix()
	res, err := tx.Exec(`
		UPDATE standing
		SET played = played + 1,
		    won = won + ?,
		    drawn = drawn + ?,
		    lost = lost + ?,
		    points = points + ?,
		    goals_for = goals_for + ?,
		    goals_against = goals_against + ?,
		    updated_at = ?
		WHERE competition_id = ? AND team_id = ?
	`, won, drawn, lost, points, goalsFor, goalsAgainst, now, competitionID, teamID)
	if err != nil {
		return err
	}

	rows, err := res.RowsAffected()
	if err != nil {
		return err
	}
	if rows > 0 {
		return nil
	}

	_, err = tx.Exec(`
		INSERT INTO standing
		    (competition_id, team_id, points, played, won, drawn, lost, goals_for, goals_against, created_at, updated_at)
		VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?)
	`, competitionID, teamID, points, won, drawn, lost, goalsFor, goalsAgainst, now, now)
	return err
}

// ── Ollama client ─────────────────────────────────────────────────────────────

type OllamaRequest struct {
	Model   string                 `json:"model"`
	Prompt  string                 `json:"prompt"`
	Stream  bool                   `json:"stream"`
	Options map[string]interface{} `json:"options"`
}

type OllamaResponse struct {
	Response string `json:"response"`
}

// CallOllama calls Ollama with stream=true to avoid idle timeouts on slow CPU inference.
// Each line is a JSON chunk {"response":"token","done":false}; we accumulate tokens until done=true.
// SIP-0079: lang is passed to strictOllamaPrompt for bilingual instruction.
func (e *MatchEngine) CallOllama(prompt, lang string, onToken func(string)) string {
	if !envBool("GM_LLM_ENABLED", true) || !envBool("GM_LLM_MATCH_ENRICH_ENABLED", true) {
		return ""
	}
	payload := OllamaRequest{
		Model:  "telecronista",
		Prompt: strictOllamaPrompt(prompt, lang),
		Stream: true,
		Options: map[string]interface{}{
			"temperature":    0.30,
			"top_p":          0.75,
			"repeat_penalty": 1.12,
			"num_predict":    25, // ~25 tokens @ ≤1tok/s CPU = <30s; well within 60s timeout
		},
	}
	body, err := json.Marshal(payload)
	if err != nil {
		return ""
	}

	// 60s timeout: streaming keeps data flowing so the connection stays alive.
	// With num_predict=25 the worst-case CPU generation is ~30s.
	client := http.Client{Timeout: 60 * time.Second}
	resp, err := client.Post("http://ollama:11434/api/generate", "application/json", bytes.NewBuffer(body))
	if err != nil {
		return ""
	}
	defer resp.Body.Close()

	type streamChunk struct {
		Response string `json:"response"`
		Done     bool   `json:"done"`
	}

	var accumulated string
	dec := json.NewDecoder(resp.Body)
	for {
		var chunk streamChunk
		if err := dec.Decode(&chunk); err != nil {
			break // EOF or error
		}
		accumulated += chunk.Response
		if onToken != nil && chunk.Response != "" {
			onToken(chunk.Response)
		}
		if chunk.Done {
			break
		}
	}

	return cleanOllamaText(accumulated)
}

func (e *MatchEngine) CallOllamaLong(facts, lang string, onToken func(string)) string {
	if !envBool("GM_LLM_ENABLED", true) || !envBool("GM_LLM_PREMATCH_ENABLED", true) {
		return ""
	}
	payload := OllamaRequest{
		Model:  "telecronista",
		Prompt: longOllamaPrompt(facts, lang),
		Stream: true,
		Options: map[string]interface{}{
			"temperature":    0.22,
			"top_p":          0.70,
			"repeat_penalty": 1.15,
			"num_predict":    220,
		},
	}
	body, err := json.Marshal(payload)
	if err != nil {
		return ""
	}

	client := http.Client{Timeout: 90 * time.Second}
	resp, err := client.Post("http://ollama:11434/api/generate", "application/json", bytes.NewBuffer(body))
	if err != nil {
		return ""
	}
	defer resp.Body.Close()

	type streamChunk struct {
		Response string `json:"response"`
		Done     bool   `json:"done"`
	}

	var accumulated string
	dec := json.NewDecoder(resp.Body)
	for {
		var chunk streamChunk
		if err := dec.Decode(&chunk); err != nil {
			break
		}
		accumulated += chunk.Response
		if onToken != nil && chunk.Response != "" {
			onToken(chunk.Response)
		}
		if chunk.Done {
			break
		}
	}

	return cleanOllamaLongText(accumulated)
}

// SIP-0079: lang is BCP-47 (it-IT or en-US). Default it-IT if unknown.
func strictOllamaPrompt(facts, lang string) string {
	if lang == "en-US" {
		return "Write football commentary in English.\n" +
			"Mandatory rules:\n" +
			"- one sentence only;\n" +
			"- maximum 22 words;\n" +
			"- use only supplied data;\n" +
			"- do not invent names, actions, scores or context;\n" +
			"- no preamble, no quotes.\n" +
			"Data: " + facts
	}
	return "Scrivi telecronaca calcio italiana.\n" +
		"Regole obbligatorie:\n" +
		"- una sola frase;\n" +
		"- massimo 22 parole;\n" +
		"- usa solo dati forniti;\n" +
		"- non inventare nomi, azioni, risultato o contesto;\n" +
		"- niente preamboli, niente virgolette.\n" +
		"Dati: " + facts
}

func longOllamaPrompt(facts, lang string) string {
	if lang == "en-US" {
		return "Write a coherent English football pre-match report.\n" +
			"Mandatory rules:\n" +
			"- 3 to 4 sentences, fluid text;\n" +
			"- 70-120 words;\n" +
			"- use only supplied data;\n" +
			"- mention stadium, fans, weather, pitch, standings, opponent;\n" +
			"- do not invent goals, past events or external facts;\n" +
			"- no emoji, no quotes.\n" +
			"Data: " + facts
	}
	return "Scrivi pre-partita calcio italiana coerente.\n" +
		"Regole obbligatorie:\n" +
		"- 3 o 4 frasi, testo fluido;\n" +
		"- 70-120 parole;\n" +
		"- usa solo dati forniti;\n" +
		"- cita stadio, spettatori, meteo, campo, classifica, avversario;\n" +
		"- non inventare gol, azioni già avvenute, o fatti esterni;\n" +
		"- niente emoji, niente virgolette.\n" +
		"Dati: " + facts
}

func cleanOllamaText(text string) string {
	text = strings.TrimSpace(strings.ReplaceAll(text, "\n", " "))
	text = strings.Trim(text, " \"'")
	text = strings.Join(strings.Fields(text), " ")
	if text == "" || len([]rune(text)) > 220 {
		return ""
	}
	if len(strings.Fields(text)) > 28 {
		return ""
	}
	lower := strings.ToLower(text)
	for _, banned := range []string{"non posso", "mi dispiace", "assistente", "come ai", "azionisti", "borsa", "finanza"} {
		if strings.Contains(lower, banned) {
			return ""
		}
	}
	return text
}

func (e *MatchEngine) streamTemplateText(fixtureID int, eventID int64, text string, meta commentaryMeta) {
	if meta.Source == "" {
		meta.Source = "template"
	}
	chunks := splitTemplateChunks(text, templateStreamMode())
	e.emitCommentaryEvent(fixtureID, eventID, "start", "", "", meta)
	if len(chunks) == 0 {
		e.emitCommentaryEvent(fixtureID, eventID, "done", "", text, meta)
		return
	}
	delay := templateChunkDelayMs()
	for _, c := range chunks {
		if strings.TrimSpace(c) == "" {
			continue
		}
		e.emitCommentaryEvent(fixtureID, eventID, "token", c, "", meta)
		if delay > 0 {
			time.Sleep(time.Duration(delay) * time.Millisecond)
		}
	}
	e.emitCommentaryEvent(fixtureID, eventID, "done", "", text, meta)
}

func splitTemplateChunks(text string, mode string) []string {
	trimmed := strings.TrimSpace(text)
	if trimmed == "" {
		return nil
	}
	if mode == "clause" {
		repl := strings.NewReplacer("...", ". ", "!", "! ", "?", "? ", ";", "; ", ":", ": ")
		parts := strings.Fields(repl.Replace(trimmed))
		if len(parts) == 0 {
			return []string{trimmed}
		}
		var out []string
		var buf []string
		for _, p := range parts {
			buf = append(buf, p)
			if strings.HasSuffix(p, ".") || strings.HasSuffix(p, "!") || strings.HasSuffix(p, "?") || len(buf) >= 6 {
				out = append(out, strings.Join(buf, " ")+" ")
				buf = buf[:0]
			}
		}
		if len(buf) > 0 {
			out = append(out, strings.Join(buf, " ")+" ")
		}
		return out
	}
	words := strings.Fields(trimmed)
	out := make([]string, 0, len(words))
	for _, w := range words {
		out = append(out, w+" ")
	}
	return out
}

func templateStreamMode() string {
	v := strings.ToLower(strings.TrimSpace(os.Getenv("GM_TEMPLATE_STREAM_MODE")))
	if v == "clause" {
		return "clause"
	}
	return "token"
}

func templateChunkDelayMs() int {
	raw := strings.TrimSpace(os.Getenv("GM_TEMPLATE_STREAM_CHUNK_MS"))
	if raw == "" {
		return 120
	}
	n := 120
	fmt.Sscanf(raw, "%d", &n)
	if n < 0 {
		return 0
	}
	if n > 2000 {
		return 2000
	}
	return n
}

func envBool(key string, defaultVal bool) bool {
	v := strings.ToLower(strings.TrimSpace(os.Getenv(key)))
	if v == "" {
		return defaultVal
	}
	switch v {
	case "1", "true", "yes", "on":
		return true
	case "0", "false", "no", "off":
		return false
	default:
		return defaultVal
	}
}

func (e *MatchEngine) pickTemplate(eventType, subtype, gameState string, minute, fixtureID int, tokens map[string]string) (string, bool) {
	rows := []commentaryTemplateRow{}
	err := e.DB.Select(&rows, `
		SELECT id, text, weight
		FROM commentary_template
		WHERE enabled = 1
		  AND event_type = ?
		  AND (subtype IS NULL OR subtype = '' OR ? = '' OR subtype = ?)
		  AND (game_state IS NULL OR game_state = '' OR ? = '' OR game_state = ?)
		  AND (min_minute IS NULL OR min_minute <= ?)
		  AND (max_minute IS NULL OR max_minute >= ?)
		ORDER BY
		  CASE WHEN game_state = ? THEN 0 ELSE 1 END,
		  CASE WHEN subtype = ? THEN 0 ELSE 1 END,
		  weight DESC,
		  id DESC
	`, eventType, subtype, subtype, gameState, gameState, minute, minute, gameState, subtype)
	if err != nil || len(rows) == 0 {
		return "", false
	}

	home := e.teamName(fixtureID, "home")
	away := e.teamName(fixtureID, "away")
	defaultTokens := map[string]string{
		"home_team":       home,
		"away_team":       away,
		"score_home":      "0",
		"score_away":      "0",
		"minute":          fmt.Sprintf("%d", minute),
		"player_attacker": "un giocatore",
		"player_defender": "il difensore",
		"player_gk":       "il portiere",
		"player_assist":   "un compagno",
		"player_in":       "il nuovo entrato",
		"player_out":      "il giocatore uscente",
		"spectators":      "numerosi",
		"game_state":      gameState,
	}
	for k, v := range tokens {
		defaultTokens[k] = v
	}

	recent := e.recentDescriptions(fixtureID, eventType, 5)
	recentSet := map[string]bool{}
	for _, t := range recent {
		recentSet[strings.TrimSpace(t)] = true
	}

	candidates := make([]commentaryTemplateRow, 0, len(rows))
	for _, row := range rows {
		rendered := renderTemplateText(row.Text, defaultTokens)
		if strings.TrimSpace(rendered) == "" {
			continue
		}
		if recentSet[strings.TrimSpace(rendered)] {
			continue
		}
		candidates = append(candidates, row)
	}
	if len(candidates) == 0 {
		candidates = rows
	}

	total := 0
	for _, c := range candidates {
		w := c.Weight
		if w <= 0 {
			w = 1
		}
		total += w
	}
	if total <= 0 {
		return "", false
	}
	roll := rand.Intn(total) + 1
	sum := 0
	chosen := candidates[len(candidates)-1]
	for _, c := range candidates {
		w := c.Weight
		if w <= 0 {
			w = 1
		}
		sum += w
		if roll <= sum {
			chosen = c
			break
		}
	}

	out := strings.TrimSpace(renderTemplateText(chosen.Text, defaultTokens))
	if out == "" {
		return "", false
	}
	return out, true
}

func (e *MatchEngine) recentDescriptions(fixtureID int, eventType string, limit int) []string {
	type row struct {
		Detail string `db:"detail"`
	}
	rows := []row{}
	err := e.DB.Select(&rows, `
		SELECT detail
		FROM match_event
		WHERE fixture_id = ? AND type = ?
		ORDER BY id DESC
		LIMIT ?
	`, fixtureID, eventType, limit)
	if err != nil || len(rows) == 0 {
		return nil
	}
	out := make([]string, 0, len(rows))
	for _, row := range rows {
		d := map[string]interface{}{}
		if err := json.Unmarshal([]byte(row.Detail), &d); err != nil {
			continue
		}
		if desc, ok := d["description"].(string); ok && strings.TrimSpace(desc) != "" {
			out = append(out, desc)
		}
	}
	return out
}

func renderTemplateText(template string, tokens map[string]string) string {
	out := template
	for k, v := range tokens {
		out = strings.ReplaceAll(out, "{"+k+"}", v)
	}
	return out
}

func cleanOllamaLongText(text string) string {
	text = strings.TrimSpace(strings.ReplaceAll(text, "\n", " "))
	text = strings.Trim(text, " \"'")
	text = strings.Join(strings.Fields(text), " ")
	if text == "" || len([]rune(text)) > 850 {
		return ""
	}
	words := len(strings.Fields(text))
	if words < 35 || words > 150 {
		return ""
	}
	lower := strings.ToLower(text)
	for _, banned := range []string{"non posso", "mi dispiace", "assistente", "come ai", "azionisti", "borsa", "finanza"} {
		if strings.Contains(lower, banned) {
			return ""
		}
	}
	return text
}

func (e *MatchEngine) buildHalfTimeFallback(fixtureID int, homeScore, awayScore int) string {
	home := e.teamName(fixtureID, "home")
	away := e.teamName(fixtureID, "away")
	if tpl, ok := e.pickTemplate("half_time", "", deriveGameState(homeScore, awayScore, "home", 45), 45, fixtureID, map[string]string{
		"home_team":  home,
		"away_team":  away,
		"score_home": fmt.Sprintf("%d", homeScore),
		"score_away": fmt.Sprintf("%d", awayScore),
		"minute":     "45",
	}); ok {
		return tpl
	}

	switch {
	case homeScore == awayScore:
		return fmt.Sprintf("Intervallo: %s %d-%d %s. Primo tempo equilibrato, partita apertissima.", home, homeScore, awayScore, away)
	case homeScore > awayScore:
		return fmt.Sprintf("Intervallo: %s avanti %d-%d su %s. Gara in controllo, ospiti chiamati a reagire.", home, homeScore, awayScore, away)
	default:
		return fmt.Sprintf("Intervallo: %s avanti %d-%d su %s. Casa sotto, serve svolta nel secondo tempo.", away, awayScore, homeScore, home)
	}
}

func deriveGameState(homeScore, awayScore int, side string, minute int) string {
	diff := homeScore - awayScore
	if side == "away" {
		diff = awayScore - homeScore
	}

	if minute >= 80 {
		if diff < 0 {
			return "late_push"
		}
		if diff > 0 {
			return "time_wasting"
		}
		return "late_push"
	}

	if diff > 0 {
		return "winning"
	}
	if diff < 0 {
		if minute >= 60 {
			return "high_press"
		}
		return "losing"
	}
	if minute >= 60 {
		return "balanced"
	}
	return "drawing"
}
