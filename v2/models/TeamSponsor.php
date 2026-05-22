<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property int $sponsor_id
 * @property int $signed_at
 * @property int $ends_at
 * @property string $status
 * @property Sponsor $sponsor
 */
class TeamSponsor extends ActiveRecord
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_TERMINATED = 'terminated';

    public static function tableName(): string { return '{{%team_sponsor}}'; }

    public function rules(): array
    {
        return [
            [['team_id', 'sponsor_id', 'signed_at', 'ends_at'], 'required'],
            [['team_id', 'sponsor_id', 'signed_at', 'ends_at'], 'integer'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_EXPIRED, self::STATUS_TERMINATED]],
        ];
    }

    public function getSponsor()
    {
        return $this->hasOne(Sponsor::class, ['id' => 'sponsor_id']);
    }

    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }
}
