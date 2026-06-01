<?php

declare(strict_types=1);

namespace app\components;

use Yii;

final class OrchestratorEventService
{
    public static function isEnabled(): bool
    {
        $raw = getenv('GM_ORCHESTRATOR_ENABLED');
        if ($raw === false || $raw === '') {
            return false;
        }
        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function enqueue(
        string $eventType,
        ?string $aggregateType = null,
        ?int $aggregateId = null,
        array $payload = [],
        ?int $availableAt = null
    ): int {
        $now = time();
        $availableAt ??= $now;

        Yii::$app->db->createCommand()->insert('{{%orchestrator_event}}', [
            'event_type' => $eventType,
            'aggregate_type' => $aggregateType,
            'aggregate_id' => $aggregateId,
            'payload_json' => empty($payload) ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => (int) $availableAt,
            'created_at' => $now,
            'processed_at' => null,
            'last_error' => null,
        ])->execute();

        return (int) Yii::$app->db->getLastInsertID();
    }
}

