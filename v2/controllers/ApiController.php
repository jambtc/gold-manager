<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\RateLimiter;
use yii\web\Controller;
use yii\web\Response;

class ApiController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'rateLimiter' => [
                'class' => RateLimiter::class,
                'enableRateLimitHeaders' => true,
            ],
        ];
    }

    /**
     * GET /api/user/status
     * Legacy alias kept for backward compatibility.
     */
    public function actionUserStatus(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('X-API-Version', 'legacy');
        Yii::$app->response->headers->set('Deprecation', 'true');
        Yii::$app->response->headers->set('Sunset', 'Thu, 31 Dec 2026 23:59:59 GMT');
        Yii::$app->response->headers->set('Link', '</api/v1/user/status>; rel="successor-version"');

        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        $identity->refresh();

        return $this->asJson([
            'ready'  => $identity->isReady(),
            'status' => $identity->status,
        ]);
    }
}
