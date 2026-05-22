<?php

declare(strict_types=1);

namespace app\commands;

use app\components\seeders\FixtureSeeder;
use app\components\CharacterTraitHelper;
use app\components\FriendlyChallengeService;
use app\components\PlayerAttributeHelper;
use app\components\seeders\PlayerSeeder;
use app\components\WorldData;
use app\components\WorldSeeder;
use app\components\NewsService;
use app\components\SponsorService;
use app\models\Competition;
use app\models\Contract;
use app\models\Fixture;
use app\models\Formation;
use app\models\FormationSlot;
use app\models\NewsItem;
use app\models\Player;
use app\models\PlayerPool;
use app\models\Staff;
use app\models\StaffHistory;
use app\models\StaffMarket;
use app\models\Standing;
use app\models\Stadium;
use app\models\Sponsor;
use app\models\Team;
use app\models\TeamSponsor;
use app\models\Transfer;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Economy engine console commands.
 *
 * Typical cron setup (run once per day at midnight):
 *   0 0 * * * docker compose -f /var/www/gold-manager/docker-compose.yml \
 *             exec -T php ./yii economy/pay-wages >> /var/log/gold-manager-economy.log 2>&1
 *
 * Run at end of each match day:
 *   economy/credit-match-revenue <fixture_id>
 *
 * Run at season end:
 *   economy/season-rollover <competition_id>
 */
class EconomyController extends Controller
{
    private const BASE_SEASON_GRANT = 500000;
    private const POSITION_BONUS = 50000;
    private const ALL_SKILL_STATS = [
        'skill_po', 'skill_df', 'skill_cn', 'skill_pa',
        'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr',
    ];
    private const SPONSOR_NAME_PREFIXES = [
        'Aurora', 'Nordic', 'Linea', 'Vetta', 'Orion', 'Borgo', 'Alpina', 'Nuova',
        'Prime', 'Sigma', 'Vector', 'Atlas', 'Solaris', 'Titan', 'Mercurio', 'Zenit',
    ];
    private const SPONSOR_NAME_SUFFIXES = [
        'Energy', 'Foods', 'Motors', 'Steel', 'Digital', 'Group', 'Holding', 'Logistica',
        'Pay', 'Cloud', 'Finance', 'Lab', 'Systems', 'Build', 'Media', 'Partners',
    ];

    /**
     * Weekly financial tick: wages out, match revenues + sponsor in.
     * Run once per game-week (Monday cron).
     *
     * Usage: ./yii economy/pay-wages
     */
    public function actionPayWages(): int
    {
        SponsorService::expireContracts();
        $teams = Team::find()->with(['stadium', 'contracts'])->all();

        // Pre-load pending home fixtures (finished, not yet credited)
        $pendingFixtures = Fixture::find()
            ->where(['status' => Fixture::STATUS_FINISHED, 'revenue_credited' => 0])
            ->all();

        // Index by home_team_id for quick lookup
        $revenueByTeam = [];
        foreach ($pendingFixtures as $fixture) {
            $revenueByTeam[$fixture->home_team_id][] = $fixture;
        }

        foreach ($teams as $team) {
            $wages   = $team->totalWageBill();
            $matchRev = 0;
            $sponsorRev = 0;
            $delta   = 0;

            // ── Wages out ────────────────────────────────────────────────
            $delta -= $wages;

            // ── Match revenues (home fixtures) ───────────────────────────
            if (!empty($revenueByTeam[$team->id])) {
                foreach ($revenueByTeam[$team->id] as $fixture) {
                    $stadium = $team->stadium;
                    if ($stadium) {
                        if ($fixture->competition?->type === 'friendly' && $fixture->friendly_spectators !== null) {
                            // Friendly: actual spectators × stadium friendly price
                            $rev = (int)($fixture->friendly_spectators * $stadium->friendly_ticket_price);
                        } elseif ($fixture->competition?->type !== 'friendly') {
                            // Competitive: dynamic attendance based on position/round/opponent
                            $attendance = $this->computeCompetitiveAttendance($fixture);
                            $rev = $stadium->calculateMatchRevenue($attendance);
                        } else {
                            // Friendly without tracked spectators: flat 45%
                            $rev = $stadium->calculateMatchRevenue(0.45);
                        }
                        $matchRev += $rev;
                        $stadium->season_revenue += $rev;
                        $stadium->save(false);
                    }
                    $fixture->revenue_credited = 1;
                    $fixture->save(false);
                }
                $delta += $matchRev;
            }

            // ── Weekly sponsor installment (only active contract) ───────
            $activeSponsor = SponsorService::getActiveContractForTeam((int) $team->id);
            $sponsorRev = SponsorService::weeklyInstallment($activeSponsor);
            if ($sponsorRev > 0) {
                $delta += (int) $sponsorRev;
            }

            if ($delta === 0) continue;

            $team->budget += $delta;
            $team->save(false);

            // SIP-0050: finance news for human managers
            if ($team->user_id) {
                \app\components\NewsService::create(
                    (int)$team->user_id,
                    \app\models\NewsItem::CAT_FINANCE,
                    $delta >= 0 ? '💰' : '📉',
                    $delta >= 0
                        ? sprintf('Bilancio settimanale: +€%s', number_format($delta, 0, ',', '.'))
                        : sprintf('Bilancio settimanale: −€%s', number_format(abs($delta), 0, ',', '.')),
                    sprintf('Stipendi −€%s | Biglietti +€%s | Sponsor +€%s',
                        number_format($wages, 0, ',', '.'),
                        number_format($matchRev, 0, ',', '.'),
                        number_format($sponsorRev, 0, ',', '.')
                    )
                );
            }

            $sign = $delta >= 0 ? '+' : '';
            $this->stdout(sprintf(
                "%s %s: stipendi −€%s | biglietti +€%s | sponsor +€%s → %s€%s | budget €%s\n",
                $delta >= 0 ? '💰' : '⚠️ ',
                $team->name,
                number_format($wages, 0, ',', '.'),
                number_format($matchRev, 0, ',', '.'),
                number_format($sponsorRev, 0, ',', '.'),
                $sign,
                number_format($delta, 0, ',', '.'),
                number_format($team->budget, 0, ',', '.')
            ));
        }

        ['generated' => $generated, 'pool' => $poolCount] = $this->maintainPlayerPool(50);
        $cpuFilled = $this->maintainCpuRostersFromPool(18);
        $staffRestocked = $this->restockStaffMarketForManagers();
        $this->stdout("🌱 Pool giovani: +{$generated}, totale {$poolCount}\n");
        $this->stdout("🤖 Roster CPU reintegrati: {$cpuFilled}\n");
        $this->stdout("🧑‍💼 Staff market restock: {$staffRestocked}\n");

        return ExitCode::OK;
    }

    /**
     * Manual sponsor market rebalance (useful for one-shot tests).
     * Keeps sponsor catalog between 10% and 25% of total teams.
     *
     * Usage: ./yii economy/refresh-sponsor-pool [season]
     */
    public function actionRefreshSponsorPool(int $season = 0): int
    {
        $season = $season > 0 ? $season : $this->currentSeasonForGeneration();
        $result = $this->refreshSponsorMarketForSeason($season);

        $this->stdout(sprintf(
            "🤝 Sponsor pool (stagione %d): team=%d min=%d target=%d cap=%d | creati=%d rimossi=%d attivi_scaduti=%d tot_sponsor=%d\n",
            $season,
            $result['teams'],
            $result['min'],
            $result['target'],
            $result['max'],
            $result['created'],
            $result['deleted'],
            $result['expiredContracts'],
            $result['current']
        ));

        return ExitCode::OK;
    }

    /**
     * Expires pending friendly challenges older than 24h.
     *
     * Usage: ./yii economy/expire-friendly-challenges
     */
    public function actionExpireFriendlyChallenges(): int
    {
        $expired = FriendlyChallengeService::expirePendingChallenges();
        $played = FriendlyChallengeService::syncPlayedStatus();
        $this->stdout("🤝 Amichevoli: scadute {$expired} · chiuse come giocate {$played}\n");
        return ExitCode::OK;
    }

    /**
     * Applies DAILY training effects to all players.
     * Reads training_skill allocation for each team and updates individual stats.
     *
     * Usage: ./yii economy/apply-daily-training
     */
    public function actionApplyDailyTraining(): int
    {
        return $this->actionApplyWeeklyTraining();
    }

    /**
     * Legacy alias kept for backward compatibility.
     * Internally it now executes daily-scaled training logic.
     *
     * Usage: ./yii economy/apply-weekly-training
     */
    public function actionApplyWeeklyTraining(): int
    {
        $season = (int) Yii::$app->db->createCommand(
            'SELECT MIN(season) FROM {{%competition}}'
        )->queryScalar() ?: 1;
        $week = (int) date('W');
        $now = time();
        $dailyScale = $this->dailyTrainingScale();

        $statMap = [
            'alloc_po' => 'skill_po', 'alloc_df' => 'skill_df',
            'alloc_cn' => 'skill_cn', 'alloc_pa' => 'skill_pa',
            'alloc_rg' => 'skill_rg', 'alloc_cr' => 'skill_cr',
            'alloc_tc' => 'skill_tc', 'alloc_tr' => 'skill_tr',
        ];
        $tacticFields = ['pressing','contropiede','possesso','palla_bassa','lancio_lungo','catenaccio','fuorigioco','calci_piazzati'];

        $teams = Team::find()->all();

        foreach ($teams as $team) {
            $skillRow = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_skill}} WHERE team_id=:t AND season=:s',
                [':t' => $team->id, ':s' => $season]
            )->queryOne();
            $tacticRow = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic}} WHERE team_id=:t AND season=:s',
                [':t' => $team->id, ':s' => $season]
            )->queryOne();
            $tacticPlan = null;
            try {
                $tacticPlan = Yii::$app->db->createCommand(
                    'SELECT * FROM {{%training_tactic_plan}} WHERE team_id=:t AND season=:s',
                    [':t' => $team->id, ':s' => $season]
                )->queryOne();
            } catch (\Throwable) {
                $tacticPlan = null;
            }

            if (!$skillRow) continue;
            if (!$tacticRow) {
                $tacticRow = [
                    'pressing' => 30, 'contropiede' => 20, 'possesso' => 40, 'palla_bassa' => 30,
                    'lancio_lungo' => 20, 'catenaccio' => 20, 'fuorigioco' => 10, 'calci_piazzati' => 30,
                ];
            }
            if (!$tacticPlan) {
                $tacticPlan = [
                    'pressing' => 15, 'contropiede' => 10, 'possesso' => 15, 'palla_bassa' => 10,
                    'lancio_lungo' => 10, 'catenaccio' => 10, 'fuorigioco' => 10, 'calci_piazzati' => 20,
                ];
            }

            // Staff physical bonus
            $staffRows = Yii::$app->db->createCommand(
                'SELECT role, efficiency FROM {{%staff}} WHERE team_id=:t',
                [':t' => $team->id]
            )->queryAll();
            $staffMod = 1.0;
            $headCoachEff = 0.0;
            $fitnessCoachEff = 0.0;
            foreach ($staffRows as $s) {
                if (in_array($s['role'], ['head_coach', 'assistant_coach', 'fitness_coach'], true)) {
                    $staffMod += (float)$s['efficiency'] / 1000;
                }
                if (($s['role'] ?? '') === 'head_coach') {
                    $headCoachEff = max($headCoachEff, (float) $s['efficiency']);
                }
                if (($s['role'] ?? '') === 'fitness_coach') {
                    $fitnessCoachEff = max($fitnessCoachEff, (float) $s['efficiency']);
                }
            }
            $tacticalStaffMod = 1 + (($headCoachEff * 0.10) / 100);

            // Tactical LEVEL progression/decay from daily training plan.
            $tacticChanged = false;
            $tacticUpdate = ['updated_at' => $now];
            foreach ($tacticFields as $field) {
                $value = max(0, min(100, (int) ($tacticRow[$field] ?? 0)));
                $alloc = max(0, min(100, (int) ($tacticPlan[$field] ?? 0)));
                if ($alloc > 0) {
                    $prob = ($alloc / 100) * 0.10 * $tacticalStaffMod * $dailyScale;
                    if (mt_rand(1, 10000) <= (int) round($prob * 10000)) {
                        $value = min(100, $value + 1);
                        $tacticChanged = true;
                    }
                } else {
                    $decayChance = max(1, (int) round(200 * $dailyScale));
                    if ($value > 0 && mt_rand(1, 10000) <= $decayChance) {
                        $value = max(0, $value - 1);
                        $tacticChanged = true;
                    }
                }
                $tacticRow[$field] = $value;
                $tacticUpdate[$field] = $value;
            }
            if ($tacticChanged && !empty($tacticRow['id'])) {
                Yii::$app->db->createCommand()->update(
                    '{{%training_tactic}}',
                    $tacticUpdate,
                    ['id' => (int) $tacticRow['id']]
                )->execute();
            }
            try {
                $snapshotDate = date('Y-m-d', $now);
                Yii::$app->db->createCommand()->upsert('{{%training_tactic_snapshot}}', [
                    'team_id' => (int) $team->id,
                    'season' => $season,
                    'snapshot_date' => $snapshotDate,
                    'pressing' => (int) ($tacticRow['pressing'] ?? 0),
                    'contropiede' => (int) ($tacticRow['contropiede'] ?? 0),
                    'possesso' => (int) ($tacticRow['possesso'] ?? 0),
                    'palla_bassa' => (int) ($tacticRow['palla_bassa'] ?? 0),
                    'lancio_lungo' => (int) ($tacticRow['lancio_lungo'] ?? 0),
                    'catenaccio' => (int) ($tacticRow['catenaccio'] ?? 0),
                    'fuorigioco' => (int) ($tacticRow['fuorigioco'] ?? 0),
                    'calci_piazzati' => (int) ($tacticRow['calci_piazzati'] ?? 0),
                    'created_at' => $now,
                ], [
                    'pressing' => (int) ($tacticRow['pressing'] ?? 0),
                    'contropiede' => (int) ($tacticRow['contropiede'] ?? 0),
                    'possesso' => (int) ($tacticRow['possesso'] ?? 0),
                    'palla_bassa' => (int) ($tacticRow['palla_bassa'] ?? 0),
                    'lancio_lungo' => (int) ($tacticRow['lancio_lungo'] ?? 0),
                    'catenaccio' => (int) ($tacticRow['catenaccio'] ?? 0),
                    'fuorigioco' => (int) ($tacticRow['fuorigioco'] ?? 0),
                    'calci_piazzati' => (int) ($tacticRow['calci_piazzati'] ?? 0),
                    'created_at' => $now,
                ])->execute();
            } catch (\Throwable) {
                // Snapshot table may not be migrated yet.
            }

            $physicalLoad = 0;
            foreach (array_keys($statMap) as $allocKey) {
                $physicalLoad += max(0, min(100, (int) ($skillRow[$allocKey] ?? 0)));
            }
            $physicalLoad += max(0, min(100, (int) ($skillRow['alloc_forma'] ?? 0)));
            $physicalLoad += max(0, min(100, (int) ($skillRow['alloc_cond'] ?? 0)));
            $physicalLoad = min(100, $physicalLoad);

            $tacticalLoadSum = 0;
            foreach ($tacticFields as $field) {
                $tacticalLoadSum += max(0, min(100, (int) ($tacticPlan[$field] ?? 0)));
            }
            $tacticalLoad = min(100, (int) $tacticalLoadSum);
            $totalLoad = (int) round(($physicalLoad + $tacticalLoad) / 2);

            $baseFreshnessDelta = match (true) {
                $totalLoad >= 80 => -3,
                $totalLoad >= 50 => -1,
                $totalLoad >= 20 => +2,
                default => +5,
            };

            $matchSoon = Fixture::find()
                ->where(['status' => Fixture::STATUS_SCHEDULED])
                ->andWhere(['between', 'match_date', $now, $now + (3 * 86400)])
                ->andWhere(['or', ['home_team_id' => $team->id], ['away_team_id' => $team->id]])
                ->exists();

            $players  = Player::find()->where(['team_id' => $team->id])->all();
            $teamAverageForm = 50;
            if (!empty($players)) {
                $teamAverageForm = (int) round(array_sum(array_map(
                    static fn(Player $p): int => (int) $p->form,
                    $players
                )) / count($players));
            }

            $activeFormation = Formation::find()
                ->where(['team_id' => $team->id, 'is_active' => 1])
                ->with('slots')
                ->one();
            $starterIds = [];
            $captainId = 0;
            if ($activeFormation) {
                $captainId = (int) ($activeFormation->captain_player_id ?? 0);
                foreach ($activeFormation->slots as $slot) {
                    if ((int) ($slot->zone ?? 0) > 63) {
                        continue;
                    }
                    $pid = (int) ($slot->player_id ?? 0);
                    if ($pid > 0) {
                        $starterIds[$pid] = true;
                    }
                }
            }
            $playersById = [];
            foreach ($players as $p) {
                $playersById[(int) $p->id] = $p;
            }
            $captainCharacter = $captainId > 0 && isset($playersById[$captainId])
                ? CharacterTraitHelper::normalize((string) $playersById[$captainId]->character)
                : '';
            $teamCaptainAura = $captainCharacter === 'carismatico' ? 1.05 : 1.00;
            $improved = 0;
            $talentLevelUps = 0;
            $freshnessAdjusted = 0;
            $formPenalties = 0.0;

            foreach ($players as $player) {
                // Skip injured players — no training effect
                if ($player->injury_weeks > 0) {
                    continue;
                }

                $ageMod  = max(0.3, 1.0 - max(0, ($player->age - 25) * 0.02));
                $changed = false;

                // ── Skill stats (SIP-0037: charMod per-stat) ────────────────
                foreach ($statMap as $alloc => $stat) {
                    $pts     = (int)($skillRow[$alloc] ?? 0);
                    $isCaptain = $captainId > 0 && (int) $player->id === $captainId;
                    $benchedStreak = isset($starterIds[(int) $player->id]) ? 0 : 2;
                    $charMod = CharacterTraitHelper::trainingModifier(
                        $player->character ?? '',
                        $stat,
                        $player->position ?? '',
                        $isCaptain,
                        $teamAverageForm,
                        $benchedStreak
                    );
                    if ($teamCaptainAura > 1.0 && !$isCaptain) {
                        $charMod *= $teamCaptainAura;
                    }
                    $xpGained = ($pts / 100) * 10 * $ageMod * $charMod * $staffMod * $dailyScale;

                    if ($pts > 0) {
                        $prob = ($pts / 100) * 0.08 * $ageMod * $charMod * $staffMod * $dailyScale;
                        if (mt_rand(1, 10000) <= (int)($prob * 10000)) {
                            $player->$stat = min(99, $player->$stat + 1);
                            $changed = true;
                        }
                        Yii::$app->db->createCommand()->insert('{{%training_log}}', [
                            'team_id' => (int) $team->id,
                            'player_id' => (int) $player->id,
                            'week' => $week,
                            'season' => $season,
                            'stat' => $stat,
                            'xp_gained' => round($xpGained, 3),
                            'new_value' => (int) $player->$stat,
                            'created_at' => $now,
                        ])->execute();
                    } else {
                        // Decay scaled to daily cadence.
                        $decayChance = (int) round(300 * $dailyScale);
                        if ($player->$stat > 30 && mt_rand(1, 10000) <= $decayChance) {
                            $player->$stat = max(1, $player->$stat - 1);
                            $changed = true;
                        }
                    }
                }

                // ── Forma and condition (direct, not probabilistic) ──────────
                $formAlloc = (int)($skillRow['alloc_forma'] ?? 0);
                $condAlloc = (int)($skillRow['alloc_cond']  ?? 0);

                if ($formAlloc > 0) {
                    $formGain = $this->rollScaledPositive((float) $formAlloc * 0.08, $dailyScale);
                    if ($formGain > 0) {
                        $player->form = min(99, (int) $player->form + $formGain);
                        $changed = true;
                    }
                }
                if ($condAlloc > 0) {
                    $condGain = $this->rollScaledPositive((float) $condAlloc * 0.08, $dailyScale);
                    if ($condGain > 0) {
                        $player->condition = min(99, (int) $player->condition + $condGain);
                        $changed = true;
                    }
                }

                Yii::$app->db->createCommand()->insert('{{%training_log}}', [
                    'team_id' => (int) $team->id,
                    'player_id' => (int) $player->id,
                    'week' => $week,
                    'season' => $season,
                    'stat' => 'form',
                    'xp_gained' => round(($formAlloc / 100) * 10 * $dailyScale, 3),
                    'new_value' => (int) $player->form,
                    'created_at' => $now,
                ])->execute();
                Yii::$app->db->createCommand()->insert('{{%training_log}}', [
                    'team_id' => (int) $team->id,
                    'player_id' => (int) $player->id,
                    'week' => $week,
                    'season' => $season,
                    'stat' => 'condition',
                    'xp_gained' => round(($condAlloc / 100) * 10 * $dailyScale, 3),
                    'new_value' => (int) $player->condition,
                    'created_at' => $now,
                ])->execute();

                // SIP-0039 fatigue management + staff mitigation
                $playerFreshDelta = $this->rollScaledSigned($baseFreshnessDelta, $dailyScale);
                if (strtolower((string) $player->character) === 'diligente') {
                    $playerFreshDelta -= 2;
                }
                if ($playerFreshDelta < 0) {
                    $playerFreshDelta += min(2, (int) floor($fitnessCoachEff / 50));
                }
                if ($playerFreshDelta !== 0) {
                    $player->freshness = max(0, min(100, (int) $player->freshness + $playerFreshDelta));
                    $changed = true;
                    $freshnessAdjusted++;
                    Yii::$app->db->createCommand()->insert('{{%training_log}}', [
                        'team_id' => (int) $team->id,
                        'player_id' => (int) $player->id,
                        'week' => $week,
                        'season' => $season,
                        'stat' => 'freshness',
                        'xp_gained' => (float) $playerFreshDelta,
                        'new_value' => (int) $player->freshness,
                        'created_at' => $now,
                    ])->execute();
                }

                // SIP-0039: match-week penalty on heavy load
                if ($matchSoon && $totalLoad > 60) {
                    $formPenalty = (($totalLoad - 60) * 0.1) * $dailyScale;
                    $newForm = max(0, (int) round($player->form - $formPenalty));
                    if ($newForm !== (int) $player->form) {
                        $player->form = $newForm;
                        $changed = true;
                        $formPenalties += $formPenalty;
                        Yii::$app->db->createCommand()->insert('{{%training_log}}', [
                            'team_id' => (int) $team->id,
                            'player_id' => (int) $player->id,
                            'week' => $week,
                            'season' => $season,
                            'stat' => 'form_match_penalty',
                            'xp_gained' => round(-$formPenalty, 3),
                            'new_value' => (int) $player->form,
                            'created_at' => $now,
                        ])->execute();
                    }
                }

                if ($changed) {
                    // Recalculate general_skill as average of 8 skills
                    $player->general_skill = (int) round(
                        ($player->skill_po + $player->skill_df + $player->skill_cn
                        + $player->skill_pa + $player->skill_rg + $player->skill_cr
                        + $player->skill_tc + $player->skill_tr) / 8
                    );
                    $player->save(false);
                    $improved++;
                }

                $talentLevelUps += PlayerAttributeHelper::progressTalentsDaily($player, $skillRow, 80);
            }

            // SIP-0061: write snapshot for progress charts (daily cadence)
            foreach ($players as $player) {
                \app\models\TrainingSnapshot::writeSnapshot($team->id, (int)$player->id, $player);
            }

            $this->stdout(
                "📈 {$team->name}: training={$improved} · talenti +lvl {$talentLevelUps} · "
                . "fatica(load {$totalLoad}) freshnessAdj={$freshnessAdjusted} · "
                . "matchSoon=" . ($matchSoon ? 'yes' : 'no') . " formPenalty=" . round($formPenalties, 2) . "\n"
            );
        }

        return ExitCode::OK;
    }

    /**
     * Reduces injury_weeks by 1 for all injured players.
     * Run once per game-week (Monday cron).
     *
     * Usage: ./yii economy/apply-weekly-recovery
     */
    public function actionApplyWeeklyRecovery(): int
    {
        $injured = Player::find()->where(['>', 'injury_weeks', 0])->all();

        foreach ($injured as $player) {
            $player->injury_weeks = max(0, $player->injury_weeks - 1);
            if ($player->injury_weeks === 0) {
                $player->injury_type = null;
                $player->condition   = 50;
                $this->stdout("✅ {$player->name} è guarito — torna disponibile.\n");
            }
            $player->save(false);
        }

        $this->stdout('Recovery applicato a ' . count($injured) . " giocatori.\n");
        return ExitCode::OK;
    }

    /**
     * Credits match-day revenue to a team's stadium after a home fixture.
     *
     * Usage: ./yii economy/credit-match-revenue <fixture_id> [attendanceFactor]
     *
     * @param int   $fixtureId
     * @param float $attendanceFactor  0.0–1.0 (default 0.70)
     */
    public function actionCreditMatchRevenue(int $fixtureId, float $attendanceFactor = 0.70): int
    {
        $fixture = Fixture::findOne($fixtureId);
        if (!$fixture) {
            $this->stderr("Fixture #$fixtureId non trovata.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $stadium = Stadium::findOne(['team_id' => $fixture->home_team_id]);
        if (!$stadium) {
            $this->stderr("Nessuno stadio per la squadra #{$fixture->home_team_id}.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $revenue = $stadium->creditMatchRevenue($attendanceFactor);

        $this->stdout(sprintf(
            "🏟  %s: +€%s di incasso (%.0f%% di occupazione, %d posti)\n",
            $fixture->homeTeam->name,
            number_format($revenue, 0, ',', '.'),
            $attendanceFactor * 100,
            $stadium->capacity
        ));

        return ExitCode::OK;
    }

    /**
     * Season rollover (SIP-0027): archive standings, expire contracts, budget grants,
     * reset standings, generate next season fixtures, increment competition season.
     *
     * Usage: ./yii economy/season-rollover <competition_id> <new_season>
     */
    public function actionSeasonRollover(int $competitionId, int $newSeason): int
    {
        $competition = Competition::findOne($competitionId);
        if (!$competition) {
            $this->stderr("Competizione #$competitionId non trovata.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($competition->type === 'friendly') {
            $this->stderr("Rollover non supportato per competizioni amichevoli.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($newSeason <= $competition->season) {
            $this->stderr("Nuova stagione non valida: attuale={$competition->season}, richiesta={$newSeason}.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $pendingFixtures = (int) Fixture::find()
            ->where(['competition_id' => $competitionId])
            ->andWhere(['!=', 'status', Fixture::STATUS_FINISHED])
            ->count();

        if ($pendingFixtures > 0) {
            $this->stderr("Rollover bloccato: $pendingFixtures fixture non finite nella competizione #$competitionId.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $isFirstRolloverForSeason = !$this->competitionSeasonAlreadyExists($newSeason);
        $db = Yii::$app->db;
        $tx = $db->beginTransaction();

        try {
            [$rankedTeamIds, $archivedRows] = $this->archiveCompetitionStandings($competition, $newSeason);
            $this->stdout("🗂  Classifica archiviata: {$archivedRows} righe\n");

            $promotionMoves = $this->applyPromotionRelegationHook($competition);
            $this->stdout("↕️  Promozioni/retrocessioni applicate: {$promotionMoves}\n");

            ['expired' => $expired, 'released' => $released] = $this->expireContractsAndReleaseFreeAgents($rankedTeamIds, $newSeason);
            $this->stdout("📋 Contratti scaduti: {$expired} · svincolati: {$released}\n");

            ['expired' => $expiredStaff, 'motivated' => $motivatedDrop] = $this->expireStaffContractsForCompetitionTeams($rankedTeamIds, $newSeason);
            $this->stdout("🧑‍💼 Staff: contratti scaduti {$expiredStaff} · motivazione ridotta {$motivatedDrop}\n");

            $loanReturns = $this->processLoanReturns($newSeason);
            $this->stdout("↩️  Prestiti rientrati: {$loanReturns}\n");

            if ($isFirstRolloverForSeason) {
                $db->createCommand('UPDATE {{%player}} SET age = age + 1')->execute();
                $this->stdout("🎂 Età giocatori aggiornata (+1, eseguito una sola volta per stagione)\n");

                ['evolved' => $evolved, 'retired' => $retired] = $this->applySeasonPlayerEvolutionAndRetirement($newSeason);
                $this->stdout("📈 Progressione skill: {$evolved} · ritirati: {$retired}\n");

                $sponsorRefresh = $this->refreshSponsorMarketForSeason($newSeason);
                $this->stdout(sprintf(
                    "🤝 Sponsor pool: team=%d min=%d target=%d cap=%d | creati=%d rimossi=%d attivi_scaduti=%d tot_sponsor=%d\n",
                    $sponsorRefresh['teams'],
                    $sponsorRefresh['min'],
                    $sponsorRefresh['target'],
                    $sponsorRefresh['max'],
                    $sponsorRefresh['created'],
                    $sponsorRefresh['deleted'],
                    $sponsorRefresh['expiredContracts'],
                    $sponsorRefresh['current']
                ));
            }

            $retiredVeterans = $this->retireVeteransWithProbability($newSeason);
            $this->stdout("🛑 Ritiri veterani (35+): {$retiredVeterans}\n");

            $this->applySeasonBudgetGrants($rankedTeamIds);
            $this->stdout("💰 Budget stagionale allocato alle {$this->countTeams($rankedTeamIds)} squadre del campionato\n");

            $this->resetSeasonRevenueForTeams($rankedTeamIds);
            $this->stdout("🏟  Incassi stadio azzerati per le squadre del campionato\n");

            $nextSeasonTeamIds = $this->currentCompetitionTeamIds($competitionId);
            if (empty($nextSeasonTeamIds)) {
                $nextSeasonTeamIds = $rankedTeamIds;
            }

            Standing::deleteAll(['competition_id' => $competitionId]);
            $this->recreateZeroStandings($competitionId, $nextSeasonTeamIds);
            $this->stdout("📊 Classifica resettata e ricreata per stagione $newSeason\n");

            Fixture::deleteAll([
                'and',
                ['competition_id' => $competitionId],
                ['!=', 'status', Fixture::STATUS_FINISHED],
            ]);
            $fixturesCreated = $this->generateNextSeasonFixtures($competition, $nextSeasonTeamIds);
            $this->stdout("📅 Nuovo calendario generato: {$fixturesCreated} fixture\n");

            ['generated' => $generatedYouth, 'pool' => $poolCount] = $this->maintainPlayerPool(50);
            $cpuFilled = $this->maintainCpuRostersFromPool(18);
            $staffRestocked = $this->restockStaffMarketForManagers();
            $this->stdout("🌱 Post-rollover pool: +{$generatedYouth}, totale {$poolCount}\n");
            $this->stdout("🤖 Post-rollover roster CPU: {$cpuFilled}\n");
            $this->stdout("🧑‍💼 Post-rollover staff market: {$staffRestocked}\n");

            $competition->season = $newSeason;
            $competition->save(false);

            $tx->commit();
            $this->stdout("✅ Rollover completato: {$competition->name} → stagione {$newSeason}\n");
        } catch (\Throwable $e) {
            $tx->rollBack();
            $this->stderr("❌ Rollover fallito: {$e->getMessage()}\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }

    /**
     * Shows the financial summary of all teams.
     *
     * Usage: ./yii economy/summary
     */
    public function actionSummary(): int
    {
        $teams = Team::find()->with(['contracts', 'staff', 'stadium'])->all();

        $this->stdout(sprintf("\n%-25s %12s %12s %12s\n", 'Team', 'Budget', 'Wage Bill', 'Stadium Rev.'));
        $this->stdout(str_repeat('─', 65) . "\n");

        foreach ($teams as $team) {
            $stadium = $team->stadium;
            $this->stdout(sprintf(
                "%-25s %12s %12s %12s\n",
                $team->name,
                '€' . number_format($team->budget, 0, ',', '.'),
                '€' . number_format($team->totalWageBill(), 0, ',', '.'),
                $stadium ? '€' . number_format($stadium->season_revenue, 0, ',', '.') : '  n/a'
            ));
        }

        return ExitCode::OK;
    }

    /**
     * Manual trigger for SIP-0047 generation cycle.
     *
     * Usage: ./yii economy/generate-youth [targetPool=50]
     */
    public function actionGenerateYouth(int $targetPool = 50): int
    {
        ['generated' => $generated, 'pool' => $poolCount] = $this->maintainPlayerPool(max(1, $targetPool));
        $cpuFilled = $this->maintainCpuRostersFromPool(18);
        $staffRestocked = $this->restockStaffMarketForManagers();

        $this->stdout("🌱 Pool giovani: +{$generated}, totale {$poolCount}\n");
        $this->stdout("🤖 Roster CPU reintegrati: {$cpuFilled}\n");
        $this->stdout("🧑‍💼 Staff market restock: {$staffRestocked}\n");

        return ExitCode::OK;
    }

    /**
     * @return array{generated:int,pool:int}
     */
    private function maintainPlayerPool(int $target): array
    {
        $now = time();

        // Cleanup expired/invalid pool entries.
        $expiredIds = PlayerPool::find()
            ->where(['<', 'expires_at', $now])
            ->select('id')
            ->column();
        if (!empty($expiredIds)) {
            PlayerPool::deleteAll(['id' => $expiredIds]);
        }

        $invalidPool = PlayerPool::find()
            ->alias('pp')
            ->leftJoin(['p' => Player::tableName()], 'p.id = pp.player_id')
            ->where(['or', ['p.id' => null], ['not', ['p.team_id' => null]]])
            ->select('pp.id')
            ->column();
        if (!empty($invalidPool)) {
            PlayerPool::deleteAll(['id' => $invalidPool]);
        }

        $current = (int) PlayerPool::find()
            ->alias('pp')
            ->innerJoin(['p' => Player::tableName()], 'p.id = pp.player_id')
            ->where(['p.team_id' => null])
            ->andWhere(['>', 'pp.expires_at', $now])
            ->count();

        $toGenerate = max(0, $target - $current);
        for ($i = 0; $i < $toGenerate; $i++) {
            $this->createGeneratedFreeAgentIntoPool();
        }

        $total = (int) PlayerPool::find()->count();
        return ['generated' => $toGenerate, 'pool' => $total];
    }

    private function createGeneratedFreeAgentIntoPool(): void
    {
        $stats = [
            'skill_po' => random_int(20, 40),
            'skill_df' => random_int(20, 40),
            'skill_cn' => random_int(20, 40),
            'skill_pa' => random_int(20, 40),
            'skill_rg' => random_int(20, 40),
            'skill_cr' => random_int(20, 40),
            'skill_tc' => random_int(20, 40),
            'skill_tr' => random_int(20, 40),
        ];

        $position = $this->randomPositionForPool();
        $profile = WorldData::statProfile($position);
        foreach ($profile['primary'] as $s) {
            $stats[$s] = random_int(40, 62);
        }
        foreach ($profile['secondary'] as $s) {
            $stats[$s] = max($stats[$s], random_int(30, 52));
        }

        $sum = 0;
        foreach (self::ALL_SKILL_STATS as $k) {
            $sum += (int) $stats[$k];
        }
        $general = (int) round($sum / count(self::ALL_SKILL_STATS));
        $general = max(30, min(55, $general));

        $footRoll = random_int(1, 100);
        $foot = $footRoll <= 80 ? 'R' : ($footRoll <= 95 ? 'L' : 'LR');
        $name = WorldData::randomFirstName() . ' ' . WorldData::randomLastName();
        $age = random_int(17, 21);

        $player = new Player();
        $player->team_id = null;
        $player->number = random_int(1, 99);
        $player->name = $name;
        $player->age = $age;
        $player->position = $position;
        $player->foot = $foot;
        $player->skill_po = $stats['skill_po'];
        $player->skill_df = $stats['skill_df'];
        $player->skill_cn = $stats['skill_cn'];
        $player->skill_pa = $stats['skill_pa'];
        $player->skill_rg = $stats['skill_rg'];
        $player->skill_cr = $stats['skill_cr'];
        $player->skill_tc = $stats['skill_tc'];
        $player->skill_tr = $stats['skill_tr'];
        $player->experience = random_int(1, 20);
        $player->general_skill = $general;
        $player->form = random_int(78, 100);
        $player->freshness = random_int(80, 100);
        $player->condition = random_int(80, 100);
        $player->character = WorldData::CHARACTERS[array_rand(WorldData::CHARACTERS)];
        $player->save(false);
        PlayerAttributeHelper::ensureInitialTalents($player);

        $valuator = Yii::$app->has('playerValuator')
            ? Yii::$app->get('playerValuator')
            : new \app\components\PlayerValuator();
        $asking = max(0, (int) round($valuator->marketValue($player) * 0.8, -3));
        $salaryAsk = max(10000, (int) round($valuator->suggestedSalary($player) * 0.7, -2));

        $pool = new PlayerPool();
        $pool->player_id = (int) $player->id;
        $pool->asking_fee = $asking;
        $pool->salary_ask = $salaryAsk;
        $pool->available_since = time();
        $pool->expires_at = time() + (3600 * 24 * 365 * 2);
        $pool->save(false);
    }

    private function randomPositionForPool(): string
    {
        $roll = random_int(1, 100);
        if ($roll <= 12) {
            return 'GK';
        }
        if ($roll <= 42) {
            return 'DF';
        }
        if ($roll <= 74) {
            return 'MF';
        }
        return 'FW';
    }

    private function maintainCpuRostersFromPool(int $minimumRoster): int
    {
        $cpuTeams = Team::find()->where(['is_cpu' => 1])->all();
        $filled = 0;
        $season = $this->currentSeasonForGeneration();

        foreach ($cpuTeams as $team) {
            $roster = Player::find()->where(['team_id' => $team->id])->all();
            $count = count($roster);
            $guard = 0;

            while ($count < $minimumRoster && $guard < 30) {
                $guard++;
                $position = $this->pickNeededPositionForCpu((int) $team->id);
                $pool = PlayerPool::findBestForPosition($position);

                if ($pool && $pool->player) {
                    $player = $pool->player;
                    if ($player->team_id !== null) {
                        $pool->delete();
                        continue;
                    }
                    $player->team_id = (int) $team->id;
                    $player->save(false);

                    $contract = new Contract();
                    $contract->player_id = (int) $player->id;
                    $contract->team_id = (int) $team->id;
                    $contract->salary = (int) $pool->salary_ask;
                    $contract->season_start = $season;
                    $contract->season_end = $season + 2;
                    $contract->status = Contract::STATUS_ACTIVE;
                    $contract->save(false);

                    $pool->delete();
                } else {
                    $this->generatePlayerForCpuTeam((int) $team->id, $position, $season);
                }

                $count++;
                $filled++;
            }
        }

        return $filled;
    }

    private function pickNeededPositionForCpu(int $teamId): string
    {
        $desired = ['GK' => 2, 'DF' => 6, 'MF' => 6, 'FW' => 4];
        $rows = Player::find()
            ->select(['position', 'COUNT(*) AS c'])
            ->where(['team_id' => $teamId])
            ->groupBy(['position'])
            ->asArray()
            ->all();
        $have = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        foreach ($rows as $row) {
            $pos = strtoupper((string) ($row['position'] ?? ''));
            if (isset($have[$pos])) {
                $have[$pos] = (int) $row['c'];
            }
        }

        foreach (['GK', 'DF', 'MF', 'FW'] as $pos) {
            if ($have[$pos] < $desired[$pos]) {
                return $pos;
            }
        }

        return ['GK', 'DF', 'MF', 'FW'][array_rand(['GK', 'DF', 'MF', 'FW'])];
    }

    private function generatePlayerForCpuTeam(int $teamId, string $position, int $season): void
    {
        $seed = new PlayerSeeder();
        $attrs = $seed->buildAttributes(
            $position,
            random_int(1, 99),
            WorldData::randomFirstName() . ' ' . WorldData::randomLastName()
        );

        $player = new Player();
        $player->team_id = $teamId;
        $player->setAttributes($attrs, false);
        $player->save(false);
        PlayerAttributeHelper::ensureInitialTalents($player);

        $valuator = Yii::$app->has('playerValuator')
            ? Yii::$app->get('playerValuator')
            : new \app\components\PlayerValuator();

        $contract = new Contract();
        $contract->player_id = (int) $player->id;
        $contract->team_id = $teamId;
        $contract->salary = max(10000, (int) $valuator->suggestedSalary($player));
        $contract->season_start = $season;
        $contract->season_end = $season + 2;
        $contract->status = Contract::STATUS_ACTIVE;
        $contract->save(false);
    }

    private function restockStaffMarketForManagers(): int
    {
        $roles = [
            Staff::ROLE_HEAD_COACH,
            Staff::ROLE_ASSISTANT_COACH,
            Staff::ROLE_GOALKEEPING_COACH,
            Staff::ROLE_FITNESS_COACH,
            Staff::ROLE_SCOUT,
        ];
        $baseSalary = [
            Staff::ROLE_HEAD_COACH => 80000,
            Staff::ROLE_ASSISTANT_COACH => 40000,
            Staff::ROLE_GOALKEEPING_COACH => 45000,
            Staff::ROLE_FITNESS_COACH => 30000,
            Staff::ROLE_SCOUT => 25000,
        ];

        $created = 0;
        $now = time();
        $teams = Team::find()->where(['is_cpu' => 0])->andWhere(['not', ['user_id' => null]])->all();
        foreach ($teams as $team) {
            foreach ($roles as $role) {
                $available = (int) StaffMarket::find()
                    ->where(['team_id' => $team->id, 'role' => $role])
                    ->andWhere(['>', 'expires_at', $now])
                    ->count();

                $missing = max(0, 3 - $available);
                for ($i = 0; $i < $missing; $i++) {
                    $ability = random_int(30, 95);
                    $experience = random_int(5, 30);
                    $motivation = random_int(60, 100);
                    $salary = (int) round(($baseSalary[$role] ?? 30000) * ($ability / 50));

                    $cand = new StaffMarket();
                    $cand->team_id = (int) $team->id;
                    $cand->name = WorldData::randomFirstName() . ' ' . WorldData::randomLastName();
                    $cand->role = $role;
                    $cand->ability = $ability;
                    $cand->experience = $experience;
                    $cand->motivation = $motivation;
                    $cand->salary = $salary;
                    $cand->contract_length = random_int(1, 3);
                    $cand->negotiations = 4;
                    $cand->raise_used = 0;
                    $cand->generated_at = $now;
                    $cand->expires_at = $now + (3600 * 24 * 21);
                    $cand->save(false);
                    $created++;
                }
            }
        }

        return $created;
    }

    private function processLoanReturns(int $newSeason): int
    {
        $loans = Transfer::find()
            ->where(['transfer_type' => Transfer::TYPE_LOAN, 'status' => Transfer::STATUS_COMPLETED])
            ->andWhere(['not', ['loan_return_season' => null]])
            ->andWhere(['<=', 'loan_return_season', $newSeason])
            ->all();

        $returned = 0;
        foreach ($loans as $loan) {
            $player = $loan->player;
            if (!$player) {
                $loan->loan_return_season = null;
                $loan->save(false, ['loan_return_season', 'updated_at']);
                continue;
            }
            if ((int) $player->team_id === (int) $loan->to_team_id && $loan->from_team_id) {
                $player->team_id = (int) $loan->from_team_id;
                $player->save(false);
                $returned++;
            }
            $loan->loan_return_season = null;
            $loan->save(false, ['loan_return_season', 'updated_at']);
        }

        return $returned;
    }

    private function retireVeteransWithProbability(int $newSeason): int
    {
        $players = Player::find()->where(['>=', 'age', 35])->all();
        $retired = 0;
        $season = max(1, $newSeason - 1);
        $now = time();

        foreach ($players as $player) {
            $chance = min(100, max(0, ((int) $player->age - 34) * 20));
            $mustRetire = (int) $player->age >= 39;
            $roll = random_int(1, 100);
            if (!$mustRetire && $roll > $chance) {
                continue;
            }

            $teamId = $player->team_id !== null ? (int) $player->team_id : null;
            $team = $teamId ? Team::findOne($teamId) : null;

            Yii::$app->db->createCommand()->insert('{{%player_event}}', [
                'player_id' => (int) $player->id,
                'team_id' => $teamId,
                'season' => $season,
                'type' => 'retirement',
                'message' => sprintf('%s si ritira dal calcio giocato.', $player->name),
                'created_at' => $now,
            ])->execute();

            if ($team && $team->user_id) {
                \app\components\NewsService::create(
                    (int) $team->user_id,
                    \app\models\NewsItem::CAT_SYSTEM,
                    '🧓',
                    "{$player->name} annuncia il ritiro",
                    'Il giocatore lascia il calcio professionistico al termine della stagione.'
                );
            }

            PlayerPool::deleteAll(['player_id' => (int) $player->id]);
            $player->delete();
            $retired++;
        }

        return $retired;
    }

    private function currentSeasonForGeneration(): int
    {
        $season = (int) Competition::find()->where(['!=', 'type', 'friendly'])->max('season');
        return $season > 0 ? $season : 1;
    }

    private function competitionSeasonAlreadyExists(int $season): bool
    {
        return Competition::find()
            ->where(['!=', 'type', 'friendly'])
            ->andWhere(['>=', 'season', $season])
            ->exists();
    }

    /**
     * Maintains sponsor catalog yearly.
     * Rules:
     * - lower bound: 10% of total teams
     * - upper bound: 25% of total teams
     * - target fill: random between 20% and 25%
     * - delete only random sponsors unused by any team (or non-active fallback)
     *
     * @return array{teams:int,min:int,max:int,target:int,current:int,created:int,deleted:int,expiredContracts:int}
     */
    private function refreshSponsorMarketForSeason(int $season): array
    {
        $expiredContracts = SponsorService::expireContracts();

        $teamCount = (int) Team::find()->count();
        if ($teamCount <= 0) {
            return [
                'teams' => 0,
                'min' => 0,
                'max' => 0,
                'target' => 0,
                'current' => (int) Sponsor::find()->count(),
                'created' => 0,
                'deleted' => 0,
                'expiredContracts' => (int) $expiredContracts,
            ];
        }

        $minSponsors = max(1, (int) ceil($teamCount * 0.10));
        $maxSponsors = max($minSponsors, (int) floor($teamCount * 0.25));
        $targetMin = max($minSponsors, (int) ceil($teamCount * 0.20));
        $targetMax = max($targetMin, $maxSponsors);
        $targetSponsors = random_int($targetMin, $targetMax);

        $created = 0;
        $deleted = 0;
        $currentSponsors = (int) Sponsor::find()->count();

        if ($currentSponsors < $targetSponsors) {
            $created = $this->createRandomSponsors($targetSponsors - $currentSponsors, $season);
        } elseif ($currentSponsors > $maxSponsors) {
            $deleted = $this->deleteRandomUnusedSponsors($currentSponsors - $maxSponsors);
        }

        return [
            'teams' => $teamCount,
            'min' => $minSponsors,
            'max' => $maxSponsors,
            'target' => $targetSponsors,
            'current' => (int) Sponsor::find()->count(),
            'created' => $created,
            'deleted' => $deleted,
            'expiredContracts' => (int) $expiredContracts,
        ];
    }

    private function createRandomSponsors(int $count, int $season): int
    {
        if ($count <= 0) {
            return 0;
        }

        $created = 0;
        $attempts = 0;
        $maxAttempts = max(20, $count * 25);

        while ($created < $count && $attempts < $maxAttempts) {
            $attempts++;
            $name = $this->generateSponsorName();
            if (Sponsor::find()->where(['name' => $name])->exists()) {
                continue;
            }

            $base = random_int(30_000, 180_000);
            $inflation = 1 + (max(0, $season - 1) * 0.03);
            $base = (int) (round(($base * $inflation) / 1_000) * 1_000);
            $winBonus = (int) round($base * (random_int(8, 18) / 100));

            $sponsor = new Sponsor();
            $sponsor->name = $name;
            $sponsor->base_payment = $base;
            $sponsor->win_bonus = $winBonus;
            $sponsor->duration_seasons = random_int(1, 2);
            $sponsor->prestige_required = random_int(0, 40);

            if ($sponsor->save(false)) {
                $created++;
            }
        }

        return $created;
    }

    private function deleteRandomUnusedSponsors(int $count): int
    {
        if ($count <= 0) {
            return 0;
        }

        $deleted = 0;

        // First pass: sponsors never used by any team.
        $neverUsedIds = Sponsor::find()
            ->alias('s')
            ->leftJoin('{{%team_sponsor}} ts', 'ts.sponsor_id = s.id')
            ->where(['ts.id' => null])
            ->select('s.id')
            ->column();

        shuffle($neverUsedIds);
        $firstBatch = array_slice($neverUsedIds, 0, $count);
        if (!empty($firstBatch)) {
            $deleted += Sponsor::deleteAll(['id' => $firstBatch]);
        }

        $remaining = $count - $deleted;
        if ($remaining <= 0) {
            return $deleted;
        }

        // Fallback: no active usage (old/expired links only).
        $inactiveIds = Sponsor::find()
            ->alias('s')
            ->where([
                'not exists',
                TeamSponsor::find()
                    ->alias('ts')
                    ->select('1')
                    ->where('ts.sponsor_id = s.id')
                    ->andWhere(['ts.status' => 'active']),
            ])
            ->select('s.id')
            ->column();

        shuffle($inactiveIds);
        $secondBatch = array_slice(array_values(array_diff($inactiveIds, $firstBatch)), 0, $remaining);
        if (!empty($secondBatch)) {
            TeamSponsor::deleteAll(['sponsor_id' => $secondBatch]);
            $deleted += Sponsor::deleteAll(['id' => $secondBatch]);
        }

        return $deleted;
    }

    private function generateSponsorName(): string
    {
        $prefix = self::SPONSOR_NAME_PREFIXES[array_rand(self::SPONSOR_NAME_PREFIXES)];
        $suffix = self::SPONSOR_NAME_SUFFIXES[array_rand(self::SPONSOR_NAME_SUFFIXES)];
        $year = (int) date('y');

        if (random_int(1, 100) <= 35) {
            return sprintf('%s %s %d', $prefix, $suffix, random_int(1, 99));
        }

        if (random_int(1, 100) <= 20) {
            return sprintf('%s %s %d', $prefix, $suffix, $year);
        }

        return sprintf('%s %s', $prefix, $suffix);
    }

    /**
     * @return array{0:int[],1:int} ranked team IDs, archived rows count
     */
    private function archiveCompetitionStandings(Competition $competition, int $newSeason): array
    {
        $standings = Standing::find()
            ->where(['competition_id' => $competition->id])
            ->orderBy([
                'points' => SORT_DESC,
                new \yii\db\Expression('(goals_for - goals_against) DESC'),
                'goals_for' => SORT_DESC,
                'team_id' => SORT_ASC,
            ])
            ->all();

        if (empty($standings)) {
            throw new \RuntimeException('Nessuna classifica trovata da archiviare.');
        }

        $rows = [];
        $teamIds = [];
        $now = time();
        $completedSeason = $newSeason - 1;

        foreach ($standings as $i => $s) {
            $finalPos = $i + 1;
            $teamIds[] = (int) $s->team_id;
            $rows[] = [
                'standing_id'    => (int) $s->id,
                'competition_id' => (int) $s->competition_id,
                'team_id'        => (int) $s->team_id,
                'season'         => $completedSeason,
                'final_position' => $finalPos,
                'points'         => (int) $s->points,
                'played'         => (int) $s->played,
                'won'            => (int) $s->won,
                'drawn'          => (int) $s->drawn,
                'lost'           => (int) $s->lost,
                'goals_for'      => (int) $s->goals_for,
                'goals_against'  => (int) $s->goals_against,
                'archived_at'    => $now,
            ];
        }

        Yii::$app->db->createCommand()->batchInsert('{{%standing_archive}}', [
            'standing_id',
            'competition_id',
            'team_id',
            'season',
            'final_position',
            'points',
            'played',
            'won',
            'drawn',
            'lost',
            'goals_for',
            'goals_against',
            'archived_at',
        ], $rows)->execute();

        return [$teamIds, count($rows)];
    }

    /**
     * @param int[] $rankedTeamIds
     */
    private function applySeasonBudgetGrants(array $rankedTeamIds): void
    {
        $n = count($rankedTeamIds);
        foreach ($rankedTeamIds as $idx => $teamId) {
            $team = Team::findOne($teamId);
            if (!$team) {
                continue;
            }
            $pos = $idx + 1;
            $grant = self::BASE_SEASON_GRANT + (self::POSITION_BONUS * max(0, $n - $pos));
            $team->budget += $grant;
            $team->save(false);
        }
    }

    /**
     * @param int[] $competitionTeamIds
     * @return array{expired:int,released:int}
     */
    private function expireContractsAndReleaseFreeAgents(array $competitionTeamIds, int $newSeason): array
    {
        if (empty($competitionTeamIds)) {
            return ['expired' => 0, 'released' => 0];
        }

        $expiredContracts = Contract::find()
            ->select(['id', 'player_id'])
            ->where([
                'team_id' => $competitionTeamIds,
                'status' => Contract::STATUS_ACTIVE,
            ])
            ->andWhere(['<', 'season_end', $newSeason])
            ->asArray()
            ->all();

        if (empty($expiredContracts)) {
            return ['expired' => 0, 'released' => 0];
        }

        $contractIds = array_map(static fn(array $row): int => (int) $row['id'], $expiredContracts);
        $playerIds = array_values(array_unique(array_map(static fn(array $row): int => (int) $row['player_id'], $expiredContracts)));

        $expired = Contract::updateAll(
            ['status' => Contract::STATUS_EXPIRED],
            ['id' => $contractIds]
        );

        $released = 0;
        if (!empty($playerIds)) {
            $released = Player::updateAll(['team_id' => null], ['id' => $playerIds]);
        }

        return ['expired' => (int) $expired, 'released' => (int) $released];
    }

    /**
     * @param int[] $competitionTeamIds
     * @return array{expired:int,motivated:int}
     */
    private function expireStaffContractsForCompetitionTeams(array $competitionTeamIds, int $newSeason): array
    {
        if (empty($competitionTeamIds)) {
            return ['expired' => 0, 'motivated' => 0];
        }

        $expiredRows = Staff::find()
            ->where(['team_id' => $competitionTeamIds])
            ->andWhere(['<', 'contract_ends', $newSeason])
            ->all();

        $expiredCount = 0;
        foreach ($expiredRows as $staff) {
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
            $history->left_season = $newSeason - 1;
            $history->left_reason = 'expired';
            $history->created_at = time();
            $history->save(false);

            $teamName = Team::find()->select('name')->where(['id' => (int) $staff->team_id])->scalar();
            $this->stdout("📣 Contratto scaduto: {$staff->name} lascia {$teamName}.\n");
            $staff->delete();
            $expiredCount++;
        }

        $motivatedDrop = Staff::updateAll(
            ['motivation' => new \yii\db\Expression('GREATEST(0, motivation - 10)')],
            [
                'and',
                ['team_id' => $competitionTeamIds],
                ['contract_ends' => $newSeason],
            ]
        );

        return ['expired' => $expiredCount, 'motivated' => (int) $motivatedDrop];
    }

    /**
     * @return array{evolved:int,retired:int}
     */
    private function applySeasonPlayerEvolutionAndRetirement(int $newSeason): array
    {
        $players = Player::find()->all();
        $evolved = 0;

        foreach ($players as $player) {
            $this->applyGrowthDecayToPlayer($player);
            $player->general_skill = $this->recalculateGeneralSkill($player);
            $player->save(false);
            $evolved++;
        }

        $retired = $this->retireFreeAgentsOverAge($newSeason);

        return ['evolved' => $evolved, 'retired' => $retired];
    }

    private function applyGrowthDecayToPlayer(Player $player): void
    {
        [$primaryStats, $secondaryStats] = $this->resolveStatGroups($player->position);
        [$primaryRange, $secondaryRange] = $this->resolveAgeDeltaRanges((int) $player->age);

        foreach ($primaryStats as $stat) {
            $current = (int) $player->{$stat};
            $delta = random_int($primaryRange[0], $primaryRange[1]);
            $player->{$stat} = $this->clampSkill($current + $delta);
        }

        foreach ($secondaryStats as $stat) {
            $current = (int) $player->{$stat};
            $delta = random_int($secondaryRange[0], $secondaryRange[1]);
            $player->{$stat} = $this->clampSkill($current + $delta);
        }
    }

    /**
     * @return array{0:int,1:int}
     */
    private function resolveAgeDeltaRanges(int $age): array
    {
        return match (true) {
            $age < 23 => [[0, 3], [0, 2]],
            $age <= 27 => [[0, 1], [0, 1]],
            $age <= 30 => [[0, 0], [0, 0]],
            $age <= 34 => [[-2, 0], [-1, 0]],
            default => [[-4, -1], [-2, 0]],
        };
    }

    /**
     * @return array{0:string[],1:string[]}
     */
    private function resolveStatGroups(string $position): array
    {
        $p = strtoupper($position);

        if (in_array($p, ['GK', 'PO'], true)) {
            return [['skill_po'], ['skill_rg', 'skill_tc']];
        }
        if (in_array($p, ['DF', 'D', 'DS', 'DD'], true)) {
            return [['skill_df', 'skill_cn'], ['skill_pa', 'skill_tc']];
        }
        if (in_array($p, ['MF', 'C', 'CS', 'CD'], true)) {
            return [['skill_cn', 'skill_pa'], ['skill_rg', 'skill_tc', 'skill_cr']];
        }
        if (in_array($p, ['FW', 'A', 'AS', 'AD'], true)) {
            return [['skill_tr', 'skill_cr'], ['skill_pa', 'skill_rg', 'skill_tc']];
        }

        return [['skill_pa', 'skill_cn'], ['skill_df', 'skill_rg', 'skill_tc']];
    }

    private function recalculateGeneralSkill(Player $player): int
    {
        $total = 0;
        foreach (self::ALL_SKILL_STATS as $stat) {
            $total += (int) $player->{$stat};
        }

        return (int) round($total / count(self::ALL_SKILL_STATS));
    }

    private function clampSkill(int $value): int
    {
        return max(1, min(99, $value));
    }

    private function retireFreeAgentsOverAge(int $newSeason): int
    {
        $retiredPlayers = Player::find()
            ->where(['team_id' => null])
            ->andWhere(['>', 'age', 38])
            ->all();

        if (empty($retiredPlayers)) {
            return 0;
        }

        $now = time();
        $rows = [];
        $playerIds = [];
        $retiredSeason = $newSeason - 1;

        foreach ($retiredPlayers as $player) {
            $playerIds[] = (int) $player->id;
            $rows[] = [
                'player_id' => (int) $player->id,
                'team_id' => null,
                'season' => $retiredSeason,
                'type' => 'retirement',
                'message' => sprintf('%s si ritira dal calcio professionistico.', $player->name),
                'created_at' => $now,
            ];
        }

        Yii::$app->db->createCommand()->batchInsert('{{%player_event}}', [
            'player_id',
            'team_id',
            'season',
            'type',
            'message',
            'created_at',
        ], $rows)->execute();

        return Player::deleteAll(['id' => $playerIds]);
    }

    private function applyPromotionRelegationHook(Competition $competition): int
    {
        $moves = 0;

        if ($competition->tier === Competition::TIER_C) {
            $moves += $this->moveTopHumanWithCpuSwap($competition, Competition::TIER_B);
            // Option 1: eliminate worst CPU in Serie C and seed a fresh one
            $moves += $this->replaceBottomCpuInSerieC($competition);
            return $moves;
        }

        if ($competition->tier === Competition::TIER_B) {
            $moves += $this->moveTopHumanWithCpuSwap($competition, Competition::TIER_A);
            $moves += $this->moveBottomHumanWithCpuSwap($competition, Competition::TIER_C);
            return $moves;
        }

        if ($competition->tier === Competition::TIER_A) {
            $moves += $this->moveBottomHumanWithCpuSwap($competition, Competition::TIER_B);
        }

        return $moves;
    }

    /**
     * Eliminates the worst CPU team in a Serie C girone and replaces it with a
     * freshly generated CPU team. This refreshes the league each season.
     * Human teams are never eliminated.
     */
    private function replaceBottomCpuInSerieC(Competition $competition): int
    {
        $standing = $this->findCpuStanding($competition->id, false); // false = worst
        if ($standing === null) {
            return 0;
        }

        $oldTeam = Team::findOne($standing->team_id);
        if (!$oldTeam || !$oldTeam->is_cpu) {
            return 0;
        }

        $oldName = $oldTeam->name;

        // Delete all data for the outgoing CPU team
        $playerIds = Player::find()->select('id')->where(['team_id' => $oldTeam->id])->column();
        if (!empty($playerIds)) {
            Contract::deleteAll(['player_id' => $playerIds]);
            $formationIds = Formation::find()->select('id')->where(['team_id' => $oldTeam->id])->column();
            if (!empty($formationIds)) {
                FormationSlot::deleteAll(['formation_id' => $formationIds]);
            }
            Formation::deleteAll(['team_id' => $oldTeam->id]);
            Player::deleteAll(['team_id' => $oldTeam->id]);
        }
        Standing::deleteAll(['team_id' => $oldTeam->id]);
        Stadium::deleteAll(['team_id' => $oldTeam->id]);
        $oldTeam->delete();

        // Seed a fresh CPU team into this Serie C girone
        $worldSeeder = new WorldSeeder();
        $newTeam = $worldSeeder->seedCpuTeamForCompetition($competition, 3_000_000);

        $this->stdout("🔄 Serie C refresh: {$oldName} eliminata → {$newTeam->name} inserita nel girone {$competition->group_number}\n");
        return 1;
    }

    private function moveTopHumanWithCpuSwap(Competition $sourceCompetition, int $targetTier): int
    {
        $sourceStanding = $this->findHumanStanding($sourceCompetition->id, true);
        if ($sourceStanding === null) {
            return 0;
        }

        $targetCompetition = $this->findTargetCompetition($sourceCompetition, $targetTier);
        if ($targetCompetition === null) {
            return 0;
        }

        $targetCpuStanding = $this->findCpuStanding($targetCompetition->id, false);
        if ($targetCpuStanding === null) {
            return 0;
        }

        $team = Team::findOne($sourceStanding->team_id);
        $result = $this->swapStandingCompetition($sourceStanding, $targetCpuStanding);
        if ($result > 0 && $team) {
            $tierName = Competition::TIER_NAMES[$targetTier] ?? "Tier {$targetTier}";
            $this->stdout("⬆️  Promozione: {$team->name} → {$tierName} (girone {$targetCompetition->group_number})\n");
            $this->publishDivisionMoveNews($team, $sourceCompetition, $targetCompetition, true);
        }
        return $result;
    }

    private function moveBottomHumanWithCpuSwap(Competition $sourceCompetition, int $targetTier): int
    {
        $sourceStanding = $this->findHumanStanding($sourceCompetition->id, false);
        if ($sourceStanding === null) {
            return 0;
        }

        $targetCompetition = $this->findTargetCompetition($sourceCompetition, $targetTier);
        if ($targetCompetition === null) {
            return 0;
        }

        $targetCpuStanding = $this->findCpuStanding($targetCompetition->id, false);
        if ($targetCpuStanding === null) {
            return 0;
        }

        $team = Team::findOne($sourceStanding->team_id);
        $result = $this->swapStandingCompetition($sourceStanding, $targetCpuStanding);
        if ($result > 0 && $team) {
            $tierName = Competition::TIER_NAMES[$targetTier] ?? "Tier {$targetTier}";
            $this->stdout("⬇️  Retrocessione: {$team->name} → {$tierName} (girone {$targetCompetition->group_number})\n");
            $this->publishDivisionMoveNews($team, $sourceCompetition, $targetCompetition, false);
        }
        return $result;
    }

    private function publishDivisionMoveNews(
        Team $team,
        Competition $fromCompetition,
        Competition $toCompetition,
        bool $isPromotion
    ): void {
        if (!$team->user_id) {
            return;
        }

        $tierName = Competition::TIER_NAMES[(int)$toCompetition->tier] ?? $toCompetition->getLabel();
        $title = $isPromotion
            ? "Promozione in {$tierName}"
            : "Retrocessione in {$tierName}";
        $body = $isPromotion
            ? sprintf('%s sale da %s a %s.', $team->name, $fromCompetition->getLabel(), $toCompetition->getLabel())
            : sprintf('%s scende da %s a %s.', $team->name, $fromCompetition->getLabel(), $toCompetition->getLabel());

        NewsService::create(
            (int) $team->user_id,
            NewsItem::CAT_SYSTEM,
            $isPromotion ? '⬆️' : '⬇️',
            $title,
            $body,
            'index.php?r=standing%2Findex&competitionId=' . (int) $toCompetition->id,
            1
        );
    }

    private function findTargetCompetition(Competition $sourceCompetition, int $targetTier): ?Competition
    {
        $target = Competition::find()
            ->where([
                'type' => 'league',
                'tier' => $targetTier,
                'group_number' => $sourceCompetition->group_number,
                'season' => $sourceCompetition->season,
            ])
            ->one();

        if ($target !== null) {
            return $target;
        }

        return Competition::find()
            ->where([
                'type' => 'league',
                'tier' => $targetTier,
                'season' => $sourceCompetition->season,
            ])
            ->orderBy(['group_number' => SORT_ASC, 'id' => SORT_ASC])
            ->one();
    }

    private function findHumanStanding(int $competitionId, bool $top): ?Standing
    {
        $query = Standing::find()
            ->alias('s')
            ->innerJoin(Team::tableName() . ' t', 't.id = s.team_id')
            ->where(['s.competition_id' => $competitionId])
            ->andWhere(['t.is_cpu' => 0])
            ->andWhere(['is not', 't.user_id', null]);

        if ($top) {
            $query->orderBy([
                's.points' => SORT_DESC,
                new \yii\db\Expression('(s.goals_for - s.goals_against) DESC'),
                's.goals_for' => SORT_DESC,
                's.team_id' => SORT_ASC,
            ]);
        } else {
            $query->orderBy([
                's.points' => SORT_ASC,
                new \yii\db\Expression('(s.goals_for - s.goals_against) ASC'),
                's.goals_for' => SORT_ASC,
                's.team_id' => SORT_DESC,
            ]);
        }

        return $query->one();
    }

    private function findCpuStanding(int $competitionId, bool $top): ?Standing
    {
        $query = Standing::find()
            ->alias('s')
            ->innerJoin(Team::tableName() . ' t', 't.id = s.team_id')
            ->where(['s.competition_id' => $competitionId])
            ->andWhere(['t.is_cpu' => 1])
            ->andWhere(['t.user_id' => null]);

        if ($top) {
            $query->orderBy([
                's.points' => SORT_DESC,
                new \yii\db\Expression('(s.goals_for - s.goals_against) DESC'),
                's.goals_for' => SORT_DESC,
                's.team_id' => SORT_ASC,
            ]);
        } else {
            $query->orderBy([
                's.points' => SORT_ASC,
                new \yii\db\Expression('(s.goals_for - s.goals_against) ASC'),
                's.goals_for' => SORT_ASC,
                's.team_id' => SORT_DESC,
            ]);
        }

        return $query->one();
    }

    private function swapStandingCompetition(Standing $humanStanding, Standing $cpuStanding): int
    {
        $from = (int) $humanStanding->competition_id;
        $to = (int) $cpuStanding->competition_id;

        if ($from === $to) {
            return 0;
        }

        $humanStanding->competition_id = $to;
        $cpuStanding->competition_id = $from;
        $humanStanding->save(false);
        $cpuStanding->save(false);

        return 1;
    }

    /**
     * @return int[]
     */
    private function currentCompetitionTeamIds(int $competitionId): array
    {
        $rows = Standing::find()
            ->where(['competition_id' => $competitionId])
            ->orderBy([
                'points' => SORT_DESC,
                new \yii\db\Expression('(goals_for - goals_against) DESC'),
                'goals_for' => SORT_DESC,
                'team_id' => SORT_ASC,
            ])
            ->all();

        $teamIds = [];
        foreach ($rows as $row) {
            $teamIds[] = (int) $row->team_id;
        }

        return $teamIds;
    }

    /**
     * @param int[] $teamIds
     */
    private function resetSeasonRevenueForTeams(array $teamIds): void
    {
        if (empty($teamIds)) {
            return;
        }
        Stadium::updateAll(['season_revenue' => 0], ['team_id' => $teamIds]);
    }

    /**
     * @param int[] $teamIds
     */
    private function recreateZeroStandings(int $competitionId, array $teamIds): void
    {
        foreach ($teamIds as $teamId) {
            $standing = new Standing();
            $standing->competition_id = $competitionId;
            $standing->team_id = $teamId;
            $standing->points = 0;
            $standing->played = 0;
            $standing->won = 0;
            $standing->drawn = 0;
            $standing->lost = 0;
            $standing->goals_for = 0;
            $standing->goals_against = 0;
            $standing->save(false);
        }
    }

    /**
     * @param int[] $rankedTeamIds
     */
    private function generateNextSeasonFixtures(Competition $competition, array $rankedTeamIds): int
    {
        $teams = Team::find()
            ->where(['id' => $rankedTeamIds])
            ->indexBy('id')
            ->all();

        $orderedTeams = [];
        foreach ($rankedTeamIds as $id) {
            if (isset($teams[$id])) {
                $orderedTeams[] = $teams[$id];
            }
        }

        if (count($orderedTeams) < 2) {
            throw new \RuntimeException('Numero squadre insufficiente per generare calendario.');
        }

        // Start at least one week after rollover date.
        $kickoffBase = strtotime('next Saturday midnight', time() + 7 * 86400);
        if ($kickoffBase === false) {
            $kickoffBase = time() + 7 * 86400;
        }

        (new FixtureSeeder())->seed($orderedTeams, $competition, $kickoffBase);

        $n = count($orderedTeams);
        return $n * ($n - 1);
    }

    private function dailyTrainingScale(): float
    {
        $raw = trim((string) getenv('GM_TRAINING_DAILY_SCALE'));
        if ($raw !== '' && is_numeric($raw)) {
            $v = (float) $raw;
            if ($v > 0.0 && $v <= 2.0) {
                return $v;
            }
        }

        return 1 / 7;
    }

    private function rollScaledPositive(float $base, float $scale): int
    {
        $scaled = max(0.0, $base * $scale);
        if ($scaled <= 0.0) {
            return 0;
        }
        $whole = (int) floor($scaled);
        $frac = $scaled - $whole;
        if ($frac > 0 && mt_rand(1, 10000) <= (int) round($frac * 10000)) {
            $whole++;
        }
        return $whole;
    }

    private function rollScaledSigned(int $baseDelta, float $scale): int
    {
        if ($baseDelta === 0) {
            return 0;
        }
        $sign = $baseDelta > 0 ? 1 : -1;
        return $sign * $this->rollScaledPositive((float) abs($baseDelta), $scale);
    }

    /**
     * @param int[] $teamIds
     */
    private function countTeams(array $teamIds): int
    {
        return count($teamIds);
    }

    /**
     * Computes attendance factor (0.0–1.0) for a competitive fixture based on:
     *  - Home team league position (top = more fans)
     *  - Away team league position (rival/top team = more fans)
     *  - Season round (opening/closing = more fans)
     */
    private function computeCompetitiveAttendance(Fixture $fixture): float
    {
        $base = 0.60; // base 60%

        // ── Home team standing position ──────────────────────────────────
        $homeStanding = Standing::find()
            ->where(['team_id' => $fixture->home_team_id, 'competition_id' => $fixture->competition_id])
            ->one();
        $totalTeams = (int) Standing::find()
            ->where(['competition_id' => $fixture->competition_id])
            ->count();

        if ($homeStanding && $totalTeams > 0) {
            $posRatio = 1 - (($homeStanding->position - 1) / max(1, $totalTeams - 1));
            // top team: +15%, bottom team: -15%
            $base += ($posRatio - 0.5) * 0.30;
        }

        // ── Away team appeal ─────────────────────────────────────────────
        $awayStanding = Standing::find()
            ->where(['team_id' => $fixture->away_team_id, 'competition_id' => $fixture->competition_id])
            ->one();
        if ($awayStanding && $totalTeams > 0) {
            $awayRatio = 1 - (($awayStanding->position - 1) / max(1, $totalTeams - 1));
            // top away team: +10%
            $base += ($awayRatio - 0.5) * 0.20;
        }

        // ── Season round effect ──────────────────────────────────────────
        $totalRounds = (int) Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM {{%fixture}} WHERE competition_id=:c',
            [':c' => $fixture->competition_id]
        )->queryScalar();
        $roundsPlayed = (int) Yii::$app->db->createCommand(
            'SELECT COUNT(*) FROM {{%fixture}} WHERE competition_id=:c AND status=:s AND match_date <= :d',
            [':c' => $fixture->competition_id, ':s' => Fixture::STATUS_FINISHED, ':d' => $fixture->match_date]
        )->queryScalar();

        if ($totalRounds > 0) {
            $roundProgress = $roundsPlayed / $totalRounds;
            if ($roundProgress <= 0.10) $base += 0.12; // opening day
            elseif ($roundProgress >= 0.90) $base += 0.18; // title/relegation run-in
        }

        return max(0.10, min(1.0, $base));
    }
}
