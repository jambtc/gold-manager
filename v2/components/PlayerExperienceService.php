<?php

declare(strict_types=1);

namespace app\components;

use Yii;
use app\models\Fixture;
use app\models\FormationSlot;
use yii\db\Expression;

/**
 * SIP-0068 — Player Quadrant Experience & Cell Heatmap service.
 *
 * Handles:
 *   - Post-match experience accumulation (quadrant + cell)
 *   - Daily decay (quadrant + cell)
 *   - Bonus computation (quadrant / cell / combined)
 */
final class PlayerExperienceService
{
    // Decay rates (daily)
    private const DECAY_QUAD = 0.995;
    private const DECAY_CELL = 0.990;

    // Threshold: quadrant/cell not used in last N matches → apply decay
    private const THRESH_QUAD = 3;
    private const THRESH_CELL = 2;

    // Bonus caps & scale
    private const CAP_QUAD  = 10.0;
    private const SCALE_QUAD = 140.0;
    private const CAP_CELL  = 3.0;
    private const SCALE_CELL = 90.0;

    // -------------------------------------------------------------------------
    // Post-match accumulation
    // -------------------------------------------------------------------------

    /**
     * Apply experience for all players in a finished fixture.
     * Call this after player_stat rows have been persisted.
     */
    public function applyFixtureExperience(int $fixtureId): void
    {
        $fixture = Fixture::findOne($fixtureId);
        if (!$fixture) {
            return;
        }
        $isFriendly = $fixture->competition?->type === 'friendly';

        // Load minutes_played for all players in this fixture
        $statRows = Yii::$app->db->createCommand(
            'SELECT player_id, minutes_played FROM {{%player_stat}} WHERE fixture_id = :fid',
            [':fid' => $fixtureId]
        )->queryAll();

        if (empty($statRows)) {
            return;
        }

        // Load starting zones from formation slots for both teams
        // We need ONE zone per player: the zone they started in.
        // FormationSlot is the pre-match formation — query both home/away formations
        // that were active when the match was played.
        $playerZones = $this->loadStartingZones($fixture);

        foreach ($statRows as $row) {
            $playerId = (int) $row['player_id'];
            $minutes  = (int) $row['minutes_played'];
            if ($minutes <= 0) {
                continue;
            }

            // Credits calculation
            if ($isFriendly) {
                $officialCredits = 0;
                $friendlyCredits = $minutes >= 45 ? 1 : 0;
            } else {
                $officialCredits = $minutes >= 60 ? 2 : 1;
                $friendlyCredits = 0;
            }

            if ($officialCredits === 0 && $friendlyCredits === 0) {
                continue;
            }

            $zone = $playerZones[$playerId] ?? null;
            if ($zone === null) {
                // Fallback: skip if we don't know where they played
                continue;
            }

            // Convert to display zone (legacy 1-64) for cell exp
            $displayZone = PitchZoneHelper::toLegacyDisplayZone($zone);
            // Quadrant from the engine zone
            $quadrant = QuadrantHelper::zoneToQuadrant($zone);

            // Upsert quadrant exp
            Yii::$app->db->createCommand()->upsert(
                '{{%player_quadrant_exp}}',
                [
                    'player_id'        => $playerId,
                    'quadrant'         => $quadrant,
                    'official_credits' => $officialCredits,
                    'friendly_credits' => $friendlyCredits,
                ],
                [
                    'official_credits' => new Expression('official_credits + ' . $officialCredits),
                    'friendly_credits' => new Expression('friendly_credits + ' . $friendlyCredits),
                ]
            )->execute();

            // Upsert cell exp
            Yii::$app->db->createCommand()->upsert(
                '{{%player_cell_exp}}',
                [
                    'player_id'        => $playerId,
                    'zone'             => $displayZone,
                    'official_credits' => $officialCredits,
                    'friendly_credits' => $friendlyCredits,
                ],
                [
                    'official_credits' => new Expression('official_credits + ' . $officialCredits),
                    'friendly_credits' => new Expression('friendly_credits + ' . $friendlyCredits),
                ]
            )->execute();
        }
    }

    // -------------------------------------------------------------------------
    // Daily decay
    // -------------------------------------------------------------------------

    /**
     * Apply daily decay to quadrant and cell exp for all players.
     * Should be called from EconomyController::actionApplyDailyTraining (or equivalent).
     */
    public function applyDailyDecay(): void
    {
        $this->decayQuadrant();
        $this->decayCell();
    }

    private function decayQuadrant(): void
    {
        // For each player+quadrant: find how many recent matches used that quadrant.
        // If < THRESH_QUAD usages in last THRESH_QUAD matches → decay.
        // Performance: bulk update using subquery.

        // Get all players with any quadrant exp
        $playerIds = Yii::$app->db->createCommand(
            'SELECT DISTINCT player_id FROM {{%player_quadrant_exp}} WHERE official_credits > 0 OR friendly_credits > 0'
        )->queryColumn();

        if (empty($playerIds)) {
            return;
        }

        foreach ($playerIds as $playerId) {
            $playerId = (int) $playerId;
            // Last N fixture zones for this player
            $recentZones = $this->getRecentZones($playerId, self::THRESH_QUAD);
            $recentQuadrants = array_unique(array_map(
                static fn(int $z): string => QuadrantHelper::zoneToQuadrant($z),
                $recentZones
            ));

            // Decay all quadrants NOT in recent usage
            Yii::$app->db->createCommand(
                'UPDATE {{%player_quadrant_exp}}
                 SET official_credits = GREATEST(0, FLOOR(official_credits * :rate)),
                     friendly_credits = GREATEST(0, FLOOR(friendly_credits * :rate))
                 WHERE player_id = :pid'
                . (empty($recentQuadrants)
                    ? ''
                    : ' AND quadrant NOT IN (' . implode(',', array_fill(0, count($recentQuadrants), '?')) . ')'),
                array_merge([':rate' => self::DECAY_QUAD, ':pid' => $playerId], $recentQuadrants)
            )->execute();
        }
    }

    private function decayCell(): void
    {
        $playerIds = Yii::$app->db->createCommand(
            'SELECT DISTINCT player_id FROM {{%player_cell_exp}} WHERE official_credits > 0 OR friendly_credits > 0'
        )->queryColumn();

        if (empty($playerIds)) {
            return;
        }

        foreach ($playerIds as $playerId) {
            $playerId = (int) $playerId;
            $recentZones = $this->getRecentZones($playerId, self::THRESH_CELL);
            // Convert engine zones to display zones for comparison
            $recentDisplayZones = array_unique(array_map(
                static fn(int $z): int => PitchZoneHelper::toLegacyDisplayZone($z),
                $recentZones
            ));

            Yii::$app->db->createCommand(
                'UPDATE {{%player_cell_exp}}
                 SET official_credits = GREATEST(0, FLOOR(official_credits * :rate)),
                     friendly_credits = GREATEST(0, FLOOR(friendly_credits * :rate))
                 WHERE player_id = :pid'
                . (empty($recentDisplayZones)
                    ? ''
                    : ' AND zone NOT IN (' . implode(',', $recentDisplayZones) . ')'),
                [':rate' => self::DECAY_CELL, ':pid' => $playerId]
            )->execute();
        }
    }

    // -------------------------------------------------------------------------
    // Bonus formulas
    // -------------------------------------------------------------------------

    /**
     * Returns quadrant bonus [0..10] for a player in a given quadrant.
     */
    public function getQuadrantBonus(int $playerId, string $quadrant): float
    {
        $row = Yii::$app->db->createCommand(
            'SELECT official_credits, friendly_credits FROM {{%player_quadrant_exp}}
             WHERE player_id = :pid AND quadrant = :q',
            [':pid' => $playerId, ':q' => $quadrant]
        )->queryOne();

        if (!$row) {
            return 0.0;
        }

        $raw = (int) $row['official_credits'] * 2 + (int) $row['friendly_credits'];
        return min(self::CAP_QUAD, self::CAP_QUAD * (1 - exp(-$raw / self::SCALE_QUAD)));
    }

    /**
     * Returns cell bonus [0..3] for a player in a given display zone (1-64).
     */
    public function getCellBonus(int $playerId, int $displayZone): float
    {
        $row = Yii::$app->db->createCommand(
            'SELECT official_credits, friendly_credits FROM {{%player_cell_exp}}
             WHERE player_id = :pid AND zone = :z',
            [':pid' => $playerId, ':z' => $displayZone]
        )->queryOne();

        if (!$row) {
            return 0.0;
        }

        $raw = (int) $row['official_credits'] * 2 + (int) $row['friendly_credits'];
        return min(self::CAP_CELL, self::CAP_CELL * (1 - exp(-$raw / self::SCALE_CELL)));
    }

    /**
     * Returns combined cell power for a player: base + bonusQ + bonusC, clamped 1-99.
     *
     * @param int   $playerId
     * @param int   $engineZone  new engine zone (10 or row*10+lane)
     * @param float $baseOverall from Player::getOverallForPosition
     */
    public function getCellPower(int $playerId, int $engineZone, float $baseOverall): float
    {
        $quadrant    = QuadrantHelper::zoneToQuadrant($engineZone);
        $displayZone = PitchZoneHelper::toLegacyDisplayZone($engineZone);
        $bonusQ      = $this->getQuadrantBonus($playerId, $quadrant);
        $bonusC      = $this->getCellBonus($playerId, $displayZone);
        return max(1.0, min(99.0, $baseOverall + $bonusQ + $bonusC));
    }

    // -------------------------------------------------------------------------
    // Bulk loaders (for heatmap, no N+1)
    // -------------------------------------------------------------------------

    /**
     * Returns all quadrant rows for a player keyed by quadrant.
     *
     * @return array<string, array{official_credits:int, friendly_credits:int}>
     */
    public function getAllQuadrantExp(int $playerId): array
    {
        $rows = Yii::$app->db->createCommand(
            'SELECT quadrant, official_credits, friendly_credits FROM {{%player_quadrant_exp}} WHERE player_id = :pid',
            [':pid' => $playerId]
        )->queryAll();

        $result = [];
        foreach (QuadrantHelper::ALL as $q) {
            $result[$q] = ['official_credits' => 0, 'friendly_credits' => 0];
        }
        foreach ($rows as $row) {
            $result[$row['quadrant']] = [
                'official_credits' => (int) $row['official_credits'],
                'friendly_credits' => (int) $row['friendly_credits'],
            ];
        }
        return $result;
    }

    /**
     * Returns all cell rows for a player keyed by display zone (1-64).
     *
     * @return array<int, array{official_credits:int, friendly_credits:int}>
     */
    public function getAllCellExp(int $playerId): array
    {
        $rows = Yii::$app->db->createCommand(
            'SELECT zone, official_credits, friendly_credits FROM {{%player_cell_exp}} WHERE player_id = :pid',
            [':pid' => $playerId]
        )->queryAll();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['zone']] = [
                'official_credits' => (int) $row['official_credits'],
                'friendly_credits' => (int) $row['friendly_credits'],
            ];
        }
        return $result;
    }

    /**
     * Compute quadrant bonuses bulk for display (no N+1).
     *
     * @return array<string, float>  quadrant => bonus value
     */
    public function getQuadrantBonusMap(int $playerId): array
    {
        $expMap = $this->getAllQuadrantExp($playerId);
        $result = [];
        foreach ($expMap as $quadrant => $row) {
            $raw = $row['official_credits'] * 2 + $row['friendly_credits'];
            $result[$quadrant] = min(self::CAP_QUAD, self::CAP_QUAD * (1 - exp(-$raw / self::SCALE_QUAD)));
        }
        return $result;
    }

    /**
     * Compute cell bonuses bulk for display (no N+1).
     *
     * @return array<int, float>  displayZone => bonus value
     */
    public function getCellBonusMap(int $playerId): array
    {
        $expMap = $this->getAllCellExp($playerId);
        $result = [];
        foreach ($expMap as $zone => $row) {
            $raw = $row['official_credits'] * 2 + $row['friendly_credits'];
            $result[$zone] = min(self::CAP_CELL, self::CAP_CELL * (1 - exp(-$raw / self::SCALE_CELL)));
        }
        return $result;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Load starting zone (engine zone) for each player in a fixture.
     * Uses FormationSlot of the active formation for each team.
     *
     * @return array<int, int>  player_id => engine zone
     */
    private function loadStartingZones(Fixture $fixture): array
    {
        $zones = [];
        $ms = Yii::$app->db->createCommand(
            'SELECT home_formation_id, away_formation_id
             FROM {{%match_state}}
             WHERE fixture_id = :fid
             LIMIT 1',
            [':fid' => (int) $fixture->id]
        )->queryOne();

        $formationIds = [];
        if (!empty($ms['home_formation_id'])) {
            $formationIds[] = (int) $ms['home_formation_id'];
        }
        if (!empty($ms['away_formation_id'])) {
            $formationIds[] = (int) $ms['away_formation_id'];
        }

        if (empty($formationIds)) {
            return $zones;
        }

        $sql = 'SELECT player_id, zone
                FROM {{%formation_slot}}
                WHERE player_id IS NOT NULL
                  AND ' . PitchZoneHelper::onPitchSql('zone') . '
                  AND formation_id IN (' . implode(',', array_fill(0, count($formationIds), '?')) . ')';
        $slots = Yii::$app->db->createCommand($sql, $formationIds)->queryAll();

        foreach ($slots as $slot) {
            $pid = (int) ($slot['player_id'] ?? 0);
            $z = (int) ($slot['zone'] ?? 0);
            if ($pid <= 0 || !PitchZoneHelper::isOnPitch($z)) {
                continue;
            }
            $zones[$pid] = PitchZoneHelper::normalizeToCurrent($z);
        }
        return $zones;
    }

    /**
     * Get the last N engine zones played by a player (from player_stat + formation_slot).
     *
     * @return int[]
     */
    private function getRecentZones(int $playerId, int $n): array
    {
        // Last N fixtures where player has minutes > 0, newest first.
        $rows = Yii::$app->db->createCommand(
            'SELECT ps.fixture_id, ps.team_id, f.home_team_id, f.away_team_id, ms.home_formation_id, ms.away_formation_id
             FROM {{%player_stat}} ps
             INNER JOIN {{%fixture}} f ON f.id = ps.fixture_id
             LEFT JOIN {{%match_state}} ms ON ms.fixture_id = ps.fixture_id
             WHERE ps.player_id = :pid
               AND ps.minutes_played > 0
             ORDER BY f.match_date DESC, ps.fixture_id DESC
             LIMIT ' . max(1, (int) $n),
            [':pid' => $playerId]
        )->queryAll();

        $zones = [];
        foreach ($rows as $row) {
            $fixtureId = (int) ($row['fixture_id'] ?? 0);
            $teamId = (int) ($row['team_id'] ?? 0);
            if ($fixtureId <= 0 || $teamId <= 0) {
                continue;
            }

            $formationId = null;
            $homeTeamId = (int) ($row['home_team_id'] ?? 0);
            $awayTeamId = (int) ($row['away_team_id'] ?? 0);

            if ($homeTeamId > 0 && $teamId === $homeTeamId) {
                $formationId = !empty($row['home_formation_id']) ? (int) $row['home_formation_id'] : null;
            } elseif ($awayTeamId > 0 && $teamId === $awayTeamId) {
                $formationId = !empty($row['away_formation_id']) ? (int) $row['away_formation_id'] : null;
            }
            if ($formationId === null || $formationId <= 0) {
                continue;
            }

            $zone = Yii::$app->db->createCommand(
                'SELECT zone FROM {{%formation_slot}} WHERE formation_id = :fid AND player_id = :pid LIMIT 1',
                [':fid' => $formationId, ':pid' => $playerId]
            )->queryScalar();

            if ($zone) {
                $zones[] = PitchZoneHelper::normalizeToCurrent((int) $zone);
            }
        }

        return $zones;
    }
}
