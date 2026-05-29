<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuctionService;
use app\components\MultiplayerSyncService;
use app\components\NewsService;
use app\components\NotificationService;
use app\components\TelegramService;
use app\components\TransferWindowService;
use app\models\Competition;
use app\models\MarketBid;
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
                    'withdraw-bid'    => ['post'],
                ],
            ],
        ];
    }

    /**
     * Transfer market listing (single list: listed players + free agents).
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
        $perPage  = 20;
        $activeTab = in_array(Yii::$app->request->get('tab'), ['listed','offers'], true)
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
        if ($maxFee > 0) {
            $listedQuery->andWhere(['<=', 'COALESCE(t.asking_fee, t.fee)', $maxFee]);
        }
        if ($maxAge > 0) {
            $listedQuery->andWhere(['<=', 'p.age', $maxAge]);
        }
        $transfers = $listedQuery->orderBy(['t.listed_at' => SORT_DESC])->all();
        if ($minSkill > 0) {
            $transfers = array_values(array_filter($transfers, static function (Transfer $t) use ($minSkill): bool {
                return $t->player !== null && (int) $t->player->getNaturalOverall() >= $minSkill;
            }));
        }
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
        if ($maxAge > 0) {
            $poolQuery->andWhere(['<=', 'p.age', $maxAge]);
        }
        $poolPlayers = $poolQuery->orderBy([
            'pp.expires_at' => SORT_ASC,
            'pp.id' => SORT_ASC,
        ])->all();
        if ($minSkill > 0) {
            $poolPlayers = array_values(array_filter($poolPlayers, static function (PlayerPool $pp) use ($minSkill): bool {
                return $pp->player !== null && (int) $pp->player->getNaturalOverall() >= $minSkill;
            }));
        }
        usort($poolPlayers, static function (PlayerPool $a, PlayerPool $b): int {
            $ea = (int) ($a->expires_at ?? 0);
            $eb = (int) ($b->expires_at ?? 0);
            if ($ea !== $eb) {
                return $ea <=> $eb;
            }
            $oa = $a->player ? (int) $a->player->getNaturalOverall() : 0;
            $ob = $b->player ? (int) $b->player->getNaturalOverall() : 0;
            if ($oa !== $ob) {
                return $ob <=> $oa;
            }
            return ((int) $a->id) <=> ((int) $b->id);
        });
        $marketBidRows = MarketBid::find()
            ->where([
                'team_id' => (int) $team->id,
                'status' => MarketBid::STATUS_PENDING,
            ])
            ->andWhere(['market_type' => MarketBid::transferMarketTypes()])
            ->all();
        $marketBidMap = [];
        foreach ($marketBidRows as $row) {
            $marketBidMap[(int) $row->market_ref_id] = $row;
        }

        $marketRows = [];
        foreach ($transfers as $transfer) {
            $player = $transfer->player;
            if (!$player) {
                continue;
            }
            $marketRows[] = [
                'kind' => 'listed',
                'player' => $player,
                'transfer' => $transfer,
                'marketEntry' => null,
                'isOwn' => (bool) ($team && (int) $transfer->from_team_id === (int) $team->id),
                'team' => $transfer->fromTeam,
                'askingFee' => (int) ($transfer->asking_fee ?: $transfer->fee),
                'salaryAsk' => null,
                'expiresAt' => null,
                'sortGroup' => 1,
                'sortTs' => (int) ($transfer->listed_at ?? 0),
            ];
        }
        foreach ($poolPlayers as $marketEntry) {
            $player = $marketEntry->player;
            if (!$player) {
                continue;
            }
            $marketRows[] = [
                'kind' => 'market',
                'player' => $player,
                'transfer' => null,
                'marketEntry' => $marketEntry,
                'isOwn' => false,
                'team' => null,
                'askingFee' => (int) $marketEntry->asking_fee,
                'salaryAsk' => (int) $marketEntry->salary_ask,
                'expiresAt' => (int) $marketEntry->expires_at,
                'sortGroup' => 0,
                'sortTs' => (int) $marketEntry->expires_at,
            ];
        }
        // Rows the user already bid on float to the top within their group.
        $bidRefIds = array_keys($marketBidMap);
        usort($marketRows, static function (array $a, array $b) use ($bidRefIds): int {
            $ga = (int) ($a['sortGroup'] ?? 99);
            $gb = (int) ($b['sortGroup'] ?? 99);
            if ($ga !== $gb) {
                return $ga <=> $gb;
            }
            $hasBidA = (int) in_array((int) ($a['marketEntry']?->id ?? 0), $bidRefIds, true);
            $hasBidB = (int) in_array((int) ($b['marketEntry']?->id ?? 0), $bidRefIds, true);
            if ($hasBidA !== $hasBidB) {
                return $hasBidB <=> $hasBidA; // bid rows first
            }
            $tsa = (int) ($a['sortTs'] ?? 0);
            $tsb = (int) ($b['sortTs'] ?? 0);
            if ($ga === 0) {
                return $tsa <=> $tsb; // free-agent auctions: earliest expiry first
            }
            return $tsb <=> $tsa; // listed transfers: newest first
        });

        $marketTotal = count($marketRows);
        $marketPage = max(1, (int) Yii::$app->request->get('page', 1));
        $marketPages = max(1, (int) ceil($marketTotal / $perPage));
        if ($marketPage > $marketPages) {
            $marketPage = $marketPages;
        }
        $marketRows = array_slice($marketRows, ($marketPage - 1) * $perPage, $perPage);

        $myOffersSent = TransferOffer::find()
            ->where(['from_team_id' => $team->id])
            ->with(['player', 'transfer', 'toTeam'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(30)
            ->all();

        $incomingOffers = TransferOffer::find()
            ->where(['to_team_id' => $team->id, 'status' => TransferOffer::STATUS_PENDING])
            ->with(['player', 'transfer', 'fromTeam'])
            ->orderBy(['expires_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        return $this->render('market', [
            'team'           => $team,
            'marketRows'     => $marketRows,
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
            'windowBounds'   => TransferWindowService::getActiveWindowBounds(null, (int) $team->id),
            'activeTab'      => $activeTab,
            'marketBidMap'   => $marketBidMap,
            'myAuctionBids'  => $marketBidRows,
            'perPage'        => $perPage,
            'marketTotal'    => $marketTotal,
            'marketPage'     => $marketPage,
            'marketPages'    => $marketPages,
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
            throw new NotFoundHttpException(Yii::t('app', 'Player not found.'));
        }
        if ((int) $player->team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You cannot sell a player who does not belong to you.'));
            return $this->redirect(['/team/view']);
        }

        $alreadyListed = Transfer::find()
            ->where(['player_id' => $player->id])
            ->andWhere(['status' => [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE]])
            ->exists();
        if ($alreadyListed) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Player already on the market.'));
            return $this->redirect(['market']);
        }

        // 48h cooldown after manual delist without sale
        $cooldown = Yii::$app->db->createCommand(
            'SELECT unlocks_at FROM {{%transfer_cooldown}} WHERE player_id=:pid',
            [':pid' => $player->id]
        )->queryScalar();
        if ($cooldown && strtotime($cooldown) > time()) {
            $remaining = ceil((strtotime($cooldown) - time()) / 3600);
            Yii::$app->session->setFlash('error', Yii::t('app', 'Player in cooldown: you can re-list in {hours}h.', ['{hours}' => $remaining]));
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
            Yii::t('app', '{name} is on the market ({type}) at €{fee}.', [
                '{name}' => $player->name,
                '{type}' => $transferType === Transfer::TYPE_LOAN ? Yii::t('app', 'loan') : Yii::t('app', 'sale'),
                '{fee}'  => number_format($askingFee, 0, ',', '.'),
            ])
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
            throw new NotFoundHttpException(Yii::t('app', 'Offer invalid or expired.'));
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
            throw new NotFoundHttpException(Yii::t('app', 'Transfer not found.'));
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

        if (!MultiplayerSyncService::ensureOnce('transfer.accept-offer.' . $id, 45)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Request already processed.'));
            return $this->redirect(['market']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $offer = TransferOffer::findOne($id);
        if (!$team || !$offer) {
            throw new NotFoundHttpException(Yii::t('app', 'Offer not found.'));
        }

        $lockToken = MultiplayerSyncService::acquireLock('transfer.accept.' . (int) $offer->transfer_id, 20);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Concurrent operation in progress. Retry in a few seconds.'));
            return $this->redirect(['market']);
        }

        try {
        $requestId = MultiplayerSyncService::currentRequestId('transfer.accept-offer.' . $id);
        $requestSource = MultiplayerSyncService::currentRequestSource();
        if ((int) $offer->to_team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Offer not within your jurisdiction.'));
            return $this->redirect(['market']);
        }
        if ($offer->status !== TransferOffer::STATUS_PENDING || (int) $offer->expires_at <= time()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Offer no longer valid.'));
            return $this->redirect(['market']);
        }

        $transfer = $offer->transfer;
        if (!$transfer || !in_array($transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Transfer not acceptable.'));
            return $this->redirect(['market']);
        }

        $buyer = Team::findOne((int) $offer->from_team_id);
        if (!$buyer) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Bidding team not found.'));
            return $this->redirect(['market']);
        }
        if ($transfer->transfer_type !== Transfer::TYPE_FREE && (int) $buyer->budget < (int) $offer->offered_fee) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Bidder budget insufficient at confirmation time.'));
            return $this->redirect(['market']);
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $now = time();
            $rows = TransferOffer::updateAll(
                [
                    'status' => TransferOffer::STATUS_ACCEPTED,
                    'updated_at' => $now,
                    'request_id' => $requestId,
                    'request_source' => $requestSource,
                ],
                [
                    'id' => (int) $offer->id,
                    'status' => TransferOffer::STATUS_PENDING,
                    'to_team_id' => (int) $team->id,
                ]
            );
            if ($rows !== 1) {
                $tx->rollBack();
                Yii::$app->session->setFlash('info', Yii::t('app', 'Offer already resolved by another process.'));
                return $this->redirect(['market']);
            }

            $transferRows = Transfer::updateAll(
                [
                    'to_team_id' => (int) $buyer->id,
                    'offered_by_team' => (int) $buyer->id,
                    'fee' => (int) $offer->offered_fee,
                    'status' => Transfer::STATUS_ACCEPTED,
                    'loan_return_season' => ($transfer->transfer_type === Transfer::TYPE_LOAN && !$transfer->loan_return_season)
                        ? $this->currentSeason() + 1
                        : $transfer->loan_return_season,
                ],
                ['id' => (int) $transfer->id, 'status' => [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE]]
            );
            if ($transferRows !== 1) {
                $tx->rollBack();
                Yii::$app->session->setFlash('error', Yii::t('app', 'Transfer no longer acceptable.'));
                return $this->redirect(['market']);
            }

        TransferOffer::updateAll(
            [
                'status' => TransferOffer::STATUS_REJECTED,
                'updated_at' => $now,
                'request_id' => $requestId,
                'request_source' => $requestSource,
            ],
            [
                'and',
                ['transfer_id' => $transfer->id],
                ['status' => TransferOffer::STATUS_PENDING],
                ['!=', 'id', $offer->id],
            ]
        );

            $transfer->refresh();

            if ($transfer->complete($this->currentSeason())) {
                $tx->commit();
            Yii::$app->session->setFlash('success', Yii::t('app', 'Offer accepted and transfer completed.'));
            // News al compratore
            if ($buyer->user_id) {
                $playerName = Player::findOne($transfer->player_id)?->name ?? 'giocatore';
                $fmtFee = number_format((int) $offer->offered_fee, 0, ',', '.');
                NotificationService::notify(
                    (int) $buyer->user_id, NewsItem::CAT_TRANSFER, '✅',
                    "Offerta accettata: {$playerName}",
                    "{$team->name} ha accettato €{$fmtFee}",
                    Yii::$app->urlManager->createUrl(['/transfer/market']), 1,
                    "✅ <b>Offerta accettata!</b>\n{$playerName} acquistato per €{$fmtFee}"
                );
            }
            } else {
                $tx->rollBack();
            Yii::$app->session->setFlash('error', Yii::t('app', 'Error during transfer finalisation.'));
            }
        } catch (\Throwable $e) {
            if ($tx->isActive) {
                $tx->rollBack();
            }
            Yii::error('Transfer accept failed: ' . $e->getMessage(), 'sync');
            Yii::$app->session->setFlash('error', Yii::t('app', 'Concurrent error during offer acceptance.'));
        }

        return $this->redirect(['market']);
        } finally {
            MultiplayerSyncService::releaseLock('transfer.accept.' . (int) $offer->transfer_id, $lockToken);
        }
    }

    public function actionRejectOffer(int $id): Response
    {
        if (!MultiplayerSyncService::ensureOnce('transfer.reject-offer.' . $id, 20)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Request already processed.'));
            return $this->redirect(['market']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $offer = TransferOffer::findOne($id);
        if (!$team || !$offer) {
            throw new NotFoundHttpException(Yii::t('app', 'Offer not found.'));
        }
        if ((int) $offer->to_team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Offer not within your jurisdiction.'));
            return $this->redirect(['market']);
        }
        if ($offer->status !== TransferOffer::STATUS_PENDING) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Offer already resolved.'));
            return $this->redirect(['market']);
        }

        $offer->status = TransferOffer::STATUS_REJECTED;
        $offer->request_id = MultiplayerSyncService::currentRequestId('transfer.reject-offer.' . $id);
        $offer->request_source = MultiplayerSyncService::currentRequestSource();
        $offer->save(false, ['status', 'updated_at', 'request_id', 'request_source']);
        Yii::$app->session->setFlash('success', Yii::t('app', 'Offer rejected.'));

        // News al compratore
        $buyerTeam = Team::findOne((int) $offer->from_team_id);
        if ($buyerTeam?->user_id) {
            $playerName = Player::findOne($offer->player_id)?->name ?? 'giocatore';
            $fmtFee = number_format((int) $offer->offered_fee, 0, ',', '.');
            NotificationService::notify(
                (int) $buyerTeam->user_id, NewsItem::CAT_TRANSFER, '❌',
                "Offerta rifiutata: {$playerName}",
                "{$team->name} ha rifiutato la tua offerta di €{$fmtFee}",
                Yii::$app->urlManager->createUrl(['/transfer/market']), 0,
                "❌ <b>Offerta rifiutata</b> per {$playerName}\n{$team->name} ha rifiutato €{$fmtFee}"
            );
        }

        return $this->redirect(['market']);
    }

    public function actionDelist(int $id): Response
    {
        if (!MultiplayerSyncService::ensureOnce('transfer.delist.' . $id, 20)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Request already processed.'));
            return $this->redirect(['market']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $transfer = Transfer::findOne($id);
        if (!$team || !$transfer) {
            throw new NotFoundHttpException(Yii::t('app', 'Transfer not found.'));
        }
        if ((int) $transfer->from_team_id !== (int) $team->id) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You cannot withdraw this listing.'));
            return $this->redirect(['market']);
        }
        if (!in_array($transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Listing can no longer be withdrawn.'));
            return $this->redirect(['market']);
        }

        $transfer->status = Transfer::STATUS_CANCELLED;
        $transfer->resolved_at = time();
        $transfer->save(false, ['status', 'resolved_at', 'updated_at']);

        // Insert/update 48h cooldown so player cannot be re-listed immediately
        $unlocksAt = date('Y-m-d H:i:s', time() + 48 * 3600);
        Yii::$app->db->createCommand(
            'INSERT INTO {{%transfer_cooldown}} (player_id, team_id, unlocks_at)
             VALUES (:pid, :tid, :ua)
             ON DUPLICATE KEY UPDATE team_id=:tid, unlocks_at=:ua',
            [':pid' => $transfer->player_id, ':tid' => $team->id, ':ua' => $unlocksAt]
        )->execute();
        $requestId = MultiplayerSyncService::currentRequestId('transfer.delist.' . $id);
        $requestSource = MultiplayerSyncService::currentRequestSource();
        TransferOffer::updateAll(
            [
                'status' => TransferOffer::STATUS_WITHDRAWN,
                'updated_at' => time(),
                'request_id' => $requestId,
                'request_source' => $requestSource,
            ],
            ['transfer_id' => $transfer->id, 'status' => TransferOffer::STATUS_PENDING]
        );
        Yii::$app->session->setFlash('success', Yii::t('app', 'Player withdrawn from market.'));
        return $this->redirect(['market']);
    }

    /**
     * Places/updates free-agent auction bid from unified transfer market.
     */
    public function actionSignFreeAgent(int $id): Response
    {
        if (!$this->assertWindowOpen()) {
            return $this->redirect(['market']);
        }

        if (!MultiplayerSyncService::ensureOnce('transfer.sign-free-agent.' . $id, 45)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Request already processed.'));
            return $this->redirect(['/team/view']);
        }

        $lockToken = MultiplayerSyncService::acquireLock('transfer.market.' . $id, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Concurrent operation in progress. Please retry.'));
            return $this->redirect(['market']);
        }

        try {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        $marketEntry = PlayerPool::findOne($id);
        if (!$team || !$marketEntry) {
            throw new NotFoundHttpException(Yii::t('app', 'Player not available on the market.'));
        }
        if ((int) $marketEntry->expires_at <= time()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Auction ended: you can no longer place this bid.'));
            return $this->redirect(['market', 'tab' => 'listed']);
        }

        $player = $marketEntry->player;
        if (!$player || $player->team_id !== null) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Player no longer available.'));
            return $this->redirect(['market']);
        }

        $offered = (int) Yii::$app->request->post('offered_fee', 0);
        if ($offered <= 0) {
            $offered = max(1, (int) $marketEntry->asking_fee);
        }
        if ((int) $team->budget < $offered) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Insufficient budget to place this bid.'));
            return $this->redirect(['market']);
        }

        $bid = AuctionService::placeOrUpdateBid(
            (int) $team->id,
            MarketBid::TYPE_TRANSFER_MARKET,
            (int) $marketEntry->id,
            $offered
        );
        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'Bid placed on {name}: €{amount} (expires {date}).', [
                '{name}'   => $player->name,
                '{amount}' => number_format((int) $bid->bid_amount, 0, ',', '.'),
                '{date}'   => date('d/m H:i', (int) $bid->expires_at),
            ])
        );
        return $this->redirect(['market', 'tab' => 'listed']);
        } finally {
            MultiplayerSyncService::releaseLock('transfer.market.' . $id, $lockToken);
        }
    }

    public function actionWithdrawBid(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }

        $bid = MarketBid::findOne([
            'id'      => $id,
            'team_id' => (int) $team->id,
            'status'  => MarketBid::STATUS_PENDING,
        ]);

        if (!$bid) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Offer not found or already concluded.'));
            return $this->redirect(['market', 'tab' => 'offers']);
        }

        $bid->status = MarketBid::STATUS_CANCELLED;
        $bid->resolved_at = time();
        $bid->result_note = 'withdrawn';
        $bid->save(false, ['status', 'resolved_at', 'result_note', 'updated_at']);

        Yii::$app->session->setFlash('success', Yii::t('app', 'Offer withdrawn.'));
        return $this->redirect(['market', 'tab' => 'offers']);
    }

    private function createOrUpdateOffer(Transfer $transfer, int $offered): Response
    {
        if (!$this->assertWindowOpen()) {
            return $this->redirect(['market']);
        }

        if (!MultiplayerSyncService::ensureOnce('transfer.make-offer.' . (int) $transfer->id, 12)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Duplicate offer request ignored.'));
            return $this->redirect(['market']);
        }

        if (!in_array($transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
            throw new NotFoundHttpException(Yii::t('app', 'Offer invalid or expired.'));
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }
        if ((int) $transfer->from_team_id === (int) $team->id) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You cannot bid on your own player.'));
            return $this->redirect(['market']);
        }

        $offered = max(1, $offered);
        if ($transfer->transfer_type !== Transfer::TYPE_FREE && (int) $team->budget < $offered) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Insufficient budget for the proposed offer.'));
            return $this->redirect(['market']);
        }

        $expiresAt = time() + 48 * 3600;
        $requestId = MultiplayerSyncService::currentRequestId('transfer.make-offer.' . (int) $transfer->id);
        $requestSource = MultiplayerSyncService::currentRequestSource();
        $offer = TransferOffer::findOne([
            'transfer_id' => (int) $transfer->id,
            'from_team_id' => (int) $team->id,
            'status' => TransferOffer::STATUS_PENDING,
        ]);

        if ($offer) {
            $offer->offered_fee = $offered;
            $offer->expires_at = $expiresAt;
            $offer->request_id = $requestId;
            $offer->request_source = $requestSource;
            $offer->save(false, ['offered_fee', 'expires_at', 'updated_at', 'request_id', 'request_source']);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Offer updated.'));
        } else {
            $offer = new TransferOffer();
            $offer->transfer_id = (int) $transfer->id;
            $offer->from_team_id = (int) $team->id;
            $offer->to_team_id = (int) $transfer->from_team_id;
            $offer->player_id = (int) $transfer->player_id;
            $offer->offered_fee = $offered;
            $offer->status = TransferOffer::STATUS_PENDING;
            $offer->expires_at = $expiresAt;
            $offer->request_id = $requestId;
            $offer->request_source = $requestSource;
            $offer->save(false);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Offer sent.'));

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
            Yii::t('app', 'Transfer window closed. Next opening: {date}', [
                '{date}' => date('d/m/Y H:i', TransferWindowService::nextOpeningTimestamp(null, $teamId)),
            ])
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
