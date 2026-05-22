<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Competition;
use app\models\Standing;
use app\models\Team;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class StandingController extends Controller
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
     * Displays the standing for a specific competition.
     */
    public function actionIndex(int $competitionId = null): string
    {
        // Default: show the competition the logged-in manager belongs to
        if ($competitionId === null) {
            $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
            if ($myTeam) {
                $myStanding = Standing::findOne(['team_id' => $myTeam->id]);
                $competitionId = $myStanding?->competition_id;
            }
            // Fallback: first competition ordered by tier desc (lowest tier first)
            if (!$competitionId) {
                $comp = Competition::find()
                    ->where(['!=', 'type', 'friendly'])
                    ->orderBy(['tier' => SORT_DESC, 'id' => SORT_ASC])
                    ->one();
                $competitionId = $comp?->id;
            }
        }

        $competition = Competition::findOne($competitionId);
        if (!$competition) {
            throw new NotFoundHttpException('Competizione non trovata.');
        }

        $standings = Standing::find()
            ->where(['competition_id' => $competitionId])
            ->orderBy(new \yii\db\Expression('points DESC, (goals_for - goals_against) DESC, goals_for DESC'))
            ->all();

        // All non-friendly competitions for the switcher
        $allComps = Competition::find()
            ->where(['!=', 'type', 'friendly'])
            ->orderBy(['tier' => SORT_ASC, 'group_number' => SORT_ASC])
            ->all();

        // Highlight my team
        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);

        return $this->render('index', [
            'competition' => $competition,
            'standings'   => $standings,
            'allComps'    => $allComps,
            'myTeamId'    => $myTeam?->id,
        ]);
    }
}
