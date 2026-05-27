<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $challenger_id
 * @property int $challenged_id
 * @property string $status
 * @property int $proposed_at
 * @property string|null $decline_reason
 * @property int|null $fixture_id
 * @property int $created_at
 * @property int|null $responded_at
 * @property string|null $request_id
 * @property string|null $request_source
 *
 * @property Team $challenger
 * @property Team $challenged
 * @property Fixture|null $fixture
 */
class FriendlyChallenge extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PLAYED = 'played';

    public static function tableName(): string
    {
        return '{{%friendly_challenge}}';
    }

    public function rules(): array
    {
        return [
            [['challenger_id', 'challenged_id', 'proposed_at', 'created_at'], 'required'],
            [['challenger_id', 'challenged_id', 'proposed_at', 'fixture_id', 'created_at', 'responded_at'], 'integer'],
            [['status'], 'string', 'max' => 20],
            [['decline_reason'], 'string', 'max' => 120],
            [['request_id'], 'string', 'max' => 96],
            [['request_source'], 'string', 'max' => 24],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_ACCEPTED,
                self::STATUS_DECLINED,
                self::STATUS_EXPIRED,
                self::STATUS_PLAYED,
            ]],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
        ];
    }

    public function getChallenger()
    {
        return $this->hasOne(Team::class, ['id' => 'challenger_id']);
    }

    public function getChallenged()
    {
        return $this->hasOne(Team::class, ['id' => 'challenged_id']);
    }

    public function getFixture()
    {
        return $this->hasOne(Fixture::class, ['id' => 'fixture_id']);
    }
}
