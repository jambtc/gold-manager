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

	weather, field := randomWeatherField()
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

func randomWeatherField() (string, string) {
	options := []struct {
		weather string
		field   string
	}{
		{"soleggiato", "terreno asciutto e rapido"},
		{"nuvoloso", "manto regolare e compatto"},
		{"ventoso", "vento a raffiche, palloni lunghi più insidiosi"},
		{"piovoso", "terreno pesante e scivoloso"},
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
			_ = e.DB.Get(&homeFormID, "SELECT f.id FROM formation f JOIN fixture fix ON fix.home_team_id = f.team_id WHERE fix.id = ? AND f.is_active = 1 LIMIT 1", fixtureID)
			_ = e.DB.Get(&awayFormID, "SELECT f.id FROM formation f JOIN fixture fix ON fix.away_team_id = f.team_id WHERE fix.id = ? AND f.is_active = 1 LIMIT 1", fixtureID)
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
		state.Phase = "FIRST_HALF"
		if _, err = e.DB.Exec("UPDATE match_state SET phase = 'FIRST_HALF', current_minute = 0 WHERE fixture_id = ?", fixtureID); err != nil {
			return err
		}

		fallback := fmt.Sprintf(
			"Calcio d'inizio: %s muove il primo pallone contro %s.",
			e.teamName(fixtureID, "home"),
			e.teamName(fixtureID, "away"),
		)
		detail := fmt.Sprintf(`{"description":%q}`, fallback)
		res, err2 := e.DB.Exec(
			"INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, 0, 'kickoff', 'home', ?)",
			fixtureID,
			detail,
		)
		if err2 == nil {
			eventID, _ := res.LastInsertId()
			e.broadcastEvent(fixtureID, eventID, 0, "kickoff", "home", fallback, state.HomeScore, state.AwayScore, "")
		}
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
		state.HalfTimeTicks++
		e.DB.Exec("UPDATE match_state SET half_time_ticks = ? WHERE fixture_id = ?", state.HalfTimeTicks, fixtureID)
		if state.HalfTimeTicks < 15 {
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
		fallback := "Fischio d'inizio del secondo tempo! Si riparte con grande intensità!"
		res, err2 := tx.Exec("INSERT INTO match_event (fixture_id, minute, type, team_side, detail) VALUES (?, 45, 'second_half_start', 'home', ?)",
			fixtureID, fmt.Sprintf(`{"description":%q}`, fallback))
		if err2 != nil {
			tx.Rollback()
			return err2
		}
		if err = tx.Commit(); err != nil {
			return err
		}
		eventID, _ := res.LastInsertId()
		e.broadcastEvent(fixtureID, eventID, 45, "second_half_start", "home", fallback, state.HomeScore, state.AwayScore, "")
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

	// SIP-0037: team trait bonus (average of active XI traits)
	homeLosing := state.HomeScore < state.AwayScore
	awayLosing := state.AwayScore < state.HomeScore
	homeTraits := e.teamTraitsProfile(fixtureID, "home", homeLosing, true)
	awayTraits := e.teamTraitsProfile(fixtureID, "away", awayLosing, false)
	homeBonus := homeTraits.Bonus
	awayBonus := awayTraits.Bonus

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
	// Home goal ~2.5%
	case chance < 2.5*homeBonus:
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

		// Away goal ~2.5%
	case chance < 5.0*awayBonus:
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

		// Home near_miss or gk_save ~4.5%
	case chance < 9.5:
		attackSide = "home"
		shooter := e.randomPlayerName(fixtureID, "home", "FW")
		gk := e.randomPlayerName(fixtureID, "away", "GK")
		suspense := pickSuspense(homeSuspense)
		suspenseText = suspense
		if rand.Intn(2) == 0 {
			eventType, teamSide = "near_miss", "home"
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
	case chance < 14.0:
		attackSide = "away"
		shooter := e.randomPlayerName(fixtureID, "away", "FW")
		gk := e.randomPlayerName(fixtureID, "home", "GK")
		suspense := pickSuspense(awaySuspense)
		suspenseText = suspense
		if rand.Intn(2) == 0 {
			eventType, teamSide = "near_miss", "away"
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
	case chance < 29.0:
		if chance < 21.5 {
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
	case chance < 39.0:
		if chance < 34.0 {
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

	// Cards check (~1.5% per team per tick)
	e.resolveGoCards(fixtureID, state.CurrentMinute, homeTraits, awayTraits)

	// Injury check (~0.05% per team per tick)
	e.resolveGoInjury(fixtureID, state.CurrentMinute, homeTraits, awayTraits)

	// Pending substitutions from match_command table
	e.processMatchCommands(fixtureID, state.CurrentMinute)

	return nil
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

func (e *MatchEngine) resolveGoCards(fixtureID, minute int, homeTraits, awayTraits teamTraitProfile) {
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

func (e *MatchEngine) resolveGoInjury(fixtureID, minute int, homeTraits, awayTraits teamTraitProfile) {
	teams := map[string]teamTraitProfile{"home": homeTraits, "away": awayTraits}
	for side, traits := range teams {
		baseChance := 0.0005 * traits.InjuryScalar
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

		if c.CommandType == "substitution" {
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
		}
		_, _ = e.DB.Exec("UPDATE match_command SET processed = 1 WHERE id = ?", c.ID)
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

// activeLineupPlayers returns current on-pitch players from match_state formations (zones <= 63).
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
		  AND fs.zone <= 63
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
	text := e.CallOllama(prompt, func(token string) {
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
	text := e.CallOllamaLong(payload.Prompt, func(token string) {
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

type teamTraitProfile struct {
	Bonus            float64
	DisciplineScalar float64
	InjuryScalar     float64
	HasCarismatico   bool
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
		WHERE f.team_id = ? AND f.is_active = 1 AND fs.zone <= 63 AND fs.player_id IS NOT NULL
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
	return profile
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
	if err = e.applyStandings(tx, fixtureID, state.HomeScore, state.AwayScore); err != nil {
		tx.Rollback()
		return err
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
func (e *MatchEngine) CallOllama(prompt string, onToken func(string)) string {
	if !envBool("GM_LLM_ENABLED", true) || !envBool("GM_LLM_MATCH_ENRICH_ENABLED", true) {
		return ""
	}
	payload := OllamaRequest{
		Model:  "telecronista",
		Prompt: strictOllamaPrompt(prompt),
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

func (e *MatchEngine) CallOllamaLong(facts string, onToken func(string)) string {
	if !envBool("GM_LLM_ENABLED", true) || !envBool("GM_LLM_PREMATCH_ENABLED", true) {
		return ""
	}
	payload := OllamaRequest{
		Model:  "telecronista",
		Prompt: longOllamaPrompt(facts),
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

func strictOllamaPrompt(facts string) string {
	return "Scrivi telecronaca calcio italiana.\n" +
		"Regole obbligatorie:\n" +
		"- una sola frase;\n" +
		"- massimo 22 parole;\n" +
		"- usa solo dati forniti;\n" +
		"- non inventare nomi, azioni, risultato o contesto;\n" +
		"- niente preamboli, niente virgolette.\n" +
		"Dati: " + facts
}

func longOllamaPrompt(facts string) string {
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
	text = strings.Trim(text, " \"'“”")
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
	text = strings.Trim(text, " \"'“”")
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
