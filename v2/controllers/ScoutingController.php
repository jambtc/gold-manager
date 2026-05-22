<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\ScoutingService;
use app\models\ScoutingReport;
use app\models\Team;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ScoutingController extends Controller
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
                'actions' => ['scout' => ['post'], 'dismiss' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $hasScout = ScoutingService::canScout((int) $team->id);

        $pending = ScoutingReport::find()
            ->where(['team_id' => $team->id, 'status' => 'pending'])
            ->with('player')->orderBy(['ready_at' => SORT_ASC])->all();

        $ready = ScoutingReport::find()
            ->where(['team_id' => $team->id, 'status' => 'ready'])
            ->with('player')->orderBy(['ready_at' => SORT_DESC])->all();

        $archived = ScoutingReport::find()
            ->where(['team_id' => $team->id, 'status' => 'dismissed'])
            ->with('player')->orderBy(['id' => SORT_DESC])->limit(20)->all();

        return $this->render('index', compact('team', 'hasScout', 'pending', 'ready', 'archived'));
    }

    public function actionReport(int $id): string
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $report = ScoutingReport::findOne(['id' => $id, 'team_id' => $team?->id ?? 0, 'status' => 'ready']);
        if (!$report) throw new NotFoundHttpException('Rapporto non trovato.');

        $data = $report->report_text ? (json_decode($report->report_text, true) ?? []) : [];
        return $this->render('report', compact('report', 'data'));
    }

    public function actionScout(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $playerId = (int) Yii::$app->request->post('player_id');
        if (!$playerId) {
            Yii::$app->session->setFlash('error', 'Giocatore non specificato.');
            return $this->redirect(['index']);
        }

        if (!ScoutingService::canScout((int) $team->id)) {
            Yii::$app->session->setFlash('error', 'Devi assumere uno scout prima di poter osservare i giocatori.');
            return $this->redirect(['/staff/view']);
        }

        if (ScoutingService::isPending((int) $team->id, $playerId)) {
            Yii::$app->session->setFlash('info', 'Questo giocatore è già sotto osservazione.');
            return $this->redirect(['index']);
        }

        $report = ScoutingService::queueReport((int) $team->id, $playerId);
        $days   = max(1, (int) ceil(($report->ready_at - time()) / 86400));

        Yii::$app->session->setFlash('success', "Giocatore sotto osservazione. Rapporto in {$days} giorno/i.");
        return $this->redirect(['index']);
    }

    public function actionDismiss(int $id): Response
    {
        $team   = Team::findOne(['user_id' => Yii::$app->user->id]);
        $report = ScoutingReport::findOne(['id' => $id, 'team_id' => $team?->id ?? 0]);
        if ($report) {
            $report->status = 'dismissed';
            $report->save(false);
        }
        return $this->redirect(['index']);
    }
}
