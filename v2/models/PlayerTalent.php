<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $player_id
 * @property string $code
 * @property int $level
 * @property int $progress_weeks
 * @property int $created_at
 * @property int $updated_at
 */
class PlayerTalent extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%player_talent}}';
    }

    public function rules(): array
    {
        return [
            [['player_id', 'code'], 'required'],
            [['player_id', 'level', 'progress_weeks', 'created_at', 'updated_at'], 'integer'],
            [['code'], 'string', 'max' => 32],
            [['level'], 'integer', 'min' => 1, 'max' => 3],
            [['progress_weeks'], 'integer', 'min' => 0, 'max' => 999],
            [['player_id', 'code'], 'unique', 'targetAttribute' => ['player_id', 'code']],
            [['player_id'], 'exist', 'skipOnError' => true, 'targetClass' => Player::class, 'targetAttribute' => ['player_id' => 'id']],
        ];
    }
}

