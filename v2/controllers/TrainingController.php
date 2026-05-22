<?php

declare(strict_types=1);

namespace app\controllers;

use app\models\Player;
use app\models\Staff;
use app\models\Team;
use app\models\Fixture;
use app\models\TrainingSnapshot;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

/**
 * SIP-0036 / SIP-0039 — Training System.
 * Two panels: Fisico (skill allocation) and Tattico (tactic levels).
 */
class TrainingController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => ['save-skill' => ['post'], 'save-tactic' => ['post']],
            ],
        ];
    }

    public function actionIndex(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);

        $season = (int) Yii::$app->db->createCommand('SELECT MIN(season) FROM {{%competition}}')->queryScalar() ?: 1;

        // Load or initialise skill training record
        $skill = Yii::$app->db->createCommand(
            'SELECT * FROM {{%training_skill}} WHERE team_id=:t AND season=:s',
            [':t' => $team->id, ':s' => $season]
        )->queryOne();
        if (!$skill) {
            Yii::$app->db->createCommand()->insert('{{%training_skill}}', [
                'team_id' => $team->id, 'season' => $season,
                'alloc_forma' => 10, 'alloc_cond' => 10,
                'alloc_po' => 5,  'alloc_df' => 10, 'alloc_cn' => 10,
                'alloc_pa' => 10, 'alloc_rg' => 10, 'alloc_cr' => 10,
                'alloc_tc' => 10, 'alloc_tr' => 5,
                'updated_at' => time(),
            ])->execute();
            $skill = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_skill}} WHERE team_id=:t AND season=:s',
                [':t' => $team->id, ':s' => $season]
            )->queryOne();
        }

        // Load or initialise tactic LEVEL record (used by match engine)
        $tactic = Yii::$app->db->createCommand(
            'SELECT * FROM {{%training_tactic}} WHERE team_id=:t AND season=:s',
            [':t' => $team->id, ':s' => $season]
        )->queryOne();
        if (!$tactic) {
            Yii::$app->db->createCommand()->insert('{{%training_tactic}}', [
                'team_id' => $team->id, 'season' => $season,
                'pressing' => 30, 'contropiede' => 20, 'possesso' => 40,
                'palla_bassa' => 30, 'lancio_lungo' => 20, 'catenaccio' => 20,
                'fuorigioco' => 10, 'calci_piazzati' => 30,
                'updated_at' => time(),
            ])->execute();
            $tactic = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic}} WHERE team_id=:t AND season=:s',
                [':t' => $team->id, ':s' => $season]
            )->queryOne();
        }
        // Load or initialise tactic DAILY PLAN record (points allocation, total 100)
        $tacticPlan = null;
        try {
            $tacticPlan = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic_plan}} WHERE team_id=:t AND season=:s',
                [':t' => $team->id, ':s' => $season]
            )->queryOne();
            if (!$tacticPlan) {
                Yii::$app->db->createCommand()->insert('{{%training_tactic_plan}}', [
                    'team_id' => $team->id,
                    'season' => $season,
                    'pressing' => 15,
                    'contropiede' => 10,
                    'possesso' => 15,
                    'palla_bassa' => 10,
                    'lancio_lungo' => 10,
                    'catenaccio' => 10,
                    'fuorigioco' => 10,
                    'calci_piazzati' => 20,
                    'updated_at' => time(),
                ])->execute();
                $tacticPlan = Yii::$app->db->createCommand(
                    'SELECT * FROM {{%training_tactic_plan}} WHERE team_id=:t AND season=:s',
                    [':t' => $team->id, ':s' => $season]
                )->queryOne();
            }
        } catch (\Throwable) {
            // Migration not applied yet: fallback to normalized tactic levels.
            $tacticPlan = [
                'pressing' => 15,
                'contropiede' => 10,
                'possesso' => 15,
                'palla_bassa' => 10,
                'lancio_lungo' => 10,
                'catenaccio' => 10,
                'fuorigioco' => 10,
                'calci_piazzati' => 20,
            ];
        }

        // Staff bonuses for display
        $staffBonus = $this->computeStaffBonus($team->id);
        $tacticalAvg = (int) round(array_sum([
            (int) ($tactic['pressing'] ?? 0),
            (int) ($tactic['contropiede'] ?? 0),
            (int) ($tactic['possesso'] ?? 0),
            (int) ($tactic['palla_bassa'] ?? 0),
            (int) ($tactic['lancio_lungo'] ?? 0),
            (int) ($tactic['catenaccio'] ?? 0),
            (int) ($tactic['fuorigioco'] ?? 0),
            (int) ($tactic['calci_piazzati'] ?? 0),
        ]) / 8);
        $now = time();
        $matchSoon = Fixture::find()
            ->where(['status' => Fixture::STATUS_SCHEDULED])
            ->andWhere(['between', 'match_date', $now, $now + (3 * 86400)])
            ->andWhere(['or', ['home_team_id' => $team->id], ['away_team_id' => $team->id]])
            ->exists();
        $tacticDelta = [];
        try {
            $snaps = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic_snapshot}}
                 WHERE team_id=:t AND season=:s
                 ORDER BY snapshot_date DESC
                 LIMIT 2',
                [':t' => $team->id, ':s' => $season]
            )->queryAll();
            $latest = $snaps[0] ?? null;
            $prev = $snaps[1] ?? null;
            foreach (['pressing','contropiede','possesso','palla_bassa','lancio_lungo','catenaccio','fuorigioco','calci_piazzati'] as $f) {
                $curr = (int)($latest[$f] ?? ($tactic[$f] ?? 0));
                $old = (int)($prev[$f] ?? $curr);
                $tacticDelta[$f] = $curr - $old;
            }
        } catch (\Throwable) {
            $tacticDelta = [];
        }

        return $this->render('index', [
            'team'       => $team,
            'skill'      => $skill,
            'tactic'     => $tactic,
            'tacticPlan' => $tacticPlan,
            'tacticDelta' => $tacticDelta,
            'staffBonus' => $staffBonus,
            'season'     => $season,
            'tacticalAvg' => $tacticalAvg,
            'matchSoon' => $matchSoon,
        ]);
    }

    public function actionSaveSkill(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);
        $season = (int) Yii::$app->db->createCommand('SELECT MIN(season) FROM {{%competition}}')->queryScalar() ?: 1;

        $fields = ['alloc_forma','alloc_cond','alloc_po','alloc_df','alloc_cn','alloc_pa','alloc_rg','alloc_cr','alloc_tc','alloc_tr'];
        $data = ['updated_at' => time()];
        $total = 0;
        foreach ($fields as $f) {
            $v = max(0, min(100, (int) Yii::$app->request->post($f, 0)));
            $data[$f] = $v;
            $total += $v;
        }
        if ($total > 100) {
            Yii::$app->session->setFlash('error', "Totale punti ($total) supera 100.");
            return $this->redirect(['index']);
        }
        Yii::$app->db->createCommand()->update('{{%training_skill}}', $data,
            ['team_id' => $team->id, 'season' => $season])->execute();
        Yii::$app->session->setFlash('success', 'Allenamento fisico salvato.');
        return $this->redirect(['index']);
    }

    public function actionSaveTactic(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->redirect(['/site/index']);
        $season = (int) Yii::$app->db->createCommand('SELECT MIN(season) FROM {{%competition}}')->queryScalar() ?: 1;

        $fields = ['pressing','contropiede','possesso','palla_bassa','lancio_lungo','catenaccio','fuorigioco','calci_piazzati'];
        $data = ['updated_at' => time()];
        $total = 0;
        foreach ($fields as $f) {
            $v = max(0, min(100, (int) Yii::$app->request->post($f, 0)));
            $data[$f] = $v;
            $total += $v;
        }
        if ($total > 100) {
            Yii::$app->session->setFlash('error', "Totale punti tattici ($total) supera 100.");
            return $this->redirect(['index', 'tab' => 'tattico']);
        }
        try {
            Yii::$app->db->createCommand()->update('{{%training_tactic_plan}}', $data,
                ['team_id' => $team->id, 'season' => $season])->execute();
        } catch (\Throwable) {
            Yii::$app->session->setFlash('error', 'Tabella piano tattico non presente. Esegui le migrazioni.');
            return $this->redirect(['index', 'tab' => 'tattico']);
        }
        Yii::$app->session->setFlash('success', 'Piano allenamento tattico aggiornato.');
        return $this->redirect(['index', 'tab' => 'tattico']);
    }

    private function computeStaffBonus(int $teamId): array
    {
        $staff = Yii::$app->db->createCommand(
            'SELECT role, efficiency FROM {{%staff}} WHERE team_id=:t',
            [':t' => $teamId]
        )->queryAll();

        $bonus = ['physical' => 0.0, 'tactical' => 0.0, 'gk' => 0.0, 'recovery' => 0.0];
        foreach ($staff as $s) {
            $eff = (float) $s['efficiency'];
            match ($s['role'] ?? '') {
                Staff::ROLE_HEAD_COACH        => $bonus['tactical'] += round(0.10 * $eff, 1),
                Staff::ROLE_ASSISTANT_COACH   => $bonus['physical'] += round(0.05 * $eff, 1),
                Staff::ROLE_GOALKEEPING_COACH => $bonus['gk']       += round(0.15 * $eff, 1),
                Staff::ROLE_FITNESS_COACH     => $bonus['physical'] += round(0.03 * $eff, 1),
                Staff::ROLE_SCOUT             => $bonus['recovery'] += round(0.20 * $eff, 1),
                default         => null,
            };
        }
        return $bonus;
    }

    /** GET /training/progress?playerId=X&weeks=8 */
    public function actionProgress(int $playerId, int $weeks = 8): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->asJson(['error' => 'no team']);

        $player = Player::findOne(['id' => $playerId, 'team_id' => $team->id]);
        if (!$player) return $this->asJson(['error' => 'not found']);

        $snapshots = TrainingSnapshot::getProgressForPlayer($playerId, $weeks);

        return $this->asJson([
            'player'    => ['id' => $player->id, 'name' => $player->name, 'position' => $player->position],
            'snapshots' => $snapshots,
        ]);
    }

    /** GET /training/stats — team KPI + momentum + tactical trend */
    public function actionStats(): Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) return $this->asJson(['error' => 'no team']);
        $teamId = (int) $team->id;

        // ── Trend forma/condizione (ultimi 14 giorni) ───────────────────
        $trendRows = Yii::$app->db->createCommand(
            'SELECT s.snapshot_date,
                    AVG(s.form) AS avg_form,
                    AVG(s.condition_val) AS avg_condition
             FROM {{%training_snapshot}} s
             WHERE s.team_id = :t
             GROUP BY s.snapshot_date
             ORDER BY s.snapshot_date DESC
             LIMIT 14',
            [':t' => $teamId]
        )->queryAll();
        $trendRows = array_reverse($trendRows);
        $trend = [
            'dates' => array_map(static fn(array $r): string => (string) $r['snapshot_date'], $trendRows),
            'form' => array_map(static fn(array $r): float => round((float) $r['avg_form'], 1), $trendRows),
            'condition' => array_map(static fn(array $r): float => round((float) $r['avg_condition'], 1), $trendRows),
        ];

        // ── KPI attuali squadra ──────────────────────────────────────────
        $kpi = Yii::$app->db->createCommand(
            'SELECT AVG(form) AS avg_form, AVG(`condition`) AS avg_condition, AVG(freshness) AS avg_freshness
             FROM {{%player}} WHERE team_id = :t',
            [':t' => $teamId]
        )->queryOne() ?: [];
        $kpi = [
            'avg_form' => round((float) ($kpi['avg_form'] ?? 0), 1),
            'avg_condition' => round((float) ($kpi['avg_condition'] ?? 0), 1),
            'avg_freshness' => round((float) ($kpi['avg_freshness'] ?? 0), 1),
        ];

        // ── Momentum giocatori (crescita/calo ultimi 7 giorni) ──────────
        $fromDate = date('Y-m-d', strtotime('-7 days'));
        $snapRows = Yii::$app->db->createCommand(
            'SELECT player_id, snapshot_date, general_skill
             FROM {{%training_snapshot}}
             WHERE team_id = :t AND snapshot_date >= :d
             ORDER BY player_id ASC, snapshot_date ASC',
            [':t' => $teamId, ':d' => $fromDate]
        )->queryAll();
        $playerIds = [];
        $first = [];
        $last = [];
        foreach ($snapRows as $r) {
            $pid = (int) $r['player_id'];
            $playerIds[$pid] = true;
            if (!isset($first[$pid])) {
                $first[$pid] = (int) $r['general_skill'];
            }
            $last[$pid] = (int) $r['general_skill'];
        }
        $players = [];
        if (!empty($playerIds)) {
            $players = Player::find()
                ->select(['id', 'name', 'position'])
                ->where(['id' => array_keys($playerIds)])
                ->indexBy('id')
                ->all();
        }
        $momentum = [];
        foreach (array_keys($playerIds) as $pid) {
            if (!isset($players[$pid])) {
                continue;
            }
            $delta = ((int) ($last[$pid] ?? 0)) - ((int) ($first[$pid] ?? 0));
            $momentum[] = [
                'player_id' => $pid,
                'name' => (string) $players[$pid]->name,
                'position' => (string) $players[$pid]->position,
                'delta' => $delta,
                'current' => (int) ($last[$pid] ?? 0),
            ];
        }
        usort($momentum, static fn(array $a, array $b): int => $b['delta'] <=> $a['delta']);
        $topGrowth = array_slice($momentum, 0, 5);
        $topDrop = $momentum;
        usort($topDrop, static fn(array $a, array $b): int => $a['delta'] <=> $b['delta']);
        $topDrop = array_slice($topDrop, 0, 5);

        // ── Trend tattiche squadra (livello + delta 7g + alloc attuale) ─
        $tacticTrend = [];
        $plan = [];
        try {
            $season = (int) Yii::$app->db->createCommand('SELECT MIN(season) FROM {{%competition}}')->queryScalar() ?: 1;
            $tacticFields = ['pressing','contropiede','possesso','palla_bassa','lancio_lungo','catenaccio','fuorigioco','calci_piazzati'];
            $snap = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic_snapshot}}
                 WHERE team_id = :t AND season = :s
                 ORDER BY snapshot_date ASC',
                [':t' => $teamId, ':s' => $season]
            )->queryAll();
            $latest = !empty($snap) ? $snap[count($snap) - 1] : null;
            $base = null;
            foreach ($snap as $row) {
                if ((string) $row['snapshot_date'] >= $fromDate) {
                    $base = $row;
                    break;
                }
            }
            if ($base === null && !empty($snap)) {
                $base = $snap[0];
            }

            $plan = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic_plan}} WHERE team_id = :t AND season = :s',
                [':t' => $teamId, ':s' => $season]
            )->queryOne() ?: [];

            foreach ($tacticFields as $f) {
                $curr = (int) ($latest[$f] ?? 0);
                $old = (int) ($base[$f] ?? $curr);
                $tacticTrend[$f] = [
                    'current' => $curr,
                    'delta_7d' => $curr - $old,
                    'alloc' => (int) ($plan[$f] ?? 0),
                ];
            }
        } catch (\Throwable) {
            $tacticTrend = [];
        }

        return $this->asJson([
            'trend' => $trend,
            'kpi' => $kpi,
            'topGrowth' => $topGrowth,
            'topDrop' => $topDrop,
            'tacticTrend' => $tacticTrend,
        ]);
    }
}
