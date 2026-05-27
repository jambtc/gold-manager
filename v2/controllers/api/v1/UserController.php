<?php

declare(strict_types=1);

namespace app\controllers\api\v1;

use app\models\User;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\AccessControl;
use yii\filters\RateLimiter;
use yii\web\Controller;
use yii\web\Response;

class UserController extends Controller
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
     * GET /api/v1/user/status
     * Returns {"ready": true|false, "status": "pending|active|error"}
     *
     * @OA\Get(
     *   path="/api/v1/user/status",
     *   summary="Stato onboarding utente",
     *   tags={"User"},
     *   security={{"SessionCookie": {}}},
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="ready", type="boolean", example=true),
     *       @OA\Property(property="status", type="string", example="active")
     *     )
     *   ),
     *   @OA\Response(
     *     response=401,
     *     description="Utente non autenticato"
     *   )
     * )
     */
    public function actionStatus(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->response->headers->set('X-API-Version', '1');

        /** @var User $identity */
        $identity = Yii::$app->user->identity;
        $identity->refresh();

        return $this->asJson([
            'ready' => $identity->isReady(),
            'status' => $identity->status,
        ]);
    }
}
