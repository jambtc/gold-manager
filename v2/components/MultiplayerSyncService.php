<?php

declare(strict_types=1);

namespace app\components;

use Yii;
use yii\base\Component;

class MultiplayerSyncService extends Component
{
    public static function currentRequestId(string $scope = 'default'): string
    {
        return self::resolveRequestId($scope);
    }

    public static function currentRequestSource(): string
    {
        $request = Yii::$app->request;
        $explicit = trim((string) $request->headers->get('X-Request-Source', ''));
        if ($explicit !== '') {
            return substr(preg_replace('/[^a-zA-Z0-9_\-:.]/', '', $explicit) ?: 'web', 0, 24);
        }

        $path = '/' . ltrim((string) $request->pathInfo, '/');
        if (str_starts_with($path, '/api/')) {
            return 'api';
        }

        return 'web';
    }

    public static function ensureOnce(string $scope, int $ttlSeconds = 30): bool
    {
        $requestId = self::resolveRequestId($scope);
        $key = 'gm:idem:' . $scope . ':' . $requestId;
        return self::setIfAbsent($key, (string) time(), $ttlSeconds);
    }

    public static function acquireLock(string $scope, int $ttlSeconds = 10): string|null
    {
        $token = bin2hex(random_bytes(16));
        $key = 'gm:lock:' . $scope;
        $ok = self::setIfAbsent($key, $token, $ttlSeconds);
        return $ok ? $token : null;
    }

    public static function releaseLock(string $scope, string $token): void
    {
        $key = 'gm:lock:' . $scope;

        try {
            if (Yii::$app->has('redis')) {
                $current = (string) Yii::$app->redis->executeCommand('GET', [$key]);
                if ($current !== '' && hash_equals($current, $token)) {
                    Yii::$app->redis->executeCommand('DEL', [$key]);
                }
                return;
            }
        } catch (\Throwable) {
            // fallback below
        }

        try {
            $current = (string) Yii::$app->cache->get($key);
            if ($current !== '' && hash_equals($current, $token)) {
                Yii::$app->cache->delete($key);
            }
        } catch (\Throwable) {
            // best effort
        }
    }

    private static function setIfAbsent(string $key, string $value, int $ttlSeconds): bool
    {
        $ttl = max(1, $ttlSeconds);

        try {
            if (Yii::$app->has('redis')) {
                $result = Yii::$app->redis->executeCommand('SET', [$key, $value, 'NX', 'EX', $ttl]);
                return $result === 'OK';
            }
        } catch (\Throwable) {
            // fallback below
        }

        try {
            return (bool) Yii::$app->cache->add($key, $value, $ttl);
        } catch (\Throwable) {
            return false;
        }
    }

    private static function resolveRequestId(string $scope): string
    {
        $request = Yii::$app->request;
        $explicit = trim((string) ($request->headers->get('X-Idempotency-Key')
            ?? $request->post('request_id')
            ?? $request->get('request_id')
            ?? ''));
        if ($explicit !== '') {
            return substr(preg_replace('/[^a-zA-Z0-9_\-:.]/', '', $explicit) ?: $explicit, 0, 96);
        }

        $payload = [
            'scope' => $scope,
            'uid' => (int) (Yii::$app->user->id ?? 0),
            'method' => strtoupper((string) $request->method),
            'path' => (string) $request->pathInfo,
            'query' => (array) $request->get(),
            'body' => (array) $request->post(),
        ];

        return sha1((string) json_encode($payload, JSON_UNESCAPED_UNICODE));
    }
}
