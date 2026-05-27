<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\NewsService;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class NotificationController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    /**
     * Lightweight JSON endpoint used by fallback polling.
     *
     * @return array{ok:bool,unread_count:int,latest_id:int}
     */
    public function actionPoll(): array
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $snapshot = NewsService::snapshot((int) Yii::$app->user->id);

        return [
            'ok' => true,
            'unread_count' => $snapshot['unread_count'],
            'latest_id' => $snapshot['latest_id'],
        ];
    }

    /**
     * SSE stream for near-real-time header badge updates.
     */
    public function actionStream(): string
    {
        $userId = (int) Yii::$app->user->id;
        if (Yii::$app->session->isActive) {
            Yii::$app->session->close();
        }

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $headers = $response->headers;
        $headers->set('Content-Type', 'text/event-stream');
        $headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
        $headers->set('Connection', 'keep-alive');
        $headers->set('X-Accel-Buffering', 'no');

        @ini_set('output_buffering', 'off');
        @ini_set('zlib.output_compression', '0');
        @ini_set('implicit_flush', '1');
        while (ob_get_level() > 0) {
            @ob_end_flush();
        }
        ob_implicit_flush(true);
        ignore_user_abort(true);

        $since = (int) Yii::$app->request->get('since', 0);
        $lastEventIdHeader = trim((string) Yii::$app->request->headers->get('Last-Event-ID', ''));
        if ($lastEventIdHeader !== '' && ctype_digit($lastEventIdHeader)) {
            $since = max($since, (int) $lastEventIdHeader);
        }

        $startTs = time();
        $maxSec = 25;
        $firstTick = true;
        $previous = NewsService::snapshot($userId);

        while (!connection_aborted() && (time() - $startTs) < $maxSec) {
            $current = NewsService::snapshot($userId);
            $changed = $firstTick
                || $current['latest_id'] > $since
                || $current['unread_count'] !== $previous['unread_count'];

            if ($changed) {
                $eventId = max($current['latest_id'], $since);
                $payload = json_encode(
                    [
                        'unread_count' => $current['unread_count'],
                        'latest_id' => $current['latest_id'],
                        'server_ts' => time(),
                    ],
                    JSON_UNESCAPED_UNICODE
                );
                if ($payload === false) {
                    $payload = '{"unread_count":0,"latest_id":0}';
                }

                echo "id: {$eventId}\n";
                echo "event: news\n";
                echo "data: {$payload}\n\n";
                @ob_flush();
                flush();

                $firstTick = false;
                $since = max($since, $current['latest_id']);
            } else {
                echo "event: ping\n";
                echo 'data: {"ok":true}' . "\n\n";
                @ob_flush();
                flush();
            }

            $previous = $current;
            usleep(1000000);
        }

        return '';
    }
}

