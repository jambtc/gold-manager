<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "player".
 *
 * @property int $id
 * @property int|null $team_id
 * @property int|null $number
 * @property string $name
 * @property int $age
 * @property string $position
 * @property string $nationality
 * @property int    $height_cm
 * @property int    $weight_kg
 * @property string $foot
 * @property int $skill_po
 * @property int $skill_df
 * @property int $skill_cn
 * @property int $skill_pa
 * @property int $skill_rg
 * @property int $skill_cr
 * @property int $skill_tc
 * @property int $skill_tr
 * @property int $skill_cp
 * @property int $experience
 * @property int $general_skill
 * @property int $form
 * @property int $freshness
 * @property int $condition
 * @property string|null $character
 * @property int $injury_weeks       0 = healthy; >0 = weeks remaining
 * @property string|null $injury_type  null | 'lieve' | 'medio' | 'grave'
 * @property int $yellow_cards       current season
 * @property int $red_cards          current season
 * @property int $suspended_matches  matches still to serve
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Team $team
 */
class Player extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%player}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'position'], 'required'],
            [['team_id', 'number', 'age', 'skill_po', 'skill_df', 'skill_cn', 'skill_pa', 'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr', 'skill_cp', 'experience', 'general_skill', 'form', 'freshness', 'condition'], 'integer'],
            [['name', 'character'], 'string', 'max' => 255],
            [['position'], 'string', 'max' => 5],
            [['nationality'], 'string', 'max' => 3],
            [['height_cm', 'weight_kg'], 'integer', 'min' => 1, 'max' => 255],
            [['foot'], 'string', 'max' => 2],
            [['team_id'], 'exist', 'skipOnError' => true, 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'team_id' => 'Team ID',
            'number' => 'Jersey Number',
            'name' => 'Name',
            'age' => 'Age',
            'position' => 'Position',
            'nationality' => 'Nationality',
            'height_cm'  => 'Height (cm)',
            'weight_kg'  => 'Weight (kg)',
            'foot' => 'Foot',
            'skill_po' => 'Goalkeeping',
            'skill_df' => 'Defending',
            'skill_cn' => 'Tackling',
            'skill_pa' => 'Passing',
            'skill_rg' => 'Playmaking',
            'skill_cr' => 'Crossing',
            'skill_tc' => 'Technique',
            'skill_tr' => 'Shooting',
            'skill_cp' => 'Set Pieces',
            'experience' => 'Experience',
            'general_skill' => 'General Skill',
            'form' => 'Form',
            'freshness' => 'Freshness',
            'condition' => 'Condition',
            'character' => 'Character Traits',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Team]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }

    /**
     * Calculates the player's overall rating for a specific pitch zone.
     * Supports both legacy zones and SIP-0067 10-row zones.
     *
     * @param int $positionOrd The pitch zone number
     * @return float
     */
    /**
     * SIP-0068: quadrant heatmap for this player.
     * Returns array keyed by quadrant with 'official_credits', 'friendly_credits', 'bonus'.
     *
     * @return array<string, array{official_credits:int, friendly_credits:int, bonus:float}>
     */
    public function getQuadrantHeatmap(): array
    {
        $svc = new \app\components\PlayerExperienceService();
        $expMap = $svc->getAllQuadrantExp($this->id);
        $bonusMap = $svc->getQuadrantBonusMap($this->id);
        $result = [];
        foreach ($expMap as $q => $row) {
            $result[$q] = [
                'official_credits' => $row['official_credits'],
                'friendly_credits' => $row['friendly_credits'],
                'bonus'            => round($bonusMap[$q] ?? 0.0, 1),
            ];
        }
        return $result;
    }

    /**
     * SIP-0068: cell heatmap for this player (64 cells).
     * Returns array keyed by display zone (1-64) with 'official_credits', 'friendly_credits', 'bonus'.
     *
     * @return array<int, array{official_credits:int, friendly_credits:int, bonus:float}>
     */
    public function getCellHeatmap(): array
    {
        $svc = new \app\components\PlayerExperienceService();
        $expMap = $svc->getAllCellExp($this->id);
        $bonusMap = $svc->getCellBonusMap($this->id);
        $result = [];
        foreach ($expMap as $zone => $row) {
            $result[$zone] = [
                'official_credits' => $row['official_credits'],
                'friendly_credits' => $row['friendly_credits'],
                'bonus'            => round($bonusMap[$zone] ?? 0.0, 1),
            ];
        }
        return $result;
    }

    public function getOverallForPosition(int $positionOrd): float
    {
        $coeffs = \app\components\PitchZoneHelper::getCoefficientsForZone($positionOrd);

        if (!$coeffs) {
            return 0.0;
        }

        $pdd = 4;
        $lane = \app\components\PitchZoneHelper::laneCode($positionOrd);
        if ($lane === 'L') {
            $pdd = $this->foot === 'R' ? -6 : ($this->foot === 'L' ? 6 : 4);
        } elseif ($lane === 'C') {
            $pdd = $this->foot === 'LR' ? 7 : 4;
        } elseif ($lane === 'R') {
            $pdd = $this->foot === 'R' ? 6 : ($this->foot === 'L' ? -6 : 4);
        }

        $controllo = 15; // Hardcoded in legacy

        $baseScore = $pdd
            + ($this->skill_po * (float)$coeffs['po'])
            + ($this->skill_df * (float)$coeffs['df'])
            + ($this->skill_cn * (float)$coeffs['cn'])
            + ($this->skill_pa * (float)$coeffs['pa'])
            + ($this->skill_rg * (float)$coeffs['rg'])
            + ($this->skill_cr * (float)$coeffs['cr']);

        $totalScore = $baseScore
            + ($this->skill_tc * (float)$coeffs['tc'])
            + ($this->skill_tr * (float)$coeffs['tr']);

        return round($totalScore / (101 - $controllo), 1);
    }

    /**
     * Calcola l'overall sulla posizione naturale ideale del giocatore.
     */
    public function getNaturalOverall(): int
    {
        $zone = match (strtoupper((string)$this->position)) {
            'GK' => \app\components\PitchZoneHelper::GK_ZONE,
            'DF' => \app\components\PitchZoneHelper::zone(3, 2), // Riga 3, Centro
            'MF' => \app\components\PitchZoneHelper::zone(6, 2), // Riga 6, Centro
            'FW' => \app\components\PitchZoneHelper::zone(9, 2), // Riga 9, Centro
            default => \app\components\PitchZoneHelper::zone(6, 2),
        };
        return (int) round($this->getOverallForPosition($zone));
    }
}
