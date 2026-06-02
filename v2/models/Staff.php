<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int      $id
 * @property int      $team_id
 * @property string   $name
 * @property string   $nationality
 * @property string   $role            head_coach|assistant_coach|goalkeeping_coach|fitness_coach|scout
 * @property int      $ability         1-100
 * @property int      $experience
 * @property int      $age
 * @property int      $motivation      1-100
 * @property int      $contract_ends
 * @property string|null $philosophy   offensivo|difensivo|bilanciato|contropiede|possesso
 * @property int      $salary
 * @property int      $termination_fee
 * @property int      $efficiency
 * @property string   $specialisation
 *
 * @property Team $team
 */
class Staff extends ActiveRecord
{
    const ROLE_HEAD_COACH        = 'head_coach';
    const ROLE_ASSISTANT_COACH   = 'assistant_coach';
    const ROLE_GOALKEEPING_COACH = 'goalkeeping_coach';
    const ROLE_FITNESS_COACH     = 'fitness_coach';
    const ROLE_DOCTOR            = 'doctor';
    const ROLE_SCOUT             = 'scout';

    public static function tableName(): string { return '{{%staff}}'; }

    public function behaviors(): array { return [TimestampBehavior::class]; }

    public function rules(): array
    {
        return [
            [['team_id', 'name', 'role'], 'required'],
            [['team_id', 'ability', 'experience', 'age', 'motivation', 'contract_ends', 'salary', 'termination_fee', 'efficiency'], 'integer'],
            [['name', 'philosophy'], 'string'],
            [['nationality'], 'string', 'max' => 3],
            [['role'], 'string', 'max' => 30],
            [['specialisation'], 'string', 'max' => 20],
            [['role'], 'in', 'range' => [
                self::ROLE_HEAD_COACH, self::ROLE_ASSISTANT_COACH,
                self::ROLE_GOALKEEPING_COACH, self::ROLE_FITNESS_COACH,
                self::ROLE_DOCTOR, self::ROLE_SCOUT,
            ]],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'team_id'     => 'Team',
            'name'        => 'Name',
            'nationality' => 'Nationality',
            'role'        => 'Role',
            'ability'     => 'Ability',
            'experience'  => 'Experience',
            'age'         => 'Age',
            'motivation'  => 'Motivation',
            'contract_ends' => 'Contract Ends (Season)',
            'philosophy'  => 'Philosophy',
            'salary'      => 'Salary (€/season)',
            'efficiency'  => 'Efficiency',
            'specialisation' => 'Specialisation',
            'role'        => 'Role',
        ];
    }

    /**
     * Computes the coach efficiency bonus (ported from legacy 1vs1.php formula).
     * Returns a float factor applied to department totals.
     */
    public function getEfficiencyBonus(): float
    {
        return (0.9 * $this->ability * $this->motivation) / 100 + $this->experience / 8;
    }

    public function recomputeEfficiency(): int
    {
        $value = round((0.9 * $this->ability * $this->motivation / 100) + ($this->experience / 8), 1);
        $this->efficiency = (int) round($value);
        return $this->efficiency;
    }

    /**
     * Returns the impact this staff member has on each department (for MatchEngine).
     * Keys: po, df, cn, at  Values: bonus points
     */
    public function getDepartmentBonus(): array
    {
        $eff = $this->getEfficiencyBonus();

        return match ($this->role) {
            self::ROLE_HEAD_COACH        => ['po' => 0,             'df' => $eff * 0.3, 'cn' => $eff * 0.3, 'at' => $eff * 0.3],
            self::ROLE_ASSISTANT_COACH   => ['po' => 0,             'df' => $eff / 30,  'cn' => $eff / 30,  'at' => $eff / 30],
            self::ROLE_GOALKEEPING_COACH => ['po' => $eff * 0.035,  'df' => 0,          'cn' => 0,          'at' => 0],
            self::ROLE_FITNESS_COACH     => ['po' => $eff * 0.01,   'df' => $eff * 0.01,'cn' => $eff * 0.01,'at' => $eff * 0.01],
            default                      => ['po' => 0,             'df' => 0,          'cn' => 0,          'at' => 0],
        };
    }

    /**
     * SIP-0091: Pro-rata termination fee based on remaining contract.
     * fee_due = base_fee × (seasons_left / original_length)
     */
    public function currentTerminationFee(int $currentSeason): int
    {
        if ($this->termination_fee <= 0) return 0;
        $contractLength = max(1, $this->contract_ends - $currentSeason + 1);
        // original length: inferred from contract_ends minus seasons served
        // simplified: use remaining seasons as fraction (conservative, manager-friendly)
        $seasonsLeft = max(0, $this->contract_ends - $currentSeason);
        return (int) max(0, round($this->termination_fee * ($seasonsLeft / max(1, $contractLength))));
    }

    /**
     * SIP-0091: Salary discount multiplier for multi-season contracts.
     * 1 season = 1.00, 2 = 0.90, 3 = 0.82
     */
    public static function durationSalaryMultiplier(int $seasons): float
    {
        return match ($seasons) {
            2  => 0.90,
            3  => 0.82,
            default => 1.00,
        };
    }

    /**
     * SIP-0091: Calculate termination fee at signing.
     * factor = random in [0.25, 0.50]
     */
    public static function calcTerminationFee(int $salary, int $contractLength, float $factor): int
    {
        return (int) max(5000, round($salary * $contractLength * $factor, -3));
    }

    public function getTeam() { return $this->hasOne(Team::class, ['id' => 'team_id']); }
}
