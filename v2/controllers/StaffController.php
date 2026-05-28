<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\AuctionService;
use Yii;
use app\models\Team;
use app\models\MarketBid;
use app\models\Staff;
use app\models\StaffHistory;
use app\models\StaffMarket;
use app\models\Competition;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;

class StaffController extends Controller
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
                    'negotiate' => ['post'],
                    'raise-offer' => ['post'],
                    'fire' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Displays current staff.
     */
    public function actionView(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $this->ensureMarketForTeam($team->id);

        $staff = Staff::find()
            ->where(['team_id' => $team->id])
            ->orderBy(['role' => SORT_ASC, 'efficiency' => SORT_DESC])
            ->all();
        $market = StaffMarket::find()
            ->where(['team_id' => $team->id])
            ->andWhere(['>', 'expires_at', time()])
            ->orderBy(['expires_at' => SORT_ASC, 'role' => SORT_ASC, 'ability' => SORT_DESC, 'id' => SORT_ASC])
            ->all();
        $history = StaffHistory::find()
            ->where(['team_id' => $team->id])
            ->orderBy(['left_season' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(30)
            ->all();
        $pendingBids = MarketBid::find()
            ->where([
                'team_id' => (int) $team->id,
                'market_type' => MarketBid::TYPE_STAFF,
                'status' => MarketBid::STATUS_PENDING,
            ])
            ->all();
        $pendingByCandidate = [];
        foreach ($pendingBids as $bid) {
            $pendingByCandidate[(int) $bid->market_ref_id] = $bid;
        }

        return $this->render('view', [
            'team' => $team,
            'staff' => $staff,
            'market' => $market,
            'history' => $history,
            'pendingByCandidate' => $pendingByCandidate,
        ]);
    }

    /**
     * Places/updates staff auction bid.
     */
    public function actionNegotiate(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $candidate = StaffMarket::findOne(['id' => $id, 'team_id' => $team->id]);
        if (!$candidate) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Candidate not available.'));
            return $this->redirect(['view']);
        }
        if ((int) $candidate->expires_at <= time()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Auction ended: you can no longer bid on this candidate.'));
            return $this->redirect(['view']);
        }

        $offered = (int) Yii::$app->request->post('offered_fee', 0);
        if ($offered <= 0) {
            $offered = max(1000, (int) $candidate->salary);
        }
        if ((int) $team->budget < $offered) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Insufficient budget to place this bid.'));
            return $this->redirect(['view']);
        }

        $bid = AuctionService::placeOrUpdateBid(
            (int) $team->id,
            MarketBid::TYPE_STAFF,
            (int) $candidate->id,
            $offered
        );
        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'Staff offer placed: €{amount} (expires {date}).', [
                '{amount}' => number_format((int) $bid->bid_amount, 0, ',', '.'),
                '{date}'   => date('d/m H:i', (int) $bid->expires_at),
            ])
        );
        return $this->redirect(['view']);
    }

    /**
     * Quick +15% bid raise on candidate auction.
     */
    public function actionRaiseOffer(int $id): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $candidate = StaffMarket::findOne(['id' => $id, 'team_id' => $team->id]);
        if (!$candidate) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Candidate not available.'));
            return $this->redirect(['view']);
        }
        if ((int) $candidate->expires_at <= time()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Auction ended: you can no longer raise the offer.'));
            return $this->redirect(['view']);
        }
        $existing = MarketBid::findOne([
            'team_id' => (int) $team->id,
            'market_type' => MarketBid::TYPE_STAFF,
            'market_ref_id' => (int) $candidate->id,
            'status' => MarketBid::STATUS_PENDING,
        ]);
        $base = $existing ? (int) $existing->bid_amount : max(1000, (int) $candidate->salary);
        $newBid = max($base + 1000, (int) round($base * 1.15));
        if ((int) $team->budget < $newBid) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Insufficient budget for bid raise.'));
            return $this->redirect(['view']);
        }

        $bid = AuctionService::placeOrUpdateBid(
            (int) $team->id,
            MarketBid::TYPE_STAFF,
            (int) $candidate->id,
            $newBid
        );
        Yii::$app->session->setFlash(
            'success',
            Yii::t('app', 'Staff offer raised to €{amount}.', ['{amount}' => number_format((int) $bid->bid_amount, 0, ',', '.')])
        );
        return $this->redirect(['view']);
    }

    /**
     * Fires a staff member with one-week compensation.
     */
    public function actionFire(int $id): Response
    {
        $staff = Staff::findOne($id);
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);

        if ($staff && $team && $staff->team_id === $team->id) {
            $season = $this->currentSeason();
            $this->archiveStaffHistory($staff, $season, 'fired');

            $compensation = (int) ceil(max(0, (int) $staff->salary) / 52);
            $team->budget -= $compensation;
            $team->save(false, ['budget', 'updated_at']);

            $staff->delete();
            Yii::$app->session->setFlash('success', Yii::t('app', 'Staff member fired. Severance: €{amount}', ['{amount}' => number_format($compensation, 0, ',', '.')]));
        }

        return $this->redirect(['view']);
    }

    private function currentSeason(): int
    {
        $season = (int) Competition::find()->where(['!=', 'type', 'friendly'])->max('season');
        return $season > 0 ? $season : 1;
    }

    private function ensureMarketForTeam(int $teamId): void
    {
        StaffMarket::deleteAll([
            'and',
            ['team_id' => $teamId],
            ['<=', 'expires_at', time()],
        ]);

        $now = time();
        $maxTtlSeconds = max(3600, AuctionService::hoursForType(MarketBid::TYPE_STAFF) * 3600);
        $minTtlSeconds = max(3600, (int) floor($maxTtlSeconds * 0.55));
        $roles = [
            Staff::ROLE_HEAD_COACH,
            Staff::ROLE_FITNESS_COACH,
            Staff::ROLE_DOCTOR,
            Staff::ROLE_SCOUT,
            Staff::ROLE_ASSISTANT_COACH,
            Staff::ROLE_GOALKEEPING_COACH,
        ];
        $targetTotal = 8;

        // Ensure at least one active candidate per role.
        foreach ($roles as $role) {
            $availableRole = (int) StaffMarket::find()
                ->where(['team_id' => $teamId, 'role' => $role])
                ->andWhere(['>', 'expires_at', $now])
                ->count();
            if ($availableRole <= 0) {
                $this->createMarketCandidate($teamId, $role, $now, $minTtlSeconds, $maxTtlSeconds);
            }
        }

        $active = (int) StaffMarket::find()
            ->where(['team_id' => $teamId])
            ->andWhere(['>', 'expires_at', $now])
            ->count();
        if ($active >= $targetTotal) {
            return;
        }

        $toCreate = $targetTotal - $active;
        for ($i = 0; $i < $toCreate; $i++) {
            $role = $roles[array_rand($roles)];
            $this->createMarketCandidate($teamId, $role, $now, $minTtlSeconds, $maxTtlSeconds);
        }
    }

    private function createMarketCandidate(int $teamId, string $role, int $now, int $minTtlSeconds, int $maxTtlSeconds): void
    {
        $ability = random_int(40, 92);
        $experience = random_int(5, 40);
        $motivation = random_int(55, 98);
        $baseSalary = [
            Staff::ROLE_HEAD_COACH => 80000,
            Staff::ROLE_ASSISTANT_COACH => 40000,
            Staff::ROLE_GOALKEEPING_COACH => 45000,
            Staff::ROLE_FITNESS_COACH => 30000,
            Staff::ROLE_DOCTOR => 42000,
            Staff::ROLE_SCOUT => 25000,
        ];
        $salary = (int) round(($baseSalary[$role] ?? 30000) * ($ability / 50));

        $cand = new StaffMarket();
        $cand->team_id = $teamId;
        $cand->name = $this->randomNameByRole($role);
        $cand->role = $role;
        $cand->ability = $ability;
        $cand->experience = $experience;
        $cand->motivation = $motivation;
        $cand->salary = $salary;
        $cand->contract_length = random_int(1, 3);
        $cand->negotiations = 4;
        $cand->raise_used = 0;
        $cand->generated_at = $now;
        $cand->expires_at = $now + random_int($minTtlSeconds, $maxTtlSeconds);
        $cand->save(false);
    }

    private function randomNameByRole(string $role): string
    {
        $prefix = match ($role) {
            Staff::ROLE_HEAD_COACH => 'Mister',
            Staff::ROLE_FITNESS_COACH => 'Prep.',
            Staff::ROLE_DOCTOR => 'Dott.',
            Staff::ROLE_SCOUT => 'Scout',
            Staff::ROLE_ASSISTANT_COACH => 'Vice',
            Staff::ROLE_GOALKEEPING_COACH => 'All. Portieri',
            default => 'Staff',
        };
        $first = ['Marco', 'Stefano', 'Davide', 'Luca', 'Franco', 'Paolo', 'Andrea', 'Simone'];
        $last = ['Rossi', 'Bianchi', 'Ferrari', 'Romano', 'Conti', 'Ricci', 'Lombardi', 'Gallo', 'Greco'];
        return $prefix . ' ' . $first[array_rand($first)] . ' ' . $last[array_rand($last)];
    }

    private function archiveStaffHistory(Staff $staff, int $leftSeason, string $reason): void
    {
        $history = new StaffHistory();
        $history->team_id = (int) $staff->team_id;
        $history->name = (string) $staff->name;
        $history->role = (string) $staff->role;
        $history->ability = (int) $staff->ability;
        $history->experience = (int) $staff->experience;
        $history->motivation = (int) $staff->motivation;
        $history->salary = (int) $staff->salary;
        $history->efficiency = (int) $staff->efficiency;
        $history->joined_season = max(1, (int) $staff->contract_ends - 1);
        $history->left_season = $leftSeason;
        $history->left_reason = $reason;
        $history->created_at = time();
        $history->save(false);
    }
}
