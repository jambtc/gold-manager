<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\FriendlyChallengeService;
use app\components\OrchestratorTaskBridge;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class OrchestratorController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'market-resolve' => ['post'],
                    'friendly-start-now' => ['post'],
                ],
            ],
        ];
    }

    public function actionMarketResolve(): Response
    {
        if (!$this->isAuthorized()) {
            return $this->asJson(['ok' => false, 'error' => 'Unauthorized'])->setStatusCode(401);
        }

        try {
            $result = OrchestratorTaskBridge::resolveMarketAuctions();
            return $this->asJson(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            Yii::warning('orchestrator market-resolve failed: ' . $e->getMessage(), 'orchestrator');
            return $this->asJson(['ok' => false, 'error' => $e->getMessage()])->setStatusCode(500);
        }
    }

    public function actionFriendlyStartNow(): Response
    {
        if (!$this->isAuthorized()) {
            return $this->asJson(['ok' => false, 'error' => 'Unauthorized'])->setStatusCode(401);
        }

        $id = (int) Yii::$app->request->post('id', 0);
        if ($id <= 0) {
            return $this->asJson(['ok' => false, 'error' => 'Missing challenge id'])->setStatusCode(400);
        }

        try {
            $result = FriendlyChallengeService::forceStartNow($id);
            if (!$result['ok']) {
                return $this->asJson(['ok' => false, 'result' => $result])->setStatusCode(409);
            }
            return $this->asJson(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            Yii::warning('orchestrator friendly-start-now failed: ' . $e->getMessage(), 'orchestrator');
            return $this->asJson(['ok' => false, 'error' => $e->getMessage()])->setStatusCode(500);
        }
    }

    private function isAuthorized(): bool
    {
        $expected = trim((string) getenv('GM_ORCHESTRATOR_TOKEN'));
        if ($expected === '') {
            return false;
        }
        $given = (string) Yii::$app->request->headers->get('X-Orchestrator-Token', '');
        return hash_equals($expected, $given);
    }
}

