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
}
