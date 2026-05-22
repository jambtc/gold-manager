<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $role
 * @property int $ability
 * @property int $experience
 * @property int $motivation
 * @property int $salary
 * @property int $efficiency
 * @property int|null $joined_season
 * @property int $left_season
 * @property string $left_reason
 * @property int $created_at
 *
 * @property Team $team
 */
class StaffHistory extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%staff_history}}';
    }

    public function rules(): array
    {
        return [
            [['team_id', 'name', 'role', 'left_season', 'left_reason', 'created_at'], 'required'],
            [['team_id', 'ability', 'experience', 'motivation', 'salary', 'efficiency', 'joined_season', 'left_season', 'created_at'], 'integer'],
            [['name'], 'string', 'max' => 80],
            [['role'], 'string', 'max' => 30],
            [['left_reason'], 'string', 'max' => 20],
        ];
    }

    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }
}

