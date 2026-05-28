<?php

declare(strict_types=1);

namespace app\components;

final class PitchZoneHelper
{
    public const GK_ZONE = 10;
    public const DISPLAY_GK_ZONE = 64;
    public const LEGACY_MIN_ZONE = 1;
    public const LEGACY_MAX_ZONE = 63;
    public const STORED_DISPLAY_OFFSET = 1000;
    public const STORED_DISPLAY_MIN_ZONE = self::STORED_DISPLAY_OFFSET + self::LEGACY_MIN_ZONE; // 1001
    public const STORED_DISPLAY_MAX_ZONE = self::STORED_DISPLAY_OFFSET + self::LEGACY_MAX_ZONE; // 1063
    public const ROW_MIN = 1;
    public const ROW_MAX = 10;
    public const LANE_MIN = 1;
    public const LANE_MAX = 3;

    private function __construct()
    {
    }

    public static function zone(int $row, int $lane): int
    {
        $row = max(self::ROW_MIN, min(self::ROW_MAX, $row));
        $lane = max(self::LANE_MIN, min(self::LANE_MAX, $lane));
        if ($row === 1) {
            return self::GK_ZONE;
        }
        return ($row * 10) + $lane;
    }

    public static function isLegacyZone(int $zone): bool
    {
        return $zone >= self::LEGACY_MIN_ZONE && $zone <= self::LEGACY_MAX_ZONE;
    }

    public static function isStoredDisplayZone(int $zone): bool
    {
        return $zone >= self::STORED_DISPLAY_MIN_ZONE && $zone <= self::STORED_DISPLAY_MAX_ZONE;
    }

    public static function encodeStoredDisplayZone(int $displayZone): int
    {
        $displayZone = max(self::LEGACY_MIN_ZONE, min(self::LEGACY_MAX_ZONE, $displayZone));
        return self::STORED_DISPLAY_OFFSET + $displayZone;
    }

    public static function decodeStoredDisplayZone(int $storedZone): int
    {
        if (!self::isStoredDisplayZone($storedZone)) {
            return max(self::LEGACY_MIN_ZONE, min(self::LEGACY_MAX_ZONE, $storedZone));
        }
        return $storedZone - self::STORED_DISPLAY_OFFSET;
    }

    /**
     * Returns all DB-zone candidates that represent a given display zone.
     * Useful to clear/move slots across mixed old/new encodings safely.
     *
     * @return int[]
     */
    public static function displayZoneCandidates(int $displayZone): array
    {
        if ($displayZone === self::DISPLAY_GK_ZONE) {
            return [self::DISPLAY_GK_ZONE, self::GK_ZONE];
        }

        if (!self::isLegacyZone($displayZone)) {
            return [];
        }

        $out = [
            self::encodeStoredDisplayZone($displayZone), // new unambiguous storage
            $displayZone,                                 // legacy raw storage
            self::normalizeDisplayZone($displayZone),     // historical current-zone storage
        ];

        return array_values(array_unique(array_map('intval', $out)));
    }

    public static function isCurrentZone(int $zone): bool
    {
        if ($zone === self::GK_ZONE) {
            return true;
        }
        $row = intdiv($zone, 10);
        $lane = $zone % 10;
        return $row >= 2 && $row <= self::ROW_MAX && $lane >= self::LANE_MIN && $lane <= self::LANE_MAX;
    }

    public static function isOnPitch(int $zone): bool
    {
        return $zone === self::DISPLAY_GK_ZONE
            || self::isCurrentZone($zone)
            || self::isLegacyZone($zone)
            || self::isStoredDisplayZone($zone);
    }

    public static function isGoalkeeperZone(int $zone): bool
    {
        // GK is always at GK_ZONE (10) or DISPLAY_GK_ZONE (64).
        // Legacy zone 60 (old row 9, col 4) is no longer a GK zone — it now hosts
        // the deep center-back (libero) in the new zone system.
        return $zone === self::GK_ZONE || $zone === self::DISPLAY_GK_ZONE;
    }

    /**
     * @return array{row:int,lane:int,is_gk:bool}
     */
    public static function coords(int $zone): array
    {
        if (self::isStoredDisplayZone($zone)) {
            $zone = self::decodeStoredDisplayZone($zone);
        }

        if (self::isCurrentZone($zone)) {
            if ($zone === self::GK_ZONE) {
                return ['row' => 1, 'lane' => 2, 'is_gk' => true];
            }
            return [
                'row' => intdiv($zone, 10),
                'lane' => $zone % 10,
                'is_gk' => false,
            ];
        }

        if ($zone === self::DISPLAY_GK_ZONE) {
            return ['row' => 1, 'lane' => 2, 'is_gk' => true];
        }

        if (!self::isLegacyZone($zone)) {
            return ['row' => 6, 'lane' => 2, 'is_gk' => false];
        }

        $legacy = self::legacyCoords($zone);
        // Legacy row 9, col 4 was the old GK cell (zone 60), but GK is now always
        // at GK_ZONE(10)/DISPLAY_GK_ZONE(64). Treat zone 60 as normal center-back.
        $newRow = 11 - $legacy['row']; // old row 1(att) -> new row 10(att)
        $lane = self::legacyColToLane($legacy['col']);
        return ['row' => $newRow, 'lane' => $lane, 'is_gk' => false];
    }

    public static function laneCode(int $zone): string
    {
        $coords = self::coords($zone);
        return match ($coords['lane']) {
            1 => 'L',
            3 => 'R',
            default => 'C',
        };
    }

    public static function row(int $zone): int
    {
        return self::coords($zone)['row'];
    }

    /**
     * Normalize a DISPLAY zone (1-64) to an engine zone for heatmap/view rendering.
     * Zone 64 → GK_ZONE(10). Zones 1-63 are ALWAYS treated as legacy zones.
     * Unlike normalizeToCurrent(), this never confuses zone 10 with GK_ZONE.
     */
    public static function normalizeDisplayZone(int $displayZone): int
    {
        if (self::isStoredDisplayZone($displayZone)) {
            $displayZone = self::decodeStoredDisplayZone($displayZone);
        }
        if ($displayZone === self::DISPLAY_GK_ZONE) {
            return self::GK_ZONE;
        }
        if (!self::isLegacyZone($displayZone)) {
            return self::zone(6, 2);
        }
        $legacy = self::legacyCoords($displayZone);
        $newRow = 11 - $legacy['row'];
        $lane   = self::legacyColToLane($legacy['col']);
        return self::zone($newRow, $lane);
    }

    public static function normalizeToCurrent(int $zone): int
    {
        if (self::isStoredDisplayZone($zone)) {
            $zone = self::decodeStoredDisplayZone($zone);
        }
        if ($zone === self::DISPLAY_GK_ZONE) {
            return self::GK_ZONE;
        }
        if (self::isCurrentZone($zone)) {
            return $zone;
        }
        if (!self::isLegacyZone($zone)) {
            return self::zone(6, 2);
        }
        if (self::isGoalkeeperZone($zone)) {
            return self::GK_ZONE;
        }
        $coords = self::coords($zone);
        return self::zone($coords['row'], $coords['lane']);
    }

    /**
     * Convert current 10-row zone to legacy 7x9 display zone for UI compatibility.
     *
     * IMPORTANT: new zone codes 21-63 overlap with legacy codes 1-63.
     * isCurrentZone must be checked FIRST so MF/FW zones (61, 62…) are not
     * misidentified as legacy and returned unchanged (which would place them
     * at the wrong grid position).
     */
    public static function toLegacyDisplayZone(int $zone): int
    {
        if (self::isStoredDisplayZone($zone)) {
            return self::decodeStoredDisplayZone($zone);
        }
        if ($zone === self::DISPLAY_GK_ZONE) {
            return self::DISPLAY_GK_ZONE;
        }
        if ($zone === self::GK_ZONE) {
            return self::DISPLAY_GK_ZONE;
        }
        // Check current zone BEFORE legacy because zones 21-63 are both.
        if (self::isCurrentZone($zone)) {
            $coords = self::coords($zone);
            $legacyRow = 11 - $coords['row']; // new row10(att)->legRow1, new row2(def)->legRow9
            $legacyCol = match ($coords['lane']) {
                1 => 2,
                3 => 6,
                default => 4,
            };
            return (($legacyRow - 1) * 7) + $legacyCol;
        }
        if (self::isLegacyZone($zone)) {
            return $zone; // pure legacy-only zone (not a valid current zone)
        }
        return 32; // fallback
    }

    /**
     * SQL snippet for "player is on pitch" zone filtering.
     * Pass column with alias when needed, e.g. "fs.zone".
     */
    public static function onPitchSql(string $column = 'zone', bool $includeLegacy = true): string
    {
        $current = sprintf(
            '((%1$s = %2$d) OR ((%1$s BETWEEN 20 AND 103) AND (MOD(%1$s,10) BETWEEN 1 AND 3)))',
            $column,
            self::GK_ZONE
        );
        $withDisplayGk = sprintf('((%s) OR (%s = %d))', $current, $column, self::DISPLAY_GK_ZONE);
        if (!$includeLegacy) {
            return $withDisplayGk;
        }
        return sprintf(
            '(%s OR (%s BETWEEN %d AND %d) OR (%s BETWEEN %d AND %d))',
            $withDisplayGk,
            $column,
            self::LEGACY_MIN_ZONE,
            self::LEGACY_MAX_ZONE,
            $column,
            self::STORED_DISPLAY_MIN_ZONE,
            self::STORED_DISPLAY_MAX_ZONE
        );
    }

    /**
     * @return array{row:int,col:int}
     */
    private static function legacyCoords(int $zone): array
    {
        $row = intdiv(max(1, $zone) - 1, 7) + 1; // 1..9
        $col = ((max(1, $zone) - 1) % 7) + 1;    // 1..7
        return ['row' => $row, 'col' => $col];
    }

    private static function legacyColToLane(int $col): int
    {
        return match (true) {
            $col <= 2 => 1,
            $col >= 6 => 3,
            default => 2,
        };
    }

    public const FORMULA_2_COEFFS = [
        10  => ['po' => 65.0, 'df' => 8.0,  'cn' => 4.0,  'pa' => 2.0,  'rg' => 10.0, 'cr' => 0.5,  'tc' => 5.5,  'tr' => 0.5],
        21  => ['po' => 0.0,  'df' => 22.7, 'cn' => 19.1, 'pa' => 19.5, 'rg' => 3.8,  'cr' => 19.4, 'tc' => 7.7,  'tr' => 3.2],
        22  => ['po' => 0.0,  'df' => 18.3, 'cn' => 16.1, 'pa' => 18.7, 'rg' => 6.3,  'cr' => 23.8, 'tc' => 9.2,  'tr' => 3.6],
        23  => ['po' => 0.0,  'df' => 2.4,  'cn' => 9.1,  'pa' => 21.4, 'rg' => 10.9, 'cr' => 32.7, 'tc' => 11.4, 'tr' => 7.4],
        31  => ['po' => 0.0,  'df' => 20.5, 'cn' => 23.6, 'pa' => 13.4, 'rg' => 17.2, 'cr' => 8.9,  'tc' => 9.2,  'tr' => 4.2],
        32  => ['po' => 0.0,  'df' => 21.8, 'cn' => 25.3, 'pa' => 11.1, 'rg' => 22.4, 'cr' => 3.8,  'tc' => 10.9, 'tr' => 4.7],
        33  => ['po' => 0.0,  'df' => 25.5, 'cn' => 24.7, 'pa' => 14.3, 'rg' => 12.5, 'cr' => 9.2,  'tc' => 8.7,  'tr' => 3.4],
        41  => ['po' => 0.0,  'df' => 18.4, 'cn' => 18.0, 'pa' => 18.0, 'rg' => 7.7,  'cr' => 20.4, 'tc' => 9.3,  'tr' => 4.2],
        42  => ['po' => 0.0,  'df' => 24.8, 'cn' => 22.0, 'pa' => 15.6, 'rg' => 9.4,  'cr' => 12.4, 'tc' => 10.0, 'tr' => 3.1],
        43  => ['po' => 0.0,  'df' => 27.9, 'cn' => 22.6, 'pa' => 17.3, 'rg' => 4.0,  'cr' => 15.0, 'tc' => 7.8,  'tr' => 2.1],
        51  => ['po' => 0.0,  'df' => 17.3, 'cn' => 16.7, 'pa' => 19.0, 'rg' => 6.4,  'cr' => 24.1, 'tc' => 9.2,  'tr' => 3.3],
        52  => ['po' => 0.0,  'df' => 17.9, 'cn' => 23.7, 'pa' => 11.3, 'rg' => 23.4, 'cr' => 5.2,  'tc' => 11.1, 'tr' => 5.1],
        53  => ['po' => 0.0,  'df' => 37.1, 'cn' => 26.7, 'pa' => 14.3, 'rg' => 6.9,  'cr' => 2.4,  'tc' => 9.9,  'tr' => 2.4],
        61  => ['po' => 0.0,  'df' => 13.3, 'cn' => 14.1, 'pa' => 18.6, 'rg' => 8.4,  'cr' => 27.5, 'tc' => 9.7,  'tr' => 4.0],
        62  => ['po' => 0.0,  'df' => 13.0, 'cn' => 14.1, 'pa' => 18.8, 'rg' => 7.9,  'cr' => 28.3, 'tc' => 9.4,  'tr' => 3.6],
        63  => ['po' => 0.0,  'df' => 12.7, 'cn' => 14.2, 'pa' => 19.1, 'rg' => 7.4,  'cr' => 29.1, 'tc' => 9.0,  'tr' => 3.8],
        71  => ['po' => 0.0,  'df' => 7.3,  'cn' => 10.5, 'pa' => 23.0, 'rg' => 7.9,  'cr' => 32.1, 'tc' => 10.2, 'tr' => 6.2],
        72  => ['po' => 0.0,  'df' => 5.5,  'cn' => 19.0, 'pa' => 10.7, 'rg' => 30.5, 'cr' => 6.9,  'tc' => 13.2, 'tr' => 11.7],
        73  => ['po' => 0.0,  'df' => 2.1,  'cn' => 8.0,  'pa' => 22.8, 'rg' => 9.1,  'cr' => 37.8, 'tc' => 10.7, 'tr' => 6.5],
        81  => ['po' => 0.0,  'df' => 1.3,  'cn' => 6.9,  'pa' => 27.4, 'rg' => 7.5,  'cr' => 36.7, 'tc' => 10.7, 'tr' => 8.4],
        82  => ['po' => 0.0,  'df' => 1.9,  'cn' => 11.6, 'pa' => 15.5, 'rg' => 10.5, 'cr' => 9.8,  'tc' => 19.6, 'tr' => 29.2],
        83  => ['po' => 0.0,  'df' => 1.5,  'cn' => 7.6,  'pa' => 25.6, 'rg' => 8.6,  'cr' => 33.4, 'tc' => 11.5, 'tr' => 10.2],
        91  => ['po' => 0.0,  'df' => 1.0,  'cn' => 6.4,  'pa' => 32.7, 'rg' => 5.9,  'cr' => 32.7, 'tc' => 11.1, 'tr' => 9.3],
        92  => ['po' => 0.0,  'df' => 1.0,  'cn' => 9.6,  'pa' => 16.6, 'rg' => 5.0,  'cr' => 10.0, 'tc' => 21.5, 'tr' => 34.8],
        93  => ['po' => 0.0,  'df' => 1.0,  'cn' => 6.4,  'pa' => 32.7, 'rg' => 5.9,  'cr' => 32.7, 'tc' => 11.1, 'tr' => 9.3],
        101 => ['po' => 0.0,  'df' => 0.9,  'cn' => 6.0,  'pa' => 30.9, 'rg' => 5.6,  'cr' => 31.6, 'tc' => 11.0, 'tr' => 9.2],
        102 => ['po' => 0.0,  'df' => 0.9,  'cn' => 8.9,  'pa' => 16.1, 'rg' => 4.8,  'cr' => 10.5, 'tc' => 20.8, 'tr' => 33.1],
        103 => ['po' => 0.0,  'df' => 0.9,  'cn' => 6.0,  'pa' => 30.9, 'rg' => 5.6,  'cr' => 31.6, 'tc' => 11.0, 'tr' => 9.2]
    ];

    public static function getCoefficientsForZone(int $zone): ?array
    {
        $normalized = self::normalizeToCurrent($zone);
        return self::FORMULA_2_COEFFS[$normalized] ?? null;
    }
}
