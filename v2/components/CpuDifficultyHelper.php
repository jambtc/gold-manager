<?php

declare(strict_types=1);

namespace app\components;

use app\models\Competition;
use app\models\Formation;
use app\models\Standing;
use app\models\Team;
use Yii;

final class CpuDifficultyHelper
{
    /**
     * @return 'casual'|'normal'|'competitive'|'hardcore'
     */
    public static function resolveDifficulty(Team $team, ?int $competitionId = null): string
    {
        $standing = self::findStanding($team, $competitionId);
        $tier = (int) ($standing['tier'] ?? Competition::TIER_C);
        $position = (int) ($standing['position'] ?? 8);
        $totalTeams = max(1, (int) ($standing['total'] ?? 16));
        $pct = $position / $totalTeams;

        $base = match ($tier) {
            Competition::TIER_A => 'competitive',
            Competition::TIER_B => 'normal',
            default => 'casual',
        };

        if ($tier === Competition::TIER_A && $pct <= 0.25) {
            return 'hardcore';
        }
        if ($tier !== Competition::TIER_C && $pct <= 0.40 && $base === 'normal') {
            return 'competitive';
        }
        if ($pct >= 0.85 && $base === 'casual') {
            return 'normal';
        }

        return $base;
    }

    /**
     * @return array{
     *   level:string,
     *   tactic_interval:int,
     *   tactic_start_min:int,
     *   sub_minutes:array<int,int>,
     *   freshness_threshold_adj:int,
     *   losing_attack_min:int,
     *   winning_def_min:int
     * }
     */
    public static function profile(string $difficulty): array
    {
        return match ($difficulty) {
            'hardcore' => [
                'level' => 'hardcore',
                'tactic_interval' => 5,
                'tactic_start_min' => 25,
                'sub_minutes' => [46, 55, 65, 75, 85],
                'freshness_threshold_adj' => 8,
                'losing_attack_min' => 58,
                'winning_def_min' => 52,
            ],
            'competitive' => [
                'level' => 'competitive',
                'tactic_interval' => 8,
                'tactic_start_min' => 28,
                'sub_minutes' => [46, 58, 70, 80],
                'freshness_threshold_adj' => 5,
                'losing_attack_min' => 62,
                'winning_def_min' => 56,
            ],
            'casual' => [
                'level' => 'casual',
                'tactic_interval' => 12,
                'tactic_start_min' => 35,
                'sub_minutes' => [60, 75],
                'freshness_threshold_adj' => -3,
                'losing_attack_min' => 74,
                'winning_def_min' => 66,
            ],
            default => [
                'level' => 'normal',
                'tactic_interval' => 10,
                'tactic_start_min' => 30,
                'sub_minutes' => [46, 60, 75],
                'freshness_threshold_adj' => 0,
                'losing_attack_min' => 68,
                'winning_def_min' => 60,
            ],
        };
    }

    /**
     * @return array{
     *   module:string,
     *   tactic:string,
     *   marking:string,
     *   offside_trap:int,
     *   trained_tactic:string,
     *   difficulty:string
     * }
     */
    public static function suggestPreMatchSetup(Team $team, Formation $formation, ?int $competitionId = null): array
    {
        $difficulty = self::resolveDifficulty($team, $competitionId);
        $training = self::loadTrainingTactic((int) $team->id);
        $standing = self::findStanding($team, $competitionId);
        $position = (int) ($standing['position'] ?? 8);
        $totalTeams = max(1, (int) ($standing['total'] ?? 16));
        $pct = $position / $totalTeams;

        $counts = self::availableRoleCounts((int) $team->id);
        $module = self::pickModule($counts, $difficulty, $training);

        $tactic = 'balanced';
        if ($pct >= 0.75) {
            $tactic = $difficulty === 'casual' ? 'balanced' : 'all_out_attack';
        } elseif ($pct <= 0.25) {
            $tactic = in_array($difficulty, ['competitive', 'hardcore'], true) ? 'ultra_defensive' : 'balanced';
        } elseif (in_array($difficulty, ['competitive', 'hardcore'], true)) {
            $tactic = ((int) ($training['pressing'] ?? 0) > (int) ($training['possesso'] ?? 0)) ? 'all_out_attack' : 'balanced';
        }

        $marking = in_array($difficulty, ['competitive', 'hardcore'], true) ? 'man' : 'zone';
        $offside = in_array($difficulty, ['competitive', 'hardcore'], true) ? 1 : 0;
        $trained = self::pickFocusFromStyle($tactic, $training);

        return [
            'module' => $module,
            'tactic' => $tactic,
            'marking' => $marking,
            'offside_trap' => $offside,
            'trained_tactic' => $trained,
            'difficulty' => $difficulty,
        ];
    }

    /**
     * @return array{tier:int,position:int,total:int}|array{}
     */
    private static function findStanding(Team $team, ?int $competitionId = null): array
    {
        if ($competitionId !== null && $competitionId > 0) {
            $competition = Competition::findOne($competitionId);
            if ($competition) {
                $rows = Standing::find()
                    ->where(['competition_id' => $competitionId])
                    ->orderBy([
                        'points' => SORT_DESC,
                        new \yii\db\Expression('(goals_for - goals_against) DESC'),
                        'goals_for' => SORT_DESC,
                        'team_id' => SORT_ASC,
                    ])
                    ->all();
                $total = count($rows);
                foreach ($rows as $idx => $row) {
                    if ((int) $row->team_id === (int) $team->id) {
                        return ['tier' => (int) $competition->tier, 'position' => $idx + 1, 'total' => $total];
                    }
                }
            }
        }

        $standingRows = Standing::find()
            ->where(['team_id' => (int) $team->id])
            ->with('competition')
            ->all();
        if (empty($standingRows)) {
            return [];
        }

        usort($standingRows, static fn(Standing $a, Standing $b): int => ((int) $b->played <=> (int) $a->played));
        $row = $standingRows[0];
        $competition = $row->competition;
        if (!$competition) {
            return [];
        }

        $rows = Standing::find()
            ->where(['competition_id' => (int) $competition->id])
            ->orderBy([
                'points' => SORT_DESC,
                new \yii\db\Expression('(goals_for - goals_against) DESC'),
                'goals_for' => SORT_DESC,
                'team_id' => SORT_ASC,
            ])
            ->all();
        $total = count($rows);
        foreach ($rows as $idx => $item) {
            if ((int) $item->team_id === (int) $team->id) {
                return ['tier' => (int) $competition->tier, 'position' => $idx + 1, 'total' => $total];
            }
        }
        return [];
    }

    /**
     * @return array<string,int>
     */
    private static function availableRoleCounts(int $teamId): array
    {
        $rows = Yii::$app->db->createCommand(
            'SELECT position, COUNT(*) AS c
             FROM {{%player}}
             WHERE team_id = :t
               AND COALESCE(injury_weeks,0) = 0
               AND COALESCE(suspended_matches,0) = 0
             GROUP BY position',
            [':t' => $teamId]
        )->queryAll();

        $counts = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        foreach ($rows as $row) {
            $pos = strtoupper((string) ($row['position'] ?? ''));
            if (isset($counts[$pos])) {
                $counts[$pos] = (int) ($row['c'] ?? 0);
            }
        }
        return $counts;
    }

    /**
     * @param array<string,int> $counts
     * @param array<string,int> $training
     */
    private static function pickModule(array $counts, string $difficulty, array $training): string
    {
        if (($counts['DF'] ?? 0) >= 5 && ($counts['FW'] ?? 0) >= 2 && (($training['catenaccio'] ?? 0) >= 55)) {
            return '5-3-2';
        }
        if (($counts['FW'] ?? 0) >= 3 && ($counts['MF'] ?? 0) >= 3 && in_array($difficulty, ['competitive', 'hardcore'], true)) {
            return '4-3-3';
        }
        if (($counts['MF'] ?? 0) >= 5 && ($counts['FW'] ?? 0) >= 2 && ($counts['DF'] ?? 0) >= 3) {
            return '3-5-2';
        }
        if (($counts['MF'] ?? 0) >= 5 && ($counts['FW'] ?? 0) >= 1 && ($counts['DF'] ?? 0) >= 4) {
            return '4-2-3-1';
        }
        return '4-4-2';
    }

    /**
     * @return array<string,int>
     */
    private static function loadTrainingTactic(int $teamId): array
    {
        $row = Yii::$app->db->createCommand(
            'SELECT pressing, contropiede, possesso, palla_bassa, lancio_lungo, catenaccio, fuorigioco
             FROM {{%training_tactic}}
             WHERE team_id = :teamId
             ORDER BY season DESC
             LIMIT 1',
            [':teamId' => $teamId]
        )->queryOne();

        if (!$row) {
            return [
                'pressing' => 0,
                'contropiede' => 0,
                'possesso' => 0,
                'palla_bassa' => 0,
                'lancio_lungo' => 0,
                'catenaccio' => 0,
                'fuorigioco' => 0,
            ];
        }

        return [
            'pressing' => (int) ($row['pressing'] ?? 0),
            'contropiede' => (int) ($row['contropiede'] ?? 0),
            'possesso' => (int) ($row['possesso'] ?? 0),
            'palla_bassa' => (int) ($row['palla_bassa'] ?? 0),
            'lancio_lungo' => (int) ($row['lancio_lungo'] ?? 0),
            'catenaccio' => (int) ($row['catenaccio'] ?? 0),
            'fuorigioco' => (int) ($row['fuorigioco'] ?? 0),
        ];
    }

    /**
     * @param array<string,int> $training
     */
    private static function pickFocusFromStyle(string $style, array $training): string
    {
        $groups = match ($style) {
            'all_out_attack' => ['contropiede', 'lancio_lungo', 'pressing'],
            'ultra_defensive' => ['catenaccio', 'fuorigioco', 'possesso'],
            default => ['possesso', 'palla_bassa', 'pressing'],
        };

        $best = '';
        $bestVal = -1;
        foreach ($groups as $key) {
            $v = (int) ($training[$key] ?? 0);
            if ($v > $bestVal) {
                $bestVal = $v;
                $best = $key;
            }
        }
        return $best !== '' ? $best : $groups[0];
    }
}

