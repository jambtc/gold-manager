package models

type MatchCommand struct {
	ID               int     `db:"id"`
	FixtureID        int     `db:"fixture_id"`
	TeamID           int     `db:"team_id"`
	CommandType      string  `db:"command_type"`
	Payload          string  `db:"payload"`
	MinuteSubmitted  int     `db:"minute_submitted"`
	ExecutedAtMinute *int    `db:"executed_at_minute"`
	CreatedAt        int     `db:"created_at"`
}
