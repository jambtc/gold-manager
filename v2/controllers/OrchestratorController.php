<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\FriendlyChallengeService;
use app\components\OrchestratorTaskBridge;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * SIP-0077: Internal HTTP endpoints called by the Go orchestrator.
 * All actions require X-Orchestrator-Token header.
 */
class OrchestratorController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors(): array
    {
        $actions = [
            'market-resolve', 'market-refresh',
            'run-fixtures',
            'notification-dispatch', 'pre-match-notifications', 'daily-digest',
            'pay-wages', 'daily-training', 'weekly-recovery',
            'loan-returns', 'auto-rollover', 'expansion-pool',
            'cpu-formations', 'scouting',
            'expire-friendlies',
            'friendly-start-now',
        ];

        return [
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => array_fill_keys($actions, ['post']),
            ],
        ];
    }

    // ── Market ────────────────────────────────────────────────────────────────

    public function actionMarketResolve(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::resolveMarketAuctions());
    }

    public function actionMarketRefresh(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::marketRefresh());
    }

    // ── Fixtures ──────────────────────────────────────────────────────────────

    public function actionRunFixtures(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::runFixtures());
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    public function actionNotificationDispatch(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::notificationDispatch());
    }

    public function actionPreMatchNotifications(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::preMatchNotifications());
    }

    public function actionDailyDigest(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::dailyDigest());
    }

    // ── Economy ───────────────────────────────────────────────────────────────

    public function actionPayWages(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::payWages());
    }

    public function actionDailyTraining(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::dailyTraining());
    }

    public function actionWeeklyRecovery(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::weeklyRecovery());
    }

    public function actionLoanReturns(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::loanReturns());
    }

    public function actionAutoRollover(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::autoRollover());
    }

    public function actionExpansionPool(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::expansionPool());
    }

    // ── Game ──────────────────────────────────────────────────────────────────

    public function actionCpuFormations(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::cpuFormations());
    }

    public function actionScouting(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::scoutingProcess());
    }

    public function actionExpireFriendlies(): Response
    {
        return $this->job(fn() => OrchestratorTaskBridge::expireFriendlyChallenges());
    }

    // ── Events ────────────────────────────────────────────────────────────────

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

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function job(\Closure $fn): Response
    {
        if (!$this->isAuthorized()) {
            return $this->asJson(['ok' => false, 'error' => 'Unauthorized'])->setStatusCode(401);
        }
        try {
            $result = $fn();
            return $this->asJson(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            $action = Yii::$app->requestedAction?->id ?? 'unknown';
            Yii::warning("orchestrator {$action} failed: " . $e->getMessage(), 'orchestrator');
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
