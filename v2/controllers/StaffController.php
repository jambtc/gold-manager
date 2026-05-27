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
        if (!$candidate || $candidate->expires_at <= time()) {
            Yii::$app->session->setFlash('error', 'Candidato non disponibile.');
            return $this->redirect(['view']);
        }

        $offered = (int) Yii::$app->request->post('offered_fee', 0);
        if ($offered <= 0) {
            $offered = max(1000, (int) $candidate->salary);
        }
        if ((int) $team->budget < $offered) {
            Yii::$app->session->setFlash('error', 'Budget insufficiente per piazzare questa offerta.');
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
            sprintf(
                'Offerta staff piazzata: €%s (scade %s).',
                number_format((int) $bid->bid_amount, 0, ',', '.'),
                date('d/m H:i', (int) $bid->expires_at)
            )
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
        if (!$candidate || $candidate->expires_at <= time()) {
            Yii::$app->session->setFlash('error', 'Candidato non disponibile.');
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
            Yii::$app->session->setFlash('error', 'Budget insufficiente per il rialzo.');
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
            "Offerta staff alzata a €" . number_format((int) $bid->bid_amount, 0, ',', '.') . "."
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
            Yii::$app->session->setFlash('success', 'Addetto licenziato. Indennizzo: €' . number_format($compensation, 0, ',', '.'));
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
        $active = (int) StaffMarket::find()
            ->where(['team_id' => $teamId])
            ->andWhere(['>', 'expires_at', time()])
            ->count();
        if ($active >= 8) {
            return;
        }

        $now = time();
        $expiresAt = $now + (AuctionService::hoursForType(MarketBid::TYPE_STAFF) * 3600);
        $roles = [
            Staff::ROLE_HEAD_COACH,
            Staff::ROLE_FITNESS_COACH,
            Staff::ROLE_SCOUT,
            Staff::ROLE_ASSISTANT_COACH,
            Staff::ROLE_GOALKEEPING_COACH,
        ];

        $toCreate = 8 - $active;
        for ($i = 0; $i < $toCreate; $i++) {
            $role = $roles[array_rand($roles)];
            $ability = random_int(40, 92);
            $experience = random_int(5, 40);
            $motivation = random_int(55, 98);
            $baseSalary = [
                Staff::ROLE_HEAD_COACH => 80000,
                Staff::ROLE_ASSISTANT_COACH => 40000,
                Staff::ROLE_GOALKEEPING_COACH => 45000,
                Staff::ROLE_FITNESS_COACH => 30000,
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
            $cand->expires_at = $expiresAt;
            $cand->save(false);
        }
    }

    private function randomNameByRole(string $role): string
    {
        $prefix = match ($role) {
            Staff::ROLE_HEAD_COACH => 'Mister',
            Staff::ROLE_FITNESS_COACH => 'Prep.',
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
