<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Fixture;
use app\models\Formation;
use app\models\FormationSlot;
use app\models\ManMarking;
use app\models\Player;
use app\models\ScoutingReport;
use app\models\Team;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class MarkingController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => ['assign' => ['post'], 'remove' => ['post']],
            ],
        ];
    }

    public function actionIndex(int $fixtureId): string|Response
    {
        $fixture = Fixture::findOne($fixtureId);
        if (!$fixture || $fixture->status === Fixture::STATUS_FINISHED) {
            throw new NotFoundHttpException(Yii::t('app', 'Match not found or already concluded.'));
        }

        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$myTeam) return $this->redirect(['/site/index']);

        $isMine = $fixture->home_team_id === $myTeam->id || $fixture->away_team_id === $myTeam->id;
        if (!$isMine) throw new NotFoundHttpException(Yii::t('app', 'You are not involved in this match.'));

        $oppTeamId = $fixture->home_team_id === $myTeam->id
            ? $fixture->away_team_id
            : $fixture->home_team_id;
        $oppTeam = Team::findOne($oppTeamId);

        // My formation starters (defenders/DMs only as valid markers)
        $myFormation = Formation::findOne(['team_id' => $myTeam->id, 'is_active' => 1]);
        $myStarters  = $myFormation
            ? FormationSlot::find()->with('player')->where(['formation_id' => $myFormation->id])->all()
            : [];
        $myDefenders = array_values(array_filter($myStarters, fn($s) =>
            $s->player && in_array($s->player->position, ['DF','MF'], true)
        ));

        // Opponent roster: scout report → formation → top players
        $oppRoster = $this->resolveOpponentRoster($oppTeamId, $myTeam->id);

        // Current markings
        $markings = ManMarking::forFixtureTeam($fixtureId, (int)$myTeam->id);

        return $this->render('index', [
            'fixture'     => $fixture,
            'myTeam'      => $myTeam,
            'oppTeam'     => $oppTeam,
            'myDefenders' => $myDefenders,
            'oppRoster'   => $oppRoster,
            'markings'    => $markings,
            'canAdd'      => count($markings) < 3 && $fixture->status === Fixture::STATUS_SCHEDULED,
        ]);
    }

    public function actionAssign(): Response
    {
        $myTeam    = Team::findOne(['user_id' => Yii::$app->user->id]);
        $fixtureId = (int) Yii::$app->request->post('fixture_id');
        $markerId  = (int) Yii::$app->request->post('marker_id');
        $markedId  = (int) Yii::$app->request->post('marked_id');

        $fixture = Fixture::findOne($fixtureId);
        if (!$fixture || !$myTeam || $fixture->status !== Fixture::STATUS_SCHEDULED) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Invalid match or already started.'));
            return $this->redirect(['index', 'fixtureId' => $fixtureId]);
        }

        $count = ManMarking::find()
            ->where(['fixture_id' => $fixtureId, 'team_id' => $myTeam->id])
            ->count();
        if ($count >= 3) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Maximum 3 markings per match.'));
            return $this->redirect(['index', 'fixtureId' => $fixtureId]);
        }

        $m = new ManMarking();
        $m->fixture_id = $fixtureId;
        $m->team_id    = (int)$myTeam->id;
        $m->marker_id  = $markerId;
        $m->marked_id  = $markedId;
        $m->created_at = time();

        if (!$m->save()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Marking already present or error.'));
        }
        return $this->redirect(['index', 'fixtureId' => $fixtureId]);
    }

    public function actionRemove(int $id): Response
    {
        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        $m = ManMarking::findOne(['id' => $id, 'team_id' => $myTeam?->id ?? 0]);
        if ($m) $m->delete();
        return $this->redirect(['index', 'fixtureId' => $m->fixture_id ?? 0]);
    }

    private function resolveOpponentRoster(int $oppTeamId, int $myTeamId): array
    {
        // 1. Scout report
        $report = ScoutingReport::find()
            ->where(['team_id' => $myTeamId, 'status' => 'ready'])
            ->andWhere(['player_id' => Player::find()->select('id')->where(['team_id' => $oppTeamId])])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if ($report && $report->report_text) {
            $data = json_decode($report->report_text, true) ?? [];
            if (!empty($data['name'])) {
                return [['name' => $data['name'], 'position' => $data['position'] ?? '?',
                         'skill' => $data['general_skill'] ?? '?', 'approx' => true,
                         'player_id' => $report->player_id]];
            }
        }

        // 2. Opponent active formation
        $oppFormation = Formation::findOne(['team_id' => $oppTeamId, 'is_active' => 1]);
        if ($oppFormation) {
            $slots = FormationSlot::find()->with('player')
                ->where(['formation_id' => $oppFormation->id])->all();
            $result = [];
            foreach ($slots as $slot) {
                if (!$slot->player) continue;
                $noise = mt_rand(-10, 10);
                $result[] = [
                    'player_id' => $slot->player_id,
                    'name'      => $slot->player->name,
                    'position'  => $slot->player->position,
                    'skill'     => '~' . max(1, min(99, $slot->player->general_skill + $noise)),
                    'approx'    => true,
                ];
            }
            usort($result, fn($a, $b) => strcmp($a['position'], $b['position']));
            return $result;
        }

        // 3. Top 11 by skill (fallback)
        $players = Player::find()->where(['team_id' => $oppTeamId])
            ->orderBy(['general_skill' => SORT_DESC])->limit(11)->all();
        return array_map(fn($p) => [
            'player_id' => $p->id,
            'name'      => $p->name,
            'position'  => $p->position,
            'skill'     => '~' . max(1, min(99, $p->general_skill + mt_rand(-10, 10))),
            'approx'    => true,
        ], $players);
    }
}
