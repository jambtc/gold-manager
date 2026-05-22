<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int         $id
 * @property int         $fixture_id
 * @property int         $minute
 * @property string      $type
 * @property string      $team_side
 * @property int|null    $player_id
 * @property string|null $detail
 *
 * @property Fixture     $fixture
 * @property Player|null $player
 */
class MatchEvent extends ActiveRecord
{
    // Event types
    const TYPE_KICKOFF         = 'kickoff';
    const TYPE_MIDFIELD_WIN    = 'midfield_win';
    const TYPE_ATTACK_ATTEMPT  = 'attack_attempt';
    const TYPE_SHOT_ON_GOAL    = 'shot_on_goal';
    const TYPE_NEAR_MISS       = 'near_miss';
    const TYPE_GK_SAVE         = 'gk_save';
    const TYPE_GOAL            = 'goal';
    const TYPE_HALF_TIME       = 'half_time';
    const TYPE_SECOND_HALF     = 'second_half_start';
    const TYPE_FULL_TIME       = 'full_time';
    const TYPE_SUBSTITUTION    = 'substitution';
    const TYPE_TACTIC_CHANGE   = 'tactic_change';

    public static function tableName(): string
    {
        return '{{%match_event}}';
    }

    public function rules(): array
    {
        return [
            [['fixture_id', 'minute', 'type', 'team_side'], 'required'],
            [['fixture_id', 'minute', 'player_id'], 'integer'],
            [['type'], 'string', 'max' => 30],
            [['team_side'], 'string', 'max' => 4],
            [['detail'], 'string'],
        ];
    }

    /**
     * Returns the decoded detail JSON as an array.
     */
    public function getDetailArray(): array
    {
        return $this->detail ? (array) json_decode($this->detail, true) : [];
    }

    public function getFixture()
    {
        return $this->hasOne(Fixture::class, ['id' => 'fixture_id']);
    }

    public function getPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'player_id']);
    }
}
