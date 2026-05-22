<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Competition;
use app\models\Fixture;
use app\models\MatchEvent;
use app\models\MatchState;
use app\models\Standing;
use app\models\Team;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends Controller
{
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'delete-user'       => ['post'],
                    'play-friendly'     => ['post'],
                    'simulate-fixture'  => ['post'],
                ],
            ],
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                        'matchCallback' => function () {
                            /** @var User|null $user */
                            $user = Yii::$app->user->identity;
                            return $user !== null && $user->isAdmin();
                        },
                    ],
                ],
                'denyCallback' => function () {
                    if (Yii::$app->user->isGuest) {
                        return $this->redirect(['/site/login']);
                    }
                    throw new ForbiddenHttpException('Admin only.');
                },
            ],
        ];
    }

    /**
     * Admin dashboard: league overview + manager list.
     */
    public function actionIndex(): string
    {
        $competitions = Competition::find()
            ->orderBy(['tier' => SORT_ASC, 'group_number' => SORT_ASC])
            ->all();

        $managers = User::find()
            ->where(['role' => User::ROLE_MANAGER])
            ->orderBy(['created_at' => SORT_ASC])
            ->all();

        $teamsByUser = [];
        foreach ($managers as $manager) {
            $teamsByUser[$manager->id] = Team::findOne(['user_id' => $manager->id]);
        }

        $standingsByComp = [];
        foreach ($competitions as $comp) {
            $standingsByComp[$comp->id] = Standing::find()
                ->with('team')
                ->where(['competition_id' => $comp->id])
                ->orderBy(['points' => SORT_DESC, 'goals_for' => SORT_DESC])
                ->all();
        }

        // Aggregate stats for the overview cards
        $stats = $this->buildStats();

        return $this->render('index', [
            'competitions'    => $competitions,
            'managers'        => $managers,
            'teamsByUser'     => $teamsByUser,
            'standingsByComp' => $standingsByComp,
            'stats'           => $stats,
        ]);
    }

    private function buildStats(): array
    {
        $db = Yii::$app->db;

        $serieC = Competition::find()
            ->select('id')
            ->where(['tier' => Competition::TIER_C])
            ->column();

        $freeSlotsC = $serieC
            ? (int) Team::find()
                ->innerJoin(Standing::tableName() . ' s', 's.team_id = ' . Team::tableName() . '.id')
                ->where([Team::tableName() . '.is_cpu' => 1, Team::tableName() . '.user_id' => null, 's.competition_id' => $serieC])
                ->count()
            : 0;

        return [
            'managers'        => (int) User::find()->where(['role' => User::ROLE_MANAGER])->count(),
            'free_slots'      => $freeSlotsC,
            'competitions'    => (int) $db->createCommand('SELECT COUNT(*) FROM {{%competition}}')->queryScalar(),
            'playing'         => (int) $db->createCommand('SELECT COUNT(*) FROM {{%fixture}} WHERE status = 1')->queryScalar(),
            'scheduled'       => (int) $db->createCommand('SELECT COUNT(*) FROM {{%fixture}} WHERE status = 0')->queryScalar(),
            'finished'        => (int) $db->createCommand('SELECT COUNT(*) FROM {{%fixture}} WHERE status = 2')->queryScalar(),
            'season'          => (int) $db->createCommand('SELECT MIN(season) FROM {{%competition}}')->queryScalar(),
            'total_players'   => (int) $db->createCommand('SELECT COUNT(*) FROM {{%player}}')->queryScalar(),
        ];
    }

    /**
     * Deletes a manager. Their team (if any) is returned to CPU control.
     * POST only, CSRF protected.
     */
    public function actionDeleteUser(): Response
    {
        $id   = (int) Yii::$app->request->post('id', 0);
        $user = $id > 0 ? User::findOne($id) : null;

        if ($user === null) {
            throw new NotFoundHttpException("User #$id not found.");
        }

        if ($user->isAdmin()) {
            Yii::$app->session->setFlash('error', 'Cannot delete an admin account.');
            return $this->redirect(['/admin/index']);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $team = Team::findOne(['user_id' => $id]);
            if ($team !== null) {
                $team->is_cpu  = 1;
                $team->user_id = null;
                if (!$team->save(false)) {
                    throw new \RuntimeException('Failed to release team: ' . json_encode($team->errors));
                }
            }

            $username = $user->username;
            $user->delete();

            $transaction->commit();

            Yii::$app->session->setFlash('success', "Manager «{$username}» eliminato." .
                ($team ? " La squadra «{$team->name}» è stata restituita al controllo CPU." : ''));
        } catch (\Throwable $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', 'Errore: ' . $e->getMessage());
        }

        return $this->redirect(['/admin/index']);
    }

    /**
     * Queue jobs monitor: waiting, reserved, recent failed.
     */
    public function actionJobs(): string
    {
        $channel = Yii::$app->queue->channel;
        $redis   = Yii::$app->redis;

        $waitingIds   = $redis->lrange("$channel.waiting", 0, -1)   ?? [];
        $reservedIds  = $redis->zrange("$channel.reserved", 0, -1)  ?? [];
        $failedIds    = $redis->lrange("$channel.failed", 0, 49)     ?? [];

        $load = function (array $ids) use ($channel, $redis): array {
            $jobs = [];
            foreach ($ids as $id) {
                $info = $redis->hgetall("$channel.$id");
                if ($info) {
                    $jobs[] = array_merge(['id' => $id], $info);
                }
            }
            return $jobs;
        };

        return $this->render('jobs', [
            'waiting'  => $load($waitingIds),
            'reserved' => $load($reservedIds),
            'failed'   => $load($failedIds),
            'channel'  => $channel,
        ]);
    }

    /**
     * POST /admin/simulate-fixture — simulates an existing fixture instantly.
     */
    public function actionSimulateFixture(): Response
    {
        $fixtureId = (int) Yii::$app->request->post('fixture_id');
        $fixture   = Fixture::findOne($fixtureId);

        if (!$fixture) {
            Yii::$app->session->setFlash('error', "Fixture #$fixtureId non trovata.");
            return $this->redirect(['/fixture/index']);
        }

        if ($fixture->status === Fixture::STATUS_FINISHED) {
            return $this->redirect(['/fixture/view', 'id' => $fixtureId]);
        }

        // Reset and let the Go live worker simulate it.
        MatchState::deleteAll(['fixture_id' => $fixtureId]);
        MatchEvent::deleteAll(['fixture_id' => $fixtureId]);

        $fixture->status     = Fixture::STATUS_PLAYING;
        $fixture->home_score = 0;
        $fixture->away_score = 0;
        $fixture->save(false);

        return $this->redirect(['/fixture/live', 'id' => $fixtureId]);
    }

    /**
     * GET /admin/friendly — form to create a quick friendly match.
     */
    public function actionFriendly(): string
    {
        // Load teams with their competition tier via standing join
        $teams = Team::find()->orderBy(['name' => SORT_ASC])->all();

        // Build a map team_id → competition label
        $teamLeague = [];
        foreach ($teams as $team) {
            $standing = \app\models\Standing::find()
                ->with('competition')
                ->where(['team_id' => $team->id])
                ->one();
            $teamLeague[$team->id] = $standing ? $standing->competition->getLabel() : '—';
        }

        return $this->render('friendly', ['teams' => $teams, 'teamLeague' => $teamLeague]);
    }

    /**
     * POST /admin/play-friendly — creates a friendly fixture and simulates it instantly.
     */
    public function actionPlayFriendly(): Response
    {
        $homeId = (int) Yii::$app->request->post('home_team_id');
        $awayId = (int) Yii::$app->request->post('away_team_id');

        if (!$homeId || !$awayId || $homeId === $awayId) {
            Yii::$app->session->setFlash('error', 'Seleziona due squadre diverse.');
            return $this->redirect(['/admin/friendly']);
        }

        // Get or create the "Amichevoli" competition
        $comp = Competition::findOne(['type' => 'friendly']);
        if (!$comp) {
            $comp = new Competition();
            $comp->name         = 'Amichevoli';
            $comp->type         = 'friendly';
            $comp->season       = 1;
            $comp->tier         = Competition::TIER_C;
            $comp->group_number = 0;
            $comp->save(false);
        }

        $fixture = new Fixture();
        $fixture->competition_id = $comp->id;
        $fixture->home_team_id   = $homeId;
        $fixture->away_team_id   = $awayId;
        $fixture->match_date     = time();
        $fixture->status         = Fixture::STATUS_PLAYING;
        $fixture->home_score     = 0;
        $fixture->away_score     = 0;

        if (!$fixture->save()) {
            Yii::$app->session->setFlash('error', 'Errore creazione partita: ' . json_encode($fixture->errors));
            return $this->redirect(['/admin/friendly']);
        }

        return $this->redirect(['/fixture/live', 'id' => $fixture->id]);
    }
}
