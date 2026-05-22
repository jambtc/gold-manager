<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\NewsService;
use app\components\SponsorService;
use app\models\NewsItem;
use app\models\Sponsor;
use Yii;
use app\models\Team;
use app\models\TeamSponsor;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

class SponsorController extends Controller
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
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'sign' => ['post'],
                    'renew' => ['post'],
                    'terminate' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Displays available and active sponsors.
     */
    public function actionIndex(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        SponsorService::expireContracts();

        $activeSponsor = SponsorService::getActiveContractForTeam((int) $team->id);
        $availableSponsors = SponsorService::findAvailableSponsorsForTeam($team);
        if ($activeSponsor) {
            $availableSponsors = array_values(array_filter(
                $availableSponsors,
                static fn (Sponsor $s): bool => (int) $s->id !== (int) $activeSponsor->sponsor_id
            ));
        }
        $prestige = SponsorService::computeTeamPrestige($team);
        $history = TeamSponsor::find()
            ->with('sponsor')
            ->where(['team_id' => $team->id])
            ->orderBy(['signed_at' => SORT_DESC, 'id' => SORT_DESC])
            ->all();

        return $this->render('index', [
            'team' => $team,
            'activeSponsor' => $activeSponsor,
            'availableSponsors' => $availableSponsors,
            'history' => $history,
            'prestige' => $prestige,
        ]);
    }

    /**
     * Signs a sponsorship contract.
     */
    public function actionSign(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }
        $sponsor = Sponsor::findOne($id);
        if (!$sponsor) {
            throw new NotFoundHttpException();
        }

        $allowed = false;
        foreach (SponsorService::findAvailableSponsorsForTeam($team) as $offer) {
            if ((int) $offer->id === (int) $sponsor->id) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            Yii::$app->session->setFlash('error', 'Sponsor non disponibile per il livello attuale del club.');
            return $this->redirect(['index']);
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            SponsorService::expireContracts(false);
            $active = SponsorService::getActiveContractForTeam((int) $team->id);

            $switchPenalty = 0;
            if ($active) {
                if ((int) $active->sponsor_id === (int) $sponsor->id) {
                    Yii::$app->session->setFlash('info', "Hai già {$sponsor->name} come sponsor attivo.");
                    $tx->rollBack();
                    return $this->redirect(['index']);
                }
                $active->status = TeamSponsor::STATUS_TERMINATED;
                $active->ends_at = time();
                $active->save(false, ['status', 'ends_at']);

                if ($active->sponsor) {
                    $switchPenalty = (int) round($active->sponsor->base_payment * 0.05);
                }
            }

            $now = time();
            $contract = new TeamSponsor();
            $contract->team_id = (int) $team->id;
            $contract->sponsor_id = (int) $sponsor->id;
            $contract->signed_at = $now;
            $contract->ends_at = $now + SponsorService::contractDurationSeconds((int) $sponsor->duration_seasons);
            $contract->status = TeamSponsor::STATUS_ACTIVE;
            $contract->save(false);

            $bonus = (int) round($sponsor->base_payment * 0.10);
            $team->budget += ($bonus - $switchPenalty);
            $team->save(false);

            NewsService::create(
                (int) $team->user_id,
                NewsItem::CAT_FINANCE,
                '=',
                "Nuovo sponsor: {$sponsor->name}",
                sprintf(
                    'Contratto firmato (%d stagione/i). Bonus firma €%s%s.',
                    (int) $sponsor->duration_seasons,
                    number_format($bonus, 0, ',', '.'),
                    $switchPenalty > 0 ? ' · Penale cambio €' . number_format($switchPenalty, 0, ',', '.') : ''
                ),
                Yii::$app->urlManager->createUrl(['/sponsor/index']),
                1
            );

            $tx->commit();
            Yii::$app->session->setFlash(
                'success',
                "Contratto firmato con {$sponsor->name}. Bonus €" . number_format($bonus, 0, ',', '.')
                . ($switchPenalty > 0 ? " (penale cambio €" . number_format($switchPenalty, 0, ',', '.') . ")." : '.')
            );
        } catch (\Throwable $e) {
            $tx->rollBack();
            Yii::$app->session->setFlash('error', 'Errore firma sponsor: ' . $e->getMessage());
        }
        return $this->redirect(['index']);
    }

    public function actionRenew(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }

        SponsorService::expireContracts(false);
        $active = SponsorService::getActiveContractForTeam((int) $team->id);
        if (!$active || !$active->sponsor) {
            Yii::$app->session->setFlash('error', 'Nessun contratto sponsor attivo da rinnovare.');
            return $this->redirect(['index']);
        }

        $active->ends_at += SponsorService::contractDurationSeconds((int) $active->sponsor->duration_seasons);
        $active->save(false, ['ends_at']);

        $renewalBonus = (int) round($active->sponsor->base_payment * 0.05);
        $team->budget += $renewalBonus;
        $team->save(false);

        NewsService::create(
            (int) $team->user_id,
            NewsItem::CAT_FINANCE,
            'R',
            "Rinnovo sponsor: {$active->sponsor->name}",
            sprintf(
                'Contratto esteso fino al %s. Bonus rinnovo €%s.',
                date('d/m/Y', (int) $active->ends_at),
                number_format($renewalBonus, 0, ',', '.')
            ),
            Yii::$app->urlManager->createUrl(['/sponsor/index'])
        );

        Yii::$app->session->setFlash(
            'success',
            "Contratto rinnovato con {$active->sponsor->name}. Bonus €" . number_format($renewalBonus, 0, ',', '.')
        );
        return $this->redirect(['index']);
    }

    public function actionTerminate(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }

        SponsorService::expireContracts(false);
        $active = SponsorService::getActiveContractForTeam((int) $team->id);
        if (!$active || !$active->sponsor) {
            Yii::$app->session->setFlash('error', 'Nessun contratto sponsor attivo da terminare.');
            return $this->redirect(['index']);
        }

        $name = $active->sponsor->name;
        $penalty = (int) round($active->sponsor->base_payment * 0.08);

        $active->status = TeamSponsor::STATUS_TERMINATED;
        $active->ends_at = time();
        $active->save(false, ['status', 'ends_at']);

        $team->budget -= $penalty;
        $team->save(false);

        NewsService::create(
            (int) $team->user_id,
            NewsItem::CAT_FINANCE,
            'X',
            "Rescissione sponsor: {$name}",
            sprintf('Contratto terminato anticipatamente. Penale €%s.', number_format($penalty, 0, ',', '.')),
            Yii::$app->urlManager->createUrl(['/sponsor/index']),
            1
        );

        Yii::$app->session->setFlash('warning', "Contratto con {$name} terminato. Penale €" . number_format($penalty, 0, ',', '.'));
        return $this->redirect(['index']);
    }
}
