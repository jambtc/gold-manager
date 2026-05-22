<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Competition;
use app\models\Team;
use app\models\Player;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

use yii\web\Response;

class TeamController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays the squad of the logged-in user's team.
     */
    public function actionView(): Response|string
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        
        if (!$team) {
            // If no team, maybe redirect to creation (future step)
            return $this->redirect(['/site/index']);
        }

        $players = Player::find()
            ->where(['team_id' => $team->id])
            ->orderBy(['position' => SORT_ASC, 'general_skill' => SORT_DESC])
            ->all();

        $season = (int) Competition::find()->where(['!=', 'type', 'friendly'])->max('season');
        if ($season <= 0) {
            $season = 1;
        }

        $seasonStatsRows = Yii::$app->db->createCommand(
            'SELECT
                ps.player_id,
                COALESCE(SUM(ps.goals), 0) AS goals,
                COALESCE(SUM(ps.assists), 0) AS assists,
                COALESCE(SUM(ps.yellow_cards), 0) AS yellow_cards,
                COALESCE(SUM(ps.red_cards), 0) AS red_cards
             FROM {{%player_stat}} ps
             JOIN {{%fixture}} f ON f.id = ps.fixture_id
             JOIN {{%competition}} c ON c.id = f.competition_id
             WHERE ps.team_id = :teamId
               AND c.type <> "friendly"
               AND c.season = :season
             GROUP BY ps.player_id',
            [':teamId' => $team->id, ':season' => $season]
        )->queryAll();

        $seasonStatsMap = [];
        foreach ($seasonStatsRows as $row) {
            $seasonStatsMap[(int) $row['player_id']] = [
                'goals' => (int) $row['goals'],
                'assists' => (int) $row['assists'],
                'yellow_cards' => (int) $row['yellow_cards'],
                'red_cards' => (int) $row['red_cards'],
            ];
        }

        return $this->render('view', [
            'team' => $team,
            'players' => $players,
            'valuator' => Yii::$app->playerValuator,
            'seasonStatsMap' => $seasonStatsMap,
            'season' => $season,
        ]);
    }
}
