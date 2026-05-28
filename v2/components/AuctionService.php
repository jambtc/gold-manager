<?php

declare(strict_types=1);

namespace app\components;

use app\models\MarketBid;
use Yii;

class AuctionService
{
    public static function hoursForType(string $type): int
    {
        $normalized = MarketBid::normalizeMarketType($type);
        return match ($normalized) {
            MarketBid::TYPE_TRANSFER_MARKET => self::envInt('GM_MARKET_PLAYER_AUCTION_HOURS', 24),
            MarketBid::TYPE_STAFF => self::envInt('GM_MARKET_STAFF_AUCTION_HOURS', 48),
            MarketBid::TYPE_SPONSOR => self::envInt('GM_MARKET_SPONSOR_AUCTION_HOURS', 48),
            default => 24,
        };
    }

    public static function expiresAt(string $type): int
    {
        return time() + (self::hoursForType($type) * 3600);
    }

    public static function placeOrUpdateBid(
        int $teamId,
        string $type,
        int $refId,
        int $amount
    ): MarketBid {
        $amount = max(1, $amount);
        $now = time();

        $normalized = MarketBid::normalizeMarketType($type);
        $query = MarketBid::find()
            ->where([
                'team_id' => $teamId,
                'market_ref_id' => $refId,
                'status' => MarketBid::STATUS_PENDING,
            ]);
        if ($normalized === MarketBid::TYPE_TRANSFER_MARKET) {
            $query->andWhere(['market_type' => MarketBid::transferMarketTypes()]);
        } else {
            $query->andWhere(['market_type' => $normalized]);
        }
        $existing = $query->orderBy(['id' => SORT_DESC])->one();

        if ($existing) {
            $existing->bid_amount = $amount;
            $existing->market_type = $normalized;
            $existing->expires_at = self::expiresAt($type);
            $existing->resolved_at = null;
            $existing->result_note = null;
            $existing->updated_at = $now;
            $existing->save(false, ['bid_amount', 'market_type', 'expires_at', 'resolved_at', 'result_note', 'updated_at']);
            return $existing;
        }

        $bid = new MarketBid();
        $bid->team_id = $teamId;
        $bid->market_type = $normalized;
        $bid->market_ref_id = $refId;
        $bid->bid_amount = $amount;
        $bid->status = MarketBid::STATUS_PENDING;
        $bid->expires_at = self::expiresAt($type);
        $bid->created_at = $now;
        $bid->updated_at = $now;
        $bid->save(false);
        return $bid;
    }

    public static function myPendingBids(int $teamId, string $type): array
    {
        $normalized = MarketBid::normalizeMarketType($type);
        $query = MarketBid::find()
            ->where([
                'team_id' => $teamId,
                'status' => MarketBid::STATUS_PENDING,
            ]);
        if ($normalized === MarketBid::TYPE_TRANSFER_MARKET) {
            $query->andWhere(['market_type' => MarketBid::transferMarketTypes()]);
        } else {
            $query->andWhere(['market_type' => $normalized]);
        }
        return $query
            ->orderBy(['expires_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    private static function envInt(string $key, int $default): int
    {
        $raw = getenv($key);
        if ($raw === false || $raw === '') {
            return $default;
        }
        $value = (int) $raw;
        return $value > 0 ? $value : $default;
    }
}
