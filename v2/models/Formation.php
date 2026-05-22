<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property bool $is_active
 * @property string|null $tactic
 * @property string|null $marking
 * @property int|null $offside_trap
 * @property string|null $trained_tactic
 * @property int $effort
 * @property int|null $captain_player_id
 * @property int|null $penalty_player_id
 * @property int|null $freekick_player_id
 * @property int|null $corner_player_id
 *
 * @property Team $team
 * @property FormationSlot[] $slots
 * @property Player|null $captainPlayer
 * @property Player|null $penaltyPlayer
 * @property Player|null $freekickPlayer
 * @property Player|null $cornerPlayer
 */
class Formation extends ActiveRecord
{
    public static function tableName(): string { return '{{%formation}}'; }

    public function behaviors(): array { return [TimestampBehavior::class]; }

    public function rules(): array
    {
        return [
            [['team_id'], 'required'],
            [['team_id', 'effort', 'captain_player_id', 'penalty_player_id', 'freekick_player_id', 'corner_player_id', 'offside_trap'], 'integer'],
            [['is_active'], 'boolean'],
            [['name', 'tactic', 'marking', 'trained_tactic'], 'string'],
        ];
    }

    public function getTeam() { return $this->hasOne(Team::class, ['id' => 'team_id']); }
    public function getSlots() { return $this->hasMany(FormationSlot::class, ['formation_id' => 'id']); }
    public function getCaptainPlayer() { return $this->hasOne(Player::class, ['id' => 'captain_player_id']); }
    public function getPenaltyPlayer() { return $this->hasOne(Player::class, ['id' => 'penalty_player_id']); }
    public function getFreekickPlayer() { return $this->hasOne(Player::class, ['id' => 'freekick_player_id']); }
    public function getCornerPlayer()   { return $this->hasOne(Player::class, ['id' => 'corner_player_id']); }
}
