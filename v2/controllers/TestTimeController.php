<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Fixture;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Temporary dev-only controls for time acceleration.
 * SIP-0053.
 */
class TestTimeController extends Controller
{
    private const LOCK_KEY = 'gm:test-time:advance-day';
    private const DAY_SECONDS = 86400;

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    ['allow' => true, 'roles' => ['@']],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'advance-day' => ['post'],
                ],
            ],
        ];
    }

    public function actionAdvanceDay(): Response
    {
        $this->assertFeatureEnabled();
        $this->assertUserCanUseFeature();

        $cache = Yii::$app->cache;
        $lockAcquired = $cache->add(self::LOCK_KEY, (string) time(), 60);
        if (!$lockAcquired) {
            Yii::$app->session->setFlash('warning', 'Operazione già in corso. Riprova tra pochi secondi.');
            return $this->redirectBack();
        }

        try {
            $liveFixtures = (int) Fixture::find()
                ->where(['status' => Fixture::STATUS_PLAYING])
                ->count();

            if ($liveFixtures > 0) {
                Yii::$app->session->setFlash('error', "Bloccato: {$liveFixtures} partita/e live in corso.");
                return $this->redirectBack();
            }

            $now = time();
            $db = Yii::$app->db;
            $tx = $db->beginTransaction();

            try {
                $dueBefore = (int) Fixture::find()
                    ->where(['status' => Fixture::STATUS_SCHEDULED])
                    ->andWhere(['<=', 'match_date', $now])
                    ->count();

                $shifted = (int) $db->createCommand(
                    'UPDATE {{%fixture}} SET match_date = match_date - :delta WHERE status = :scheduled'
                )
                    ->bindValue(':delta', self::DAY_SECONDS)
                    ->bindValue(':scheduled', Fixture::STATUS_SCHEDULED)
                    ->execute();

                $dueAfter = (int) Fixture::find()
                    ->where(['status' => Fixture::STATUS_SCHEDULED])
                    ->andWhere(['<=', 'match_date', $now])
                    ->count();

                $tx->commit();
            } catch (\Throwable $e) {
                $tx->rollBack();
                throw $e;
            }

            Yii::$app->session->setFlash(
                'success',
                "Test time +1 giorno completato. Fixture spostate: {$shifted}. Due prima: {$dueBefore}, due ora: {$dueAfter}."
            );
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', 'Advance day fallito: ' . $e->getMessage());
        } finally {
            $cache->delete(self::LOCK_KEY);
        }

        return $this->redirectBack();
    }

    private function assertFeatureEnabled(): void
    {
        $enabled = YII_ENV_DEV && getenv('GM_TEST_TIME_TRAVEL') === '1';
        if (!$enabled) {
            throw new NotFoundHttpException('Not found.');
        }
    }

    private function assertUserCanUseFeature(): void
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User) {
            throw new ForbiddenHttpException('Forbidden.');
        }

        if ($identity->isAdmin()) {
            return;
        }

        if ($identity->role !== User::ROLE_MANAGER) {
            throw new ForbiddenHttpException('Forbidden.');
        }
    }

    private function redirectBack(): Response
    {
        $referrer = Yii::$app->request->referrer;
        if (is_string($referrer) && $referrer !== '') {
            return $this->redirect($referrer);
        }

        return $this->redirect(['/site/index']);
    }
}

