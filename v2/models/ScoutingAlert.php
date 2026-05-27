<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property int $player_id
 * @property int $created_at
 */
class ScoutingAlert extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%scouting_alert}}';
    }

    public function rules(): array
    {
        return [
            [['team_id', 'player_id', 'created_at'], 'required'],
            [['team_id', 'player_id', 'created_at'], 'integer'],
        ];
    }
}
