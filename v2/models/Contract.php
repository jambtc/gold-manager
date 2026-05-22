<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $player_id
 * @property int    $team_id
 * @property int    $salary         € per season
 * @property int|null $release_clause
 * @property int    $season_start
 * @property int    $season_end
 * @property string $status         active|expired|terminated|transferred
 *
 * @property Player $player
 * @property Team   $team
 */
class Contract extends ActiveRecord
{
    const STATUS_ACTIVE      = 'active';
    const STATUS_EXPIRED     = 'expired';
    const STATUS_TERMINATED  = 'terminated';
    const STATUS_TRANSFERRED = 'transferred';

    public static function tableName(): string { return '{{%contract}}'; }

    public function behaviors(): array { return [TimestampBehavior::class]; }

    public function rules(): array
    {
        return [
            [['player_id', 'team_id', 'season_start', 'season_end'], 'required'],
            [['player_id', 'team_id', 'salary', 'release_clause', 'season_start', 'season_end'], 'integer'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_ACTIVE, self::STATUS_EXPIRED,
                self::STATUS_TERMINATED, self::STATUS_TRANSFERRED,
            ]],
            [['player_id'], 'exist', 'targetClass' => Player::class, 'targetAttribute' => ['player_id' => 'id']],
            [['team_id'],   'exist', 'targetClass' => Team::class,   'targetAttribute' => ['team_id'   => 'id']],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'player_id'      => 'Player',
            'team_id'        => 'Team',
            'salary'         => 'Salary (€/season)',
            'release_clause' => 'Release Clause (€)',
            'season_start'   => 'Season Start',
            'season_end'     => 'Season End',
            'status'         => 'Status',
        ];
    }

    /** Is this contract currently binding? */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /** Seasons remaining on this contract. */
    public function seasonsRemaining(int $currentSeason): int
    {
        return max(0, $this->season_end - $currentSeason);
    }

    public function getPlayer() { return $this->hasOne(Player::class, ['id' => 'player_id']); }
    public function getTeam()   { return $this->hasOne(Team::class,   ['id' => 'team_id']);   }
}
