<?php

declare(strict_types=1);

namespace app\components;

use app\models\User;
use Jambtc\Yii2TelegramNotify\TelegramClient;
use Jambtc\Yii2TelegramNotify\TelegramNotifier;
use Jambtc\Yii2TelegramNotify\TelegramOnboardingService;

class TelegramService
{
    public static function sendToUser(int $userId, string $text, bool $silent = false): bool
    {
        $user = User::findOne($userId);
        if (!$user
            || !$user->telegram_enabled
            || empty($user->telegram_bot_token)
            || empty($user->telegram_chat_id)
        ) {
            return false;
        }

        return self::send(
            trim((string) $user->telegram_bot_token),
            trim((string) $user->telegram_chat_id),
            $text,
            $silent
        );
    }

    public static function send(string $token, string $chatId, string $text, bool $silent = false): bool
    {
        if (empty($token) || empty($chatId)) return false;

        try {
            // Use direct HTTP call to support parse_mode=HTML (library doesn't set it)
            $payload = json_encode([
                'chat_id'                  => $chatId,
                'text'                     => $text,
                'parse_mode'               => 'HTML',
                'disable_web_page_preview' => true,
                'disable_notification'     => $silent,
            ]);

            $ctx = stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . strlen($payload ?? '') . "\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ]]);

            $url      = 'https://api.telegram.org/bot' . trim($token) . '/sendMessage';
            $response = @file_get_contents($url, false, $ctx);
            if ($response === false) return false;
            $data = json_decode($response, true);
            return (bool) ($data['ok'] ?? false);
        } catch (\Throwable) {
            return false;
        }
    }

    public static function verifyBot(string $token): array
    {
        try {
            $svc = new TelegramOnboardingService(new TelegramClient());
            return $svc->verifyBot($token);
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public static function buildDeepLink(string $botUsername, int $userId): array
    {
        try {
            $svc = new TelegramOnboardingService(new TelegramClient());
            return $svc->buildDeepLink($botUsername, $userId, 'gm');
        } catch (\Throwable $e) {
            return ['deep_link' => '', 'token_hash' => '', 'error' => $e->getMessage()];
        }
    }

    public static function findChatForToken(string $token, string $expectedHash): array
    {
        try {
            $svc = new TelegramOnboardingService(new TelegramClient());
            return $svc->findChatForTokenHash($token, $expectedHash);
        } catch (\Throwable $e) {
            return ['found' => false, 'error' => $e->getMessage()];
        }
    }
}
