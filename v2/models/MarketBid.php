<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $team_id
 * @property string $market_type
 * @property int $market_ref_id
 * @property int $bid_amount
 * @property string $status
 * @property int $expires_at
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $resolved_at
 * @property string|null $result_note
 */
class MarketBid extends ActiveRecord
{
    public const TYPE_TRANSFER_MARKET = 'transfer_market';
    // Legacy alias kept for backward compatibility on old rows/configs.
    public const TYPE_PLAYER_POOL = 'player_pool';
    public const TYPE_STAFF = 'staff';
    public const TYPE_SPONSOR = 'sponsor';

    public const STATUS_PENDING = 'pending';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_CANCELLED = 'cancelled';

    public static function tableName(): string
    {
        return '{{%market_bid}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['team_id', 'market_type', 'market_ref_id', 'bid_amount', 'status', 'expires_at'], 'required'],
            [['team_id', 'market_ref_id', 'bid_amount', 'expires_at', 'created_at', 'updated_at', 'resolved_at'], 'integer'],
            [['market_type'], 'string', 'max' => 24],
            [['status'], 'string', 'max' => 20],
            [['result_note'], 'string', 'max' => 255],
            [['market_type'], 'in', 'range' => [self::TYPE_TRANSFER_MARKET, self::TYPE_PLAYER_POOL, self::TYPE_STAFF, self::TYPE_SPONSOR]],
            [['status'], 'in', 'range' => [self::STATUS_PENDING, self::STATUS_WON, self::STATUS_LOST, self::STATUS_CANCELLED]],
        ];
    }

    /**
     * @return string[]
     */
    public static function transferMarketTypes(): array
    {
        return [self::TYPE_TRANSFER_MARKET, self::TYPE_PLAYER_POOL];
    }

    public static function normalizeMarketType(string $type): string
    {
        return in_array($type, self::transferMarketTypes(), true)
            ? self::TYPE_TRANSFER_MARKET
            : $type;
    }

    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }
}
