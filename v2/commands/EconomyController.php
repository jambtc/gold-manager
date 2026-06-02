<?php

declare(strict_types=1);

namespace app\commands;

use app\components\seeders\FixtureSeeder;
use app\components\AuctionService;
use app\components\CharacterTraitHelper;
use app\components\PhysicalHelper;
use app\components\FriendlyChallengeService;
use app\components\PlayerAttributeHelper;
use app\components\ScoutingService;
use app\components\TransferWindowService;
use app\components\seeders\PlayerSeeder;
use app\components\WorldData;
use app\components\WorldSeeder;
use app\components\NewsService;
use app\components\NotificationService;
use app\components\SponsorService;
use app\components\TelegramService;
use app\models\Competition;
use app\models\Contract;
use app\models\Fixture;
use app\models\Formation;
use app\models\FormationSlot;
use app\models\FriendlyChallenge;
use app\models\MarketBid;
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
use app\models\TransferOffer;
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
    private const DEFAULT_TRANSFER_POOL_MIN_PLAYERS = 80;
    private const DEFAULT_TRANSFER_POOL_MAX_PLAYERS = 140;
    private const ALL_SKILL_STATS = [
        'skill_po', 'skill_df', 'skill_cn', 'skill_pa',
        'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr', 'skill_cp',
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
                    $delta >= 0 ? '$' : '!',
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

        ['generated' => $generated, 'pool' => $poolCount] = $this->maintainPlayerPool();
        $cpuFilled = $this->maintainCpuRostersFromPool(18);
        $cpuTransfers = $this->runCpuTransferMarketCycle();
        $cpuStaffHired = $this->runCpuStaffHiringCycle();
        $cpuFriendlies = $this->runCpuFriendlyBehavior();
        $staffRestocked = $this->restockStaffMarketForManagers();
        $auctionResult = $this->resolveExpiredMarketBids();
        $this->stdout("🌱 Pool giovani: +{$generated}, totale {$poolCount}\n");
        $this->stdout("🤖 Roster CPU reintegrati: {$cpuFilled}\n");
        $this->stdout("🤖 Mercato CPU: offerte {$cpuTransfers['offersHandled']} · acquisti {$cpuTransfers['bought']} · cessioni {$cpuTransfers['listed']}\n");
        $this->stdout("🤖 Staff CPU: assunzioni {$cpuStaffHired}\n");
        $this->stdout("🤖 Amichevoli CPU: lanciate {$cpuFriendlies['initiated']} · accettate {$cpuFriendlies['accepted']} · rifiutate {$cpuFriendlies['declined']}\n");
        $this->stdout("🧑‍💼 Staff market restock: {$staffRestocked}\n");
        $this->stdout("🔨 Aste risolte: {$auctionResult['resolved']} · vinte {$auctionResult['won']} · perse {$auctionResult['lost']}\n");

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
     * Resolves expired market auctions (players/staff/sponsors).
     * Can be scheduled frequently (e.g. every 5 minutes).
     *
     * Usage: ./yii economy/resolve-market-auctions
     */
    public function actionResolveMarketAuctions(): int
    {
        $result = $this->resolveExpiredMarketBids();
        $this->stdout("🔨 Aste risolte: {$result['resolved']} · vinte {$result['won']} · perse {$result['lost']}\n");
        return ExitCode::OK;
    }

    /**
     * SIP-0081: Send daily digest to all human managers (runs once at noon).
     * Idempotent — double-run on same day sends a second digest (acceptable).
     *
     * Usage: ./yii economy/send-daily-digest
     */
    public function actionSendDailyDigest(): int
    {
        $teams = \app\models\Team::find()
            ->where(['is_cpu' => 0])
            ->andWhere(['not', ['user_id' => null]])
            ->with(['user'])
            ->all();

        $sent = 0;
        foreach ($teams as $team) {
            if (!$team->user_id) {
                continue;
            }
            try {
                $digest = \app\components\DigestBuilder::build($team);
                NotificationService::notify(
                    (int) $team->user_id,
                    \app\models\NewsItem::CAT_SYSTEM,
                    '📊',
                    $digest->title,
                    $digest->body,
                    '',
                    0,
                    $digest->telegramText
                );
                $sent++;
            } catch (\Throwable $e) {
                Yii::warning("DigestBuilder failed for team#{$team->id}: " . $e->getMessage(), 'digest');
            }
        }

        $this->stdout("📊 Daily digest inviato: {$sent} manager\n");
        return ExitCode::OK;
    }

    /**
     * SIP-0078: Return expired loans to their original club.
     * Run every 5 minutes via cron. Idempotent — double-run is safe.
     *
     * Usage: ./yii economy/process-loan-returns
     */
    public function actionProcessLoanReturns(): int
    {
        $returned = $this->processLoanReturnsTimestamp();
        $this->stdout("↩️  Prestiti rientrati: {$returned}\n");
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
        $seasonDaysForTactic = $this->seasonDaysForTacticTraining();
        $tacticSeasonGainCap = 30;
        $tacticSeasonDecayAtZero = 18;
        $hasSetPieceAlloc = $this->hasSetPiecePhysicalAllocColumn();

        $statMap = [
            'alloc_po' => 'skill_po', 'alloc_df' => 'skill_df',
            'alloc_cn' => 'skill_cn', 'alloc_pa' => 'skill_pa',
            'alloc_rg' => 'skill_rg', 'alloc_cr' => 'skill_cr',
            'alloc_tc' => 'skill_tc', 'alloc_tr' => 'skill_tr',
            'alloc_calci_piazzati' => 'skill_cp',
        ];
        $tacticFields = ['pressing','contropiede','possesso','palla_bassa','lancio_lungo','catenaccio','fuorigioco'];

        $cpuTrainingRebalanced = $this->rebalanceCpuTrainingAllocations($season, $now);
        if ($cpuTrainingRebalanced > 0) {
            $this->stdout("🤖 Training CPU auto-bilanciato: {$cpuTrainingRebalanced} squadre\n");
        }

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
                    'lancio_lungo' => 20, 'catenaccio' => 20, 'fuorigioco' => 10,
                ];
            }
            if (!$tacticPlan) {
                $tacticPlan = [
                    'pressing' => 15, 'contropiede' => 15, 'possesso' => 15, 'palla_bassa' => 15,
                    'lancio_lungo' => 10, 'catenaccio' => 15, 'fuorigioco' => 15,
                ];
            }

            // Staff physical bonus
            $staffRows = Yii::$app->db->createCommand(
                'SELECT role, efficiency FROM {{%staff}} WHERE team_id=:t',
                [':t' => $team->id]
            )->queryAll();
            $staffMod = 1.0;
            $headCoachEff = 0.0;
            $assistantCoachEff = 0.0;
            $gkCoachEff = 0.0;
            $fitnessCoachEff = 0.0;
            $doctorEff = 0.0;
            foreach ($staffRows as $s) {
                $role = (string) ($s['role'] ?? '');
                $eff  = max(0.0, (float) ($s['efficiency'] ?? 0));
                if (in_array($role, ['head_coach', 'assistant_coach', 'fitness_coach'], true)) {
                    $staffMod += $eff / 1000;
                }
                switch ($role) {
                    case Staff::ROLE_HEAD_COACH:
                        $headCoachEff = max($headCoachEff, $eff);
                        break;
                    case Staff::ROLE_ASSISTANT_COACH:
                        $assistantCoachEff = max($assistantCoachEff, $eff);
                        break;
                    case Staff::ROLE_GOALKEEPING_COACH:
                        $gkCoachEff = max($gkCoachEff, $eff);
                        break;
                    case Staff::ROLE_FITNESS_COACH:
                        $fitnessCoachEff = max($fitnessCoachEff, $eff);
                        break;
                    case Staff::ROLE_DOCTOR:
                        $doctorEff = max($doctorEff, $eff);
                        break;
                }
            }
            $tacticalStaffMod = 1 + (($headCoachEff * 0.10) / 100);
            $assistantSkillMod = 1 + (($assistantCoachEff * 0.05) / 100);
            $gkSkillMod = 1 + (($gkCoachEff * 0.15) / 100);
            $tacticSeasonBaseline = $this->loadTacticSeasonBaseline(
                (int) $team->id,
                $season,
                $tacticFields,
                $tacticRow
            );

            // Tactical LEVEL progression/decay from daily training plan.
            $tacticChanged = false;
            $tacticUpdate = ['updated_at' => $now];
            foreach ($tacticFields as $field) {
                $value = max(0, min(100, (int) ($tacticRow[$field] ?? 0)));
                $alloc = max(0, min(100, (int) ($tacticPlan[$field] ?? 0)));
                $baseline = max(0, min(100, (int) ($tacticSeasonBaseline[$field] ?? $value)));
                $seasonCap = min(100, $baseline + $tacticSeasonGainCap);
                $original = $value;

                if ($alloc === 0) {
                    // At 0% allocation, tactic decays over time.
                    $expectedDecay = ($tacticSeasonDecayAtZero / max(1, $seasonDaysForTactic)) * $dailyScale;
                    $delta = $this->rollScaledPositive($expectedDecay, 1.0);
                    if ($delta > 0) {
                        $value = max(0, $value - $delta);
                    }
                } elseif ($alloc <= 5) {
                    // At 5% (or lower but non-zero), keep tactic stable.
                    $value = $value;
                } else {
                    // Growth with hard seasonal cap: max +30 if always at 100%.
                    if ($value < $seasonCap) {
                        $intensity = ($alloc - 5) / 95.0; // 5% => 0, 100% => 1
                        $staffAdj = max(0.85, min(1.15, $tacticalStaffMod));
                        $expectedGain = ($tacticSeasonGainCap / max(1, $seasonDaysForTactic))
                            * $intensity
                            * $staffAdj
                            * $dailyScale;
                        $delta = $this->rollScaledPositive($expectedGain, 1.0);
                        if ($delta > 0) {
                            $value = min($seasonCap, $value + $delta);
                        }
                    }
                }

                if ($value !== $original) {
                    $tacticChanged = true;
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
                    'created_at' => $now,
                ], [
                    'pressing' => (int) ($tacticRow['pressing'] ?? 0),
                    'contropiede' => (int) ($tacticRow['contropiede'] ?? 0),
                    'possesso' => (int) ($tacticRow['possesso'] ?? 0),
                    'palla_bassa' => (int) ($tacticRow['palla_bassa'] ?? 0),
                    'lancio_lungo' => (int) ($tacticRow['lancio_lungo'] ?? 0),
                    'catenaccio' => (int) ($tacticRow['catenaccio'] ?? 0),
                    'fuorigioco' => (int) ($tacticRow['fuorigioco'] ?? 0),
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
                    $skillMod = $assistantSkillMod;
                    if ($stat === 'skill_po') {
                        $skillMod *= $gkSkillMod;
                    }
                    $xpGained = ($pts / 100) * 10 * $ageMod * $charMod * $staffMod * $skillMod * $dailyScale;

                    if ($pts > 0) {
                        $prob = ($pts / 100) * 0.08 * $ageMod * $charMod * $staffMod * $skillMod * $dailyScale;
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
                    // SIP-0073: BMI deviation increases fatigue cost
                    $bmiPenalty = PhysicalHelper::trainingFatiguePenalty(
                        (int) ($player->height_cm ?? 180),
                        (int) ($player->weight_kg ?? 75),
                        (string) $player->position
                    );
                    if ($bmiPenalty > 0) {
                        $playerFreshDelta -= $bmiPenalty;
                    }
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
                if ($fitnessCoachEff > 0) {
                    $player->freshness = min(100, (int) $player->freshness + 1);
                    $changed = true;
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

        // SIP-0068: daily decay of quadrant + cell experience
        try {
            (new \app\components\PlayerExperienceService())->applyDailyDecay();
            $this->stdout("🎯 SIP-0068: exp decay applied\n");
        } catch (\Throwable $e) {
            $this->stdout("⚠️ SIP-0068 decay error: {$e->getMessage()}\n");
        }

        $auctionResult = $this->resolveExpiredMarketBids();
        $this->stdout("🔨 Aste risolte: {$auctionResult['resolved']} · vinte {$auctionResult['won']} · perse {$auctionResult['lost']}\n");

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
        $doctorEff = Yii::$app->db->createCommand(
            'SELECT team_id, MAX(efficiency) AS eff FROM {{%staff}} WHERE role=:role GROUP BY team_id',
            [':role' => Staff::ROLE_DOCTOR]
        )->queryAll();
        $doctorMap = [];
        foreach ($doctorEff as $row) {
            $doctorMap[(int) $row['team_id']] = max(0.0, (float) ($row['eff'] ?? 0));
        }

        foreach ($injured as $player) {
            $reduction = 1;
            if ($player->team_id && isset($doctorMap[$player->team_id])) {
                $reduction = 2;
            }
            $player->injury_weeks = max(0, $player->injury_weeks - $reduction);
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

            ['generated' => $generatedYouth, 'pool' => $poolCount] = $this->maintainPlayerPool();
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
     * SIP-0080: Send pre-match notifications for fixtures starting within 15 minutes.
     * Run every minute: * * * * * php yii economy/send-pre-match-notifications
     *
     * Usage: ./yii economy/send-pre-match-notifications
     */
    public function actionSendPreMatchNotifications(): int
    {
        $now     = time();
        $horizon = $now + 900; // 15 minutes
        $grace   = $now - 300; // tolerate short cron drift/restart gaps

        $fixtures = Fixture::find()
            ->with(['homeTeam', 'awayTeam'])
            ->where(['pre_match_notified' => 0])
            ->andWhere(['in', 'status', [Fixture::STATUS_SCHEDULED, Fixture::STATUS_PLAYING]])
            ->andWhere(['between', 'match_date', $grace, $horizon])
            ->all();

        $sent = 0;
        foreach ($fixtures as $fixture) {
            $homeName = (string) ($fixture->homeTeam->name ?? 'Home');
            $awayName = (string) ($fixture->awayTeam->name ?? 'Away');
            $linkUrl  = $this->safeUrl('/fixture/view', ['id' => $fixture->id]);

            // Notify home manager
            if ($fixture->homeTeam?->user_id) {
                NotificationService::preMatch(
                    (int) $fixture->homeTeam->user_id,
                    $homeName, $awayName,
                    (int) $fixture->id,
                    $linkUrl
                );
                $sent++;
            }

            // Notify away manager
            if ($fixture->awayTeam?->user_id
                && $fixture->awayTeam->user_id !== $fixture->homeTeam?->user_id
            ) {
                NotificationService::preMatch(
                    (int) $fixture->awayTeam->user_id,
                    $homeName, $awayName,
                    (int) $fixture->id,
                    $linkUrl
                );
                $sent++;
            }

            // Mark as notified
            Fixture::updateAll(['pre_match_notified' => 1], ['id' => $fixture->id]);
        }

        $this->stdout("⏰ Pre-match notifications sent: {$sent} (for " . count($fixtures) . " fixtures)\n");
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
    private function maintainPlayerPool(?int $forcedTarget = null): array
    {
        $now = time();
        $auctionHours = max(1, AuctionService::hoursForType(MarketBid::TYPE_TRANSFER_MARKET));
        $ttlSeconds = $auctionHours * 3600;
        ['min' => $minPool, 'max' => $maxPool, 'target' => $targetPool] = $this->transferPoolBounds($forcedTarget);

        // Expired pool rows leave the market; corresponding free agents are removed.
        $expiredRows = PlayerPool::find()
            ->where(['<', 'expires_at', $now])
            ->all();
        if (!empty($expiredRows)) {
            $expiredPoolIds = [];
            $expiredPlayerIds = [];
            foreach ($expiredRows as $row) {
                $expiredPoolIds[] = (int) $row->id;
                if ((int) $row->player_id > 0) {
                    $expiredPlayerIds[] = (int) $row->player_id;
                }
            }
            PlayerPool::deleteAll(['id' => $expiredPoolIds]);
            if (!empty($expiredPlayerIds)) {
                Player::deleteAll(['and', ['id' => array_unique($expiredPlayerIds)], ['team_id' => null]]);
            }
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

        // Normalize very old/legacy far future expiries to current auction horizon.
        Yii::$app->db->createCommand(
            'UPDATE {{%player_pool}} SET expires_at = :expiresAt WHERE expires_at > :maxAllowed',
            [
                ':expiresAt' => $now + $ttlSeconds,
                ':maxAllowed' => $now + $ttlSeconds,
            ]
        )->execute();

        $current = (int) PlayerPool::find()
            ->alias('pp')
            ->innerJoin(['p' => Player::tableName()], 'p.id = pp.player_id')
            ->where(['p.team_id' => null])
            ->andWhere(['>', 'pp.expires_at', $now])
            ->count();

        if ($current > $maxPool) {
            $overflow = $current - $maxPool;
            $overflowRows = PlayerPool::find()
                ->alias('pp')
                ->innerJoin(['p' => Player::tableName()], 'p.id = pp.player_id')
                ->where(['p.team_id' => null])
                ->orderBy(['pp.expires_at' => SORT_DESC, 'pp.id' => SORT_DESC])
                ->limit($overflow)
                ->all();
            $overflowPoolIds = [];
            $overflowPlayerIds = [];
            foreach ($overflowRows as $row) {
                $overflowPoolIds[] = (int) $row->id;
                if ((int) $row->player_id > 0) {
                    $overflowPlayerIds[] = (int) $row->player_id;
                }
            }
            if (!empty($overflowPoolIds)) {
                PlayerPool::deleteAll(['id' => $overflowPoolIds]);
            }
            if (!empty($overflowPlayerIds)) {
                Player::deleteAll(['and', ['id' => array_unique($overflowPlayerIds)], ['team_id' => null]]);
            }
            $current = max(0, $current - count($overflowPoolIds));
        }

        if ($current < $minPool) {
            $targetPool = max($targetPool, $minPool);
        }

        $toGenerate = max(0, $targetPool - $current);
        for ($i = 0; $i < $toGenerate; $i++) {
            $minExpiry = max(3600, (int) floor($ttlSeconds * 0.75));
            $expiresAt = $now + random_int($minExpiry, $ttlSeconds);
            $this->createGeneratedFreeAgentIntoPool($expiresAt);
        }

        $total = (int) PlayerPool::find()->count();
        return ['generated' => $toGenerate, 'pool' => $total];
    }

    private function createGeneratedFreeAgentIntoPool(?int $expiresAt = null): void
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
            'skill_cp' => random_int(5, 30),
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
        $nationality = WorldData::pickNationality();
        $name = WorldData::randomFirstName($nationality) . ' ' . WorldData::randomLastName($nationality);
        $age = random_int(17, 21);

        $player = new Player();
        $player->team_id = null;
        $player->number = random_int(1, 99);
        $player->name = $name;
        $player->nationality = $nationality;
        $player->age = $age;
        $player->height_cm = PhysicalHelper::randomHeight($position);
        $player->weight_kg = PhysicalHelper::randomWeight($position);
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
        $player->skill_cp = $stats['skill_cp'];
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
        $pool->expires_at = $expiresAt ?? (time() + (AuctionService::hoursForType(MarketBid::TYPE_TRANSFER_MARKET) * 3600));
        $pool->save(false);

        ScoutingService::notifyTeamsForNewMarketPlayer($player, $pool);
    }

    /**
     * @return array{min:int,max:int,target:int}
     */
    private function transferPoolBounds(?int $forcedTarget = null): array
    {
        if ($forcedTarget !== null) {
            $forced = max(1, (int) $forcedTarget);
            return ['min' => $forced, 'max' => $forced, 'target' => $forced];
        }

        $min = $this->envInt('GM_TRANSFER_POOL_MIN_PLAYERS', self::DEFAULT_TRANSFER_POOL_MIN_PLAYERS);
        $max = $this->envInt('GM_TRANSFER_POOL_MAX_PLAYERS', self::DEFAULT_TRANSFER_POOL_MAX_PLAYERS);
        if ($max < $min) {
            $max = $min;
        }
        $defaultTarget = (int) round(($min + $max) / 2);
        $target = $this->envInt('GM_TRANSFER_POOL_TARGET_PLAYERS', $defaultTarget);
        if ($target < $min) {
            $target = $min;
        } elseif ($target > $max) {
            $target = $max;
        }

        return ['min' => $min, 'max' => $max, 'target' => $target];
    }

    private function envInt(string $key, int $default): int
    {
        $raw = trim((string) getenv($key));
        if ($raw === '' || !is_numeric($raw)) {
            return $default;
        }
        $value = (int) $raw;
        return $value > 0 ? $value : $default;
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

    private function rebalanceCpuTrainingAllocations(int $season, int $now): int
    {
        $cpuTeams = Team::find()->where(['is_cpu' => 1])->all();
        $updated = 0;

        foreach ($cpuTeams as $team) {
            $players = Player::find()->where(['team_id' => (int) $team->id])->all();
            if (empty($players)) {
                continue;
            }

            $skillPlan = $this->buildCpuSkillTrainingPlan((int) $team->id, $players);
            $tacticPlan = $this->buildCpuTacticTrainingPlan((int) $team->id, $season);

            Yii::$app->db->createCommand()->upsert('{{%training_skill}}', array_merge([
                'team_id' => (int) $team->id,
                'season' => $season,
                'updated_at' => $now,
            ], $skillPlan), array_merge($skillPlan, [
                'updated_at' => $now,
            ]))->execute();

            Yii::$app->db->createCommand()->upsert('{{%training_tactic_plan}}', array_merge([
                'team_id' => (int) $team->id,
                'season' => $season,
                'updated_at' => $now,
            ], $tacticPlan), array_merge($tacticPlan, [
                'updated_at' => $now,
            ]))->execute();

            $updated++;
        }

        return $updated;
    }

    /**
     * @param Player[] $players
     * @return array<string,int>
     */
    private function buildCpuSkillTrainingPlan(int $teamId, array $players): array
    {
        $statMap = [
            'alloc_po' => 'skill_po',
            'alloc_df' => 'skill_df',
            'alloc_cn' => 'skill_cn',
            'alloc_pa' => 'skill_pa',
            'alloc_rg' => 'skill_rg',
            'alloc_cr' => 'skill_cr',
            'alloc_tc' => 'skill_tc',
            'alloc_tr' => 'skill_tr',
            'alloc_calci_piazzati' => 'skill_cp',
        ];
        $weights = [];

        foreach ($statMap as $alloc => $stat) {
            $sum = 0;
            foreach ($players as $p) {
                $sum += (int) ($p->{$stat} ?? 0);
            }
            $avg = (int) round($sum / max(1, count($players)));
            $weights[$alloc] = max(1, 105 - $avg);
        }

        $sumForm = 0;
        $sumCond = 0;
        foreach ($players as $p) {
            $sumForm += (int) ($p->form ?? 0);
            $sumCond += (int) ($p->condition ?? 0);
        }
        $avgForm = (int) round($sumForm / max(1, count($players)));
        $avgCond = (int) round($sumCond / max(1, count($players)));
        $weights['alloc_forma'] = max(4, 100 - $avgForm);
        $weights['alloc_cond'] = max(4, 100 - $avgCond);

        $needed = $this->cpuNeededPosition((int) $teamId);
        if ($needed !== null) {
            if ($needed === 'GK') {
                $weights['alloc_po'] += 24;
            } elseif ($needed === 'DF') {
                $weights['alloc_df'] += 14;
                $weights['alloc_cn'] += 10;
            } elseif ($needed === 'MF') {
                $weights['alloc_cn'] += 12;
                $weights['alloc_pa'] += 12;
                $weights['alloc_rg'] += 8;
            } elseif ($needed === 'FW') {
                $weights['alloc_tr'] += 14;
                $weights['alloc_tc'] += 12;
                $weights['alloc_cr'] += 10;
            }
        }
        return $this->normalizeToTotal($weights, 100);
    }

    /**
     * @return array<string,int>
     */
    private function buildCpuTacticTrainingPlan(int $teamId, int $season): array
    {
        $fields = ['pressing','contropiede','possesso','palla_bassa','lancio_lungo','catenaccio','fuorigioco'];
        $row = Yii::$app->db->createCommand(
            'SELECT * FROM {{%training_tactic}} WHERE team_id=:t AND season=:s',
            [':t' => $teamId, ':s' => $season]
        )->queryOne();
        if (!$row) {
            $row = [
                'pressing' => 30, 'contropiede' => 20, 'possesso' => 40, 'palla_bassa' => 30,
                'lancio_lungo' => 20, 'catenaccio' => 20, 'fuorigioco' => 10,
            ];
        }

        $weights = [];
        foreach ($fields as $field) {
            $value = max(0, min(100, (int) ($row[$field] ?? 0)));
            $weights[$field] = max(1, 110 - $value);
        }

        $formation = Formation::find()->where(['team_id' => $teamId, 'is_active' => 1])->one();
        $style = (string) ($formation->tactic ?? 'balanced');
        if ($style === 'all_out_attack') {
            $weights['pressing'] += 10;
            $weights['contropiede'] += 10;
            $weights['possesso'] += 8;
        } elseif ($style === 'ultra_defensive') {
            $weights['catenaccio'] += 12;
            $weights['fuorigioco'] += 8;
        } else {
            $weights['possesso'] += 8;
            $weights['palla_bassa'] += 8;
        }

        return $this->normalizeToTotal($weights, 100);
    }

    private function cpuNeededPosition(int $teamId): ?string
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
        return null;
    }

    /**
     * @param array<string,int|float> $weights
     * @return array<string,int>
     */
    private function normalizeToTotal(array $weights, int $total): array
    {
        $total = max(1, $total);
        $sum = 0.0;
        foreach ($weights as $w) {
            $sum += max(0.0, (float) $w);
        }

        if ($sum <= 0.0) {
            $keys = array_keys($weights);
            $base = intdiv($total, max(1, count($keys)));
            $rem = $total - ($base * count($keys));
            $out = [];
            foreach ($keys as $i => $k) {
                $out[$k] = $base + ($i < $rem ? 1 : 0);
            }
            return $out;
        }

        $floors = [];
        $fracs = [];
        $acc = 0;
        foreach ($weights as $k => $w) {
            $raw = (max(0.0, (float) $w) / $sum) * $total;
            $floor = (int) floor($raw);
            $floors[$k] = $floor;
            $fracs[$k] = $raw - $floor;
            $acc += $floor;
        }
        $remaining = $total - $acc;
        arsort($fracs);
        foreach (array_keys($fracs) as $k) {
            if ($remaining <= 0) {
                break;
            }
            $floors[$k]++;
            $remaining--;
        }

        return $floors;
    }

    /**
     * @return array{offersHandled:int,bought:int,listed:int}
     */
    private function runCpuTransferMarketCycle(): array
    {
        $now = time();
        $season = $this->currentSeasonForGeneration();
        $offersHandled = 0;
        $listed = 0;
        $bought = 0;

        $cpuTeams = Team::find()->where(['is_cpu' => 1])->all();
        foreach ($cpuTeams as $team) {
            if (!TransferWindowService::isOpen($now, (int) $team->id)) {
                continue;
            }

            $offersHandled += $this->cpuRespondToPendingOffers($team, $season, $now);
            $listed += $this->cpuListSurplusPlayer($team, $now);

            if ($this->cpuTrySignFromPool($team, $season)) {
                $bought++;
            } elseif ($this->cpuTryBuyListedPlayer($team, $season)) {
                $bought++;
            }
        }

        return [
            'offersHandled' => $offersHandled,
            'bought' => $bought,
            'listed' => $listed,
        ];
    }

    private function cpuRespondToPendingOffers(Team $team, int $season, int $now): int
    {
        $handled = 0;
        $valuator = Yii::$app->has('playerValuator')
            ? Yii::$app->get('playerValuator')
            : new \app\components\PlayerValuator();

        $offers = TransferOffer::find()
            ->where([
                'to_team_id' => (int) $team->id,
                'status' => TransferOffer::STATUS_PENDING,
            ])
            ->andWhere(['>', 'expires_at', $now])
            ->with(['transfer', 'player'])
            ->orderBy(['created_at' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        foreach ($offers as $offer) {
            $transfer = $offer->transfer;
            if (!$transfer || !in_array((string) $transfer->status, [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE], true)) {
                $offer->status = TransferOffer::STATUS_REJECTED;
                $offer->save(false, ['status', 'updated_at']);
                $handled++;
                continue;
            }

            $player = $offer->player ?: Player::findOne((int) $offer->player_id);
            if (!$player || (int) $player->team_id !== (int) $team->id) {
                $offer->status = TransferOffer::STATUS_REJECTED;
                $offer->save(false, ['status', 'updated_at']);
                $handled++;
                continue;
            }

            $market = max(1, (int) $valuator->marketValue($player));
            $asking = max(1, (int) ($transfer->asking_fee ?: $transfer->fee ?: $market));
            $offered = max(0, (int) $offer->offered_fee);
            $acceptThreshold = max((int) round($asking * 0.85), (int) round($market * 0.80));
            $rejectThreshold = (int) round($market * 0.70);

            if ($offered >= $acceptThreshold) {
                $buyer = Team::findOne((int) $offer->from_team_id);
                if (!$buyer || (int) $buyer->budget < $offered) {
                    $offer->status = TransferOffer::STATUS_REJECTED;
                    $offer->save(false, ['status', 'updated_at']);
                    $handled++;
                    continue;
                }

                $offer->status = TransferOffer::STATUS_ACCEPTED;
                $offer->save(false, ['status', 'updated_at']);
                TransferOffer::updateAll(
                    ['status' => TransferOffer::STATUS_REJECTED, 'updated_at' => $now],
                    [
                        'and',
                        ['transfer_id' => (int) $transfer->id],
                        ['status' => TransferOffer::STATUS_PENDING],
                        ['!=', 'id', (int) $offer->id],
                    ]
                );

                $transfer->to_team_id = (int) $buyer->id;
                $transfer->offered_by_team = (int) $buyer->id;
                $transfer->fee = $offered;
                $transfer->status = Transfer::STATUS_ACCEPTED;
                if ($transfer->transfer_type === Transfer::TYPE_LOAN && !$transfer->loan_return_season) {
                    $transfer->loan_return_season = $season + 1;
                }
                $transfer->save(false);
                $transfer->complete($season);
                $handled++;
                continue;
            }

            if ($offered <= $rejectThreshold || ((int) $offer->expires_at - $now) < 86400) {
                $offer->status = TransferOffer::STATUS_REJECTED;
                $offer->save(false, ['status', 'updated_at']);
                $handled++;
            }
        }

        return $handled;
    }

    private function cpuListSurplusPlayer(Team $team, int $now): int
    {
        $players = Player::find()->where(['team_id' => (int) $team->id])->all();
        if (empty($players)) {
            return 0;
        }
        $rosterCount = count($players);
        $budgetStress = (int) $team->budget < ((int) $team->totalWageBill() * 4);
        if ($rosterCount <= 22 && !$budgetStress) {
            return 0;
        }

        $listedPlayerIds = Transfer::find()
            ->select('player_id')
            ->where([
                'from_team_id' => (int) $team->id,
                'status' => [Transfer::STATUS_LISTED, Transfer::STATUS_BID_MADE],
            ])
            ->column();
        $listedMap = array_fill_keys(array_map('intval', $listedPlayerIds), true);

        $counts = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        foreach ($players as $p) {
            $pos = strtoupper((string) ($p->position ?? ''));
            if (isset($counts[$pos])) {
                $counts[$pos]++;
            }
        }
        $minKeep = ['GK' => 2, 'DF' => 5, 'MF' => 5, 'FW' => 3];

        $candidates = [];
        foreach ($players as $p) {
            $pid = (int) $p->id;
            if (isset($listedMap[$pid])) {
                continue;
            }
            $pos = strtoupper((string) ($p->position ?? ''));
            if (isset($minKeep[$pos]) && $counts[$pos] <= $minKeep[$pos]) {
                continue;
            }
            $candidates[] = $p;
        }
        if (empty($candidates)) {
            return 0;
        }

        usort($candidates, static function (Player $a, Player $b): int {
            $sa = (int) ($a->general_skill ?? 0);
            $sb = (int) ($b->general_skill ?? 0);
            if ($sa !== $sb) {
                return $sa <=> $sb;
            }
            $aa = (int) ($a->age ?? 0);
            $ab = (int) ($b->age ?? 0);
            if ($aa !== $ab) {
                return $ab <=> $aa;
            }
            return ((int) ($a->form ?? 0)) <=> ((int) ($b->form ?? 0));
        });
        $player = $candidates[0];

        $valuator = Yii::$app->has('playerValuator')
            ? Yii::$app->get('playerValuator')
            : new \app\components\PlayerValuator();
        $market = max(10000, (int) $valuator->marketValue($player));
        $asking = max(10000, (int) round($market * 0.90, -3));

        $transfer = new Transfer();
        $transfer->player_id = (int) $player->id;
        $transfer->from_team_id = (int) $team->id;
        $transfer->fee = $asking;
        $transfer->asking_fee = $asking;
        $transfer->proposed_salary = max(10000, (int) $valuator->suggestedSalary($player));
        $transfer->status = Transfer::STATUS_LISTED;
        $transfer->transfer_type = Transfer::TYPE_SALE;
        $transfer->loan_return_season = null;
        $transfer->listed_at = $now;
        $transfer->save(false);

        return 1;
    }

    private function cpuTrySignFromPool(Team $team, int $season): bool
    {
        $players = Player::find()->where(['team_id' => (int) $team->id])->all();
        $rosterCount = count($players);
        if ($rosterCount >= 20) {
            return false;
        }

        $position = $this->cpuNeededPosition((int) $team->id) ?? $this->pickNeededPositionForCpu((int) $team->id);
        $pool = PlayerPool::findBestForPosition($position);
        if (!$pool || !$pool->player) {
            return false;
        }
        $player = $pool->player;
        if ($player->team_id !== null) {
            $pool->delete();
            return false;
        }

        $fee = max(0, (int) $pool->asking_fee);
        if ((int) $team->budget < $fee && $rosterCount >= 18) {
            return false;
        }

        $player->team_id = (int) $team->id;
        $player->save(false);

        $contract = new Contract();
        $contract->player_id = (int) $player->id;
        $contract->team_id = (int) $team->id;
        $contract->salary = (int) max(10000, (int) $pool->salary_ask);
        $contract->season_start = $season;
        $contract->season_end = $season + 2;
        $contract->status = Contract::STATUS_ACTIVE;
        $contract->save(false);

        if ($fee > 0) {
            $team->budget = max(0, (int) $team->budget - $fee);
            $team->save(false, ['budget']);
        }

        $pool->delete();
        return true;
    }

    private function cpuTryBuyListedPlayer(Team $team, int $season): bool
    {
        $players = Player::find()->where(['team_id' => (int) $team->id])->all();
        if (count($players) >= 21) {
            return false;
        }

        $position = $this->cpuNeededPosition((int) $team->id) ?? $this->pickNeededPositionForCpu((int) $team->id);
        $target = Transfer::find()
            ->alias('t')
            ->innerJoin(['p' => Player::tableName()], 'p.id = t.player_id')
            ->where([
                't.status' => Transfer::STATUS_LISTED,
                't.transfer_type' => [Transfer::TYPE_SALE, Transfer::TYPE_LOAN],
                'p.position' => $position,
            ])
            ->andWhere(['!=', 't.from_team_id', (int) $team->id])
            ->orderBy(['t.asking_fee' => SORT_ASC, 'p.general_skill' => SORT_DESC, 't.id' => SORT_ASC])
            ->limit(5)
            ->all();

        if (empty($target)) {
            return false;
        }

        foreach ($target as $transfer) {
            $fee = max(0, (int) ($transfer->asking_fee ?: $transfer->fee));
            if ($fee <= 0) {
                continue;
            }
            if ($fee > (int) $team->budget) {
                continue;
            }
            if ($fee > (int) round((int) $team->budget * 0.35) && count($players) >= 18) {
                continue;
            }

            $transfer->to_team_id = (int) $team->id;
            $transfer->offered_by_team = (int) $team->id;
            $transfer->fee = $fee;
            $transfer->status = Transfer::STATUS_ACCEPTED;
            if ($transfer->transfer_type === Transfer::TYPE_LOAN && !$transfer->loan_return_season) {
                $transfer->loan_return_season = $season + 1;
            }
            $transfer->save(false);

            return (bool) $transfer->complete($season);
        }

        return false;
    }

    private function runCpuStaffHiringCycle(): int
    {
        $season = $this->currentSeasonForGeneration();
        $now = time();
        $hired = 0;
        $requiredRoles = [
            Staff::ROLE_HEAD_COACH,
            Staff::ROLE_ASSISTANT_COACH,
            Staff::ROLE_GOALKEEPING_COACH,
            Staff::ROLE_FITNESS_COACH,
            Staff::ROLE_DOCTOR,
        ];
        $roleBaseSalary = [
            Staff::ROLE_HEAD_COACH => 85000,
            Staff::ROLE_ASSISTANT_COACH => 45000,
            Staff::ROLE_GOALKEEPING_COACH => 50000,
            Staff::ROLE_FITNESS_COACH => 36000,
            Staff::ROLE_DOCTOR => 42000,
        ];

        $cpuTeams = Team::find()->where(['is_cpu' => 1])->all();
        foreach ($cpuTeams as $team) {
            $wageBill = max(1, (int) $team->totalWageBill());
            if ((int) $team->budget < ($wageBill * 4)) {
                continue;
            }

            foreach ($requiredRoles as $role) {
                $exists = Staff::find()->where(['team_id' => (int) $team->id, 'role' => $role])->exists();
                if ($exists) {
                    continue;
                }

                $ability = random_int(38, 88);
                $experience = random_int(8, 42);
                $motivation = random_int(58, 98);
                $salaryBase = (int) ($roleBaseSalary[$role] ?? 35000);
                $salary = max(24000, (int) round($salaryBase * ($ability / 55)));

                $staffNat = WorldData::pickNationality();
                $staff = new Staff();
                $staff->team_id = (int) $team->id;
                $staff->name = WorldData::randomFirstName($staffNat) . ' ' . WorldData::randomLastName($staffNat);
                $staff->nationality = $staffNat;
                $staff->role = $role;
                $staff->ability = $ability;
                $staff->experience = $experience;
                $staff->age = max(30, min(69, $experience + random_int(22, 31)));
                $staff->motivation = $motivation;
                $staff->contract_ends = $season + random_int(1, 3) - 1;
                $staff->salary = $salary;
                $staff->specialisation = $this->staffRoleSpecialisation($role);
                $staff->recomputeEfficiency();
                $staff->created_at = $now;
                $staff->updated_at = $now;
                if ($staff->save(false)) {
                    $hired++;
                }
            }
        }

        return $hired;
    }

    private function staffRoleSpecialisation(string $role): string
    {
        return match ($role) {
            Staff::ROLE_HEAD_COACH => 'tattica',
            Staff::ROLE_FITNESS_COACH => 'fisico',
            Staff::ROLE_GOALKEEPING_COACH => 'portieri',
            Staff::ROLE_SCOUT => 'scouting',
            Staff::ROLE_DOCTOR => 'medico',
            default => 'equilibrato',
        };
    }

    /**
     * @return array{initiated:int,accepted:int,declined:int}
     */
    private function runCpuFriendlyBehavior(): array
    {
        $now = time();
        ['accepted' => $accepted, 'declined' => $declined] = $this->processCpuPendingFriendlyChallenges($now);
        $initiated = $this->cpuInitiateFriendlyChallenges($now);

        return [
            'initiated' => $initiated,
            'accepted' => $accepted,
            'declined' => $declined,
        ];
    }

    /**
     * @return array{accepted:int,declined:int}
     */
    private function processCpuPendingFriendlyChallenges(int $now): array
    {
        $accepted = 0;
        $declined = 0;

        $pending = FriendlyChallenge::find()
            ->alias('fc')
            ->innerJoin(Team::tableName() . ' t', 't.id = fc.challenged_id')
            ->where(['fc.status' => FriendlyChallenge::STATUS_PENDING, 't.is_cpu' => 1])
            ->with(['challenger.players', 'challenged.players'])
            ->orderBy(['fc.created_at' => SORT_ASC, 'fc.id' => SORT_ASC])
            ->all();

        foreach ($pending as $challenge) {
            $challenger = $challenge->challenger;
            $challenged = $challenge->challenged;
            if (!$challenger || !$challenged) {
                $challenge->status = FriendlyChallenge::STATUS_DECLINED;
                $challenge->decline_reason = 'Invito non valido';
                $challenge->responded_at = $now;
                $challenge->save(false, ['status', 'decline_reason', 'responded_at']);
                $declined++;
                continue;
            }

            if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $challenger->id, (int) $challenge->proposed_at, (int) $challenge->id)) {
                $challenge->status = FriendlyChallenge::STATUS_DECLINED;
                $challenge->decline_reason = "Abbiamo gia' un'amichevole questa settimana";
                $challenge->responded_at = $now;
                $challenge->save(false, ['status', 'decline_reason', 'responded_at']);
                $declined++;
                continue;
            }

            ['accept' => $canAccept, 'reason' => $reason] = $this->cpuFriendlyDecision($challenged, (int) $challenge->proposed_at, (int) $challenge->id);
            if (!$canAccept) {
                $challenge->status = FriendlyChallenge::STATUS_DECLINED;
                $challenge->decline_reason = $reason;
                $challenge->responded_at = $now;
                $challenge->save(false, ['status', 'decline_reason', 'responded_at']);
                $declined++;

                if ($challenger->user_id) {
                    $title = $challenged->name . " ha declinato l'amichevole";
                    $body  = (string) $reason;
                    NotificationService::notify(
                        (int) $challenger->user_id, NewsItem::CAT_FRIENDLY, '-',
                        $title, $body, $this->safeUrl('/friendly/index'), 0,
                        "📩 <b>{$title}</b>\n{$body}"
                    );
                }
                continue;
            }

            $fixture = FriendlyChallengeService::createFixtureForChallenge($challenge);
            $challenge->status = FriendlyChallenge::STATUS_ACCEPTED;
            $challenge->fixture_id = (int) $fixture->id;
            $challenge->responded_at = $now;
            $challenge->save(false, ['status', 'fixture_id', 'responded_at']);
            $accepted++;

            if ($challenger->user_id) {
                $title = $challenged->name . " ha accettato l'amichevole";
                $body  = sprintf('Partita programmata per %s.', date('d/m H:i', (int) $challenge->proposed_at));
                NotificationService::notify(
                    (int) $challenger->user_id, NewsItem::CAT_FRIENDLY, '+',
                    $title, $body, $this->safeUrl('/fixture/live', ['id' => (int) $fixture->id]), 1,
                    "📩 <b>{$title}</b>\n{$body}"
                );
            }
        }

        return ['accepted' => $accepted, 'declined' => $declined];
    }

    /**
     * @return array{accept:bool,reason:string}
     */
    private function cpuFriendlyDecision(Team $team, int $proposedAt, ?int $excludeChallengeId = null): array
    {
        if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $team->id, $proposedAt, $excludeChallengeId)) {
            return ['accept' => false, 'reason' => "Abbiamo gia' un'amichevole questa settimana"];
        }

        $avgFreshness = FriendlyChallengeService::averageFreshness($team);
        if ($avgFreshness < 70.0) {
            return ['accept' => false, 'reason' => 'I nostri giocatori hanno bisogno di riposo'];
        }

        $acceptChance = 82;
        if ($avgFreshness < 75.0) {
            $acceptChance = 68;
        } elseif ($avgFreshness >= 88.0) {
            $acceptChance = 90;
        }

        if (random_int(1, 100) > $acceptChance) {
            return ['accept' => false, 'reason' => 'Preferiamo concentrarci sul campionato'];
        }

        return ['accept' => true, 'reason' => ''];
    }

    private function cpuInitiateFriendlyChallenges(int $now): int
    {
        $chanceRaw = trim((string) getenv('GM_CPU_FRIENDLY_INIT_CHANCE'));
        $chance = 30;
        if ($chanceRaw !== '' && is_numeric($chanceRaw)) {
            $chance = max(0, min(100, (int) $chanceRaw));
        }

        $slot = FriendlyChallengeService::nextFriendlySlot($now);
        $humanTeams = Team::find()
            ->where(['is_cpu' => 0])
            ->andWhere(['not', ['user_id' => null]])
            ->all();
        if (empty($humanTeams)) {
            return 0;
        }

        $cpuTeams = Team::find()->where(['is_cpu' => 1])->all();
        $initiated = 0;

        foreach ($cpuTeams as $cpuTeam) {
            if (random_int(1, 100) > $chance) {
                continue;
            }
            if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $cpuTeam->id, $slot)) {
                continue;
            }

            $candidates = [];
            foreach ($humanTeams as $human) {
                if ((int) $human->id === (int) $cpuTeam->id) {
                    continue;
                }
                if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $human->id, $slot)) {
                    continue;
                }
                $dup = FriendlyChallenge::find()
                    ->where([
                        'challenger_id' => (int) $cpuTeam->id,
                        'challenged_id' => (int) $human->id,
                        'status' => FriendlyChallenge::STATUS_PENDING,
                        'proposed_at' => $slot,
                    ])
                    ->exists();
                if ($dup) {
                    continue;
                }
                $candidates[] = $human;
            }

            if (empty($candidates)) {
                continue;
            }
            $target = $candidates[array_rand($candidates)];

            $challenge = new FriendlyChallenge();
            $challenge->challenger_id = (int) $cpuTeam->id;
            $challenge->challenged_id = (int) $target->id;
            $challenge->status = FriendlyChallenge::STATUS_PENDING;
            $challenge->proposed_at = $slot;
            $challenge->created_at = $now;
            if ($challenge->save(false)) {
                $initiated++;
                if ($target->user_id) {
                    $title = 'Invito amichevole ricevuto';
                    $body  = sprintf('%s ti sfida per %s.', $cpuTeam->name, date('d/m H:i', $slot));
                    NotificationService::notify(
                        (int) $target->user_id, NewsItem::CAT_FRIENDLY, 'I',
                        $title, $body, $this->safeUrl('/friendly/index'), 1,
                        "📩 <b>{$title}</b>\n{$body}"
                    );
                }
            }
        }

        return $initiated;
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
        $nat = WorldData::pickNationality();
        $seed = new PlayerSeeder();
        $attrs = $seed->buildAttributes(
            $position,
            random_int(1, 99),
            WorldData::randomFirstName($nat) . ' ' . WorldData::randomLastName($nat),
            $nat
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
            Staff::ROLE_DOCTOR,
            Staff::ROLE_SCOUT,
        ];
        $baseSalary = [
            Staff::ROLE_HEAD_COACH => 80000,
            Staff::ROLE_ASSISTANT_COACH => 40000,
            Staff::ROLE_GOALKEEPING_COACH => 45000,
            Staff::ROLE_FITNESS_COACH => 30000,
            Staff::ROLE_DOCTOR => 42000,
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

                    $candNat = WorldData::pickNationality();
                    $cand = new StaffMarket();
                    $cand->team_id = (int) $team->id;
                    $cand->name = WorldData::randomFirstName($candNat) . ' ' . WorldData::randomLastName($candNat);
                    $cand->nationality = $candNat;
                    $cand->role = $role;
                    $cand->ability = $ability;
                    $cand->experience = $experience;
                    $cand->motivation = $motivation;
                    $cand->salary = $salary;
                    $cand->contract_length = random_int(1, 3);
                    $cand->negotiations = 4;
                    $cand->raise_used = 0;
                    $cand->generated_at = $now;
                    $cand->expires_at = $now + (AuctionService::hoursForType(MarketBid::TYPE_STAFF) * 3600);
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

    /**
     * SIP-0078: Timestamp-based loan return (runs between season rollovers).
     * Idempotent: marks loan_ends_at = null after returning to prevent double-run.
     */
    private function processLoanReturnsTimestamp(): int
    {
        $now = time();

        $loans = Transfer::find()
            ->where(['transfer_type' => Transfer::TYPE_LOAN, 'status' => Transfer::STATUS_COMPLETED])
            ->andWhere(['not', ['loan_ends_at' => null]])
            ->andWhere(['<=', 'loan_ends_at', $now])
            ->all();

        $returned = 0;
        foreach ($loans as $loan) {
            $player = $loan->player;
            if (!$player) {
                $loan->loan_ends_at = null;
                $loan->save(false, ['loan_ends_at', 'updated_at']);
                continue;
            }

            $loanTeam  = $loan->toTeam;
            $ownerTeam = $loan->fromTeam;

            // Return player to original club
            if ((int) $player->team_id === (int) $loan->to_team_id && $loan->from_team_id) {
                $player->team_id = (int) $loan->from_team_id;
                $player->save(false);
                $returned++;

                // Notify owner manager
                if ($ownerTeam?->user_id) {
                    NotificationService::notify(
                        (int) $ownerTeam->user_id,
                        \app\models\NewsItem::CAT_TRANSFER, '↩️',
                        Yii::t('app', 'Loan return: {player}', ['{player}' => $player->name]),
                        Yii::t('app', '{player} returned from {club}.', [
                            '{player}' => $player->name,
                            '{club}'   => $loanTeam?->name ?? '—',
                        ]),
                        '', 1,
                        "↩️ <b>" . Yii::t('app', 'Loan return') . ":</b> {$player->name}\n"
                        . Yii::t('app', 'Back from {club}.', ['{club}' => $loanTeam?->name ?? '—'])
                    );
                }

                // Notify loan club manager
                if ($loanTeam?->user_id && $loanTeam->user_id !== $ownerTeam?->user_id) {
                    NotificationService::notify(
                        (int) $loanTeam->user_id,
                        \app\models\NewsItem::CAT_TRANSFER, '↩️',
                        Yii::t('app', 'Loan ended: {player}', ['{player}' => $player->name]),
                        Yii::t('app', '{player} has returned to {club}.', [
                            '{player}' => $player->name,
                            '{club}'   => $ownerTeam?->name ?? '—',
                        ]),
                        '', 0,
                        "↩️ <b>" . Yii::t('app', 'Loan ended') . ":</b> {$player->name} "
                        . Yii::t('app', 'returned to {club}.', ['{club}' => $ownerTeam?->name ?? '—'])
                    );
                }
            }

            // Mark as processed (idempotency)
            $loan->loan_ends_at = null;
            $loan->save(false, ['loan_ends_at', 'updated_at']);
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

    /**
     * Resolve expired bids for transfer market, staff and sponsor auctions.
     *
     * @return array{resolved:int,won:int,lost:int}
     */
    private function resolveExpiredMarketBids(): array
    {
        $now = time();
        $bids = MarketBid::find()
            ->where(['status' => MarketBid::STATUS_PENDING])
            ->andWhere(['<=', 'expires_at', $now])
            ->orderBy(['market_type' => SORT_ASC, 'market_ref_id' => SORT_ASC, 'bid_amount' => SORT_DESC, 'id' => SORT_ASC])
            ->all();
        if (empty($bids)) {
            return ['resolved' => 0, 'won' => 0, 'lost' => 0];
        }

        $grouped = [];
        foreach ($bids as $bid) {
            $normalizedType = MarketBid::normalizeMarketType((string) $bid->market_type);
            $key = $normalizedType . ':' . (int) $bid->market_ref_id;
            $grouped[$key][] = $bid;
        }

        $resolved = 0;
        $won = 0;
        $lost = 0;
        foreach ($grouped as $group) {
            $type = MarketBid::normalizeMarketType((string) ($group[0]->market_type ?? ''));
            $result = match ($type) {
                MarketBid::TYPE_TRANSFER_MARKET => $this->resolveTransferMarketAuction($group, $now),
                MarketBid::TYPE_STAFF => $this->resolveStaffAuction($group, $now),
                MarketBid::TYPE_SPONSOR => $this->resolveSponsorAuction($group, $now),
                default => $this->resolveAsLost($group, 'Tipo asta non supportato', $now),
            };
            $resolved += (int) ($result['resolved'] ?? 0);
            $won += (int) ($result['won'] ?? 0);
            $lost += (int) ($result['lost'] ?? 0);
        }

        return ['resolved' => $resolved, 'won' => $won, 'lost' => $lost];
    }

    /**
     * @param MarketBid[] $bids
     * @return array{resolved:int,won:int,lost:int}
     */
    private function resolveTransferMarketAuction(array $bids, int $now): array
    {
        $poolId = (int) $bids[0]->market_ref_id;
        $pool = PlayerPool::findOne($poolId);
        $player = $pool?->player;
        if (!$pool || !$player || $player->team_id !== null || (int) $pool->expires_at <= $now) {
            return $this->resolveAsLost($bids, 'Giocatore non più disponibile', $now);
        }

        $winner = $this->pickFirstAffordableBid($bids);
        if (!$winner) {
            return $this->resolveAsLost($bids, 'Nessuna offerta valida', $now);
        }

        $winnerTeam = Team::findOne((int) $winner->team_id);
        if (!$winnerTeam) {
            return $this->resolveAsLost($bids, 'Squadra non trovata', $now);
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            $player->team_id = (int) $winnerTeam->id;
            $player->save(false);

            Contract::updateAll(
                ['status' => Contract::STATUS_TRANSFERRED],
                ['player_id' => (int) $player->id, 'status' => Contract::STATUS_ACTIVE]
            );

            $contract = new Contract();
            $contract->player_id = (int) $player->id;
            $contract->team_id = (int) $winnerTeam->id;
            $contract->salary = (int) $pool->salary_ask;
            $contract->season_start = $this->currentSeasonForGeneration();
            $contract->season_end = $this->currentSeasonForGeneration() + 1;
            $contract->status = Contract::STATUS_ACTIVE;
            $contract->save(false);

            $transfer = new Transfer();
            $transfer->player_id = (int) $player->id;
            $transfer->from_team_id = null;
            $transfer->to_team_id = (int) $winnerTeam->id;
            $transfer->offered_by_team = (int) $winnerTeam->id;
            $transfer->fee = (int) $winner->bid_amount;
            $transfer->asking_fee = (int) $pool->asking_fee;
            $transfer->proposed_salary = (int) $pool->salary_ask;
            $transfer->transfer_type = Transfer::TYPE_FREE;
            $transfer->status = Transfer::STATUS_COMPLETED;
            $transfer->listed_at = $now;
            $transfer->resolved_at = $now;
            $transfer->save(false);

            $winnerTeam->budget -= (int) $winner->bid_amount;
            $winnerTeam->save(false, ['budget', 'updated_at']);
            $pool->delete();
            $tx->commit();
        } catch (\Throwable $e) {
            if ($tx->isActive) {
                $tx->rollBack();
            }
            Yii::warning('Auction transfer_market failed: ' . $e->getMessage(), 'economy');
            return $this->resolveAsLost($bids, 'Errore sistema in assegnazione', $now);
        }

        foreach ($bids as $bid) {
            $isWinner = (int) $bid->id === (int) $winner->id;
            $this->markBid($bid, $isWinner ? MarketBid::STATUS_WON : MarketBid::STATUS_LOST, $isWinner ? 'Asta vinta' : 'Superato da offerta migliore', $now);
        }

        if ($winnerTeam->user_id) {
            $fmtBid = number_format((int) $winner->bid_amount, 0, ',', '.');
            NotificationService::notify(
                (int) $winnerTeam->user_id, NewsItem::CAT_TRANSFER, '🏁',
                "Asta vinta: {$player->name}",
                "Acquisto completato a €{$fmtBid}.",
                $this->safeUrl('/transfer/market'), 1,
                "✅ Hai vinto l'asta per <b>{$player->name}</b>!\nCosto: €{$fmtBid}"
            );
        }
        foreach ($bids as $bid) {
            if ((int) $bid->id === (int) $winner->id) continue;
            $loserTeam = \app\models\Team::findOne($bid->team_id);
            if ($loserTeam && $loserTeam->user_id) {
                NotificationService::notify(
                    (int) $loserTeam->user_id, NewsItem::CAT_TRANSFER, '❌',
                    "Asta persa: {$player->name}",
                    "Vincitore: {$winnerTeam->name}", '', 0,
                    "❌ Hai perso l'asta per <b>{$player->name}</b>.\nVincitore: {$winnerTeam->name}"
                );
            }
        }

        return ['resolved' => count($bids), 'won' => 1, 'lost' => max(0, count($bids) - 1)];
    }

    /**
     * @param MarketBid[] $bids
     * @return array{resolved:int,won:int,lost:int}
     */
    private function resolveStaffAuction(array $bids, int $now): array
    {
        $candidateId = (int) $bids[0]->market_ref_id;
        $candidate = StaffMarket::findOne($candidateId);
        if (!$candidate) {
            return $this->resolveAsLost($bids, 'Candidato non più disponibile', $now);
        }

        $winner = $this->pickFirstAffordableBid($bids);
        if (!$winner) {
            $candidate->delete();
            return $this->resolveAsLost($bids, 'Nessuna offerta valida', $now);
        }

        $team = Team::findOne((int) $winner->team_id);
        if (!$team || (int) $team->id !== (int) $candidate->team_id) {
            $candidate->delete();
            return $this->resolveAsLost($bids, 'Offerta non valida per il candidato', $now);
        }

        $existing = Staff::findOne(['team_id' => (int) $team->id, 'role' => (string) $candidate->role]);
        if ($existing) {
            $candidate->delete();
            return $this->resolveAsLost($bids, 'Ruolo già coperto', $now);
        }

        $ratio = ((float) $winner->bid_amount) / max(1.0, (float) $candidate->salary);
        $successChance = max(20, min(95, (int) round(40 + ($ratio * 45))));
        $winRoll = random_int(1, 100);
        if ($winRoll > $successChance) {
            $candidate->delete();
            return $this->resolveAsLost($bids, 'Trattativa non conclusa', $now);
        }

        $season = $this->currentSeasonForGeneration();
        $staff = new Staff();
        $staff->team_id = (int) $team->id;
        $staff->name = (string) $candidate->name;
        $staff->role = (string) $candidate->role;
        $staff->ability = (int) $candidate->ability;
        $staff->experience = (int) $candidate->experience;
        $staff->age = max(30, min(68, (int) $candidate->experience + random_int(22, 30)));
        $staff->motivation = (int) $candidate->motivation;
        $staff->contract_ends = $season + max(1, (int) $candidate->contract_length) - 1;
        $staff->salary = (int) $winner->bid_amount;
        $staff->specialisation = $this->roleSpecialisationForAuction((string) $candidate->role);
        $staff->recomputeEfficiency();
        if (!$staff->save(false)) {
            $candidate->delete();
            return $this->resolveAsLost($bids, 'Errore firma staff', $now);
        }

        $team->budget -= (int) $winner->bid_amount;
        $team->save(false, ['budget', 'updated_at']);
        $candidate->delete();

        foreach ($bids as $bid) {
            $isWinner = (int) $bid->id === (int) $winner->id;
            $this->markBid($bid, $isWinner ? MarketBid::STATUS_WON : MarketBid::STATUS_LOST, $isWinner ? 'Asta staff vinta' : 'Offerta non vincente', $now);
        }

        if ($team->user_id) {
            $fmtStaff = number_format((int) $winner->bid_amount, 0, ',', '.');
            NotificationService::notify(
                (int) $team->user_id, NewsItem::CAT_FINANCE, '🧑‍💼',
                "Staff assunto: {$staff->name}",
                "Ruolo {$staff->role} · costo asta €{$fmtStaff}.",
                $this->safeUrl('/staff/view'), 0,
                "✅ Hai vinto l'asta staff per <b>{$staff->name}</b>!\nCosto: €{$fmtStaff}"
            );
        }
        foreach ($bids as $bid) {
            if ((int) $bid->id === (int) $winner->id) continue;
            $loserTeam = Team::findOne($bid->team_id);
            if ($loserTeam && $loserTeam->user_id) {
                NotificationService::notify(
                    (int) $loserTeam->user_id, NewsItem::CAT_STAFF, '❌',
                    "Asta staff persa: {$staff->name}",
                    "Vincitore: {$team->name}", '', 0,
                    "❌ Hai perso l'asta staff per <b>{$staff->name}</b>.\nVincitore: {$team->name}"
                );
            }
        }

        return ['resolved' => count($bids), 'won' => 1, 'lost' => max(0, count($bids) - 1)];
    }

    /**
     * @param MarketBid[] $bids
     * @return array{resolved:int,won:int,lost:int}
     */
    private function resolveSponsorAuction(array $bids, int $now): array
    {
        $sponsor = Sponsor::findOne((int) $bids[0]->market_ref_id);
        if (!$sponsor) {
            return $this->resolveAsLost($bids, 'Sponsor non più disponibile', $now);
        }

        $winner = null;
        foreach ($this->sortBidsDesc($bids) as $bid) {
            $team = Team::findOne((int) $bid->team_id);
            if (!$team || (int) $team->budget < (int) $bid->bid_amount) {
                continue;
            }
            $available = SponsorService::findAvailableSponsorsForTeam($team);
            $allowed = false;
            foreach ($available as $offer) {
                if ((int) $offer->id === (int) $sponsor->id) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                continue;
            }
            $winner = $bid;
            break;
        }

        if (!$winner) {
            return $this->resolveAsLost($bids, 'Nessuna offerta valida', $now);
        }

        $team = Team::findOne((int) $winner->team_id);
        if (!$team) {
            return $this->resolveAsLost($bids, 'Squadra non trovata', $now);
        }

        $ratio = ((float) $winner->bid_amount) / max(1.0, (float) $sponsor->base_payment);
        $successChance = max(15, min(95, (int) round(35 + ($ratio * 55))));
        if (random_int(1, 100) > $successChance) {
            return $this->resolveAsLost($bids, 'Offerta non accettata dallo sponsor', $now);
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            SponsorService::expireContracts(false);
            $active = SponsorService::getActiveContractForTeam((int) $team->id);
            $switchPenalty = 0;
            if ($active) {
                if ((int) $active->sponsor_id === (int) $sponsor->id) {
                    throw new \RuntimeException('Sponsor già attivo');
                }
                $active->status = TeamSponsor::STATUS_TERMINATED;
                $active->ends_at = $now;
                $active->save(false, ['status', 'ends_at']);
                if ($active->sponsor) {
                    $switchPenalty = (int) round((int) $active->sponsor->base_payment * 0.05);
                }
            }

            $contract = new TeamSponsor();
            $contract->team_id = (int) $team->id;
            $contract->sponsor_id = (int) $sponsor->id;
            $contract->signed_at = $now;
            $contract->ends_at = $now + SponsorService::contractDurationSeconds((int) $sponsor->duration_seasons);
            $contract->status = TeamSponsor::STATUS_ACTIVE;
            $contract->save(false);

            $bonus = (int) round((int) $sponsor->base_payment * 0.10);
            $team->budget += ($bonus - $switchPenalty - (int) $winner->bid_amount);
            $team->save(false, ['budget', 'updated_at']);
            $tx->commit();
        } catch (\Throwable $e) {
            if ($tx->isActive) {
                $tx->rollBack();
            }
            Yii::warning('Auction sponsor failed: ' . $e->getMessage(), 'economy');
            return $this->resolveAsLost($bids, 'Errore firma sponsor', $now);
        }

        foreach ($bids as $bid) {
            $isWinner = (int) $bid->id === (int) $winner->id;
            $this->markBid($bid, $isWinner ? MarketBid::STATUS_WON : MarketBid::STATUS_LOST, $isWinner ? 'Asta sponsor vinta' : 'Offerta non vincente', $now);
        }

        if ($team->user_id) {
            $fmtBid  = number_format((int) $winner->bid_amount, 0, ',', '.');
            $fmtPay  = number_format((int) $sponsor->base_payment, 0, ',', '.');
            NotificationService::notify(
                (int) $team->user_id, NewsItem::CAT_FINANCE, '🤝',
                "Sponsor acquisito: {$sponsor->name}",
                "Asta vinta con €{$fmtBid}.",
                $this->safeUrl('/sponsor/index'), 1,
                "✅ Hai vinto l'asta sponsor per <b>{$sponsor->name}</b>!\nEntrate: €{$fmtPay}/stagione"
            );
        }
        foreach ($bids as $bid) {
            if ((int) $bid->id === (int) $winner->id) continue;
            $loserTeam = Team::findOne($bid->team_id);
            if ($loserTeam && $loserTeam->user_id) {
                NotificationService::notify(
                    (int) $loserTeam->user_id, NewsItem::CAT_FINANCE, '❌',
                    "Asta sponsor persa: {$sponsor->name}",
                    "Vincitore: {$team->name}", '', 0,
                    "❌ Hai perso l'asta sponsor per <b>{$sponsor->name}</b>.\nVincitore: {$team->name}"
                );
            }
        }

        return ['resolved' => count($bids), 'won' => 1, 'lost' => max(0, count($bids) - 1)];
    }

    /**
     * @param MarketBid[] $bids
     * @return array{resolved:int,won:int,lost:int}
     */
    private function resolveAsLost(array $bids, string $reason, int $now): array
    {
        foreach ($bids as $bid) {
            $this->markBid($bid, MarketBid::STATUS_LOST, $reason, $now);
        }
        return ['resolved' => count($bids), 'won' => 0, 'lost' => count($bids)];
    }

    private function markBid(MarketBid $bid, string $status, string $note, int $now): void
    {
        $bid->status = $status;
        $bid->resolved_at = $now;
        $bid->result_note = substr($note, 0, 255);
        $bid->updated_at = $now;
        $bid->save(false, ['status', 'resolved_at', 'result_note', 'updated_at']);
    }

    /**
     * @param MarketBid[] $bids
     */
    private function pickFirstAffordableBid(array $bids): ?MarketBid
    {
        foreach ($this->sortBidsDesc($bids) as $bid) {
            $team = Team::findOne((int) $bid->team_id);
            if ($team && (int) $team->budget >= (int) $bid->bid_amount) {
                return $bid;
            }
        }
        return null;
    }

    /**
     * @param MarketBid[] $bids
     * @return MarketBid[]
     */
    private function sortBidsDesc(array $bids): array
    {
        usort($bids, static function (MarketBid $a, MarketBid $b): int {
            if ((int) $a->bid_amount === (int) $b->bid_amount) {
                return (int) $a->created_at <=> (int) $b->created_at;
            }
            return (int) $b->bid_amount <=> (int) $a->bid_amount;
        });
        return $bids;
    }

    private function roleSpecialisationForAuction(string $role): string
    {
        return match ($role) {
            Staff::ROLE_HEAD_COACH => 'tattica',
            Staff::ROLE_FITNESS_COACH => 'fisico',
            Staff::ROLE_GOALKEEPING_COACH => 'portieri',
            Staff::ROLE_SCOUT => 'scouting',
            default => 'equilibrato',
        };
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
     * Tactical training horizon in days for seasonal cap logic.
     */
    private function seasonDaysForTacticTraining(): int
    {
        $raw = trim((string) getenv('GM_TACTIC_SEASON_DAYS'));
        if ($raw !== '' && ctype_digit($raw)) {
            $v = (int) $raw;
            if ($v >= 30 && $v <= 180) {
                return $v;
            }
        }
        return 90;
    }

    /**
     * Baseline tactic values for current season.
     *
     * Uses first available snapshot of season; fallback to current row.
     *
     * @param string[] $tacticFields
     * @param array<string,mixed> $currentTacticRow
     * @return array<string,int>
     */
    private function loadTacticSeasonBaseline(
        int $teamId,
        int $season,
        array $tacticFields,
        array $currentTacticRow
    ): array {
        $baseline = [];
        foreach ($tacticFields as $field) {
            $baseline[$field] = max(0, min(100, (int) ($currentTacticRow[$field] ?? 0)));
        }

        try {
            $first = Yii::$app->db->createCommand(
                'SELECT * FROM {{%training_tactic_snapshot}}
                 WHERE team_id = :t AND season = :s
                 ORDER BY snapshot_date ASC, id ASC
                 LIMIT 1',
                [':t' => $teamId, ':s' => $season]
            )->queryOne();
            if (!$first) {
                return $baseline;
            }
            foreach ($tacticFields as $field) {
                if (array_key_exists($field, $first)) {
                    $baseline[$field] = max(0, min(100, (int) $first[$field]));
                }
            }
        } catch (\Throwable) {
            return $baseline;
        }

        return $baseline;
    }

    private function hasSetPiecePhysicalAllocColumn(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        try {
            $schema = Yii::$app->db->schema->getTableSchema('{{%training_skill}}', true);
            $has = $schema !== null && isset($schema->columns['alloc_calci_piazzati']);
        } catch (\Throwable) {
            $has = false;
        }
        return $has;
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

        // ── Home/Away standing rank (derived from ordered table) ────────
        $table = Standing::find()
            ->where(['competition_id' => $fixture->competition_id])
            ->orderBy([
                'points' => SORT_DESC,
                new \yii\db\Expression('(goals_for - goals_against) DESC'),
                'goals_for' => SORT_DESC,
                'team_id' => SORT_ASC,
            ])
            ->all();
        $totalTeams = count($table);
        $positionMap = [];
        foreach ($table as $idx => $row) {
            $positionMap[(int) $row->team_id] = $idx + 1;
        }

        $homePos = $positionMap[(int) $fixture->home_team_id] ?? null;
        if ($homePos !== null && $totalTeams > 0) {
            $posRatio = 1 - (($homePos - 1) / max(1, $totalTeams - 1));
            // top team: +15%, bottom team: -15%
            $base += ($posRatio - 0.5) * 0.30;
        }

        $awayPos = $positionMap[(int) $fixture->away_team_id] ?? null;
        if ($awayPos !== null && $totalTeams > 0) {
            $awayRatio = 1 - (($awayPos - 1) / max(1, $totalTeams - 1));
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

    /**
     * Build route URL in both web and console contexts.
     *
     * @param array<string, scalar> $params
     */
    /**
     * SIP-0025: Auto-rollover all league competitions whose season is fully played.
     * Runs daily (cron: 0 3 * * *). Safe to run multiple times — idempotent.
     *
     * Usage: ./yii economy/auto-rollover
     */
    public function actionAutoRollover(): int
    {
        $competitions = Competition::find()
            ->where(['type' => 'league'])
            ->all();

        $rolled = 0;
        $skipped = 0;

        foreach ($competitions as $competition) {
            $total = (int) Fixture::find()
                ->where(['competition_id' => $competition->id])
                ->count();

            if ($total === 0) {
                $skipped++;
                continue;
            }

            $unfinished = (int) Fixture::find()
                ->where(['competition_id' => $competition->id])
                ->andWhere(['!=', 'status', Fixture::STATUS_FINISHED])
                ->count();

            if ($unfinished > 0) {
                $skipped++;
                continue;
            }

            $newSeason = $competition->season + 1;
            $this->stdout("🔄 Rollover automatico: {$competition->name} → stagione {$newSeason}\n");

            $exitCode = $this->actionSeasonRollover($competition->id, $newSeason);
            if ($exitCode === ExitCode::OK) {
                $rolled++;
            } else {
                $this->stderr("❌ Rollover fallito per competition #{$competition->id}\n");
            }
        }

        $this->stdout("✅ Auto-rollover: {$rolled} completati, {$skipped} saltati.\n");
        return ExitCode::OK;
    }

    /**
     * SIP-0025: Pre-seed a new Serie C group when the current pool of free CPU teams
     * drops below the configured threshold (default: 4).
     * Run daily or after each registration burst.
     *
     * Usage: ./yii economy/ensure-expansion-pool [minFreeSlots]
     */
    public function actionEnsureExpansionPool(int $minFreeSlots = 4): int
    {
        $countryCodes = Competition::find()
            ->select('country_code')
            ->where(['type' => 'league', 'tier' => Competition::TIER_C])
            ->distinct()
            ->column();

        $seeded = 0;
        foreach ($countryCodes as $cc) {
            $freeSlots = (int) \app\models\Team::find()
                ->innerJoin(
                    \app\models\Standing::tableName() . ' s',
                    's.team_id = ' . \app\models\Team::tableName() . '.id'
                )
                ->innerJoin(
                    Competition::tableName() . ' c',
                    'c.id = s.competition_id'
                )
                ->where([
                    \app\models\Team::tableName() . '.is_cpu'   => 1,
                    \app\models\Team::tableName() . '.user_id'  => null,
                    \app\models\Team::tableName() . '.country_code' => $cc,
                    'c.tier' => Competition::TIER_C,
                    'c.type' => 'league',
                ])
                ->count();

            if ($freeSlots < $minFreeSlots) {
                $nextGroup = (int) Competition::find()
                    ->where(['tier' => Competition::TIER_C, 'country_code' => $cc])
                    ->max('group_number') + 1;

                $this->stdout("🌍 [{$cc}] Slot liberi: {$freeSlots} < {$minFreeSlots} — seed nuovo girone C #{$nextGroup}\n");

                $tx = \Yii::$app->db->beginTransaction();
                try {
                    (new \app\components\WorldSeeder())->seedLeague(
                        Competition::TIER_C, $nextGroup, null, 16, $cc
                    );
                    $tx->commit();
                    $seeded++;
                    $this->stdout("✅ [{$cc}] Girone C #{$nextGroup} creato.\n");
                } catch (\Throwable $e) {
                    $tx->rollBack();
                    $this->stderr("❌ [{$cc}] Espansione fallita: {$e->getMessage()}\n");
                }
            } else {
                $this->stdout("ℹ️  [{$cc}] Slot liberi ok ({$freeSlots}).\n");
            }
        }

        $this->stdout("✅ Expansion pool: {$seeded} nuovi gironi creati.\n");
        return ExitCode::OK;
    }

    private function safeUrl(string $route, array $params = []): string
    {
        if (Yii::$app instanceof \yii\console\Application) {
            $q = http_build_query($params);
            return $q !== '' ? ($route . '?' . $q) : $route;
        }
        return Yii::$app->urlManager->createUrl(array_merge([$route], $params));
    }
}
