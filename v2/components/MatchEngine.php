<?php

declare(strict_types=1);

namespace app\components;

use app\models\Fixture;
use app\models\Formation;
use app\models\MatchEvent;
use app\models\MatchState;
use app\models\Player;
use app\models\Standing;
use app\models\Staff;
use Yii;
use yii\base\Component;

/**
 * Tick-based Match Engine.
 *
 * A "tick" == one minute of match time.
 * The cron worker calls advanceTick($fixture) once per real-world minute.
 * A manager watching the match live gets the same event log via polling.
 *
 * Character trait modifiers (SIP-0020):
 *   grintoso   → +10% attack when losing
 *   ambizioso  → +5% all stats
 *   egoista    → −5% team midfield but +10% individual shot
 *   fantasioso → +10% midfield creativity (rg/pa)
 */
class MatchEngine extends Component
{
    // ──────────────────────────────────────────────────────────────────────
    //  PUBLIC API
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Advance the match by one minute (tick).
     * Called by the scheduler once per real minute.
     *
     * @param Fixture $fixture
     * @return MatchEvent[]  Events generated in this tick (may be empty)
     */
    public function advanceTick(Fixture $fixture): array
    {
        $state = $this->ensureState($fixture);
        $events = [];

        if ($state->phase === 'finished') {
            return [];
        }

        // Kick-off
        if ($state->phase === 'not_started') {
            $state->phase = 'first_half';
            $state->current_minute = 1;
            $events[] = $this->saveEvent($fixture, 1, 'kickoff', 'home', null, []);
            $state->save(false);
            return $events;
        }

        $min = $state->current_minute;

        // Half-time window: 15 pause ticks
        if ($state->phase === 'half_time') {
            $state->half_time_ticks++;
            if ($state->half_time_ticks >= 15) {
                $state->phase = 'second_half';
                $state->current_minute = 45; // next tick increments to 46 (first second-half minute)
                $events[] = $this->saveEvent($fixture, 45, 'second_half_start', 'home', null, []);
            }
            $state->save(false);
            return $events;
        }

        $events = array_merge($events, $this->applyPendingActions($fixture, $state, $min));

        $homeTotals = $this->computeTeamTotals($fixture->homeTeam, $state->homeFormation, $state, 'home');
        $awayTotals = $this->computeTeamTotals($fixture->awayTeam, $state->awayFormation, $state, 'away');

        // SIP-0035: apply staff bonus to department totals
        $homeTotals = $this->applyStaffBonus($homeTotals, $fixture->home_team_id);
        $awayTotals = $this->applyStaffBonus($awayTotals, $fixture->away_team_id);

        // SIP-0038: apply tactical training modifiers
        $homeTactics = $this->loadTactics($fixture->home_team_id);
        $awayTactics = $this->loadTactics($fixture->away_team_id);

        $events = array_merge($events, $this->resolveTick(
            $fixture,
            $state,
            $homeTotals,
            $awayTotals,
            $min,
            $homeTactics,
            $awayTactics,
            $state->homeFormation,
            $state->awayFormation
        ));

        $state->current_minute++;

        // Use >= 45 (not ===) so the window is not missed even if one save fails
        if ($min >= 45 && $min < 47 && $state->phase === 'first_half') {
            $state->phase = 'half_time';
            $state->current_minute = 45;
            // Avoid duplicate half_time events if the engine retries
            $alreadyHT = \app\models\MatchEvent::find()
                ->where(['fixture_id' => $fixture->id, 'type' => 'half_time'])
                ->exists();
            if (!$alreadyHT) {
                $events[] = $this->saveEvent($fixture, 45, 'half_time', 'home', null, [
                    'home' => $state->home_score,
                    'away' => $state->away_score,
                ]);
            }
        } elseif ($min >= 90 && in_array($state->phase, ['first_half', 'second_half'], true)) {
            // End match regardless of phase — prevents eternal loops
            $state->phase = 'finished';
            $alreadyFT = \app\models\MatchEvent::find()
                ->where(['fixture_id' => $fixture->id, 'type' => 'full_time'])
                ->exists();
            if (!$alreadyFT) {
                $events[] = $this->saveEvent($fixture, $min, 'full_time', 'home', null, [
                    'home' => $state->home_score,
                    'away' => $state->away_score,
                ]);
                $this->finalizeMatch($fixture, $state);
            }
        }

        $state->save(false);
        return $events;
    }

    /**
     * Return all events for a fixture since a given minute (for live polling).
     *
     * @param int $fixtureId
     * @param int $sinceMimute
     * @return MatchEvent[]
     */
    public function getEventsSince(int $fixtureId, int $sinceMinute): array
    {
        return MatchEvent::find()
            ->where(['fixture_id' => $fixtureId])
            ->andWhere(['>=', 'minute', $sinceMinute])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }

    /**
     * Queue a substitution from the manager for processing on the next tick.
     *
     * @param Fixture $fixture
     * @param string  $side  'home' or 'away'
     * @param int     $outPlayerId
     * @param int     $inPlayerId
     */
    public function queueSubstitution(Fixture $fixture, string $side, int $outPlayerId, int $inPlayerId): bool
    {
        $state = $this->ensureState($fixture);
        $field = "pending_{$side}_actions";
        $actions = json_decode($state->$field ?? '[]', true);

        $subsField = "{$side}_subs_used";
        if ($state->$subsField >= 3) {
            return false; // max 3 subs
        }

        $actions[] = ['type' => 'substitution', 'out' => $outPlayerId, 'in' => $inPlayerId];
        $state->$field = json_encode($actions);
        return $state->save();
    }

    /**
     * Queue a tactic change from the manager.
     *
     * @param Fixture $fixture
     * @param string  $side     'home' or 'away'
     * @param string  $tactic   New tactic name
     * @param string  $marking  New marking type
     */
    public function queueTacticChange(Fixture $fixture, string $side, string $tactic, string $marking): bool
    {
        $state = $this->ensureState($fixture);
        $field = "pending_{$side}_actions";
        $actions = json_decode($state->$field ?? '[]', true);
        $actions[] = ['type' => 'tactic_change', 'tactic' => $tactic, 'marking' => $marking];
        $state->$field = json_encode($actions);
        return $state->save();
    }

    // ──────────────────────────────────────────────────────────────────────
    //  INTERNAL — TICK RESOLUTION
    // ──────────────────────────────────────────────────────────────────────

    /**
     * The core 5-step action pipeline for one minute.
     *
     *  1. Midfield duel           (cn/rg/pa based)
     *  2. Determine attack side   (L / C / R)
     *  3. Attack vs Defence       (tr/cr vs df/cn)
     *  4. Shot quality vs GK      (tc/tr vs po)
     *  5. Near-miss / goal        (small random final roll)
     */
    protected function getActivePlayer(Fixture $fixture, MatchState $state, string $side, string $roleGroup): ?\app\models\Player
    {
        $formation = $side === 'home' ? $state->homeFormation : $state->awayFormation;
        $candidates = [];

        if ($formation) {
            foreach ($formation->slots as $slot) {
                if (!$slot->player || !PitchZoneHelper::isOnPitch((int) $slot->zone))
                    continue;
                $player = $slot->player;

                if ($roleGroup === 'GK' && $player->position === 'GK') {
                    return $player;
                }
                if ($roleGroup === 'FW' && in_array($player->position, ['FW', 'AM'])) {
                    $candidates[] = $player;
                }
                if ($roleGroup === 'MF' && in_array($player->position, ['MF', 'DM', 'AM'])) {
                    $candidates[] = $player;
                }
                if ($roleGroup === 'DF' && in_array($player->position, ['DF', 'DM'])) {
                    $candidates[] = $player;
                }
            }
        }

        if (empty($candidates)) {
            $team = $side === 'home' ? $fixture->homeTeam : $fixture->awayTeam;
            if ($team) {
                foreach ($team->players as $p) {
                    if ($roleGroup === 'GK' && $p->position === 'GK')
                        return $p;
                    if ($roleGroup === 'FW' && $p->position === 'FW')
                        $candidates[] = $p;
                    if ($roleGroup === 'MF' && $p->position === 'MF')
                        $candidates[] = $p;
                    if ($roleGroup === 'DF' && $p->position === 'DF')
                        $candidates[] = $p;
                }
            }
        }

        if (!empty($candidates)) {
            return $candidates[array_rand($candidates)];
        }

        return null;
    }

    protected function resolveTick(
        Fixture $fixture,
        MatchState $state,
        array $home,
        array $away,
        int $min,
        array $homeTactics = [],
        array $awayTactics = [],
        ?Formation $homeFormation = null,
        ?Formation $awayFormation = null
    ): array
    {
        $events = [];

        // ── SIP-0041: Cards (yellow/red) ──
        $events = array_merge($events, $this->resolveCards($fixture, $state, $min));

        // ── SIP-0040: Injuries ──
        $events = array_merge($events, $this->resolveInjuries($fixture, $state, $min));

        $homeTactics = $this->applyTacticConflicts($homeTactics);
        $awayTactics = $this->applyTacticConflicts($awayTactics);
        $homeProfile = $this->buildFormationTacticProfile($homeFormation, $homeTactics);
        $awayProfile = $this->buildFormationTacticProfile($awayFormation, $awayTactics);

        // ── Step 1: Midfield duel + lancio lungo bypass (SIP-0038) ──
        $homeBypassChance = min(100, (int) round((($homeTactics['lancio_lungo'] ?? 0) * 0.20) * $homeProfile['longball_mult']));
        $awayBypassChance = min(100, (int) round((($awayTactics['lancio_lungo'] ?? 0) * 0.20) * $awayProfile['longball_mult']));
        $homeBypass = $homeBypassChance > 0 && mt_rand(1, 100) <= $homeBypassChance;
        $awayBypass = $awayBypassChance > 0 && mt_rand(1, 100) <= $awayBypassChance;

        $forcedBypass = false;
        if ($homeBypass xor $awayBypass) {
            $attSide = $homeBypass ? 'home' : 'away';
            $forcedBypass = true;
        } elseif ($homeBypass && $awayBypass) {
            $attSide = (($homeTactics['lancio_lungo'] ?? 0) >= ($awayTactics['lancio_lungo'] ?? 0))
                ? (mt_rand(0, 100) < 55 ? 'home' : 'away')
                : (mt_rand(0, 100) < 55 ? 'away' : 'home');
            $forcedBypass = true;
        } else {
            $homeMid = (int)(($home['cn'] + $home['rg'] + $home['pa'])
                * (1 + ($homeTactics['pressing'] ?? 0) / 100 * 0.08)
                * (1 + ($homeTactics['possesso'] ?? 0) / 100 * 0.10)
                * $homeProfile['mid_mult']);
            $awayMid = (int)(($away['cn'] + $away['rg'] + $away['pa'])
                * (1 + ($awayTactics['pressing'] ?? 0) / 100 * 0.08)
                * (1 + ($awayTactics['possesso'] ?? 0) / 100 * 0.10)
                * $awayProfile['mid_mult']);
            $attSide = $this->duel($homeMid, $awayMid) ? 'home' : 'away';
        }

        $defSide = $attSide === 'home' ? 'away' : 'home';
        $atk = $attSide === 'home' ? $home : $away;
        $def = $attSide === 'home' ? $away : $home;
        $atkTactics = $attSide === 'home' ? $homeTactics : $awayTactics;
        $defTactics = $attSide === 'home' ? $awayTactics : $homeTactics;
        $atkFormation = $attSide === 'home' ? $homeFormation : $awayFormation;
        $atkProfile = $attSide === 'home' ? $homeProfile : $awayProfile;
        $defProfile = $attSide === 'home' ? $awayProfile : $homeProfile;
        $oppSide = $defSide;

        // SIP-0038: fuorigioco trap — negate attack
        $offsideChance = (int) (($defTactics['fuorigioco'] ?? 0) * 0.15 * $defProfile['offside_mult']);
        if ($defProfile['offside_enabled'] && $offsideChance > 0 && mt_rand(1, 100) <= $offsideChance) {
            return $events;
        }

        // ── Step 2: Attack side (L / C / R) ──
        $side = $this->pickAttackSide($atk);

        // ── Step 3: Attack vs Defence (SIP-0038: catenaccio + palla_bassa + contropiede) ──
        $isCounterAttack = $forcedBypass || (mt_rand(1, 100) <= 35);
        $counterBonus = $isCounterAttack ? (1 + ($atkTactics['contropiede'] ?? 0) / 100 * 0.15) : 1.0;
        $atkForce = (int)(($atk['at_' . $side] + (int)($atk['cr'] * 0.3))
            * $counterBonus
            * $atkProfile['attack_mult']);
        $defForce = (int)(($def['df'] + (int)($def['cn'] * 0.3))
            * (1 + ($defTactics['catenaccio'] ?? 0) / 100 * 0.12)
            * (1 - ($atkTactics['palla_bassa'] ?? 0) / 100 * 0.05)
            * $defProfile['def_mult']);
        $defForce = (int) ($defForce * $this->markingModifier((string) $defProfile['marking'], (string) $side));

        if (!$this->duel($atkForce, $defForce)) {
            // Save noise events only ~15% of the time — avoids flooding the timeline
            if (mt_rand(1, 100) <= 15) {
                $type = mt_rand(0, 1) === 0 ? 'midfield_duel' : 'attack_attempt';
                $attacker = $this->getActivePlayer($fixture, $state, $attSide, $type === 'midfield_duel' ? 'MF' : 'FW');
                $events[] = $this->saveEvent($fixture, $min, $type, $attSide, $attacker?->id, [
                    'side' => $side,
                    'attacker_name' => $attacker?->name,
                ]);
            }
            return $events;
        }

        // ── Step 4: Tiro vs Portiere ──
        $shotPower = (int) ($atk['tc'] * 0.4 + $atk['tr'] * 0.6);
        $gkPower = (int) ($def['po'] * 2.0 + $def['df'] * 0.2);

        if (!$this->duel($shotPower, $gkPower)) {
            $type = mt_rand(0, 1) === 0 ? 'near_miss' : 'gk_save';
            $shooter = $this->getActivePlayer($fixture, $state, $attSide, 'FW');
            $gk = $this->getActivePlayer($fixture, $state, $oppSide, 'GK');

            $events[] = $this->saveEvent($fixture, $min, $type, $attSide, $type === 'gk_save' ? ($gk ? $gk->id : null) : ($shooter ? $shooter->id : null), [
                'side' => $side,
                'shooter_name' => $shooter ? $shooter->name : null,
                'gk_name' => $gk ? $gk->name : null
            ]);
            return $events;
        }

        // ── Step 5: Gate precisione — base 30%, set-piece taker can raise cap ──
        $setPieceType = null;
        $setPieceRoll = mt_rand(1, 100);
        if ($setPieceRoll <= 4) {
            $setPieceType = 'penalty';
        } elseif ($setPieceRoll <= 14) {
            $setPieceType = 'freekick';
        }

        $roleHelper = new FormationRoleHelper();
        $precisionCap = $setPieceType && $atkFormation
            ? $roleHelper->precisionCapForSetPiece($atkFormation, $setPieceType)
            : 30;
        if ($setPieceType) {
            $precisionCap = min(65, $precisionCap + (int) round(($atkTactics['calci_piazzati'] ?? 0) * 0.20));
        }
        $taker = null;
        if ($setPieceType === 'penalty' && $atkFormation) {
            $taker = $roleHelper->resolvePenaltyTaker($atkFormation);
        } elseif ($setPieceType === 'freekick' && $atkFormation) {
            $taker = $roleHelper->resolveFreeKickTaker($atkFormation);
        }
        if ($setPieceType === 'penalty' && $taker && CharacterTraitHelper::normalize((string) $taker->character) === 'razionale') {
            $precisionCap = min(70, $precisionCap + 10);
        }
        $shooter = $taker ?: $this->getActivePlayer($fixture, $state, $attSide, 'FW');

        if (mt_rand(1, 100) > $precisionCap) {
            $events[] = $this->saveEvent($fixture, $min, 'near_miss', $attSide, $shooter ? $shooter->id : null, [
                'side' => $side,
                'shooter_name' => $shooter ? $shooter->name : null,
                'origin' => $setPieceType ?? 'open_play',
            ]);
            return $events;
        }

        // ── GOL ───────────────────────────────────────────────────────
        $scoreField = "{$attSide}_score";
        $state->$scoreField++;
        $scorer = $shooter ?: $this->getActivePlayer($fixture, $state, $attSide, 'FW');

        $events[] = $this->saveEvent($fixture, $min, 'goal', $attSide, $scorer ? $scorer->id : null, [
            'home_score' => $state->home_score,
            'away_score' => $state->away_score,
            'side' => $side,
            'scorer_name' => $scorer ? $scorer->name : null,
            'origin' => $setPieceType ?? 'open_play',
            'is_penalty' => $setPieceType === 'penalty',
        ]);

        return $events;
    }

    /**
     * SIP-0041: Generate yellow/red card events.
     * Probabilities: 1.5% yellow per team per tick (~2-3 yellows/match),
     *               0.05% direct red per team per tick (~0.09 reds/match).
     * Skipped for friendly matches. Second yellow in same match → red.
     */
    protected function resolveCards(Fixture $fixture, MatchState $state, int $min): array
    {
        $events = [];

        if ($fixture->competition?->type === 'friendly') {
            return $events;
        }

        foreach (['home', 'away'] as $side) {
            $ejectedField = "{$side}_ejected";
            $yellowsField = "{$side}_yellows";
            $player = $this->getActivePlayer($fixture, $state, $side, 'MF');
            if (!$player) {
                continue;
            }
            $disciplineMod = CharacterTraitHelper::disciplineRiskModifier((string) $player->character);

            // ── Yellow card (1.5%) ──
            $yellowChance = max(1, min(5000, (int) round(150 * $disciplineMod)));
            if (mt_rand(1, 10000) <= $yellowChance) {
                $yellows = json_decode($state->$yellowsField ?? '{}', true) ?: [];
                $inMatch = ($yellows[$player->id] ?? 0) + 1;

                if ($inMatch >= 2) {
                    // Second yellow → red; team goes to 10 men
                    $player->red_cards++;
                    $player->suspended_matches = max((int)$player->suspended_matches + 1, 1);
                    $player->save(false);
                    $state->$ejectedField = 1;
                    $yellows[$player->id] = 2;
                    $state->$yellowsField = json_encode($yellows);
                    $state->save(false);

                    $events[] = $this->saveEvent($fixture, $min, 'red_card', $side, $player->id, [
                        'player_name' => $player->name,
                        'reason'      => 'second_yellow',
                    ]);
                    $this->createDisciplineNews($fixture, $side, $player, 'rosso (doppio giallo)', $player->suspended_matches);
                } else {
                    $player->yellow_cards++;
                    if ($player->yellow_cards >= 5) {
                        $player->suspended_matches = 2;
                        $player->yellow_cards      = 0;
                        $this->createDisciplineNews($fixture, $side, $player, '5° giallo — squalifica', 2);
                    } elseif ($player->yellow_cards % 3 === 0) {
                        $player->suspended_matches = max((int)$player->suspended_matches, 1);
                        $this->createDisciplineNews($fixture, $side, $player, "{$player->yellow_cards}° giallo — squalifica", 1);
                    }
                    $player->save(false);
                    $yellows[$player->id] = $inMatch;
                    $state->$yellowsField = json_encode($yellows);
                    $state->save(false);

                    $events[] = $this->saveEvent($fixture, $min, 'yellow_card', $side, $player->id, [
                        'player_name'  => $player->name,
                        'yellow_count' => $player->yellow_cards,
                    ]);
                }
                continue; // one card event per side per tick is enough
            }

            // ── Direct red card (0.05%) — only if team not already short-handed ──
            $directRedChance = max(1, min(2500, (int) round(50 * $disciplineMod)));
            if (!$state->$ejectedField && mt_rand(1, 100000) <= $directRedChance) {
                $player->red_cards++;
                $player->suspended_matches = max((int)$player->suspended_matches + 1, 1);
                $player->save(false);
                $state->$ejectedField = 1;
                $state->save(false);

                $events[] = $this->saveEvent($fixture, $min, 'red_card', $side, $player->id, [
                    'player_name' => $player->name,
                    'reason'      => 'direct_red',
                ]);
                $this->createDisciplineNews($fixture, $side, $player, 'rosso diretto', $player->suspended_matches);
            }
        }

        return $events;
    }

    /**
     * SIP-0040: Injury check per tick.
     * Each tick: 0.05% base chance per team that a random outfield player gets injured.
     * Modifiers: age >32 (+50%), character corretto (−30%), grintoso/irrequieto (+20%).
     * Skipped for friendly matches and already-injured players.
     */
    protected function resolveInjuries(Fixture $fixture, MatchState $state, int $min): array
    {
        $events = [];

        if ($fixture->competition?->type === 'friendly') {
            return $events;
        }

        foreach (['home', 'away'] as $side) {
            // 0.05% base chance per team per tick
            if (mt_rand(1, 2000) !== 1) {
                continue;
            }

            $player = $this->getActivePlayer($fixture, $state, $side, 'MF');
            if (!$player || $player->injury_weeks > 0) {
                continue;
            }

            // Character modifier
            $charMod = match (strtolower((string)$player->character)) {
                'corretto'               => 0.70,
                'grintoso', 'irrequieto' => 1.20,
                default                  => 1.00,
            };

            // Age modifier
            $ageMod = $player->age > 32 ? 1.5 : ($player->age < 22 ? 0.7 : 1.0);

            // Condition modifier: low condition → higher risk
            $condMod = 1 + max(0, (60 - (int)$player->condition) / 100);

            // Combined roll (base already passed, apply multipliers to severity threshold)
            if (mt_rand(1, 1000) > (int)(1000 * $charMod * $ageMod * $condMod)) {
                continue;
            }

            // Severity
            $roll = mt_rand(1, 100);
            $type = match (true) {
                $roll <= 60 => 'lieve',
                $roll <= 90 => 'medio',
                default     => 'grave',
            };

            $player->injury_weeks = match ($type) {
                'lieve' => mt_rand(1, 2),
                'medio' => mt_rand(3, 4),
                'grave' => mt_rand(5, 8),
            };
            $player->injury_type  = $type;
            $player->condition    = 0;
            $player->save(false);

            $events[] = $this->saveEvent($fixture, $min, 'injury', $side, $player->id, [
                'player_name'  => $player->name,
                'injury_type'  => $type,
                'injury_weeks' => $player->injury_weeks,
            ]);

            // SIP-0050 + SIP-0058: news + Telegram for human manager
            $team = $side === 'home' ? $fixture->homeTeam : $fixture->awayTeam;
            if ($team?->user_id) {
                \app\components\NewsService::create(
                    (int)$team->user_id,
                    \app\models\NewsItem::CAT_INJURY,
                    '🏥',
                    "{$player->name} infortunato: {$type}",
                    "Fuori {$player->injury_weeks} settimane",
                    '',
                    1
                );
                \app\components\TelegramService::sendToUser(
                    (int)$team->user_id,
                    "🏥 <b>{$player->name}</b> infortunato ({$type}) — fuori {$player->injury_weeks} settimane"
                );
            }
        }

        return $events;
    }

    /**
     * Weighted random duel: draws random values in [0, value] for each side.
     */
    protected function duel(int $a, int $b): bool
    {
        if ($a <= 0 && $b <= 0)
            return false;
        return mt_rand(0, max(1, $a)) > mt_rand(0, max(1, $b));
    }

    /**
     * Pick attack side (left / center / right) weighted by player distribution.
     */
    protected function pickAttackSide(array $totals): string
    {
        $total = $totals['at_L'] + $totals['at_C'] + $totals['at_R'];
        if ($total <= 0)
            return 'C';

        $roll = mt_rand(1, $total);
        if ($roll <= $totals['at_L'])
            return 'L';
        if ($roll <= $totals['at_L'] + $totals['at_C'])
            return 'C';
        return 'R';
    }

    // ──────────────────────────────────────────────────────────────────────
    //  INTERNAL — TEAM TOTALS
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Build department totals from the active formation.
     * Applies: form, freshness, condition, effort, character trait bonuses.
     *
     * Returns:
     *   po, df, cn, rg, pa, cr, tc, tr    — summed across the XI
     *   at_L, at_C, at_R                  — attack by pitch side
     */
    protected function computeTeamTotals(?object $team, ?Formation $formation, MatchState $state, string $side): array
    {
        $totals = [
            'po' => 0,
            'df' => 0,
            'cn' => 0,
            'rg' => 0,
            'pa' => 0,
            'cr' => 0,
            'tc' => 0,
            'tr' => 0,
            'at_L' => 0,
            'at_C' => 0,
            'at_R' => 0,
        ];

        if (!$team)
            return $totals;

        $scoreField = "{$side}_score";
        $oppField = $side === 'home' ? 'away_score' : 'home_score';
        $isLosing = $state->$scoreField < $state->$oppField;

        // Load starters from formation using pitch helper (supports legacy + 10-row grid)
        if ($formation) {
            $slots = $formation->slots;
            $roleHelper = new FormationRoleHelper();
            $captain = $roleHelper->resolveCaptain($formation);
            $captainId = $captain ? (int) $captain->id : 0;
            $captainBoost = 0.0;
            $captainZone = null;
            if ($captainId > 0) {
                foreach ($slots as $capSlot) {
                    if ((int) ($capSlot->player_id ?? 0) === $captainId) {
                        $captainZone = (int) $capSlot->zone;
                        break;
                    }
                }
                if (CharacterTraitHelper::normalize((string) $captain->character) === 'carismatico') {
                    $captainBoost = 0.01;
                }
            }
            foreach ($slots as $slot) {
                if (!$slot->player || !PitchZoneHelper::isOnPitch((int) $slot->zone))
                    continue;
                $player = $slot->player;
                $slotZone = (int) $slot->zone;
                $multiplier = $this->computePlayerMultiplier($player, $formation->effort, $isLosing, $side === 'home', $slotZone);
                if ($captainBoost > 0.0 && (int) $player->id !== $captainId) {
                    $multiplier *= (1.0 + $captainBoost);
                    if ($captainZone !== null && $this->areZonesAdjacent($slotZone, $captainZone, 1)) {
                        $multiplier *= 1.02;
                    }
                }

                $totals['po'] += (int) ($player->skill_po * $multiplier);
                $totals['df'] += (int) ($player->skill_df * $multiplier);
                $totals['cn'] += (int) ($player->skill_cn * $multiplier);
                $totals['rg'] += (int) ($player->skill_rg * $multiplier);
                $totals['pa'] += (int) ($player->skill_pa * $multiplier);
                $totals['cr'] += (int) ($player->skill_cr * $multiplier);
                $totals['tc'] += (int) ($player->skill_tc * $multiplier);
                $totals['tr'] += (int) ($player->skill_tr * $multiplier);

                // Attack side based on normalized lane (L/C/R).
                $lane = PitchZoneHelper::laneCode($slotZone);
                if ($lane === 'L') {
                    $totals['at_L'] += (int) ($player->skill_tr * $multiplier);
                } elseif ($lane === 'R') {
                    $totals['at_R'] += (int) ($player->skill_tr * $multiplier);
                } else {
                    $totals['at_C'] += (int) ($player->skill_tr * $multiplier);
                }
            }
        } else {
            // Fallback: no formation — pick best 11 by general_skill, role-aware
            $all = $team->players;
            usort($all, fn($a, $b) => $b->general_skill <=> $a->general_skill);

            // Ensure at least 1 GK, up to 4 DF, up to 4 MF, rest FW
            $starters = [];
            $counts = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
            $limits = ['GK' => 1, 'DF' => 4, 'MF' => 4, 'FW' => 3];

            // First pass: fill minimums (1 GK)
            foreach ($all as $p) {
                if ($p->position === 'GK' && $counts['GK'] < 1) {
                    $starters[] = $p;
                    $counts['GK']++;
                }
            }
            // Second pass: fill up to 11
            foreach ($all as $p) {
                if (count($starters) >= 11)
                    break;
                if (in_array($p, $starters, true))
                    continue;
                $pos = $p->position;
                if (($counts[$pos] ?? 0) < ($limits[$pos] ?? 3)) {
                    $starters[] = $p;
                    $counts[$pos]++;
                }
            }
            // Third pass: fill remaining slots with best available
            foreach ($all as $p) {
                if (count($starters) >= 11)
                    break;
                if (!in_array($p, $starters, true)) {
                    $starters[] = $p;
                }
            }

            foreach ($starters as $player) {
                $multiplier = $this->computePlayerMultiplier($player, 3, $isLosing);
                $totals['po'] += (int) ($player->skill_po * $multiplier);
                $totals['df'] += (int) ($player->skill_df * $multiplier);
                $totals['cn'] += (int) ($player->skill_cn * $multiplier);
                $totals['rg'] += (int) ($player->skill_rg * $multiplier);
                $totals['pa'] += (int) ($player->skill_pa * $multiplier);
                $totals['cr'] += (int) ($player->skill_cr * $multiplier);
                $totals['tc'] += (int) ($player->skill_tc * $multiplier);
                $totals['tr'] += (int) ($player->skill_tr * $multiplier);
                // Distribute attack evenly across L/C/R
                $totals['at_L'] += (int) ($player->skill_tr * $multiplier * 0.3);
                $totals['at_C'] += (int) ($player->skill_tr * $multiplier * 0.4);
                $totals['at_R'] += (int) ($player->skill_tr * $multiplier * 0.3);
            }
        }

        // SIP-0041: 10-men penalty — player sent off, team loses ~9% of outfield power
        $ejectedField = "{$side}_ejected";
        if ($state->$ejectedField) {
            foreach (['df','cn','rg','pa','cr','tc','tr','at_L','at_C','at_R'] as $k) {
                $totals[$k] = (int)($totals[$k] * 0.91);
            }
        }

        // SIP-0041: 10-men penalty already applied above
        // SIP-0059: man-marking effects (use fixture_id from state)
        $fixtureId    = (int)$state->fixture_id;
        $oppFormation = $side === 'home' ? $state->awayFormation : $state->homeFormation;

        // Resolve team IDs from fixture row (single lightweight query)
        $row = \Yii::$app->db->createCommand(
            'SELECT home_team_id, away_team_id FROM {{%fixture}} WHERE id=:id',
            [':id' => $fixtureId]
        )->queryOne();
        $myTeamId  = $row ? (int)($side === 'home' ? $row['home_team_id'] : $row['away_team_id']) : 0;
        $oppTeamId = $row ? (int)($side === 'home' ? $row['away_team_id'] : $row['home_team_id']) : 0;

        // A) My team marks opponent players → +8% df for each active pair
        $myMarkings = \app\models\ManMarking::find()
            ->where(['fixture_id' => $fixtureId, 'team_id' => $myTeamId])
            ->all();
        foreach ($myMarkings as $m) {
            if ($this->isPlayerOnPitch($oppFormation, (int)$m->marked_id)
                && $this->isPlayerOnPitch($formation, (int)$m->marker_id)) {
                $totals['df'] = (int)($totals['df'] * 1.08);
            }
        }

        // B) Opponent marks MY players → −12% attack for each active pair
        if ($oppTeamId > 0) {
            $oppMarkings = \app\models\ManMarking::find()
                ->where(['fixture_id' => $fixtureId, 'team_id' => $oppTeamId])
                ->all();
            foreach ($oppMarkings as $m) {
                if ($this->isPlayerOnPitch($formation, (int)$m->marked_id)
                    && $this->isPlayerOnPitch($oppFormation, (int)$m->marker_id)) {
                    foreach (['tr','cr','at_L','at_C','at_R'] as $k) {
                        $totals[$k] = (int)($totals[$k] * 0.88);
                    }
                }
            }
        }

        return $totals;
    }

    protected function isPlayerOnPitch(?Formation $formation, int $playerId): bool
    {
        if (!$formation) return false;
        foreach ($formation->slots as $slot) {
            if ((int) $slot->player_id === $playerId && PitchZoneHelper::isOnPitch((int) $slot->zone)) return true;
        }
        return false;
    }

    /**
     * Compute a scalar multiplier for a player based on:
     *  - form (0-100)
     *  - freshness (0-100)
     *  - condition (0-100)
     *  - effort level (1-5 → 50-150%)
     *  - character trait bonuses
     */
    protected function computePlayerMultiplier(Player $player, int $effort, bool $isLosing, bool $isHome = true, ?int $zone = null): float
    {
        $effortMap = [1 => 0.50, 2 => 0.75, 3 => 1.00, 4 => 1.25, 5 => 1.50];
        $effortMult = $effortMap[$effort] ?? 1.0;

        $formMult  = ($player->form      / 100) * 0.30 + 0.70; // 70%…100%
        $freshMult = ($player->freshness  / 100) * 0.15 + 0.85; // 85%…100%
        $condMult  = ($player->condition  / 100) * 0.15 + 0.85; // 85%…100%

        $charBonus = 1.0;
        $inPrimaryPosition = $zone === null ? true : $this->isPrimaryZoneForPosition((string) $player->position, $zone);
        switch (CharacterTraitHelper::normalize((string) $player->character)) {
            case 'grintoso':
                $charBonus = $isLosing ? 1.10 : 1.00;
                break;
            case 'ambizioso':
                $charBonus = 1.05;
                break;
            case 'razionale':
                $charBonus = $isHome ? 1.00 : 1.05; // +5% in trasferta
                break;
            case 'diligente':
                $condMult  *= 1.05;  // allenamento intenso → condizione migliore
                $freshMult *= 0.90;  // ma si stanca di più
                break;
            case 'costante':
                // Riduce la varianza della forma: range più stretto (85%…100%)
                $formMult = ($player->form / 100) * 0.15 + 0.85;
                break;
            case 'irrequieto':
                $charBonus = $isHome ? 1.10 : 0.95; // +10% casa, -5% trasferta
                break;
            case 'inflessibile':
                $charBonus = $inPrimaryPosition ? 1.10 : 0.85;
                break;
            case 'introverso':
                $charBonus = $effort <= 2 ? 1.05 : 1.00; // rende con basso sforzo
                break;
            case 'duttile':
                $charBonus = $inPrimaryPosition ? 1.00 : 1.05;
                break;
            case 'popolare':
                $charBonus = 1.02; // piccolo morale boost di squadra
                break;
            case 'egoista':
            case 'fantasioso':
            case 'duttile':
            case 'corretto':
            case 'carismatico':
                break; // gestiti altrove o a livello squadra
        }

        return $effortMult * $formMult * $freshMult * $condMult * $charBonus;
    }

    // ──────────────────────────────────────────────────────────────────────
    //  INTERNAL — HELPERS
    // ──────────────────────────────────────────────────────────────────────

    protected function ensureState(Fixture $fixture): MatchState
    {
        $state = MatchState::findOne(['fixture_id' => $fixture->id]);
        if ($state) {
            // Normalize phase to lowercase — Go worker writes uppercase ('FINISHED', 'HALF_TIME', etc.)
            $state->phase = strtolower($state->phase);
            return $state;
        }

        $state = new MatchState();
        $state->fixture_id = $fixture->id;
        $state->phase = 'not_started';
        $state->current_minute = 0;
        $state->home_score = 0;
        $state->away_score = 0;
        $state->home_subs_used = 0;
        $state->away_subs_used = 0;
        $state->pending_home_actions = '[]';
        $state->pending_away_actions = '[]';

        $state->home_formation_id = Formation::find()
            ->where(['team_id' => $fixture->home_team_id, 'is_active' => true])
            ->scalar() ?: null;
        $state->away_formation_id = Formation::find()
            ->where(['team_id' => $fixture->away_team_id, 'is_active' => true])
            ->scalar() ?: null;

        // save(false) skips validation — avoid silent failure loops
        if (!$state->save(false)) {
            Yii::error('MatchEngine::ensureState save failed for fixture #' . $fixture->id, 'match');
        }

        return $state;
    }

    protected function saveEvent(
        Fixture $fixture,
        int $minute,
        string $type,
        string $side,
        ?int $playerId,
        array $detail
    ): MatchEvent {
        // Resolve player name if any
        $playerName = null;
        if ($playerId) {
            $player = \app\models\Player::findOne($playerId);
            if ($player) {
                $playerName = $player->name;
            }
        }

        // Handle player names from details if explicitly provided
        if (isset($detail['scorer_name'])) {
            $playerName = $detail['scorer_name'];
        } elseif (isset($detail['shooter_name'])) {
            $playerName = $detail['shooter_name'];
        } elseif (isset($detail['attacker_name'])) {
            $playerName = $detail['attacker_name'];
        }

        // Suspense text shown first (before result is revealed client-side)
        if (in_array($type, ['goal', 'gk_save', 'near_miss'], true)) {
            $detail['suspense_text'] = $this->buildSuspenseText($type, $detail, $fixture);
        }

        // Instant fallback description — LLM enrichment happens async via queue
        $desc = $this->fallbackDescription($type, $fixture, $detail, $minute, $side, $playerName);
        $detail['description'] = $desc;

        // LLM enrichment — only for matches where at least one team is human-managed.
        // CPU-vs-CPU matches skip Ollama: fallback text is sufficient and avoids
        // flooding the queue with 3×~5 enrichment jobs per CPU match batch.
        $homeTeam = $fixture->homeTeam;
        $awayTeam = $fixture->awayTeam;
        $hasHuman = ($homeTeam && !$homeTeam->is_cpu) || ($awayTeam && !$awayTeam->is_cpu);
        $isPreMatchType = in_array($type, ['kickoff', 'pre_match'], true);
        $llmAllowedForType = $isPreMatchType
            ? CommentaryTemplateService::llmPreMatchEnabled()
            : CommentaryTemplateService::llmMatchEnrichEnabled();
        $enqueueLlm = $hasHuman && $llmAllowedForType && in_array($type, ['goal', 'kickoff', 'half_time', 'full_time'], true);

        $event = new MatchEvent();
        $event->fixture_id = $fixture->id;
        $event->minute = $minute;
        $event->type = $type;
        $event->team_side = $side;
        $event->player_id = $playerId;
        $event->detail = $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : null;
        $event->save(false);

        // Async LLM enrichment via queue — never blocks the simulation
        if ($enqueueLlm && $event->id && Yii::$app->has('queue')) {
            Yii::$app->queue->push(new \app\jobs\LlmEnrichJob([
                'eventId' => $event->id,
                'fixtureId' => $fixture->id,
                'type' => $type,
                'side' => $side,
                'minute' => $minute,
                'homeScore' => $detail['home_score'] ?? ($detail['home'] ?? 0),
                'awayScore' => $detail['away_score'] ?? ($detail['away'] ?? 0),
                'fallback' => $desc,
            ]));
        }

        return $event;
    }

    private function fallbackDescription(
        string $type,
        Fixture $fixture,
        array $detail,
        int $minute = 0,
        string $side = 'home',
        ?string $playerName = null
    ): string
    {
        $home = $fixture->homeTeam->name ?? 'Casa';
        $away = $fixture->awayTeam->name ?? 'Trasferta';
        $shooter = $detail['shooter_name'] ?? ($detail['attacker_name'] ?? null);
        $gk = $detail['gk_name'] ?? null;
        $hs = $detail['home_score'] ?? ($detail['home'] ?? 0);
        $as = $detail['away_score'] ?? ($detail['away'] ?? 0);
        $gameState = $this->deriveGameState((int)$hs, (int)$as, $side, (int)$minute);

        // SIP-0062: try template first
        $templateDesc = \app\components\CommentaryTemplateService::pick(
            $type,
            (int)($detail['minute'] ?? 0),
            [
                'home_team'       => $home,
                'away_team'       => $away,
                'player_attacker' => $shooter ?? ($detail['player_name'] ?? ''),
                'player_gk'       => $gk ?? '',
                'player_in'       => $detail['in_name'] ?? '',
                'player_out'      => $detail['out_name'] ?? '',
                'score_home'      => (string)$hs,
                'score_away'      => (string)$as,
                'minute'          => (string)($detail['minute'] ?? ''),
                'spectators'      => number_format((int)($detail['spectators'] ?? 0), 0, ',', '.'),
            ],
            $detail['team_side'] ?? '',
            (int)($fixture->id ?? 0),
            $gameState
        );
        if ($templateDesc) return $templateDesc;

        $templateTokens = [
            'home_team' => $home,
            'away_team' => $away,
            'score_home' => (string) $hs,
            'score_away' => (string) $as,
            'minute' => (string) $minute,
            'player_attacker' => (string) ($shooter ?: $playerName ?: 'un giocatore'),
            'player_defender' => (string) ($detail['defender_name'] ?? 'il difensore'),
            'player_gk' => (string) ($gk ?: 'il portiere'),
            'player_assist' => (string) ($detail['assist_name'] ?? 'un compagno'),
            'player_in' => (string) ($detail['in_name'] ?? 'il nuovo entrato'),
            'player_out' => (string) ($detail['out_name'] ?? 'il giocatore uscente'),
            'spectators' => (string) ($detail['spectators'] ?? 'numerosi'),
        ];
        $templateSubtype = '';
        if ($type === 'goal') {
            $templateSubtype = $side === 'away' ? 'away' : 'home';
        }
        $templateText = CommentaryTemplateService::pick($type, $minute, $templateTokens, $templateSubtype, (int) $fixture->id, $gameState);
        if (is_string($templateText) && trim($templateText) !== '') {
            return trim($templateText);
        }

        return match ($type) {
            'kickoff' => self::pick([
                "Benvenuti allo stadio! Si parte con {$home} contro {$away}.",
                "Tutto pronto! Fischio d'inizio tra {$home} e {$away}.",
                "Si comincia! {$home} e {$away} scendono in campo.",
            ]),

            'half_time' => self::pick([
                "Duplice fischio! Si va negli spogliatoi sul {$hs}-{$as}.",
                "Fine primo tempo: {$home} {$hs} - {$as} {$away}.",
                "L'arbitro manda le squadre negli spogliatoi. Punteggio di metà: {$hs}-{$as}.",
            ]),

            'second_half_start' => self::pick([
                "Fischio d'inizio del secondo tempo! Si riparte.",
                "Le squadre tornano in campo. Si ricomincia sul {$hs}-{$as}.",
                "Riparte il match! Secondo tempo tra {$home} e {$away}.",
            ]),

            'full_time' => self::pick([
                "Triplice fischio! Finisce {$home} {$hs} - {$as} {$away}.",
                "È finita! Il risultato finale è {$hs}-{$as}.",
                "L'arbitro decreta la fine. {$home} {$hs} - {$as} {$away}.",
            ]),

            'goal' => self::pick([
                "RETE! Punteggio aggiornato: {$hs}-{$as}!",
                "GOL! La sfera gonfia la rete! Siamo sul {$hs}-{$as}!",
                "GOOOOOL! Vantaggio! Il tabellone segna {$hs}-{$as}!",
            ]),

            'gk_save' => $gk
            ? self::pick([
                "{$gk} vola e respinge il tiro" . ($shooter ? " di {$shooter}" : '') . "! Che parata!",
                "Miracolo di {$gk}!" . ($shooter ? " {$shooter} calcia," : '') . " ma il portiere è attento!",
                "{$gk} si distende e blocca" . ($shooter ? " il tentativo di {$shooter}" : '') . ". Niente gol!",
            ])
            : self::pick([
                "Parata! Il portiere respinge la conclusione.",
                "Che intervento del portiere! Palla in corner.",
                "Riflessi fulminei del portiere, il tiro è neutralizzato.",
            ]),

            'near_miss' => $shooter
            ? self::pick([
                "{$shooter} calcia e sfiora il palo! Palla fuori di un soffio.",
                "Tiro di {$shooter} — palo! Occasione clamorosa sprecata.",
                "{$shooter} trova il tempo ma manda la palla fuori.",
                "Che brivido! {$shooter} sfiora la rete ma la palla finisce fuori.",
            ])
            : self::pick([
                "Che occasione! Il tiro sfiora il palo.",
                "Palla fuori di pochissimo! Occasione sprecata.",
                "Conclusione alta, niente gol.",
            ]),

            'midfield_duel' => $shooter
            ? self::pick([
                "Gioco bloccato a centrocampo, con {$shooter} che smista la palla.",
                "{$shooter} imposta la manovra, ma la difesa chiude bene i varchi.",
                "Il gioco staziona sulla mediana. Ottimo recupero palla di {$shooter}.",
            ])
            : self::pick([
                "Il gioco staziona a centrocampo in questa fase del match.",
                "Fase di studio sulla mediana, i ritmi si abbassano.",
                "Duelli intensi a centrocampo per il possesso del pallone.",
            ]),

            'attack_attempt' => $shooter
            ? self::pick([
                "{$shooter} accelera in avanti! Azione offensiva palla al piede.",
                "Incursione pericolosa di {$shooter} che prova a sfondare centralmente.",
                "{$shooter} cerca lo spazio per servire i compagni in area avversaria.",
            ])
            : self::pick([
                "Azione offensiva! La palla viene smistata in area.",
                "Pressing in avanti, la difesa deve stringersi.",
                "Manovra d'attacco bloccata prima del tiro.",
            ]),

            'substitution' => self::pick([
                "Cambio in campo! L'allenatore mescola le carte.",
                "Sostituzione! Nuova energia fresca entra in campo.",
                "Cambio tattico! L'allenatore interviene.",
            ]),

            'tactic_change' => self::pick([
                "Cambio di modulo! L'allenatore ridisegna la squadra.",
                "Nuova tattica in campo! Si cambia assetto.",
                "L'allenatore lancia i segnali dalla panchina.",
            ]),

            'yellow_card' => $shooter
            ? self::pick([
                "Cartellino giallo per {$shooter}! L'arbitro lo ammonisce.",
                "{$shooter} protesta, ma l'arbitro è irremovibile. Giallo!",
                "Fallo tattico di {$shooter} — ammonito! Deve stare attento.",
            ])
            : self::pick([
                "Cartellino giallo! L'arbitro estrae il cartellino.",
                "Ammonizione! Il giocatore dovrà evitare altri falli.",
            ]),

            'red_card' => $shooter
            ? self::pick([
                "ROSSO! {$shooter} lascia il campo — la squadra rimane in dieci!",
                "Cartellino rosso per {$shooter}! Espulsione diretta.",
                "L'arbitro non ha dubbi: rosso per {$shooter}. Partita in salita!",
            ])
            : self::pick([
                "ROSSO DIRETTO! Espulso! La squadra giocherà in dieci.",
                "Cartellino rosso! L'arbitro non perdona. In dieci!",
            ]),

            'injury' => $shooter
            ? self::pick([
                "{$shooter} si accascia al suolo e chiede il cambio. Infortunio!",
                "Brutto stop per {$shooter}: costretto ad abbandonare il campo.",
                "{$shooter} non riesce a continuare. Entrano i sanitari.",
            ])
            : self::pick([
                "Infortunio in campo! Il giocatore è costretto al cambio.",
                "Intervento dei sanitari — il giocatore non riesce a proseguire.",
            ]),

            default => '',
        };
    }

    private function buildSuspenseText(string $type, array $detail, Fixture $fixture): string
    {
        $shooter = $detail['shooter_name'] ?? ($detail['attacker_name'] ?? null);
        $gk      = $detail['gk_name'] ?? null;
        $sides   = ['L' => 'sinistra', 'C' => 'centro', 'R' => 'destra'];
        $side    = $sides[$detail['side'] ?? 'C'] ?? 'centro';

        if ($type === 'goal') {
            return $shooter ? self::pick([
                "{$shooter} si trova davanti al portiere, il momento è decisivo...",
                "{$shooter} stoppa e calcia dalla {$side}...",
                "Azione travolgente, {$shooter} entra in area...",
                "{$shooter} prende palla, si coordina e conclude...",
            ]) : self::pick([
                "L'attaccante entra in area dalla {$side}...",
                "Conclusione dalla distanza, palla in traiettoria...",
                "Azione pericolosa verso la porta avversaria...",
            ]);
        }

        if ($type === 'gk_save') {
            return $shooter && $gk ? self::pick([
                "{$shooter} calcia deciso, {$gk} si allunga...",
                "Tiro di {$shooter} dalla {$side}, {$gk} in posizione...",
                "{$shooter} conclude, {$gk} si distende...",
            ]) : self::pick([
                "Tiro dalla {$side}, il portiere si tuffa...",
                "Conclusione potente, intervento del portiere...",
            ]);
        }

        // near_miss
        return $shooter ? self::pick([
            "{$shooter} riceve e calcia di prima intenzione dalla {$side}...",
            "{$shooter} si libera del marcatore e tenta la conclusione...",
            "Gran lancio, {$shooter} si avventa sulla palla...",
        ]) : self::pick([
            "Tiro improvviso dalla {$side}...",
            "Conclusione da dentro l'area...",
        ]);
    }

    private static function pick(array $options): string
    {
        return $options[array_rand($options)];
    }

    private function deriveGameState(int $homeScore, int $awayScore, string $side, int $minute): string
    {
        $diff = $homeScore - $awayScore;
        if ($side === 'away') {
            $diff = $awayScore - $homeScore;
        }

        if ($minute >= 80) {
            if ($diff < 0) {
                return 'late_push';
            }
            if ($diff > 0) {
                return 'time_wasting';
            }
            return 'late_push';
        }

        if ($diff > 0) {
            return 'winning';
        }
        if ($diff < 0) {
            if ($minute >= 60) {
                return 'high_press';
            }
            return 'losing';
        }
        if ($minute >= 60) {
            return 'balanced';
        }
        return 'drawing';
    }

    // ── SIP-0035: Staff bonus on department totals ────────────────────────

    protected function applyStaffBonus(array $totals, int $teamId): array
    {
        $rows = Yii::$app->db->createCommand(
            'SELECT role, efficiency, specialisation FROM {{%staff}} WHERE team_id=:t',
            [':t' => $teamId]
        )->queryAll();

        $coachBonus = 0.0;
        $assistantBonus = 0.0;
        $gkBonus = 0.0;
        $spec = ['def' => 0.33, 'mid' => 0.33, 'att' => 0.33];

        foreach ($rows as $row) {
            $role = (string) ($row['role'] ?? '');
            $eff  = max(0.0, (float) ($row['efficiency'] ?? 0));
            switch ($role) {
                case Staff::ROLE_HEAD_COACH:
                    $coachBonus = max($coachBonus, 0.30 * $eff);
                    $spec = match ($row['specialisation'] ?? 'equilibrato') {
                        'difensivista' => ['def' => 0.6, 'mid' => 0.3, 'att' => 0.1],
                        'offensivista' => ['def' => 0.1, 'mid' => 0.3, 'att' => 0.6],
                        default        => ['def' => 0.33, 'mid' => 0.33, 'att' => 0.33],
                    };
                    break;
                case Staff::ROLE_ASSISTANT_COACH:
                    $assistantBonus = max($assistantBonus, $eff / 30.3);
                    break;
                case Staff::ROLE_GOALKEEPING_COACH:
                    $gkBonus = max($gkBonus, 0.035 * $eff);
                    break;
            }
        }

        if ($coachBonus > 0 || $assistantBonus > 0 || $gkBonus > 0) {
            $mult = static fn(float $bonus): float => 1 + $bonus / 100;
            $totals['po'] = (int) round($totals['po'] * $mult($assistantBonus + $gkBonus));
            $totals['df'] = (int) round($totals['df'] * $mult($assistantBonus + $coachBonus * $spec['def']));
            $totals['cn'] = (int) round($totals['cn'] * $mult($assistantBonus + $coachBonus * $spec['mid']));
            $totals['rg'] = (int) round($totals['rg'] * $mult($assistantBonus + $coachBonus * $spec['mid']));
            $totals['pa'] = (int) round($totals['pa'] * $mult($assistantBonus + $coachBonus * $spec['mid']));
            $totals['cr'] = (int) round($totals['cr'] * $mult($assistantBonus + $coachBonus * $spec['att']));
            $totals['tc'] = (int) round($totals['tc'] * $mult($assistantBonus + $coachBonus * $spec['att']));
            $totals['tr'] = (int) round($totals['tr'] * $mult($assistantBonus + $coachBonus * $spec['att']));
        }

        return $totals;
    }

    // ── SIP-0038: load team tactic levels ────────────────────────────────

    protected function loadTactics(int $teamId): array
    {
        $row = Yii::$app->db->createCommand(
            'SELECT * FROM {{%training_tactic}} WHERE team_id=:t ORDER BY season DESC LIMIT 1',
            [':t' => $teamId]
        )->queryOne();

        return $row ?: [
            'pressing' => 30, 'contropiede' => 20, 'possesso' => 40,
            'palla_bassa' => 30, 'lancio_lungo' => 20, 'catenaccio' => 20,
            'fuorigioco' => 10, 'calci_piazzati' => 30,
        ];
    }

    /**
     * @param array<string,mixed> $training
     * @return array{
     *   style:string,
     *   marking:string,
     *   offside_enabled:bool,
     *   focus_tactic:string,
     *   focus_level:int,
     *   attack_mult:float,
     *   def_mult:float,
     *   mid_mult:float,
     *   offside_mult:float
     * }
     */
    protected function buildFormationTacticProfile(?Formation $formation, array $training): array
    {
        $style = 'balanced';
        if ($formation && in_array((string) $formation->tactic, ['balanced', 'ultra_defensive', 'all_out_attack'], true)) {
            $style = (string) $formation->tactic;
        }

        $marking = 'zone';
        if ($formation && in_array((string) $formation->marking, ['zone', 'man'], true)) {
            $marking = (string) $formation->marking;
        }

        $offsideEnabled = $formation ? ((int) ($formation->offside_trap ?? 1) > 0) : true;
        $focus = $formation ? (string) ($formation->trained_tactic ?? '') : '';
        $focusLevel = (int) ($focus !== '' ? ($training[$focus] ?? 0) : 0);

        $profile = [
            'style' => $style,
            'marking' => $marking,
            'offside_enabled' => $offsideEnabled,
            'focus_tactic' => $focus,
            'focus_level' => $focusLevel,
            'attack_mult' => 1.0,
            'def_mult' => 1.0,
            'mid_mult' => 1.0,
            'offside_mult' => 1.0,
            'longball_mult' => 1.0,
        ];

        if ($style === 'all_out_attack') {
            $profile['attack_mult'] *= 1.12;
            $profile['def_mult'] *= 0.92;
            $profile['mid_mult'] *= 1.04;
            $profile['longball_mult'] *= 1.04;
        } elseif ($style === 'ultra_defensive') {
            $profile['attack_mult'] *= 0.90;
            $profile['def_mult'] *= 1.13;
            $profile['mid_mult'] *= 0.96;
            $profile['offside_mult'] *= 1.05;
            $profile['longball_mult'] *= 1.03;
        }

        if ($focusLevel > 0) {
            $focusPct = min(100, $focusLevel) / 100;
            switch ($focus) {
                case 'pressing':
                    $profile['mid_mult'] *= (1.0 + 0.08 * $focusPct);
                    break;
                case 'possesso':
                    $profile['mid_mult'] *= (1.0 + 0.06 * $focusPct);
                    break;
                case 'contropiede':
                    $profile['attack_mult'] *= (1.0 + 0.10 * $focusPct);
                    break;
                case 'palla_bassa':
                    $profile['attack_mult'] *= (1.0 + 0.05 * $focusPct);
                    break;
                case 'lancio_lungo':
                    $profile['attack_mult'] *= (1.0 + 0.06 * $focusPct);
                    $profile['longball_mult'] *= (1.0 + 0.25 * $focusPct);
                    break;
                case 'catenaccio':
                    $profile['def_mult'] *= (1.0 + 0.10 * $focusPct);
                    break;
                case 'fuorigioco':
                    $profile['offside_mult'] *= (1.0 + 0.16 * $focusPct);
                    break;
            }
        }

        return $profile;
    }

    /**
     * SIP-0038 conflict rules:
     * - pressing + catenaccio both >50 => pressing effectiveness -30%
     * - lancio_lungo + possesso both >50 => possesso effectiveness -40%
     *
     * @param array<string,mixed> $tactics
     * @return array<string,int>
     */
    protected function applyTacticConflicts(array $tactics): array
    {
        $keys = [
            'pressing', 'contropiede', 'possesso', 'palla_bassa',
            'lancio_lungo', 'catenaccio', 'fuorigioco',
        ];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = max(0, min(100, (int) ($tactics[$k] ?? 0)));
        }

        if ($out['pressing'] > 50 && $out['catenaccio'] > 50) {
            $out['pressing'] = (int) round($out['pressing'] * 0.70);
        }
        if ($out['lancio_lungo'] > 50 && $out['possesso'] > 50) {
            $out['possesso'] = (int) round($out['possesso'] * 0.60);
        }

        return $out;
    }

    protected function markingModifier(string $marking, string $side): float
    {
        if ($marking === 'man') {
            return $side === 'C' ? 1.10 : 0.96;
        }
        if ($marking === 'zone') {
            return $side === 'C' ? 1.02 : 1.06;
        }
        return 1.0;
    }

    // ── SIP-0037: character training modifier ────────────────────────────

    /**
     * SIP-0037: Training XP modifier per character trait and stat.
     * Called once per stat per player in applyWeeklyTraining().
     *
     * @param string $position  player's position (GK/DF/MF/FW) for inflessibile
     */
    public static function characterTrainingMod(string $character, string $stat, string $position = ''): float
    {
        return CharacterTraitHelper::trainingModifier($character, $stat, $position, false, 50, 0);
    }

    protected function isPrimaryZoneForPosition(string $position, int $zone): bool
    {
        if ($zone <= 0) {
            return true;
        }
        $row = PitchZoneHelper::row($zone); // normalized 1..10
        $position = strtoupper($position);
        return match ($position) {
            'GK' => PitchZoneHelper::isGoalkeeperZone($zone),
            'DF', 'DM' => $row >= 2 && $row <= 4,
            'MF', 'AM' => $row >= 5 && $row <= 7,
            'FW' => $row >= 8,
            default => true,
        };
    }

    protected function areZonesAdjacent(int $a, int $b, int $distance = 1): bool
    {
        if ($a <= 0 || $b <= 0) {
            return false;
        }
        $coordA = PitchZoneHelper::coords($a);
        $coordB = PitchZoneHelper::coords($b);
        return abs($coordA['row'] - $coordB['row']) <= $distance
            && abs($coordA['lane'] - $coordB['lane']) <= $distance;
    }

    protected function applyPendingActions(Fixture $fixture, MatchState $state, int $min): array
    {
        $events = [];
        $this->queueCpuLiveActions($fixture, $state, $min);
        foreach (['home', 'away'] as $side) {
            $field = "pending_{$side}_actions";
            $actions = json_decode($state->$field ?? '[]', true);
            if (empty($actions))
                continue;

            $remaining = [];
            foreach ($actions as $action) {
                if ($action['type'] === 'substitution') {
                    $subsField = "{$side}_subs_used";
                    $state->$subsField++;
                    $outPlayer = \app\models\Player::findOne($action['out']);
                    $inPlayer  = \app\models\Player::findOne($action['in']);
                    $events[] = $this->saveEvent($fixture, $min, 'substitution', $side, $action['in'], [
                        'out'      => $action['out'],
                        'in'       => $action['in'],
                        'out_name' => $outPlayer?->name,
                        'in_name'  => $inPlayer?->name,
                    ]);
                    // Actually swap player in formation slot
                    $this->performSubstitution($state, $side, $action['out'], $action['in']);
                } elseif ($action['type'] === 'tactic_change') {
                    $formField = "{$side}_formation_id";
                    if ($state->$formField) {
                        $formation = Formation::findOne($state->$formField);
                        if ($formation) {
                            $formation->tactic = $action['tactic'];
                            $formation->marking = $action['marking'] ?? 'zone';
                            if (array_key_exists('offside_trap', $action)) {
                                $formation->offside_trap = (int) ((int) $action['offside_trap'] > 0 ? 1 : 0);
                            }
                            if (array_key_exists('trained_tactic', $action)) {
                                $formation->trained_tactic = $action['trained_tactic'] !== '' ? (string) $action['trained_tactic'] : null;
                            }
                            $formation->save(false, ['tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);
                        }
                    }
                    $events[] = $this->saveEvent($fixture, $min, 'tactic_change', $side, null, $action);
                }
            }
            $state->$field = json_encode($remaining);
        }
        return $events;
    }

    private function queueCpuLiveActions(Fixture $fixture, MatchState $state, int $min): void
    {
        foreach (['home', 'away'] as $side) {
            $team = $side === 'home' ? $fixture->homeTeam : $fixture->awayTeam;
            if (!$team || (int) ($team->is_cpu ?? 0) !== 1) {
                continue;
            }
            $difficulty = CpuDifficultyHelper::resolveDifficulty($team, (int) ($fixture->competition_id ?? 0));
            $profile = CpuDifficultyHelper::profile($difficulty);

            $field = "pending_{$side}_actions";
            $actions = json_decode((string) ($state->$field ?? '[]'), true);
            if (!is_array($actions)) {
                $actions = [];
            }

            if ($this->cpuShouldQueueTacticChange($actions, $min, $profile)) {
                $actions[] = $this->buildCpuTacticChangeAction($state, $side, $min, $profile);
            }

            if ($this->cpuShouldQueueSubstitution($actions, $state, $side, $min, $profile)) {
                $subAction = $this->buildCpuSubstitutionAction($state, $side, $min, $profile);
                if ($subAction !== null) {
                    $actions[] = $subAction;
                }
            }

            $state->$field = json_encode($actions);
        }
    }

    /**
     * @param array<int,array<string,mixed>> $actions
     */
    private function cpuShouldQueueTacticChange(array $actions, int $min, array $profile): bool
    {
        $start = max(1, (int) ($profile['tactic_start_min'] ?? 30));
        $interval = max(1, (int) ($profile['tactic_interval'] ?? 10));
        if ($min < $start || ($min % $interval) !== 0) {
            return false;
        }
        foreach ($actions as $action) {
            if (($action['type'] ?? '') === 'tactic_change') {
                return false;
            }
        }
        return true;
    }

    private function buildCpuTacticChangeAction(MatchState $state, string $side, int $min, array $profile): array
    {
        $diff = $side === 'home'
            ? ((int) $state->home_score - (int) $state->away_score)
            : ((int) $state->away_score - (int) $state->home_score);
        $difficulty = (string) ($profile['level'] ?? 'normal');

        $tactic = 'balanced';
        $marking = in_array($difficulty, ['competitive', 'hardcore'], true) ? 'man' : 'zone';
        $offsideTrap = in_array($difficulty, ['competitive', 'hardcore'], true) ? 1 : 0;
        $trainedTactic = 'possesso';
        $losingAttackMin = (int) ($profile['losing_attack_min'] ?? 68);
        $winningDefMin = (int) ($profile['winning_def_min'] ?? 60);

        if ($diff <= -2 && $min >= max(45, $losingAttackMin - 8)) {
            $tactic = 'all_out_attack';
            $marking = 'man';
            $offsideTrap = 1;
            $trainedTactic = 'pressing';
        } elseif ($diff <= -1 && $min >= $losingAttackMin) {
            $tactic = 'all_out_attack';
            $marking = 'man';
            $offsideTrap = 1;
            $trainedTactic = 'contropiede';
        } elseif ($diff >= 2 && $min >= max(45, $winningDefMin - 6)) {
            $tactic = 'ultra_defensive';
            $marking = 'zone';
            $offsideTrap = 0;
            $trainedTactic = 'catenaccio';
        } elseif ($diff >= 1 && $min >= $winningDefMin) {
            $tactic = 'ultra_defensive';
            $marking = 'zone';
            $offsideTrap = 0;
            $trainedTactic = 'possesso';
        } elseif ($diff === 0 && $min >= max(70, $winningDefMin + 14)) {
            $tactic = 'balanced';
            $marking = 'zone';
            $offsideTrap = 1;
            $trainedTactic = 'possesso';
        }

        return [
            'type' => 'tactic_change',
            'tactic' => $tactic,
            'marking' => $marking,
            'offside_trap' => $offsideTrap,
            'trained_tactic' => $trainedTactic,
            'cpu_auto' => 1,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $actions
     */
    private function cpuShouldQueueSubstitution(array $actions, MatchState $state, string $side, int $min, array $profile): bool
    {
        $subMinutes = array_values(array_filter(array_map('intval', (array) ($profile['sub_minutes'] ?? [46, 60, 75]))));
        if (!in_array($min, $subMinutes, true)) {
            return false;
        }
        $subsField = "{$side}_subs_used";
        $maxSubs = max(1, min(5, count($subMinutes)));
        if ((int) ($state->$subsField ?? 0) >= $maxSubs) {
            return false;
        }
        foreach ($actions as $action) {
            if (($action['type'] ?? '') === 'substitution') {
                return false;
            }
        }
        return true;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function buildCpuSubstitutionAction(MatchState $state, string $side, int $min, array $profile): ?array
    {
        $formField = "{$side}_formation_id";
        $formationId = (int) ($state->$formField ?? 0);
        if ($formationId <= 0) {
            return null;
        }

        $formation = Formation::find()->where(['id' => $formationId])->with('slots.player')->one();
        if (!$formation) {
            return null;
        }

        $starters = [];
        $bench = [];
        foreach ($formation->slots as $slot) {
            $player = $slot->player;
            if (!$player) {
                continue;
            }
            if (!PitchZoneHelper::isOnPitch((int) ($slot->zone ?? 0))) {
                $bench[] = $player;
            } else {
                $starters[] = $player;
            }
        }
        if (empty($starters) || empty($bench)) {
            return null;
        }

        $freshnessThreshold = $min >= 75 ? 66 : ($min >= 60 ? 58 : 52);
        $freshnessThreshold += (int) ($profile['freshness_threshold_adj'] ?? 0);
        $freshnessThreshold = max(40, min(85, $freshnessThreshold));
        usort($starters, static function (Player $a, Player $b): int {
            $fa = (int) ($a->freshness ?? 0);
            $fb = (int) ($b->freshness ?? 0);
            if ($fa !== $fb) {
                return $fa <=> $fb;
            }
            return ((int) ($a->form ?? 0)) <=> ((int) ($b->form ?? 0));
        });

        $outPlayer = null;
        foreach ($starters as $candidate) {
            if ((int) ($candidate->injury_weeks ?? 0) > 0) {
                continue;
            }
            if ((int) ($candidate->freshness ?? 0) <= $freshnessThreshold) {
                $outPlayer = $candidate;
                break;
            }
        }
        if ($outPlayer === null) {
            return null;
        }

        $samePosBench = array_values(array_filter($bench, static function (Player $p) use ($outPlayer): bool {
            return (string) ($p->position ?? '') === (string) ($outPlayer->position ?? '');
        }));
        $pool = !empty($samePosBench) ? $samePosBench : $bench;
        usort($pool, static function (Player $a, Player $b): int {
            $ga = (int) ($a->general_skill ?? 0);
            $gb = (int) ($b->general_skill ?? 0);
            if ($ga !== $gb) {
                return $gb <=> $ga;
            }
            return ((int) ($b->freshness ?? 0)) <=> ((int) ($a->freshness ?? 0));
        });

        $inPlayer = null;
        foreach ($pool as $candidate) {
            if ((int) ($candidate->injury_weeks ?? 0) > 0) {
                continue;
            }
            if ((int) ($candidate->condition ?? 0) < 30) {
                continue;
            }
            $inPlayer = $candidate;
            break;
        }
        if ($inPlayer === null) {
            return null;
        }

        return [
            'type' => 'substitution',
            'out' => (int) $outPlayer->id,
            'in' => (int) $inPlayer->id,
            'cpu_auto' => 1,
        ];
    }

    protected function performSubstitution(MatchState $state, string $side, int $outId, int $inId): void
    {
        $formField = "{$side}_formation_id";
        if (!$state->$formField)
            return;

        // Find the slot with the outgoing player and swap
        $slot = \app\models\FormationSlot::findOne([
            'formation_id' => $state->$formField,
            'player_id' => $outId,
        ]);
        if ($slot) {
            $slot->player_id = $inId;
            $slot->save();
        }
    }

    protected function finalizeMatch(Fixture $fixture, MatchState $state): void
    {
        $fixture->home_score = $state->home_score;
        $fixture->away_score = $state->away_score;
        $fixture->status = Fixture::STATUS_FINISHED;
        $fixture->save();

        // Persist player stats (goals) for competitive matches only
        if ($fixture->competition && $fixture->competition->type !== 'friendly') {
            $this->savePlayerStats($fixture, $state);
            $this->serveSuspensions($fixture);
        }

        // SIP-0068: accumulate quadrant + cell experience post-match (all match types)
        try {
            (new \app\components\PlayerExperienceService())->applyFixtureExperience($fixture->id);
        } catch (\Throwable $e) {
            Yii::warning('SIP-0068 exp accumulation failed: ' . $e->getMessage(), __METHOD__);
        }

        // SIP-0059: clear man-markings when match ends
        \app\models\ManMarking::clearForFixture($fixture->id);

        // Friendly attendance effects (ticket price → spectators → revenue/fatigue)
        if ($fixture->competition?->type === 'friendly') {
            $this->applyFriendlyStarterProgression($fixture, $state);
            $this->applyFriendlyAttendanceEffects($fixture);
        }

        // SIP-0050: news for human managers involved
        $this->createMatchResultNews($fixture, $state);

        // Friendly matches do not affect standings
        if ($fixture->competition?->type === 'friendly') {
            return;
        }

        // Update standings
        $this->updateStanding(
            $fixture->competition_id,
            $fixture->home_team_id,
            $state->home_score,
            $state->away_score,
            true,
            $this->countPenaltyGoalsForSide($fixture->id, 'home'),
            $this->countPenaltyGoalsForSide($fixture->id, 'away')
        );
        $this->updateStanding(
            $fixture->competition_id,
            $fixture->away_team_id,
            $state->away_score,
            $state->home_score,
            false,
            $this->countPenaltyGoalsForSide($fixture->id, 'away'),
            $this->countPenaltyGoalsForSide($fixture->id, 'home')
        );
    }

    private function applyFriendlyAttendanceEffects(Fixture $fixture): void
    {
        $stadium = \app\models\Stadium::findOne(['team_id' => $fixture->home_team_id]);
        if (!$stadium || $stadium->capacity <= 0) return;

        $ticketPrice  = (int)($fixture->friendly_ticket_price ?? $stadium->ticket_price);
        $normalPrice  = max(1, (int)$stadium->ticket_price);
        $capacity     = (int)$stadium->capacity;

        // Spectators: inverse of price (free = 85% capacity, normal = ~42%, high = few)
        $ratio      = $normalPrice / ($normalPrice + max(0, $ticketPrice));
        $spectators = (int)($capacity * $ratio * 0.85);
        $spectators = max(0, min($capacity, $spectators));

        $fixture->friendly_spectators = $spectators;
        $fixture->save(false);

        $attendancePct = $capacity > 0 ? $spectators / $capacity : 0;
        $homeTeam      = $fixture->homeTeam;

        if ($attendancePct >= 0.60) {
            // High attendance: +1 form for all home players, revenue flows normally
            \app\models\Player::updateAll(
                ['form' => new \yii\db\Expression('LEAST(99, form + 1)')],
                ['team_id' => $fixture->home_team_id]
            );
            if ($homeTeam?->user_id) {
                \app\components\NewsService::create(
                    (int)$homeTeam->user_id,
                    \app\models\NewsItem::CAT_MATCH,
                    '📣',
                    sprintf('Amichevole: %d spettatori allo stadio!', $spectators),
                    'Grande atmosfera — +1 forma per tutta la rosa'
                );
                \app\components\TelegramService::sendToUser((int)$homeTeam->user_id,
                    "📣 <b>Grande atmosfera!</b>\nAmichevole con {$spectators} spettatori — +1 forma.");
            }
        } elseif ($attendancePct < 0.20) {
            // Low attendance: freshness -3, no revenue
            \app\models\Player::updateAll(
                ['freshness' => new \yii\db\Expression('GREATEST(0, freshness - 3)')],
                ['team_id' => $fixture->home_team_id]
            );
            // Mark as no revenue
            $fixture->revenue_credited = 1; // skip in weekly pay
            $fixture->save(false);
            if ($homeTeam?->user_id) {
                \app\components\NewsService::create(
                    (int)$homeTeam->user_id,
                    \app\models\NewsItem::CAT_MATCH,
                    '😶',
                    sprintf('Amichevole quasi deserta: %d spettatori', $spectators),
                    'Nessun incasso — i giocatori hanno perso 3 punti freschezza',
                    '',
                    1
                );
            }
        }
    }

    private function applyFriendlyStarterFatigue(MatchState $state): void
    {
        foreach (['home_formation_id', 'away_formation_id'] as $field) {
            $formationId = (int) ($state->{$field} ?? 0);
            if ($formationId <= 0) {
                continue;
            }

            $playerIds = \app\models\FormationSlot::find()
                ->select('player_id')
                ->where(['formation_id' => $formationId])
                ->andWhere(['not', ['player_id' => null]])
                ->column();
            if (empty($playerIds)) {
                continue;
            }

            \app\models\Player::updateAll(
                ['freshness' => new \yii\db\Expression('GREATEST(0, freshness - 3)')],
                ['id' => $playerIds]
            );
        }
    }

    private function applyFriendlyStarterProgression(Fixture $fixture, MatchState $state): void
    {
        $homeInitial = $this->extractInitialLineupForSide($fixture, $state, 'home');
        $awayInitial = $this->extractInitialLineupForSide($fixture, $state, 'away');

        $homeDelta = $this->friendlyStatDelta(
            $state->home_score <=> $state->away_score
        );
        $awayDelta = $this->friendlyStatDelta(
            $state->away_score <=> $state->home_score
        );

        $this->applyFriendlyDeltaToPlayers($homeInitial, $homeDelta);
        $this->applyFriendlyDeltaToPlayers($awayInitial, $awayDelta);
    }

    /**
     * @return array{form:int,freshness:int,experience:int}
     */
    private function friendlyStatDelta(int $comparison): array
    {
        // comparison: 1 win, 0 draw, -1 loss
        return match ($comparison) {
            1 => ['form' => 3, 'freshness' => -5, 'experience' => 2],
            0 => ['form' => 1, 'freshness' => -3, 'experience' => 1],
            default => ['form' => -2, 'freshness' => -4, 'experience' => 1],
        };
    }

    /**
     * @param int[] $playerIds
     * @param array{form:int,freshness:int,experience:int} $delta
     */
    private function applyFriendlyDeltaToPlayers(array $playerIds, array $delta): void
    {
        if (empty($playerIds)) {
            return;
        }

        $players = Player::find()->where(['id' => $playerIds])->all();
        foreach ($players as $player) {
            $player->form = (int) max(1, min(100, ((int) $player->form) + (int) $delta['form']));
            $player->freshness = (int) max(1, min(100, ((int) $player->freshness) + (int) $delta['freshness']));
            $player->experience = (int) max(1, min(100, ((int) $player->experience) + (int) $delta['experience']));
            $player->save(false, ['form', 'freshness', 'experience', 'updated_at']);
        }
    }

    /**
     * @return int[]
     */
    private function extractInitialLineupForSide(Fixture $fixture, MatchState $state, string $side): array
    {
        $formationId = $side === 'home' ? (int) $state->home_formation_id : (int) $state->away_formation_id;
        $teamId = $side === 'home' ? (int) $fixture->home_team_id : (int) $fixture->away_team_id;
        $initialLineup = $this->extractLineupPlayerIds($formationId, $teamId);

        $subEvents = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'substitution', 'team_side' => $side])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        for ($i = count($subEvents) - 1; $i >= 0; $i--) {
            $detail = $subEvents[$i]->detail ? (json_decode($subEvents[$i]->detail, true) ?? []) : [];
            $outId = (int) ($detail['out'] ?? 0);
            $inId = (int) ($detail['in'] ?? 0);
            if ($inId > 0) {
                $initialLineup = array_values(array_filter(
                    $initialLineup,
                    static fn($id) => (int) $id !== $inId
                ));
            }
            if ($outId > 0 && !in_array($outId, $initialLineup, true)) {
                $initialLineup[] = $outId;
            }
        }

        $initialLineup = array_values(array_unique(array_map('intval', $initialLineup)));
        if (count($initialLineup) > 11) {
            $initialLineup = array_slice($initialLineup, 0, 11);
        }
        return $initialLineup;
    }

    private function createDisciplineNews(Fixture $fixture, string $side, \app\models\Player $player, string $reason, int $matches): void
    {
        $team = $side === 'home' ? $fixture->homeTeam : $fixture->awayTeam;
        if (!$team?->user_id || $matches <= 0) return;
        \app\components\NewsService::create(
            (int)$team->user_id,
            \app\models\NewsItem::CAT_DISCIPLINE,
            '🟥',
            "{$player->name} squalificato ({$reason})",
            "Salta {$matches} gara/e",
            '',
            1
        );
        \app\components\TelegramService::sendToUser(
            (int)$team->user_id,
            "🟥 <b>{$player->name}</b> squalificato ({$reason}) — salta {$matches} gara/e"
        );
    }

    protected function createMatchResultNews(Fixture $fixture, MatchState $state): void
    {
        foreach ([
            ['team_id' => $fixture->home_team_id, 'myScore' => $state->home_score, 'oppScore' => $state->away_score, 'opp' => $fixture->awayTeam?->name],
            ['team_id' => $fixture->away_team_id, 'myScore' => $state->away_score, 'oppScore' => $state->home_score, 'opp' => $fixture->homeTeam?->name],
        ] as $side) {
            $team = \app\models\Team::findOne($side['team_id']);
            if (!$team || !$team->user_id) continue;

            [$icon, $result] = match(true) {
                $side['myScore'] > $side['oppScore'] => ['⚽', 'Vinto'],
                $side['myScore'] === $side['oppScore'] => ['🤝', 'Pareggiato'],
                default => ['❌', 'Sconfitto'],
            };
            $type = $fixture->competition?->type === 'friendly' ? 'Amichevole' : 'Campionato';
            \app\components\NewsService::create(
                (int)$team->user_id,
                \app\models\NewsItem::CAT_MATCH,
                $icon,
                "{$result} {$side['myScore']}–{$side['oppScore']} contro {$side['opp']}",
                $type,
                \Yii::$app->urlManager->createUrl(['/fixture/view', 'id' => $fixture->id]),
                $side['myScore'] > $side['oppScore'] ? 1 : 0
            );
            \app\components\TelegramService::sendToUser(
                (int)$team->user_id,
                "{$icon} <b>Finale:</b> {$side['myScore']}–{$side['oppScore']} vs {$side['opp']}\n{$type}"
            );
        }
    }

    protected function serveSuspensions(Fixture $fixture): void
    {
        // Decrement suspended_matches for all players in both squads who served today.
        // Players with suspended_matches > 0 were blocked from the formation;
        // after this match completes they have served one game.
        foreach ([$fixture->home_team_id, $fixture->away_team_id] as $teamId) {
            \app\models\Player::updateAll(
                ['suspended_matches' => new \yii\db\Expression('suspended_matches - 1')],
                ['team_id' => $teamId, '>', 'suspended_matches', 0]
            );
        }
    }

    protected function savePlayerStats(Fixture $fixture, MatchState $state): void
    {
        $goals = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'goal'])
            ->all();

        $stats = []; // [player_id => stat row]

        foreach ($goals as $event) {
            $d = $event->detail ? json_decode($event->detail, true) : [];
            $teamId = $event->team_side === 'home' ? $fixture->home_team_id : $fixture->away_team_id;

            // Scorer
            $scorerId = $event->player_id;
            if ($scorerId) {
                if (!isset($stats[$scorerId])) {
                    $stats[$scorerId] = $this->blankPlayerStatRow($teamId);
                }
                $stats[$scorerId]['goals']++;
                if ($this->isPenaltyGoal($d)) {
                    $stats[$scorerId]['penalties_scored']++;
                }
            }

            // Assist (stored as assister_id in detail if available)
            $assistId = $d['assister_id'] ?? null;
            if ($assistId && $assistId !== $scorerId) {
                if (!isset($stats[$assistId])) {
                    $stats[$assistId] = $this->blankPlayerStatRow($teamId);
                }
                $stats[$assistId]['assists']++;
            }
        }

        // Cards per fixture
        $cardEvents = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id])
            ->andWhere(['type' => ['yellow_card', 'red_card']])
            ->all();

        foreach ($cardEvents as $event) {
            if (!$event->player_id) {
                continue;
            }
            $teamId = $event->team_side === 'home' ? $fixture->home_team_id : $fixture->away_team_id;
            if (!isset($stats[$event->player_id])) {
                $stats[$event->player_id] = $this->blankPlayerStatRow($teamId);
            }
            if ($event->type === 'yellow_card') {
                $stats[$event->player_id]['yellow_cards']++;
            } elseif ($event->type === 'red_card') {
                $stats[$event->player_id]['red_cards']++;
            }
        }

        // Goalkeeper saves
        $saveEvents = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'gk_save'])
            ->all();
        foreach ($saveEvents as $event) {
            if (!$event->player_id) {
                continue;
            }
            $gk = Player::findOne((int) $event->player_id);
            if (!$gk) {
                continue;
            }
            if (!isset($stats[$gk->id])) {
                $stats[$gk->id] = $this->blankPlayerStatRow((int) $gk->team_id);
            }
            $stats[$gk->id]['saves']++;
        }

        // Minutes played from lineup + substitutions + red cards
        $homeMinutes = $this->computeMinutesPlayedForSide($fixture, $state, 'home');
        $awayMinutes = $this->computeMinutesPlayedForSide($fixture, $state, 'away');
        $minutes = $homeMinutes + $awayMinutes;
        foreach ($minutes as $playerId => $m) {
            if ($m <= 0) {
                continue;
            }
            $player = Player::findOne((int) $playerId);
            if (!$player) {
                continue;
            }
            if (!isset($stats[$player->id])) {
                $stats[$player->id] = $this->blankPlayerStatRow((int) $player->team_id);
            }
            $stats[$player->id]['minutes_played'] = (int) $m;
        }

        // Penalty misses (future-proof: if event type appears)
        $penaltyMisses = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'penalty_miss'])
            ->all();
        foreach ($penaltyMisses as $event) {
            if (!$event->player_id) {
                continue;
            }
            $teamId = $event->team_side === 'home' ? $fixture->home_team_id : $fixture->away_team_id;
            if (!isset($stats[$event->player_id])) {
                $stats[$event->player_id] = $this->blankPlayerStatRow($teamId);
            }
            $stats[$event->player_id]['penalties_missed']++;
        }

        // Clean sheet for GKs who played minutes
        foreach ($stats as $playerId => &$row) {
            $player = Player::findOne((int) $playerId);
            if (!$player || $player->position !== 'GK' || (int) $row['minutes_played'] <= 0) {
                continue;
            }
            $isHome = (int) $player->team_id === (int) $fixture->home_team_id;
            $conceded = $isHome ? (int) $state->away_score : (int) $state->home_score;
            $row['clean_sheet'] = $conceded === 0 ? 1 : 0;
        }
        unset($row);

        foreach ($stats as $playerId => $s) {
            Yii::$app->db->createCommand()->upsert('{{%player_stat}}', [
                'fixture_id'  => $fixture->id,
                'player_id'   => $playerId,
                'team_id'     => $s['team_id'],
                'goals'       => $s['goals'],
                'assists'     => $s['assists'],
                'yellow_cards'=> $s['yellow_cards'],
                'red_cards'   => $s['red_cards'],
                'minutes_played' => $s['minutes_played'],
                'penalties_scored' => $s['penalties_scored'],
                'penalties_missed' => $s['penalties_missed'],
                'saves' => $s['saves'],
                'clean_sheet' => $s['clean_sheet'],
                'created_at'  => time(),
            ], [
                'goals'   => $s['goals'],
                'assists' => $s['assists'],
                'yellow_cards' => $s['yellow_cards'],
                'red_cards' => $s['red_cards'],
                'minutes_played' => $s['minutes_played'],
                'penalties_scored' => $s['penalties_scored'],
                'penalties_missed' => $s['penalties_missed'],
                'saves' => $s['saves'],
                'clean_sheet' => $s['clean_sheet'],
            ])->execute();
        }
    }

    private function blankPlayerStatRow(int $teamId): array
    {
        return [
            'team_id' => $teamId,
            'goals' => 0,
            'assists' => 0,
            'yellow_cards' => 0,
            'red_cards' => 0,
            'minutes_played' => 0,
            'penalties_scored' => 0,
            'penalties_missed' => 0,
            'saves' => 0,
            'clean_sheet' => 0,
        ];
    }

    private function isPenaltyGoal(array $detail): bool
    {
        $origin = strtolower((string) ($detail['origin'] ?? ''));
        $reason = strtolower((string) ($detail['reason'] ?? ''));
        return $origin === 'penalty' || $reason === 'penalty' || ($detail['is_penalty'] ?? false) === true;
    }

    private function countPenaltyGoalsForSide(int $fixtureId, string $side): int
    {
        $goals = MatchEvent::find()
            ->where(['fixture_id' => $fixtureId, 'type' => 'goal', 'team_side' => $side])
            ->all();
        $count = 0;
        foreach ($goals as $goal) {
            $d = $goal->detail ? (json_decode($goal->detail, true) ?? []) : [];
            if ($this->isPenaltyGoal($d)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * @return array<int,int> [player_id => minutes]
     */
    private function computeMinutesPlayedForSide(Fixture $fixture, MatchState $state, string $side): array
    {
        $formationId = $side === 'home' ? (int) $state->home_formation_id : (int) $state->away_formation_id;
        $teamId = $side === 'home' ? (int) $fixture->home_team_id : (int) $fixture->away_team_id;
        $finalLineup = $this->extractLineupPlayerIds($formationId, $teamId);

        $subEvents = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'substitution', 'team_side' => $side])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        // Reconstruct initial lineup by reversing substitutions
        $initialLineup = $finalLineup;
        for ($i = count($subEvents) - 1; $i >= 0; $i--) {
            $detail = $subEvents[$i]->detail ? (json_decode($subEvents[$i]->detail, true) ?? []) : [];
            $outId = (int) ($detail['out'] ?? 0);
            $inId = (int) ($detail['in'] ?? 0);
            if ($inId > 0) {
                $initialLineup = array_values(array_filter($initialLineup, static fn($id) => (int) $id !== $inId));
            }
            if ($outId > 0 && !in_array($outId, $initialLineup, true)) {
                $initialLineup[] = $outId;
            }
        }

        $activeSince = [];
        $minutes = [];
        foreach ($initialLineup as $pid) {
            $activeSince[(int) $pid] = 0;
            $minutes[(int) $pid] = 0;
        }

        $actions = [];
        foreach ($subEvents as $sub) {
            $detail = $sub->detail ? (json_decode($sub->detail, true) ?? []) : [];
            $actions[] = [
                'kind' => 'sub',
                'minute' => max(0, min(90, (int) $sub->minute)),
                'out' => (int) ($detail['out'] ?? 0),
                'in' => (int) ($detail['in'] ?? 0),
            ];
        }

        $redEvents = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'red_card', 'team_side' => $side])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
        foreach ($redEvents as $red) {
            if (!$red->player_id) {
                continue;
            }
            $actions[] = [
                'kind' => 'red',
                'minute' => max(0, min(90, (int) $red->minute)),
                'player_id' => (int) $red->player_id,
            ];
        }

        usort($actions, static function (array $a, array $b): int {
            if ($a['minute'] === $b['minute']) {
                return $a['kind'] <=> $b['kind'];
            }
            return $a['minute'] <=> $b['minute'];
        });

        foreach ($actions as $action) {
            $m = (int) $action['minute'];
            if ($action['kind'] === 'sub') {
                $outId = (int) $action['out'];
                $inId = (int) $action['in'];
                if ($outId > 0 && isset($activeSince[$outId])) {
                    $minutes[$outId] = ($minutes[$outId] ?? 0) + max(0, $m - (int) $activeSince[$outId]);
                    unset($activeSince[$outId]);
                }
                if ($inId > 0 && !isset($activeSince[$inId])) {
                    $activeSince[$inId] = $m;
                    $minutes[$inId] = $minutes[$inId] ?? 0;
                }
                continue;
            }

            $pid = (int) ($action['player_id'] ?? 0);
            if ($pid > 0 && isset($activeSince[$pid])) {
                $minutes[$pid] = ($minutes[$pid] ?? 0) + max(0, $m - (int) $activeSince[$pid]);
                unset($activeSince[$pid]);
            }
        }

        foreach ($activeSince as $pid => $startMinute) {
            $minutes[(int) $pid] = ($minutes[(int) $pid] ?? 0) + max(0, 90 - (int) $startMinute);
        }

        return $minutes;
    }

    /**
     * @return int[]
     */
    private function extractLineupPlayerIds(int $formationId, int $teamId): array
    {
        if ($formationId > 0) {
            $ids = (new \yii\db\Query())
                ->select('player_id')
                ->from('{{%formation_slot}}')
                ->where(['formation_id' => $formationId])
                ->andWhere(new \yii\db\Expression(PitchZoneHelper::onPitchSql('zone')))
                ->andWhere(['not', ['player_id' => null]])
                ->column();
            $ids = array_values(array_unique(array_map('intval', $ids)));
            if (!empty($ids)) {
                return $ids;
            }
        }

        return Player::find()
            ->select('id')
            ->where(['team_id' => $teamId])
            ->orderBy(['general_skill' => SORT_DESC, 'id' => SORT_ASC])
            ->limit(11)
            ->column();
    }

    protected function updateStanding(
        int $competitionId,
        int $teamId,
        int $goalsFor,
        int $goalsAgainst,
        bool $isHome,
        int $penaltiesFor = 0,
        int $penaltiesAgainst = 0
    ): void
    {
        $standing = Standing::findOne(['competition_id' => $competitionId, 'team_id' => $teamId]);
        if (!$standing) {
            $standing = new Standing();
            $standing->competition_id = $competitionId;
            $standing->team_id = $teamId;
        }

        $standing->played++;
        $standing->goals_for += $goalsFor;
        $standing->goals_against += $goalsAgainst;
        $standing->penalties_for += $penaltiesFor;
        $standing->penalties_against += $penaltiesAgainst;
        if ($goalsAgainst === 0) {
            $standing->clean_sheets++;
        }

        if ($goalsFor > $goalsAgainst) {
            $standing->won++;
            $standing->points += 3;
            $margin = $goalsFor - $goalsAgainst;
            if ($isHome) {
                $standing->biggest_win_home = max((int) $standing->biggest_win_home, $margin);
            } else {
                $standing->biggest_win_away = max((int) $standing->biggest_win_away, $margin);
            }
        } elseif ($goalsFor === $goalsAgainst) {
            $standing->drawn++;
            $standing->points += 1;
        } else {
            $standing->lost++;
        }

        $standing->save();
    }
}
