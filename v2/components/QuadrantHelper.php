<?php

declare(strict_types=1);

namespace app\components;

/**
 * SIP-0068: Maps pitch zones to/from the 10 canonical quadrants.
 *
 * Quadrants:
 *   gk
 *   def_l  def_c  def_r   (rows 2..4)
 *   mid_l  mid_c  mid_r   (rows 5..7)
 *   att_l  att_c  att_r   (rows 8..10)
 *
 * Lane 1 = L, lane 2 = C, lane 3 = R
 */
final class QuadrantHelper
{
    public const ALL = [
        'gk',
        'def_l', 'def_c', 'def_r',
        'mid_l', 'mid_c', 'mid_r',
        'att_l', 'att_c', 'att_r',
    ];

    private function __construct()
    {
    }

    /**
     * Convert a zone (new engine zone or legacy display zone) to a quadrant string.
     */
    public static function zoneToQuadrant(int $zone): string
    {
        $coords = PitchZoneHelper::coords($zone);
        if ($coords['is_gk']) {
            return 'gk';
        }
        $row = $coords['row'];
        $lane = $coords['lane'];

        $band = match (true) {
            $row >= 2 && $row <= 4 => 'def',
            $row >= 5 && $row <= 7 => 'mid',
            default                => 'att',
        };

        $side = match ($lane) {
            1       => 'l',
            3       => 'r',
            default => 'c',
        };

        return $band . '_' . $side;
    }

    /**
     * Return the canonical new-zone list for a given quadrant.
     * Useful for display hints and heatmap coloring.
     *
     * @return int[]
     */
    public static function quadrantZones(string $quadrant): array
    {
        if ($quadrant === 'gk') {
            return [PitchZoneHelper::GK_ZONE];
        }

        [$band, $side] = explode('_', $quadrant, 2);

        $rows = match ($band) {
            'def'   => [2, 3, 4],
            'mid'   => [5, 6, 7],
            'att'   => [8, 9, 10],
            default => [],
        };

        $lanes = match ($side) {
            'l'     => [1],
            'r'     => [3],
            default => [2],
        };

        $zones = [];
        foreach ($rows as $row) {
            foreach ($lanes as $lane) {
                $zones[] = PitchZoneHelper::zone($row, $lane);
            }
        }
        return $zones;
    }

    /**
     * Return a human-readable label for a quadrant.
     */
    public static function label(string $quadrant): string
    {
        return match ($quadrant) {
            'gk'    => 'Portiere',
            'def_l' => 'Difesa sx',
            'def_c' => 'Difesa centro',
            'def_r' => 'Difesa dx',
            'mid_l' => 'Centrocampo sx',
            'mid_c' => 'Centrocampo centro',
            'mid_r' => 'Centrocampo dx',
            'att_l' => 'Attacco sx',
            'att_c' => 'Attacco centro',
            'att_r' => 'Attacco dx',
            default => $quadrant,
        };
    }
}
