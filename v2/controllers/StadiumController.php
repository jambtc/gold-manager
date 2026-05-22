<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Team;
use app\models\Stadium;
use yii\web\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;

class StadiumController extends Controller
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
     * Displays stadium management page for the user's team.
     */
    public function actionView(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }

        $stadium = Stadium::findOne(['team_id' => $team->id]);
        if (!$stadium) {
            // Create default stadium if missing
            $stadium = new Stadium();
            $stadium->team_id = $team->id;
            $stadium->name = $team->name . ' Stadium';
            $stadium->save();
        }

        return $this->render('view', [
            'team' => $team,
            'stadium' => $stadium,
        ]);
    }

    /**
     * Upgrade stadium action.
     */
    public function actionUpgrade(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $stadium = Stadium::findOne(['team_id' => $team->id]);
        if (!$stadium) throw new NotFoundHttpException('Stadio non trovato.');

        if ($stadium->upgrade()) {
            Yii::$app->session->setFlash('success', "Stadio potenziato con successo! Nuova capacità: {$stadium->capacity} posti.");
        } else {
            Yii::$app->session->setFlash('error', "Budget insufficiente per il potenziamento. Servono €" . number_format($stadium->upgrade_cost, 0, ',', '.') . ".");
        }

        return $this->redirect(['view']);
    }

    /**
     * Update ticket price via AJAX or Form.
     */
    public function actionUpdatePrice(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['view']);

        $stadium = Stadium::findOne(['team_id' => $team->id]);
        if (!$stadium) return $this->redirect(['view']);

        $compPrice     = (int)Yii::$app->request->post('ticket_price', $stadium->ticket_price);
        $friendlyPrice = (int)Yii::$app->request->post('friendly_ticket_price', $stadium->friendly_ticket_price);

        $stadium->ticket_price          = max(5, min(200, $compPrice));
        $stadium->friendly_ticket_price = max(0, min(100, $friendlyPrice));
        $stadium->save();

        Yii::$app->session->setFlash('success', "Prezzi aggiornati: campionato €{$stadium->ticket_price} · amichevole €{$stadium->friendly_ticket_price}.");
        return $this->redirect(['view']);
    }
}
