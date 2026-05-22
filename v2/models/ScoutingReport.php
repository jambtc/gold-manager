<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $team_id
 * @property int $player_id
 * @property string $revealed_talent
 * @property int $potential_score
 * @property string $report_text
 * @property int $created_at
 */
class ScoutingReport extends ActiveRecord
{
    public static function tableName(): string { return '{{%scouting_report}}'; }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['team_id', 'player_id'], 'required'],
            [['team_id', 'player_id', 'potential_score'], 'integer'],
            [['report_text'], 'string'],
            [['revealed_talent'], 'string', 'max' => 50],
        ];
    }

    public function getPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'player_id']);
    }
}
