<?php

declare(strict_types=1);

namespace app\components;

use app\models\NewsItem;
use app\models\User;
use Yii;

/**
 * SIP-0080: Unified notification pipeline.
 *
 * Single entry point for all user-facing events:
 *  1. Always writes to news_item (in-app badge / news page / SSE).
 *  2. Enqueues a notification_delivery row for async Telegram dispatch.
 *     The actual HTTP call happens in NotificationController::actionDispatch().
 *
 * Controllers and services must use notify() instead of calling
 * NewsService::create() + TelegramService::sendToUser() inline.
 */
final class NotificationService
{
    /**
     * Create an in-app news item and (optionally) enqueue a Telegram delivery.
     *
     * @param string|null $telegramText  Pass null to skip Telegram entirely.
     *                                   Pass a string to enqueue delivery (only
     *                                   sent if the user has Telegram configured).
     */
    public static function notify(
        int    $userId,
        string $category,
        string $icon,
        string $title,
        string $body = '',
        string $linkUrl = '',
        int    $priority = 0,
        ?string $telegramText = null
    ): int {
        // 1. In-app news_item (always)
        $newsItemId = NewsService::create($userId, $category, $icon, $title, $body, $linkUrl, $priority);

        // 2. Enqueue Telegram delivery if caller requested it
        if ($telegramText !== null) {
            self::enqueueDelivery($userId, $newsItemId, $telegramText, $category);
        }

        return $newsItemId;
    }

    /**
     * Enqueue a Telegram delivery row (skips if user has no Telegram configured).
     * Can be called directly when a news_item already exists (e.g. from Go worker).
     */
    public static function enqueueDelivery(int $userId, ?int $newsItemId, string $telegramText, string $category = ''): void
    {
        $user = User::findOne(['id' => $userId, 'telegram_enabled' => 1]);
        if (!$user || empty($user->telegram_chat_id) || empty($user->telegram_bot_token)) {
            return;
        }

        if ($category !== '' && !$user->isTelegramCategoryEnabled($category)) {
            return;
        }

        $now = time();
        Yii::$app->db->createCommand()->insert('{{%notification_delivery}}', [
            'user_id'       => $userId,
            'news_item_id'  => $newsItemId,
            'channel'       => 'telegram',
            'status'        => 'pending',
            'telegram_text' => $telegramText,
            'attempts'      => 0,
            'created_at'    => $now,
            'updated_at'    => $now,
        ])->execute();
    }

    // ── Convenience wrappers for common events ──────────────────────────────

    public static function matchResult(int $userId, string $icon, string $vsLine, string $type, string $linkUrl): void
    {
        self::notify($userId, NewsItem::CAT_MATCH, $icon, $vsLine, $type, $linkUrl,
            $icon === '⚽' ? 1 : 0,
            "{$icon} <b>Finale:</b> {$vsLine}\n{$type}"
        );
    }

    public static function preMatch(int $userId, string $homeTeam, string $awayTeam, int $fixtureId, string $linkUrl): void
    {
        $title = Yii::t('app', '{home} vs {away} — kick-off in 15 min', [
            'home' => $homeTeam, 'away' => $awayTeam,
        ]);
        self::notify($userId, NewsItem::CAT_MATCH, '⏰', $title, '', $linkUrl, 1,
            "⏰ <b>Calcio d'inizio tra 15 minuti!</b>\n{$homeTeam} vs {$awayTeam}"
        );
    }

    public static function goal(int $userId, string $scoringTeam, string $opposingTeam, int $minute, int $scoringScore, int $opposingScore, bool $ownTeamScored): void
    {
        if ($ownTeamScored) {
            $title = Yii::t('app', 'GOAL! {team} scores at minute {min}! {s1}–{s2}', [
                'team' => $scoringTeam, 'min' => $minute,
                's1' => $scoringScore, 's2' => $opposingScore,
            ]);
            self::notify($userId, NewsItem::CAT_MATCH, '⚽', $title, '', '', 1,
                "⚽ <b>GOL!</b> {$scoringTeam} al minuto {$minute}! {$scoringScore}–{$opposingScore}"
            );
        } else {
            $title = Yii::t('app', 'Goal conceded: {team} scores at minute {min}! {s1}–{s2}', [
                'team' => $scoringTeam, 'min' => $minute,
                's1' => $opposingScore, 's2' => $scoringScore,
            ]);
            self::notify($userId, NewsItem::CAT_MATCH, '😰', $title, '', '', 0,
                "😰 <b>Gol subito</b> da {$scoringTeam} al minuto {$minute}! {$opposingScore}–{$scoringScore}"
            );
        }
    }

    public static function halfTime(int $userId, string $homeTeam, string $awayTeam, int $homeScore, int $awayScore): void
    {
        $title = Yii::t('app', 'Half time: {home} {hs}–{as} {away}', [
            'home' => $homeTeam, 'hs' => $homeScore,
            'as' => $awayScore,  'away' => $awayTeam,
        ]);
        self::notify($userId, NewsItem::CAT_MATCH, '🔔', $title, '', '', 0,
            "🔔 <b>Intervallo:</b> {$homeTeam} {$homeScore}–{$awayScore} {$awayTeam}"
        );
    }

    public static function injury(int $userId, string $playerName, string $type, int $weeks): void
    {
        self::notify($userId, NewsItem::CAT_INJURY, '🏥',
            "{$playerName} infortunato: {$type}",
            "Fuori {$weeks} settimane", '', 1,
            "🏥 <b>{$playerName}</b> infortunato ({$type}) — fuori {$weeks} settimane"
        );
    }

    public static function discipline(int $userId, string $playerName, string $reason, int $matches): void
    {
        self::notify($userId, NewsItem::CAT_DISCIPLINE, '🟥',
            "{$playerName} squalificato ({$reason})",
            "Salta {$matches} gara/e", '', 1,
            "🟥 <b>{$playerName}</b> squalificato ({$reason}) — salta {$matches} gara/e"
        );
    }

    public static function transferOffer(int $userId, string $playerName, string $teamName, int $amount): void
    {
        $fmt = number_format($amount);
        self::notify($userId, NewsItem::CAT_TRANSFER, '💰',
            "Offerta per {$playerName}: €{$fmt}",
            "Da {$teamName}", '', 1,
            "💰 <b>Offerta ricevuta</b> per {$playerName} da {$teamName}: €{$fmt}"
        );
    }

    public static function transferCompleted(int $userId, string $playerName, string $direction, int $amount, string $linkUrl = ''): void
    {
        $fmt = number_format($amount);
        $icon = $direction === 'sold' ? '💸' : '🤝';
        $verb = $direction === 'sold' ? 'Venduto' : 'Acquistato';
        self::notify($userId, NewsItem::CAT_TRANSFER, $icon,
            "{$verb}: {$playerName} — €{$fmt}",
            '', $linkUrl, 1,
            "{$icon} <b>{$verb}</b> {$playerName} per €{$fmt}"
        );
    }

    public static function friendlyChallenge(int $userId, string $challengerName, string $linkUrl = ''): void
    {
        self::notify($userId, NewsItem::CAT_FRIENDLY, '📩',
            "Sfida amichevole da {$challengerName}",
            '', $linkUrl, 1,
            "📩 <b>Sfida amichevole</b> ricevuta da {$challengerName}"
        );
    }
}
