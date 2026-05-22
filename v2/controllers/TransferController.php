<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\NewsService;
use app\components\TelegramService;
use app\components\TransferWindowService;
use app\models\Competition;
use app\models\Contract;
use app\models\NewsItem;
use app\models\Player;
use app\models\PlayerPool;
use app\models\Team;
use app\models\Transfer;
use app\models\TransferOffer;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class TransferController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'list-player' => ['post'],
                    'bid' => ['post'],
                    'make-offer' => ['post'],
                    'accept-offer' => ['post'],
                    'reject-offer' => ['post'],
                    'delist' => ['post'],
                    'sign-free-agent' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Transfer market listing (listed players + free agent pool + own offers).
     */
    public function actionMarket(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }

        $this->expireStaleOffers();

        $search   = (string) Yii::$app->request->get('q', '');
        $pos      = strtoupper((string) Yii::$app->request->get('pos', ''));
        $minSkill = (int) Yii::$app->request->get('min_skill', 0);
        $maxFee   = (int) Yii::$app->request->get('max_fee', 0);
        $maxAge   = (int) Yii::$app->request->get('max_age', 0);
        $activeTab = in_array(Yii::$app->request->get('tab'), ['listed','pool','offers'], true)
            ? Yii::$app->request->get('tab')
            : 'listed';

        $listedQuery = Transfer::find()
            ->alias('t')
            ->where(['t.status' => [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE]])
            ->andWhere(['t.transfer_type' => [Transfer::TYPE_SALE, Transfer::TYPE_LOAN]])
            ->innerJoin(['p' => Player::tableName()], 'p.id = t.player_id')
            ->with(['player', 'fromTeam']);

        if ($search !== '') {
            $listedQuery->andWhere(['like', 'p.name', $search]);
        }
        if ($pos !== '') {
            $listedQuery->andWhere(['p.position' => $pos]);
        }
        if ($minSkill > 0) {
            $listedQuery->andWhere(['>=', 'p.general_skill', $minSkill]);
        }
        if ($maxFee > 0) {
            $listedQuery->andWhere(['<=', 'COALESCE(t.asking_fee, t.fee)', $maxFee]);
        }
        if ($maxAge > 0) {
            $listedQuery->andWhere(['<=', 'p.age', $maxAge]);
        }
        $transfers = $listedQuery->orderBy(['t.listed_at' => SORT_DESC])->all();

        $poolQuery = PlayerPool::find()
            ->alias('pp')
            ->innerJoin(['p' => Player::tableName()], 'p.id = pp.player_id')
            ->where(['p.team_id' => null])
            ->andWhere(['>', 'pp.expires_at', time()])
            ->with(['player']);
        if ($search !== '') {
            $poolQuery->andWhere(['like', 'p.name', $search]);
        }
        if ($pos !== '') {
            $poolQuery->andWhere(['p.position' => $pos]);
        }
        if ($minSkill > 0) {
            $poolQuery->andWhere(['>=', 'p.general_skill', $minSkill]);
        }
        if ($maxAge > 0) {
            $poolQuery->andWhere(['<=', 'p.age', $maxAge]);
        }
        $poolPlayers = $poolQuery->orderBy(['p.general_skill' => SORT_DESC, 'pp.id' => SORT_ASC])->all();

        $myOffersSent = TransferOffer::find()
            ->where(['from_team_id' => $team->id])
            ->with(['player', 'transfer', 'toTeam'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->all();

        $incomingOffers = TransferOffer::find()
            ->where(['to_team_id' => $team->id, 'status' => TransferOffer::STATUS_PENDING])
            ->with(['player', 'transfer', 'fromTeam'])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        return $this->render('market', [
            'team'           => $team,
            'transfers'      => $transfers,
            'poolPlayers'    => $poolPlayers,
            'myOffersSent'   => $myOffersSent,
            'incomingOffers' => $incomingOffers,
            'pendingCount'   => count($incomingOffers),
            'valuator'       => Yii::$app->playerValuator,
            'q'              => $search,
            'pos'            => $pos,
            'minSkill'       => $minSkill,
            'maxFee'         => $maxFee,
            'maxAge'         => $maxAge,
            'windowOpen'     => TransferWindowService::isOpen(null, (int) $team->id),
            'nextOpenAt'     => TransferWindowService::nextOpeningTimestamp(null, (int) $team->id),
            'activeTab'      => $activeTab,
        ]);
    }

    /**
     * Lists a player from your squad on the market.
     */
    public function actionListPlayer(): Response
    {
        if (!$this->assertWindowOpen()) {
            return $this->redirect(['market']);
        }

        $id = (int) (Yii::$app->request->post('id') ?: Yii::$app->request->get('id'));
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $player = Player::findOne($id);
        if (!$team || !$player) {
            throw new NotFoundHttpException('Giocatore non trovato.');
        }
        if ((int) $player->team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', 'Non puoi vendere un giocatore che non ti appartiene.');
            return $this->redirect(['/team/view']);
        }

        $alreadyListed = Transfer::find()
            ->where(['player_id' => $player->id])
            ->andWhere(['status' => [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE]])
            ->exists();
        if ($alreadyListed) {
            Yii::$app->session->setFlash('info', 'Giocatore già sul mercato.');
            return $this->redirect(['market']);
        }

        $askingFee = (int) Yii::$app->request->post('asking_fee', 0);
        if ($askingFee <= 0) {
            $askingFee = (int) Yii::$app->playerValuator->marketValue($player);
        }
        $transferType = (string) Yii::$app->request->post('transfer_type', Transfer::TYPE_SALE);
        if (!in_array($transferType, [Transfer::TYPE_SALE, Transfer::TYPE_LOAN], true)) {
            $transferType = Transfer::TYPE_SALE;
        }

        $transfer = new Transfer();
        $transfer->player_id = (int) $player->id;
        $transfer->from_team_id = (int) $team->id;
        $transfer->fee = $askingFee;
        $transfer->asking_fee = $askingFee;
        $transfer->proposed_salary = (int) Yii::$app->playerValuator->suggestedSalary($player);
        $transfer->status = Transfer::STATUS_LISTED;
        $transfer->transfer_type = $transferType;
        $transfer->loan_return_season = $transferType === Transfer::TYPE_LOAN ? $this->currentSeason() + 1 : null;
        $transfer->listed_at = time();
        $transfer->save(false);

        Yii::$app->session->setFlash(
            'success',
            sprintf(
                '%s è sul mercato (%s) a €%s.',
                $player->name,
                $transferType === Transfer::TYPE_LOAN ? 'prestito' : 'vendita',
                number_format($askingFee, 0, ',', '.')
            )
        );
        return $this->redirect(['market']);
    }

    /**
     * Legacy buy button: mapped to offer flow with asking price.
     */
    public function actionBid(int $id): Response
    {
        $transfer = Transfer::findOne($id);
        if (!$transfer) {
            throw new NotFoundHttpException('Offerta non valida o scaduta.');
        }
        $offered = (int) ($transfer->asking_fee ?: $transfer->fee);
        return $this->createOrUpdateOffer($transfer, $offered);
    }

    /**
     * Creates/updates an offer for listed transfer.
     */
    public function actionMakeOffer(int $id): Response
    {
        $transfer = Transfer::findOne($id);
        if (!$transfer) {
            throw new NotFoundHttpException('Trasferimento non trovato.');
        }
        $offered = (int) Yii::$app->request->post('offered_fee', 0);
        if ($offered <= 0) {
            $offered = (int) ($transfer->asking_fee ?: $transfer->fee);
        }
        return $this->createOrUpdateOffer($transfer, $offered);
    }

    /**
     * Seller accepts one pending offer.
     */
    public function actionAcceptOffer(int $id): Response
    {
        if (!$this->assertWindowOpen()) {
            return $this->redirect(['market']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $offer = TransferOffer::findOne($id);
        if (!$team || !$offer) {
            throw new NotFoundHttpException('Offerta non trovata.');
        }
        if ((int) $offer->to_team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', 'Offerta non di tua competenza.');
            return $this->redirect(['market']);
        }
        if ($offer->status !== TransferOffer::STATUS_PENDING || (int) $offer->expires_at <= time()) {
            Yii::$app->session->setFlash('error', 'Offerta non più valida.');
            return $this->redirect(['market']);
        }

        $transfer = $offer->transfer;
        if (!$transfer || !in_array($transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
            Yii::$app->session->setFlash('error', 'Trasferimento non accettabile.');
            return $this->redirect(['market']);
        }

        $buyer = Team::findOne((int) $offer->from_team_id);
        if (!$buyer) {
            Yii::$app->session->setFlash('error', 'Squadra offerente non trovata.');
            return $this->redirect(['market']);
        }
        if ($transfer->transfer_type !== Transfer::TYPE_FREE && (int) $buyer->budget < (int) $offer->offered_fee) {
            Yii::$app->session->setFlash('error', 'Budget offerente insufficiente al momento della conferma.');
            return $this->redirect(['market']);
        }

        $offer->status = TransferOffer::STATUS_ACCEPTED;
        $offer->save(false, ['status', 'updated_at']);
        TransferOffer::updateAll(
            ['status' => TransferOffer::STATUS_REJECTED, 'updated_at' => time()],
            [
                'and',
                ['transfer_id' => $transfer->id],
                ['status' => TransferOffer::STATUS_PENDING],
                ['!=', 'id', $offer->id],
            ]
        );

        $transfer->to_team_id = (int) $buyer->id;
        $transfer->offered_by_team = (int) $buyer->id;
        $transfer->fee = (int) $offer->offered_fee;
        $transfer->status = Transfer::STATUS_ACCEPTED;
        if ($transfer->transfer_type === Transfer::TYPE_LOAN && !$transfer->loan_return_season) {
            $transfer->loan_return_season = $this->currentSeason() + 1;
        }
        $transfer->save(false);

        if ($transfer->complete($this->currentSeason())) {
            Yii::$app->session->setFlash('success', 'Offerta accettata e trasferimento completato.');
            // News al compratore
            if ($buyer->user_id) {
                $playerName = Player::findOne($transfer->player_id)?->name ?? 'giocatore';
                NewsService::create(
                    (int) $buyer->user_id,
                    NewsItem::CAT_TRANSFER,
                    '✅',
                    "Offerta accettata: {$playerName}",
                    sprintf('%s ha accettato €%s', $team->name, number_format((int) $offer->offered_fee, 0, ',', '.')),
                    Yii::$app->urlManager->createUrl(['/transfer/market'])
                );
                TelegramService::sendToUser((int) $buyer->user_id,
                    "✅ <b>Offerta accettata!</b>\n{$playerName} acquistato per €" . number_format((int) $offer->offered_fee, 0, ',', '.'));
            }
        } else {
            Yii::$app->session->setFlash('error', 'Errore durante finalizzazione trasferimento.');
        }

        return $this->redirect(['market']);
    }

    public function actionRejectOffer(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $offer = TransferOffer::findOne($id);
        if (!$team || !$offer) {
            throw new NotFoundHttpException('Offerta non trovata.');
        }
        if ((int) $offer->to_team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', 'Offerta non di tua competenza.');
            return $this->redirect(['market']);
        }
        if ($offer->status !== TransferOffer::STATUS_PENDING) {
            Yii::$app->session->setFlash('info', 'Offerta già risolta.');
            return $this->redirect(['market']);
        }

        $offer->status = TransferOffer::STATUS_REJECTED;
        $offer->save(false, ['status', 'updated_at']);
        Yii::$app->session->setFlash('success', 'Offerta rifiutata.');

        // News al compratore
        $buyerTeam = Team::findOne((int) $offer->from_team_id);
        if ($buyerTeam?->user_id) {
            $playerName = Player::findOne($offer->player_id)?->name ?? 'giocatore';
            NewsService::create(
                (int) $buyerTeam->user_id,
                NewsItem::CAT_TRANSFER,
                '❌',
                "Offerta rifiutata: {$playerName}",
                sprintf('%s ha rifiutato la tua offerta di €%s', $team->name, number_format((int) $offer->offered_fee, 0, ',', '.')),
                Yii::$app->urlManager->createUrl(['/transfer/market'])
            );
        }

        return $this->redirect(['market']);
    }

    public function actionDelist(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $transfer = Transfer::findOne($id);
        if (!$team || !$transfer) {
            throw new NotFoundHttpException('Trasferimento non trovato.');
        }
        if ((int) $transfer->from_team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', 'Non puoi ritirare questa inserzione.');
            return $this->redirect(['market']);
        }
        if (!in_array($transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
            Yii::$app->session->setFlash('error', 'Inserzione non più ritirabile.');
            return $this->redirect(['market']);
        }

        $transfer->status = Transfer::STATUS_CANCELLED;
        $transfer->resolved_at = time();
        $transfer->save(false, ['status', 'resolved_at', 'updated_at']);
        TransferOffer::updateAll(
            ['status' => TransferOffer::STATUS_WITHDRAWN, 'updated_at' => time()],
            ['transfer_id' => $transfer->id, 'status' => TransferOffer::STATUS_PENDING]
        );
        Yii::$app->session->setFlash('success', 'Giocatore ritirato dal mercato.');
        return $this->redirect(['market']);
    }

    /**
     * Free agent signing from player_pool.
     */
    public function actionSignFreeAgent(int $id): Response
    {
        if (!$this->assertWindowOpen()) {
            return $this->redirect(['market']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $pool = PlayerPool::findOne($id);
        if (!$team || !$pool || (int) $pool->expires_at <= time()) {
            throw new NotFoundHttpException('Giocatore non disponibile nel pool.');
        }

        $player = $pool->player;
        if (!$player || $player->team_id !== null) {
            Yii::$app->session->setFlash('error', 'Giocatore non più disponibile.');
            if ($pool) {
                $pool->delete();
            }
            return $this->redirect(['market']);
        }

        $player->team_id = (int) $team->id;
        $player->save(false);

        Contract::updateAll(
            ['status' => Contract::STATUS_TRANSFERRED],
            ['player_id' => (int) $player->id, 'status' => Contract::STATUS_ACTIVE]
        );

        $contract = new Contract();
        $contract->player_id = (int) $player->id;
        $contract->team_id = (int) $team->id;
        $contract->salary = (int) $pool->salary_ask;
        $contract->season_start = $this->currentSeason();
        $contract->season_end = $this->currentSeason() + 1;
        $contract->status = Contract::STATUS_ACTIVE;
        $contract->save(false);

        $transfer = new Transfer();
        $transfer->player_id = (int) $player->id;
        $transfer->from_team_id = null;
        $transfer->to_team_id = (int) $team->id;
        $transfer->offered_by_team = (int) $team->id;
        $transfer->fee = 0;
        $transfer->asking_fee = 0;
        $transfer->proposed_salary = (int) $pool->salary_ask;
        $transfer->transfer_type = Transfer::TYPE_FREE;
        $transfer->status = Transfer::STATUS_COMPLETED;
        $transfer->listed_at = time();
        $transfer->resolved_at = time();
        $transfer->save(false);

        $pool->delete();
        Yii::$app->session->setFlash('success', "{$player->name} firmato a parametro zero.");
        return $this->redirect(['/team/view']);
    }

    private function createOrUpdateOffer(Transfer $transfer, int $offered): Response
    {
        if (!$this->assertWindowOpen()) {
            return $this->redirect(['market']);
        }

        if (!in_array($transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
            throw new NotFoundHttpException('Offerta non valida o scaduta.');
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }
        if ((int) $transfer->from_team_id === (int) $team->id) {
            Yii::$app->session->setFlash('error', 'Non puoi fare offerta sul tuo giocatore.');
            return $this->redirect(['market']);
        }

        $offered = max(1, $offered);
        if ($transfer->transfer_type !== Transfer::TYPE_FREE && (int) $team->budget < $offered) {
            Yii::$app->session->setFlash('error', 'Budget insufficiente per l\'offerta proposta.');
            return $this->redirect(['market']);
        }

        $expiresAt = time() + 48 * 3600;
        $offer = TransferOffer::findOne([
            'transfer_id' => (int) $transfer->id,
            'from_team_id' => (int) $team->id,
            'status' => TransferOffer::STATUS_PENDING,
        ]);

        if ($offer) {
            $offer->offered_fee = $offered;
            $offer->expires_at = $expiresAt;
            $offer->save(false, ['offered_fee', 'expires_at', 'updated_at']);
            Yii::$app->session->setFlash('success', 'Offerta aggiornata.');
        } else {
            $offer = new TransferOffer();
            $offer->transfer_id = (int) $transfer->id;
            $offer->from_team_id = (int) $team->id;
            $offer->to_team_id = (int) $transfer->from_team_id;
            $offer->player_id = (int) $transfer->player_id;
            $offer->offered_fee = $offered;
            $offer->status = TransferOffer::STATUS_PENDING;
            $offer->expires_at = $expiresAt;
            $offer->save(false);
            Yii::$app->session->setFlash('success', 'Offerta inviata.');

            // News al venditore
            $sellerTeam = Team::findOne((int) $transfer->from_team_id);
            if ($sellerTeam?->user_id) {
                $playerName = Player::findOne($transfer->player_id)?->name ?? 'giocatore';
                NewsService::create(
                    (int) $sellerTeam->user_id,
                    NewsItem::CAT_TRANSFER,
                    '📬',
                    "Offerta ricevuta per {$playerName}",
                    sprintf('%s offre €%s', $team->name, number_format($offered, 0, ',', '.')),
                    Yii::$app->urlManager->createUrl(['/transfer/market']),
                    1
                );
            }
        }

        if ($transfer->status === Transfer::STATUS_LISTED) {
            $transfer->status = Transfer::STATUS_BID_MADE;
            $transfer->save(false, ['status', 'updated_at']);
        }

        return $this->redirect(['market']);
    }

    private function assertWindowOpen(): bool
    {
        $identity = Yii::$app->user->identity;
        if ($identity && method_exists($identity, 'isAdmin') && $identity->isAdmin()) {
            return true;
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $teamId = $team ? (int) $team->id : null;

        if (TransferWindowService::isOpen(null, $teamId)) {
            return true;
        }

        Yii::$app->session->setFlash(
            'error',
            'Finestra trasferimenti chiusa. Prossima apertura: '
            . date('d/m/Y H:i', TransferWindowService::nextOpeningTimestamp(null, $teamId))
        );
        return false;
    }

    private function currentSeason(): int
    {
        $season = (int) Competition::find()->where(['!=', 'type', 'friendly'])->max('season');
        return $season > 0 ? $season : 1;
    }

    private function expireStaleOffers(): void
    {
        $now = time();
        TransferOffer::updateAll(
            ['status' => TransferOffer::STATUS_WITHDRAWN, 'updated_at' => $now],
            [
                'and',
                ['status' => TransferOffer::STATUS_PENDING],
                ['<', 'expires_at', $now],
            ]
        );
    }
}
