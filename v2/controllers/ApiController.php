<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\User;
use Yii;
use yii\filters\AccessControl;
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
        ];
    }

    /**
     * GET /api/user/status
     * Returns {"ready": true|false, "status": "pending|active|error"}
     */
    public function actionUserStatus(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        $identity->refresh();

        return $this->asJson([
            'ready'  => $identity->isReady(),
            'status' => $identity->status,
        ]);
    }
}
