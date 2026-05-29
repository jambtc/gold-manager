<?php

declare(strict_types=1);

namespace app\commands;

use app\components\TelegramService;
use app\models\NotificationDelivery;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * SIP-0080: Async Telegram dispatcher.
 *
 * Cron: * * * * * php yii notification/dispatch
 *   (or every 5 min for lighter load: *\/5 * * * *)
 */
class NotificationController extends Controller
{
    /**
     * Process pending notification_delivery rows and send via Telegram.
     * Safe to run frequently — picks at most $limit rows per run.
     *
     * Usage: ./yii notification/dispatch [--limit=50]
     */
    public function actionDispatch(int $limit = 50): int
    {
        $rows = Yii::$app->db->createCommand('
            SELECT nd.id, nd.user_id, nd.telegram_text, nd.attempts,
                   u.telegram_bot_token, u.telegram_chat_id
            FROM {{%notification_delivery}} nd
            JOIN {{%user}} u ON u.id = nd.user_id
            WHERE nd.channel = :ch
              AND nd.status IN (:p, :f)
              AND nd.attempts < :max
            ORDER BY nd.created_at ASC
            LIMIT :lim
        ', [
            ':ch'  => NotificationDelivery::CHANNEL_TELEGRAM,
            ':p'   => NotificationDelivery::STATUS_PENDING,
            ':f'   => NotificationDelivery::STATUS_FAILED,
            ':max' => NotificationDelivery::MAX_ATTEMPTS,
            ':lim' => $limit,
        ])->queryAll();

        $sent = $skipped = $failed = 0;

        foreach ($rows as $row) {
            $token  = trim((string) ($row['telegram_bot_token'] ?? ''));
            $chatId = trim((string) ($row['telegram_chat_id'] ?? ''));

            if (empty($token) || empty($chatId)) {
                // User has no Telegram configured — mark as failed permanently
                $this->updateDelivery((int) $row['id'], false, (int) $row['attempts'], 'no_telegram_config');
                $skipped++;
                continue;
            }

            $ok = TelegramService::send($token, $chatId, (string) ($row['telegram_text'] ?? ''));
            $this->updateDelivery((int) $row['id'], $ok, (int) $row['attempts'], $ok ? null : 'send_failed');

            $ok ? $sent++ : $failed++;
        }

        $this->stdout("📬 dispatch: sent={$sent} failed={$failed} skipped={$skipped}\n");
        return ExitCode::OK;
    }

    /**
     * Show recent delivery stats (last 100 rows).
     *
     * Usage: ./yii notification/status
     */
    public function actionStatus(): int
    {
        $rows = Yii::$app->db->createCommand('
            SELECT status, COUNT(*) AS cnt
            FROM {{%notification_delivery}}
            WHERE created_at > :since
            GROUP BY status
        ', [':since' => time() - 86400])->queryAll();

        $this->stdout("📊 Delivery stats (last 24h):\n");
        foreach ($rows as $r) {
            $this->stdout("  {$r['status']}: {$r['cnt']}\n");
        }
        return ExitCode::OK;
    }

    private function updateDelivery(int $id, bool $success, int $prevAttempts, ?string $error): void
    {
        $now = time();
        Yii::$app->db->createCommand()->update('{{%notification_delivery}}', [
            'status'     => $success ? NotificationDelivery::STATUS_SENT : NotificationDelivery::STATUS_FAILED,
            'attempts'   => $prevAttempts + 1,
            'sent_at'    => $success ? $now : null,
            'last_error' => $success ? null : $error,
            'updated_at' => $now,
        ], ['id' => $id])->execute();
    }
}
