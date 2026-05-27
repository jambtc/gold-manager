<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property string $position
 * @property int $created_at
 */
class ScoutingNeed extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%scouting_need}}';
    }

    public function rules(): array
    {
        return [
            [['team_id', 'position', 'created_at'], 'required'],
            [['team_id', 'created_at'], 'integer'],
            [['position'], 'string', 'max' => 3],
            [['position'], 'in', 'range' => ['GK', 'DF', 'MF', 'FW']],
        ];
    }
}
