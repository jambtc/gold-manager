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
 * @property int $contract_length
 * @property int $negotiations
 * @property int $raise_used
 * @property int $generated_at
 * @property int $expires_at
 *
 * @property Team $team
 */
class StaffMarket extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%staff_market}}';
    }

    public function rules(): array
    {
        return [
            [['team_id', 'name', 'role', 'generated_at', 'expires_at'], 'required'],
            [['team_id', 'ability', 'experience', 'motivation', 'salary', 'contract_length', 'negotiations', 'raise_used', 'generated_at', 'expires_at'], 'integer'],
            [['name'], 'string', 'max' => 80],
            [['role'], 'string', 'max' => 30],
            [['role'], 'in', 'range' => [
                Staff::ROLE_HEAD_COACH,
                Staff::ROLE_ASSISTANT_COACH,
                Staff::ROLE_GOALKEEPING_COACH,
                Staff::ROLE_FITNESS_COACH,
                Staff::ROLE_SCOUT,
            ]],
        ];
    }

    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    public function getEfficiencyPreview(): float
    {
        return round((0.9 * $this->ability * $this->motivation / 100) + ($this->experience / 8), 1);
    }
}

