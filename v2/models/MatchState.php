<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Live state of a running fixture.
 * One row per fixture, created when the match starts.
 *
 * @property int         $id
 * @property int         $fixture_id
 * @property int         $current_minute
 * @property string      $phase           not_started|first_half|half_time|second_half|finished
 * @property int         $home_score
 * @property int         $away_score
 * @property int|null    $home_formation_id
 * @property int|null    $away_formation_id
 * @property string|null $pending_home_actions  JSON array of queued manager actions
 * @property string|null $pending_away_actions
 * @property int         $home_subs_used
 * @property int         $away_subs_used
 * @property int         $half_time_ticks
 * @property int         $home_ejected    1 when home team has a player sent off
 * @property int         $away_ejected    1 when away team has a player sent off
 * @property string|null $home_yellows    JSON {playerId: yellowsThisMatch}
 * @property string|null $away_yellows    JSON {playerId: yellowsThisMatch}
 * @property int         $home_effort_level  0|25|50|75|100, snapshotted from formation at kickoff
 * @property int         $away_effort_level
 *
 * @property Fixture     $fixture
 * @property Formation|null $homeFormation
 * @property Formation|null $awayFormation
 */
class MatchState extends ActiveRecord
{
    const PHASE_NOT_STARTED  = 'not_started';
    const PHASE_FIRST_HALF   = 'first_half';
    const PHASE_HALF_TIME    = 'half_time';
    const PHASE_SECOND_HALF  = 'second_half';
    const PHASE_FINISHED     = 'finished';

    public static function tableName(): string
    {
        return '{{%match_state}}';
    }

    public function rules(): array
    {
        return [
            [['fixture_id'], 'required'],
            [['fixture_id', 'current_minute', 'home_score', 'away_score',
              'home_formation_id', 'away_formation_id',
              'home_subs_used', 'away_subs_used', 'half_time_ticks',
              'home_ejected', 'away_ejected',
              'home_effort_level', 'away_effort_level'], 'integer'],
            [['phase'], 'string', 'max' => 20],
            [['pending_home_actions', 'pending_away_actions',
              'home_yellows', 'away_yellows'], 'string'],
        ];
    }

    public function getFixture()
    {
        return $this->hasOne(Fixture::class, ['id' => 'fixture_id']);
    }

    public function getHomeFormation()
    {
        return $this->hasOne(Formation::class, ['id' => 'home_formation_id']);
    }

    public function getAwayFormation()
    {
        return $this->hasOne(Formation::class, ['id' => 'away_formation_id']);
    }
}
