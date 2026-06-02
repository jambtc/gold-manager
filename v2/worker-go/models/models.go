package models

import (
	"time"
)

type Fixture struct {
	ID           int       `db:"id"`
	CompetitionID int       `db:"competition_id"`
	HomeTeamID   int       `db:"home_team_id"`
	AwayTeamID   int       `db:"away_team_id"`
	MatchDate    time.Time `db:"match_date"`
	HomeScore    int       `db:"home_score"`
	AwayScore    int       `db:"away_score"`
	Status       string    `db:"status"`
	Attendance   int       `db:"attendance"`
	Revenue      float64   `db:"revenue"`
}

type MatchState struct {
	ID                 int     `db:"id"`
	FixtureID          int     `db:"fixture_id"`
	CurrentMinute      int     `db:"current_minute"`
	HomeScore          int     `db:"home_score"`
	AwayScore          int     `db:"away_score"`
	Phase              string  `db:"phase"`
	HomeFormationID    *int    `db:"home_formation_id"`
	AwayFormationID    *int    `db:"away_formation_id"`
	PendingHomeActions *string `db:"pending_home_actions"`
	PendingAwayActions *string `db:"pending_away_actions"`
	HomeSubsUsed       int     `db:"home_subs_used"`
	AwaySubsUsed       int     `db:"away_subs_used"`
	HomeEjected        int     `db:"home_ejected"`
	AwayEjected        int     `db:"away_ejected"`
	HomeYellows        *string `db:"home_yellows"`
	AwayYellows        *string `db:"away_yellows"`
	HalfTimeTicks      int     `db:"half_time_ticks"`
	HomeEffortLevel    int     `db:"home_effort_level"`
	AwayEffortLevel    int     `db:"away_effort_level"`
}

type MatchEvent struct {
	ID          int    `db:"id"`
	FixtureID   int    `db:"fixture_id"`
	Minute      int    `db:"minute"`
	Type        string `db:"type"`
	Description string `db:"description"`
	PlayerID    *int   `db:"player_id"`
	TeamID      *int   `db:"team_id"`
}

type Player struct {
	ID           int     `db:"id"`
	TeamID       int     `db:"team_id"`
	Name         string  `db:"name"`
	Position     string  `db:"position"`
	GeneralSkill int     `db:"general_skill"`
	Form         int     `db:"form"`
	Freshness    int     `db:"freshness"`
	Condition    int     `db:"condition"`
}
