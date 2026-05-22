<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Team;
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
            ->orderBy(['role' => SORT_ASC, 'ability' => SORT_DESC, 'id' => SORT_ASC])
            ->all();
        $history = StaffHistory::find()
            ->where(['team_id' => $team->id])
            ->orderBy(['left_season' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(30)
            ->all();

        return $this->render('view', [
            'team' => $team,
            'staff' => $staff,
            'market' => $market,
            'history' => $history,
        ]);
    }

    /**
     * Attempts negotiation and signs contract on success.
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

        $existing = Staff::findOne(['team_id' => $team->id, 'role' => $candidate->role]);
        if ($existing) {
            Yii::$app->session->setFlash('error', 'Hai già uno staff in questo ruolo. Licenzia prima quello attuale.');
            return $this->redirect(['view']);
        }

        $probability = $this->successProbability((int) $candidate->negotiations);
        if ($candidate->negotiations <= 0 || random_int(1, 100) > $probability) {
            $candidate->negotiations = max(0, (int) $candidate->negotiations - 1);
            $candidate->save(false, ['negotiations']);
            Yii::$app->session->setFlash('error', "Trattativa fallita. Tentativi rimasti: {$candidate->negotiations}.");
            return $this->redirect(['view']);
        }

        $season = $this->currentSeason();
        $staff = new Staff();
        $staff->team_id = $team->id;
        $staff->name = $candidate->name;
        $staff->role = $candidate->role;
        $staff->ability = (int) $candidate->ability;
        $staff->experience = (int) $candidate->experience;
        $staff->age = max(30, min(68, (int) $candidate->experience + random_int(22, 30)));
        $staff->motivation = (int) $candidate->motivation;
        $staff->contract_ends = $season + max(1, (int) $candidate->contract_length) - 1;
        $staff->salary = (int) $candidate->salary;
        $staff->specialisation = $this->roleSpecialisation((string) $candidate->role);
        $staff->recomputeEfficiency();

        if ($staff->save(false)) {
            $candidate->delete();
            Yii::$app->session->setFlash('success', "Assunto: {$staff->name} (eff. {$staff->efficiency}). Contratto fino a stagione {$staff->contract_ends}.");
        } else {
            Yii::$app->session->setFlash('error', 'Errore durante firma contratto staff.');
        }
        return $this->redirect(['view']);
    }

    /**
     * One-time salary increase for candidate, resets negotiations to 4.
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
        if ((int) $candidate->raise_used > 0) {
            Yii::$app->session->setFlash('error', 'Rialzo già usato su questo candidato.');
            return $this->redirect(['view']);
        }

        $newSalary = (int) round((int) $candidate->salary * 1.15);
        $candidate->salary = max($newSalary, (int) $candidate->salary + 1000);
        $candidate->negotiations = 4;
        $candidate->raise_used = 1;
        $candidate->save(false, ['salary', 'negotiations', 'raise_used']);
        Yii::$app->session->setFlash('success', "Offerta alzata a €" . number_format((int) $candidate->salary, 0, ',', '.') . ". Tentativi riportati a 4.");
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

    private function roleSpecialisation(string $role): string
    {
        return match ($role) {
            Staff::ROLE_HEAD_COACH => 'tattica',
            Staff::ROLE_FITNESS_COACH => 'fisico',
            Staff::ROLE_GOALKEEPING_COACH => 'portieri',
            Staff::ROLE_SCOUT => 'scouting',
            default => 'equilibrato',
        };
    }

    private function successProbability(int $attempts): int
    {
        return match ($attempts) {
            4 => 90,
            3 => 80,
            2 => 70,
            1 => 60,
            default => 0,
        };
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

        $season = $this->currentSeason();
        $seasonEndTs = strtotime('+' . max(1, 24 * 7) . ' days');
        $now = time();
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
            $cand->expires_at = $seasonEndTs ?: ($now + 14 * 86400);
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
