<?php

declare(strict_types=1);

namespace app\components;

/**
 * SIP-0073: Physical attribute helpers shared by MatchEngine, training, and seeding.
 *
 * Go parity: formulas here MUST be mirrored in worker-go/engine/match.go (physicalFitnessMod).
 */
final class PhysicalHelper
{
    /** Reference BMI (optimal) per canonical position group. */
    private const REF_BMI = [
        'GK' => 24.0,
        'DF' => 23.5,
        'MF' => 22.5,
        'FW' => 22.0,
    ];

    /** Height/weight generation ranges [min, max] per position. */
    private const HEIGHT_RANGE = [
        'GK' => [183, 198],
        'DF' => [178, 194],
        'MF' => [170, 183],
        'FW' => [170, 188],
    ];

    private const WEIGHT_RANGE = [
        'GK' => [78, 95],
        'DF' => [75, 90],
        'MF' => [65, 80],
        'FW' => [65, 82],
    ];

    /** Minimum GK height for zero penalty. Below this, skill_po is penalised. */
    private const GK_MIN_HEIGHT = 183;

    private function __construct() {}

    /**
     * Physical Fitness Index: [0.92, 1.04].
     * PFI = 1.04 at optimal BMI; loses 0.02 per BMI unit of deviation.
     */
    public static function pfi(int $heightCm, int $weightKg, string $position): float
    {
        if ($heightCm <= 0) return 1.0;
        $bmi  = $weightKg / (($heightCm / 100.0) ** 2);
        $ref  = self::REF_BMI[self::group($position)] ?? 23.0;
        $dev  = abs($bmi - $ref);
        return max(0.92, min(1.04, 1.04 - $dev * 0.02));
    }

    /**
     * Average PFI for a set of players (pass arrays of height, weight, position).
     * Returns 1.0 if the list is empty.
     *
     * @param array<array{height_cm:int,weight_kg:int,position:string}> $players
     */
    public static function teamPfi(array $players): float
    {
        if (empty($players)) return 1.0;
        $sum = 0.0;
        foreach ($players as $p) {
            $sum += self::pfi((int)$p['height_cm'], (int)$p['weight_kg'], (string)$p['position']);
        }
        return $sum / count($players);
    }

    /**
     * GK height penalty applied to effective skill_po (not persisted).
     * Returns a non-negative integer to subtract.
     */
    public static function gkHeightPenalty(int $heightCm): int
    {
        return (int) max(0, (self::GK_MIN_HEIGHT - $heightCm) * 0.5);
    }

    /**
     * Training freshness extra cost when BMI deviates from optimal.
     * Returns a non-negative integer (extra points to subtract from freshnessDelta).
     * Only applied when the base freshnessDelta is already negative.
     */
    public static function trainingFatiguePenalty(int $heightCm, int $weightKg, string $position): int
    {
        if ($heightCm <= 0) return 0;
        $bmi = $weightKg / (($heightCm / 100.0) ** 2);
        $ref = self::REF_BMI[self::group($position)] ?? 23.0;
        $dev = abs($bmi - $ref);
        if ($dev <= 1.5) return 0;
        return (int) min(2, floor($dev * 0.4));
    }

    /** Random height (cm) for a position. */
    public static function randomHeight(string $position): int
    {
        [$min, $max] = self::HEIGHT_RANGE[self::group($position)] ?? [170, 190];
        return random_int($min, $max);
    }

    /** Random weight (kg) for a position. */
    public static function randomWeight(string $position): int
    {
        [$min, $max] = self::WEIGHT_RANGE[self::group($position)] ?? [65, 85];
        return random_int($min, $max);
    }

    public static function bmi(int $heightCm, int $weightKg): float
    {
        if ($heightCm <= 0) return 0.0;
        return round($weightKg / (($heightCm / 100.0) ** 2), 1);
    }

    private static function group(string $position): string
    {
        $p = strtoupper(trim($position));
        if ($p === 'GK' || $p === 'PO') return 'GK';
        if (in_array($p, ['DF', 'DS', 'DD', 'DC', 'D'], true)) return 'DF';
        if (in_array($p, ['MF', 'CS', 'CD', 'CC', 'C'], true)) return 'MF';
        return 'FW';
    }
}
