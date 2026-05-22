<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $team_id
 * @property string $name
 * @property int    $capacity
 * @property int    $level
 * @property int    $upgrade_cost   € to reach next level
 * @property int    $ticket_price           € per spectator, competitive matches
 * @property int    $friendly_ticket_price  € per spectator, friendlies
 * @property int    $season_revenue running total this season
 *
 * @property Team $team
 */
class Stadium extends ActiveRecord
{
    /** Capacity added per upgrade level */
    const CAPACITY_PER_LEVEL = 5000;

    /** Factor by which upgrade cost grows each level */
    const UPGRADE_COST_FACTOR = 1.8;

    public static function tableName(): string { return '{{%stadium}}'; }

    public function behaviors(): array { return [TimestampBehavior::class]; }

    public function rules(): array
    {
        return [
            [['team_id', 'name'], 'required'],
            [['team_id', 'capacity', 'level', 'upgrade_cost', 'ticket_price', 'friendly_ticket_price', 'season_revenue'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'             => 'ID',
            'team_id'        => 'Team',
            'name'           => 'Stadium Name',
            'capacity'       => 'Capacity',
            'level'          => 'Level',
            'upgrade_cost'   => 'Upgrade Cost (€)',
            'ticket_price'   => 'Ticket Price (€)',
            'season_revenue' => 'Season Revenue (€)',
        ];
    }

    /**
     * Calculates match-day revenue based on:
     * - current capacity
     * - ticket price
     * - attendance factor (0.0-1.0, driven by team form / competition importance)
     */
    public function calculateMatchRevenue(float $attendanceFactor = 0.7): int
    {
        $attendanceFactor = max(0.0, min(1.0, $attendanceFactor));
        return (int)($this->capacity * $attendanceFactor * $this->ticket_price);
    }

    /**
     * Attempt to upgrade the stadium. Deducts upgrade_cost from team budget.
     * Returns true on success, false if budget is insufficient.
     */
    public function upgrade(): bool
    {
        $team = $this->team;
        if (!$team || $team->budget < $this->upgrade_cost) {
            return false;
        }

        $team->budget -= $this->upgrade_cost;
        $team->save();

        $this->level        += 1;
        $this->capacity     += self::CAPACITY_PER_LEVEL;
        $this->upgrade_cost  = (int)($this->upgrade_cost * self::UPGRADE_COST_FACTOR);
        $this->save();

        return true;
    }

    /**
     * Credit match-day revenue to the team and accumulate season total.
     */
    public function creditMatchRevenue(float $attendanceFactor = 0.7): int
    {
        $revenue = $this->calculateMatchRevenue($attendanceFactor);

        $team = $this->team;
        if ($team) {
            $team->budget         += $revenue;
            $this->season_revenue += $revenue;
            $team->save();
            $this->save();
        }

        return $revenue;
    }

    public function getTeam() { return $this->hasOne(Team::class, ['id' => 'team_id']); }
}
