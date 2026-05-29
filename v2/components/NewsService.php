<?php

declare(strict_types=1);

namespace app\components;

use app\models\NewsItem;

class NewsService
{
    public static function create(
        int    $userId,
        string $category,
        string $icon,
        string $title,
        string $body = '',
        string $linkUrl = '',
        int    $priority = 0
    ): int {
        $item             = new NewsItem();
        $item->user_id    = $userId;
        $item->category   = $category;
        $item->icon       = $icon;
        $item->title      = $title;
        $item->body       = $body ?: null;
        $item->link_url   = $linkUrl ?: null;
        $item->priority   = $priority;
        $item->is_read    = 0;
        $item->created_at = time();
        $item->save(false);
        return (int) $item->id;
    }

    public static function unreadCount(int $userId): int
    {
        return (int) NewsItem::find()
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->count();
    }

    public static function markAllRead(int $userId): void
    {
        NewsItem::updateAll(['is_read' => 1], ['user_id' => $userId, 'is_read' => 0]);
    }

    public static function latest(int $userId, int $limit = 3): array
    {
        return NewsItem::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * @return array{unread_count:int,latest_id:int}
     */
    public static function snapshot(int $userId): array
    {
        $unreadCount = (int) NewsItem::find()
            ->where(['user_id' => $userId, 'is_read' => 0])
            ->count();

        $latestId = (int) NewsItem::find()
            ->where(['user_id' => $userId])
            ->max('id');

        return [
            'unread_count' => $unreadCount,
            'latest_id' => $latestId,
        ];
    }
}
