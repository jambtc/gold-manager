<?php

declare(strict_types=1);

namespace app\components;

/**
 * SIP-0033 — SVG Skill & Position Icon System.
 * All icons use viewBox="0 0 24 24", currentColor for theming.
 */
class SvgIcons
{
    private static function svg(string $path, string $label, int $size = 20): string
    {
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24"'
             . ' role="img" aria-label="' . htmlspecialchars($label) . '"'
             . ' xmlns="http://www.w3.org/2000/svg" fill="currentColor">'
             . '<title>' . htmlspecialchars($label) . '</title>'
             . $path
             . '</svg>';
    }

    // ── Position icons ──────────────────────────────────────────────────────

    public static function position(string $pos, int $size = 20): string
    {
        return match (strtoupper($pos)) {
            'GK'  => self::svg('<rect x="2" y="9" width="5" height="12" rx="1" opacity=".4"/><rect x="17" y="9" width="5" height="12" rx="1" opacity=".4"/><rect x="2" y="7" width="20" height="3" rx="1"/><circle cx="12" cy="5" r="2"/>', 'Portiere', $size),
            'DF'  => self::svg('<path d="M12 2L3 6v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V6L12 2z" opacity=".2"/><path d="M12 2L3 6v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V6L12 2zm0 2.2l7 3.1V12c0 4.5-3.1 8.7-7 10-3.9-1.3-7-5.5-7-10V7.3l7-3.1z"/>', 'Difensore', $size),
            'MF'  => self::svg('<circle cx="12" cy="12" r="3"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M18.4 5.6l-2.8 2.8M8.4 15.6l-2.8 2.8"/>', 'Centrocampista', $size),
            'FW'  => self::svg('<circle cx="12" cy="8" r="4"/><path d="M8 14c-2.2 1-4 3-4 6h16c0-3-1.8-5-4-6" opacity=".8"/><path d="M14 14l2 4h-2l-2-3-2 3H8l2-4"/>', 'Attaccante', $size),
            // sub-positions from legacy r*.png
            'DS','RB' => self::svg('<path d="M12 2L3 6v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V6L12 2z" opacity=".15"/><path d="M12 2L3 6v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V6L12 2zm0 2.2l7 3.1V12c0 4.5-3.1 8.7-7 10-3.9-1.3-7-5.5-7-10V7.3l7-3.1z"/><text x="18" y="20" font-size="8" text-anchor="middle" fill="currentColor">S</text>', 'Terzino Sx', $size),
            'DD','LB' => self::svg('<path d="M12 2L3 6v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V6L12 2z" opacity=".15"/><path d="M12 2L3 6v6c0 5.5 3.8 10.7 9 12 5.2-1.3 9-6.5 9-12V6L12 2zm0 2.2l7 3.1V12c0 4.5-3.1 8.7-7 10-3.9-1.3-7-5.5-7-10V7.3l7-3.1z"/><text x="18" y="20" font-size="8" text-anchor="middle" fill="currentColor">D</text>', 'Terzino Dx', $size),
            'AS','RW' => self::svg('<circle cx="12" cy="8" r="4"/><path d="M8 14c-2.2 1-4 3-4 6h16c0-3-1.8-5-4-6"/><text x="19" y="22" font-size="7" text-anchor="middle" fill="currentColor">S</text>', 'Ala Sx', $size),
            'AD','LW' => self::svg('<circle cx="12" cy="8" r="4"/><path d="M8 14c-2.2 1-4 3-4 6h16c0-3-1.8-5-4-6"/><text x="5" y="22" font-size="7" text-anchor="middle" fill="currentColor">D</text>', 'Ala Dx', $size),
            default    => self::svg('<circle cx="12" cy="12" r="10" opacity=".3"/><text x="12" y="16" font-size="10" text-anchor="middle" fill="currentColor">?</text>', $pos, $size),
        };
    }

    // ── Skill / talent icons (from legacy t_*.png) ──────────────────────────

    public static function skill(string $code, int $size = 18): string
    {
        return match (strtolower($code)) {
            'velocita','velocità'   => self::svg('<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>', 'Velocità', $size),
            'dribbling'             => self::svg('<path d="M5 6a3 3 0 1 1 6 0c0 2-3 3-3 5v1"/><circle cx="8" cy="15" r="1"/><path d="M12 12c2-1.5 5-1 6 2s-1 6-4 6"/>', 'Dribbling', $size),
            'cross'                 => self::svg('<path d="M3 20l9-9m0 0l9-9M12 11l9 9M3 4l9 9"/>', 'Cross', $size),
            'potenzadeltiro','tiro' => self::svg('<circle cx="12" cy="12" r="10" opacity=".2"/><path d="M4 12h13M13 8l4 4-4 4"/>', 'Potenza del tiro', $size),
            'colpoditesta'          => self::svg('<circle cx="12" cy="7" r="5"/><path d="M9 12l3 5 3-5"/><circle cx="20" cy="4" r="3" opacity=".6"/><path d="M17 5l-2 3"/>', 'Colpo di testa', $size),
            'fiutodelgoal'          => self::svg('<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M18.4 5.6l-2.1 2.1M7.7 16.3l-2.1 2.1"/>', 'Fiuto del goal', $size),
            'creativita'            => self::svg('<path d="M12 2a7 7 0 0 1 5 11.9V17h-2v2H9v-2H7v-3.1A7 7 0 0 1 12 2zm-1 10l-2-2 1.4-1.4 1.6 1.6 4-4L17.4 7 11 13z"/>', 'Creatività', $size),
            'visionedigioco'        => self::svg('<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>', 'Visione di gioco', $size),
            'resistenza'            => self::svg('<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>', 'Resistenza', $size),
            'calcidipunizione'      => self::svg('<circle cx="12" cy="18" r="3" opacity=".4"/><path d="M12 15V8"/><path d="M8 8h8"/><path d="M5 5l3 3m8 0l3-3"/>', 'Calci di punizione', $size),
            'calciodangolo'         => self::svg('<path d="M3 3v18h18"/><path d="M3 3l15 10"/><circle cx="3" cy="3" r="2"/>', 'Calcio d\'angolo', $size),
            'rigori'                => self::svg('<rect x="6" y="6" width="12" height="8" rx="1" opacity=".3"/><circle cx="12" cy="18" r="2"/><path d="M10 18h4"/>', 'Rigori', $size),
            'pararigori'            => self::svg('<rect x="3" y="6" width="18" height="10" rx="1" opacity=".2"/><path d="M3 6h18M3 16h18"/><circle cx="12" cy="3" r="2"/><path d="M12 5v2"/>', 'Para-rigori', $size),
            'riflessifelini'        => self::svg('<path d="M12 3C7 3 3 7 3 12s4 9 9 9 9-4 9-9-4-9-9-9zm0 4l2 4h-4l2-4zm0 9a2 2 0 1 1 0-4 2 2 0 0 1 0 4z"/>', 'Riflessi felini', $size),
            'capitano'              => self::svg('<path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>', 'Capitano', $size),
            default                 => self::svg('<circle cx="12" cy="12" r="10" opacity=".3"/><text x="12" y="16" font-size="8" text-anchor="middle" fill="currentColor">?</text>', $code, $size),
        };
    }

    // ── Foot preference ─────────────────────────────────────────────────────

    public static function foot(string $foot, int $size = 16): string
    {
        $label = match ($foot) { 'R' => 'Destro', 'L' => 'Sinistro', default => 'Ambidestro' };
        $path  = match ($foot) {
            'R'  => '<path d="M16 20c0-4-2-7-4-9V4l-2 1v6c-2 2-4 5-4 9h10z"/>',
            'L'  => '<path d="M8 20c0-4 2-7 4-9V4l2 1v6c2 2 4 5 4 9H8z"/>',
            default => '<path d="M8 20c0-4 2-7 4-9V4l-1 .5V4l1 .5V4c0 0 2 5 4 7v9H8z"/>',
        };
        return self::svg($path, $label, $size);
    }
}
