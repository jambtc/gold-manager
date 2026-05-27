<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property int $fixture_id
 * @property int $team_id
 * @property string $command_type
 * @property string|null $payload
 * @property int $minute_submitted
 * @property int|null $executed_at_minute
 * @property int $created_at
 * @property string|null $request_id
 * @property string|null $request_source
 */
class MatchCommand extends ActiveRecord
{
    public const TYPE_SUBSTITUTION = 'substitution';
    public const TYPE_TACTIC = 'tactic';
    public const TYPE_INTENSITY = 'intensity';

    public static function tableName(): string { return '{{%match_command}}'; }

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
            [['fixture_id', 'team_id', 'command_type', 'minute_submitted'], 'required'],
            [['fixture_id', 'team_id', 'minute_submitted', 'executed_at_minute'], 'integer'],
            [['command_type'], 'string', 'max' => 50],
            [['payload'], 'string'],
            [['request_id'], 'string', 'max' => 96],
            [['request_source'], 'string', 'max' => 24],
        ];
    }
}
