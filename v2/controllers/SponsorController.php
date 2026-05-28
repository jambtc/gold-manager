<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuctionService;
use app\components\NewsService;
use app\components\SponsorService;
use app\models\MarketBid;
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
        $pendingBids = MarketBid::find()
            ->where([
                'team_id' => (int) $team->id,
                'market_type' => MarketBid::TYPE_SPONSOR,
                'status' => MarketBid::STATUS_PENDING,
            ])
            ->all();
        $pendingBySponsor = [];
        foreach ($pendingBids as $bid) {
            $pendingBySponsor[(int) $bid->market_ref_id] = $bid;
        }

        return $this->render('index', [
            'team' => $team,
            'activeSponsor' => $activeSponsor,
            'availableSponsors' => $availableSponsors,
            'history' => $history,
            'prestige' => $prestige,
            'pendingBySponsor' => $pendingBySponsor,
        ]);
    }

    /**
     * Places/updates sponsor auction bid.
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
            Yii::$app->session->setFlash('error', Yii::t('app', 'Sponsor not available for the current club level.'));
            return $this->redirect(['index']);
        }

        $offered = (int) Yii::$app->request->post('offered_fee', 0);
        if ($offered <= 0) {
            $offered = max(1000, (int) round((int) $sponsor->base_payment * 0.10));
        }
        if ((int) $team->budget < $offered) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Insufficient budget to place this sponsor offer.'));
            return $this->redirect(['index']);
        }

        $bid = AuctionService::placeOrUpdateBid(
            (int) $team->id,
            MarketBid::TYPE_SPONSOR,
            (int) $sponsor->id,
            $offered
        );
        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'Sponsor offer sent: €{amount} (expires {date}).', [
                '{amount}' => number_format((int) $bid->bid_amount, 0, ',', '.'),
                '{date}'   => date('d/m H:i', (int) $bid->expires_at),
            ])
        );
        return $this->redirect(['index']);
    }

    public function actionRaiseOffer(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $sponsor = Sponsor::findOne($id);
        if (!$sponsor) throw new NotFoundHttpException();

        $existing = MarketBid::findOne([
            'team_id'       => (int) $team->id,
            'market_type'   => MarketBid::TYPE_SPONSOR,
            'market_ref_id' => (int) $sponsor->id,
            'status'        => MarketBid::STATUS_PENDING,
        ]);
        if (!$existing) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'No active bid to raise.'));
            return $this->redirect(['index']);
        }
        $base   = (int) $existing->bid_amount;
        $newBid = max($base + 1000, (int) round($base * 1.15));
        if ((int) $team->budget < $newBid) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Insufficient budget for bid raise.'));
            return $this->redirect(['index']);
        }

        $bid = AuctionService::placeOrUpdateBid(
            (int) $team->id,
            MarketBid::TYPE_SPONSOR,
            (int) $sponsor->id,
            $newBid
        );
        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'Sponsor offer raised to €{amount}.', ['{amount}' => number_format((int) $bid->bid_amount, 0, ',', '.')])
        );
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
            Yii::$app->session->setFlash('error', Yii::t('app', 'No active sponsor contract to renew.'));
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
            Yii::t('app', 'Contract renewed with {name}. Bonus €{amount}', [
                '{name}'   => $active->sponsor->name,
                '{amount}' => number_format($renewalBonus, 0, ',', '.'),
            ])
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
            Yii::$app->session->setFlash('error', Yii::t('app', 'No active sponsor contract to terminate.'));
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

        Yii::$app->session->setFlash('warning', Yii::t('app', 'Contract with {name} terminated. Penalty €{amount}', [
            '{name}'   => $name,
            '{amount}' => number_format($penalty, 0, ',', '.'),
        ]));
        return $this->redirect(['index']);
    }
}
