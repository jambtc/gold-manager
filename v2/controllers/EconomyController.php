<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\SponsorService;
use Yii;
use app\models\Team;
use app\models\Contract;
use app\models\Staff;
use app\models\Stadium;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

class EconomyController extends Controller
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
     * Financial Dashboard.
     */
    public function actionIndex(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        // Expenses
        $playerWages = (int) Contract::find()
            ->where(['team_id' => $team->id, 'status' => Contract::STATUS_ACTIVE])
            ->sum('salary');
        $staffWages = (int) Staff::find()
            ->where(['team_id' => $team->id])
            ->sum('salary');

        // Income
        $stadium = Stadium::findOne(['team_id' => $team->id]);
        $weeklyStadiumIncome = $stadium ? $stadium->calculateMatchRevenue(0.7) : 0; // Avg 70% attendance
        
        $sponsor = SponsorService::getActiveContractForTeam((int) $team->id);
        $weeklySponsorIncome = SponsorService::weeklyInstallment($sponsor);

        return $this->render('index', [
            'team' => $team,
            'playerWages' => $playerWages,
            'staffWages' => $staffWages,
            'weeklyStadiumIncome' => $weeklyStadiumIncome,
            'weeklySponsorIncome' => $weeklySponsorIncome,
        ]);
    }
}
