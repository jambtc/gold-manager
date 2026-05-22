<?php

declare(strict_types=1);

namespace app\models;

use Yii;
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
 * @property string $foot
 * @property int $skill_po
 * @property int $skill_df
 * @property int $skill_cn
 * @property int $skill_pa
 * @property int $skill_rg
 * @property int $skill_cr
 * @property int $skill_tc
 * @property int $skill_tr
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
            [['team_id', 'number', 'age', 'skill_po', 'skill_df', 'skill_cn', 'skill_pa', 'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr', 'experience', 'general_skill', 'form', 'freshness', 'condition'], 'integer'],
            [['name', 'character'], 'string', 'max' => 255],
            [['position'], 'string', 'max' => 5],
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
            'foot' => 'Foot',
            'skill_po' => 'Goalkeeping',
            'skill_df' => 'Defending',
            'skill_cn' => 'Tackling',
            'skill_pa' => 'Passing',
            'skill_rg' => 'Playmaking',
            'skill_cr' => 'Crossing',
            'skill_tc' => 'Technique',
            'skill_tr' => 'Shooting',
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
     * Calculates the player's overall rating for a specific pitch zone (1-64).
     * Based on the legacy "Formula 2" engine.
     *
     * @param int $positionOrd The pitch zone number (1-64)
     * @return float
     */
    public function getOverallForPosition(int $positionOrd): float
    {
        $coeffs = Yii::$app->db->createCommand(
            'SELECT * FROM {{%calcolatore}} WHERE formula = :f AND ord = :ord',
            [':f' => 'Formula 2', ':ord' => $positionOrd]
        )->queryOne();

        if (!$coeffs) {
            return 0.0;
        }

        $pdd = 4;
        $leftPositions = [1, 2, 8, 9, 15, 16, 22, 23, 29, 30, 36, 37, 43, 44, 50, 51, 57, 58];
        $centerPositions = [3, 4, 5, 10, 11, 12, 17, 18, 19, 24, 25, 26, 31, 32, 33, 38, 39, 40, 45, 46, 47, 52, 53, 54, 59, 60, 61];
        $rightPositions = [6, 7, 13, 14, 20, 21, 27, 28, 34, 35, 41, 42, 48, 49, 55, 56, 62, 63];

        if (in_array($positionOrd, $leftPositions, true)) {
            $pdd = $this->foot === 'R' ? -6 : ($this->foot === 'L' ? 6 : 4);
        } elseif (in_array($positionOrd, $centerPositions, true)) {
            $pdd = $this->foot === 'LR' ? 7 : 4;
        } elseif (in_array($positionOrd, $rightPositions, true)) {
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
}
