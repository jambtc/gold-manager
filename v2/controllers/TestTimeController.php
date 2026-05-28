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
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Operation already in progress. Retry in a few seconds.'));
            return $this->redirectBack();
        }

        try {
            $liveFixtures = (int) Fixture::find()
                ->where(['status' => Fixture::STATUS_PLAYING])
                ->count();

            if ($liveFixtures > 0) {
                Yii::$app->session->setFlash('error', Yii::t('app', 'Locked: {count} live match(es) in progress.', ['{count}' => $liveFixtures]));
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

                // Auto-finalize zombie fixtures: status=SCHEDULED but has match events
                // (Go worker ran them but the full_time transaction failed)
                $zombies = $db->createCommand(
                    'SELECT DISTINCT f.id,
                            (SELECT ms.home_score FROM match_state ms WHERE ms.fixture_id=f.id LIMIT 1) AS hs,
                            (SELECT ms.away_score FROM match_state ms WHERE ms.fixture_id=f.id LIMIT 1) AS as_
                     FROM {{%fixture}} f
                     WHERE f.status = :scheduled
                       AND EXISTS (SELECT 1 FROM {{%match_event}} me WHERE me.fixture_id = f.id)'
                )->bindValue(':scheduled', Fixture::STATUS_SCHEDULED)->queryAll();
                foreach ($zombies as $z) {
                    $db->createCommand()->update('{{%fixture}}', [
                        'status'     => \app\models\Fixture::STATUS_FINISHED,
                        'home_score' => (int)($z['hs'] ?? 0),
                        'away_score' => (int)($z['as_'] ?? 0),
                    ], ['id' => $z['id']])->execute();
                }

                $shifted = (int) $db->createCommand(
                    // Exclude fixtures that have match_events (already played or in zombie state)
                    'UPDATE {{%fixture}} SET match_date = match_date - :delta
                     WHERE status = :scheduled
                       AND NOT EXISTS (SELECT 1 FROM {{%match_event}} me WHERE me.fixture_id = {{%fixture}}.id)'
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
                Yii::t('app', 'Test time +1 day completed. Fixtures moved: {shifted}. Two before: {before}, two now: {after}.', [
                    '{shifted}' => $shifted,
                    '{before}'  => $dueBefore,
                    '{after}'   => $dueAfter,
                ])
            );
        } catch (\Throwable $e) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Advance day failed: {message}', ['{message}' => $e->getMessage()]));
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

