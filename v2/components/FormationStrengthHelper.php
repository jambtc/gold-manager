<?php

declare(strict_types=1);

namespace app\components;

use app\models\Formation;
use app\models\FormationSlot;
use app\models\Player;
use Yii;

final class FormationStrengthHelper
{
    /**
     * Compute theoretical team strength from current lineup.
     *
     * @param FormationSlot[] $slots
     * @return array{
     *   overall:int,
     *   dept:array{GK:int,DF:int,MF:int,FW:int},
     *   starters:int
     * }
     */
    public static function calculate(Formation $formation, array $slots): array
    {
        $deptTotals = ['GK' => 0.0, 'DF' => 0.0, 'MF' => 0.0, 'FW' => 0.0];
        $deptCount = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        $starters = 0;

        $tactics = self::loadTacticTraining((int) $formation->team_id);
        $style = in_array((string) $formation->tactic, ['balanced', 'ultra_defensive', 'all_out_attack'], true)
            ? (string) $formation->tactic
            : 'balanced';
        $focus = (string) ($formation->trained_tactic ?? '');
        $focusLevel = max(0, min(100, (int) ($focus !== '' ? ($tactics[$focus] ?? 0) : 0)));

        foreach ($slots as $slot) {
            if (!$slot->player) {
                continue;
            }

            $zone = PitchZoneHelper::normalizeToCurrent((int) $slot->zone);
            if (!PitchZoneHelper::isCurrentZone($zone)) {
                continue;
            }

            $player = $slot->player;
            $expectedRole = self::expectedRoleForZone($zone);
            $base = (float) $player->getOverallForPosition($zone);
            $readiness = self::readinessMultiplier($player);
            $roleFit = self::roleFitMultiplier((string) $player->position, $expectedRole);
            $styleMult = self::styleMultiplier($style, $expectedRole);
            $focusMult = self::focusMultiplier($focus, $focusLevel, $player, $expectedRole);

            $score = $base * $readiness * $roleFit * $styleMult * $focusMult;
            $score = max(1.0, min(99.0, $score));

            $deptTotals[$expectedRole] += $score;
            $deptCount[$expectedRole]++;
            $starters++;
        }

        $dept = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        foreach ($dept as $key => $_) {
            $dept[$key] = $deptCount[$key] > 0
                ? (int) round($deptTotals[$key] / $deptCount[$key])
                : 0;
        }

        $weights = self::deptWeights($style, $focus, $focusLevel);
        $weighted = 0.0;
        $weightTotal = 0.0;
        foreach ($dept as $role => $value) {
            if ($value <= 0) {
                continue;
            }
            $w = (float) ($weights[$role] ?? 0.0);
            if ($w <= 0.0) {
                continue;
            }
            $weighted += $value * $w;
            $weightTotal += $w;
        }

        $overall = $weightTotal > 0.0 ? (int) round($weighted / $weightTotal) : 0;
        $occupancy = max(0.0, min(1.0, $starters / 11.0));
        $overall = (int) round($overall * $occupancy);

        return [
            'overall' => $overall,
            'dept' => $dept,
            'starters' => $starters,
        ];
    }

    /**
     * @return array<string,int>
     */
    private static function loadTacticTraining(int $teamId): array
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

    private static function expectedRoleForZone(int $zone): string
    {
        $coords = PitchZoneHelper::coords($zone);
        if (($coords['is_gk'] ?? false) === true) {
            return 'GK';
        }

        $row = (int) ($coords['row'] ?? 6);
        if ($row >= 2 && $row <= 4) {
            return 'DF';
        }
        if ($row >= 8 && $row <= 10) {
            return 'FW';
        }

        return 'MF';
    }

    private static function readinessMultiplier(Player $player): float
    {
        $form = max(0, min(100, (int) $player->form)) / 100.0;
        $fresh = max(0, min(100, (int) $player->freshness)) / 100.0;
        $cond = max(0, min(100, (int) $player->condition)) / 100.0;

        $formMult = ($form * 0.30) + 0.70;
        $freshMult = ($fresh * 0.15) + 0.85;
        $condMult = ($cond * 0.15) + 0.85;

        return $formMult * $freshMult * $condMult;
    }

    private static function roleFitMultiplier(string $playerRole, string $expectedRole): float
    {
        $playerRole = strtoupper($playerRole);
        if ($playerRole === $expectedRole) {
            return 1.0;
        }
        if ($expectedRole === 'GK' || $playerRole === 'GK') {
            return 0.18;
        }
        return match ($expectedRole) {
            'DF' => match ($playerRole) {
                'MF' => 0.70,
                'FW' => 0.40,
                default => 0.55,
            },
            'MF' => match ($playerRole) {
                'DF', 'FW' => 0.78,
                default => 0.70,
            },
            'FW' => match ($playerRole) {
                'MF' => 0.80,
                'DF' => 0.55,
                default => 0.70,
            },
            default => 0.70,
        };
    }

    private static function styleMultiplier(string $style, string $expectedRole): float
    {
        if ($style === 'ultra_defensive') {
            return match ($expectedRole) {
                'GK' => 1.06,
                'DF' => 1.08,
                'MF' => 0.98,
                'FW' => 0.92,
                default => 1.0,
            };
        }
        if ($style === 'all_out_attack') {
            return match ($expectedRole) {
                'GK' => 0.94,
                'DF' => 0.92,
                'MF' => 1.03,
                'FW' => 1.08,
                default => 1.0,
            };
        }

        return 1.0;
    }

    private static function focusMultiplier(string $focus, int $focusLevel, Player $player, string $expectedRole): float
    {
        if ($focusLevel <= 0 || $focus === '') {
            return 1.0;
        }

        $f = $focusLevel / 100.0;
        return match ($focus) {
            'catenaccio' => match ($expectedRole) {
                'GK', 'DF' => 1.0 + (0.30 * $f) + ((max(0, min(100, (int) $player->freshness)) / 100.0) * 0.05),
                'MF' => 0.96 + (0.03 * $f),
                'FW' => 0.85 + (0.02 * $f),
                default => 1.0,
            },
            'lancio_lungo' => match ($expectedRole) {
                'FW' => 1.0 + (0.14 * $f) + (($player->skill_tr + $player->skill_tc) / 4000.0),
                'MF' => 1.0 + (0.07 * $f) + ($player->skill_pa / 3000.0),
                'DF' => 0.96 + (0.03 * $f),
                'GK' => 0.98 + (0.02 * $f),
                default => 1.0,
            },
            'pressing' => match ($expectedRole) {
                'MF' => 1.0 + (0.12 * $f) + (($player->skill_cn + $player->skill_rg) / 4500.0),
                'DF' => 1.0 + (0.08 * $f),
                'FW' => 1.0 + (0.05 * $f),
                default => 1.0,
            },
            'contropiede' => match ($expectedRole) {
                'FW' => 1.0 + (0.12 * $f) + (($player->skill_tr + $player->skill_tc) / 4500.0),
                'MF' => 1.0 + (0.06 * $f),
                default => 1.0,
            },
            'possesso', 'palla_bassa' => match ($expectedRole) {
                'MF' => 1.0 + (0.11 * $f) + (($player->skill_pa + $player->skill_rg) / 4500.0),
                'DF' => 1.0 + (0.05 * $f),
                'FW' => 0.98 + (0.04 * $f),
                default => 1.0,
            },
            'fuorigioco' => match ($expectedRole) {
                'DF' => 1.0 + (0.12 * $f),
                'GK' => 1.0 + (0.06 * $f),
                default => 0.97 + (0.04 * $f),
            },
            default => 1.0,
        };
    }

    /**
     * @return array{GK:int,DF:int,MF:int,FW:int}
     */
    private static function deptWeights(string $style, string $focus, int $focusLevel): array
    {
        $w = ['GK' => 15, 'DF' => 30, 'MF' => 30, 'FW' => 25];

        if ($style === 'ultra_defensive') {
            $w['GK'] += 2;
            $w['DF'] += 8;
            $w['MF'] -= 3;
            $w['FW'] -= 7;
        } elseif ($style === 'all_out_attack') {
            $w['GK'] -= 2;
            $w['DF'] -= 7;
            $w['MF'] += 3;
            $w['FW'] += 6;
        }

        $focusBoost = (int) round(max(0, min(100, $focusLevel)) / 20); // 0..5
        switch ($focus) {
            case 'catenaccio':
            case 'fuorigioco':
                $w['DF'] += $focusBoost;
                $w['GK'] += (int) round($focusBoost / 2);
                $w['FW'] -= $focusBoost;
                break;
            case 'lancio_lungo':
            case 'contropiede':
                $w['FW'] += $focusBoost;
                $w['MF'] += (int) round($focusBoost / 2);
                $w['DF'] -= (int) round($focusBoost / 2);
                break;
            case 'pressing':
            case 'possesso':
            case 'palla_bassa':
                $w['MF'] += $focusBoost;
                break;
        }

        foreach ($w as $k => $v) {
            $w[$k] = max(8, min(45, (int) $v));
        }

        return $w;
    }
}
