<?php

declare(strict_types=1);

namespace app\components;

use app\models\Staff;
use yii\helpers\Html;

final class UiIconHelper
{
    private const SPRITE_URL = '/icons/gm-icons.svg';

    private function __construct()
    {
    }

    public static function normalizePosition(string $position): string
    {
        $p = strtoupper(trim($position));
        return match (true) {
            in_array($p, ['PO', 'GK'], true) => 'GK',
            in_array($p, ['D', 'DF', 'DS', 'DD', 'DC', 'LB', 'RB'], true) => $p === 'DS' || $p === 'RB' ? 'DS' : ($p === 'DD' || $p === 'LB' ? 'DD' : 'DF'),
            in_array($p, ['C', 'MF', 'CS', 'CD', 'CC'], true) => 'MF',
            in_array($p, ['A', 'FW', 'AS', 'AD', 'AC', 'LW', 'RW'], true) => $p === 'AS' || $p === 'RW' ? 'AS' : ($p === 'AD' || $p === 'LW' ? 'AD' : 'FW'),
            default => $p !== '' ? $p : 'UNK',
        };
    }

    public static function renderPositionIcon(string $position, int $size = 14): string
    {
        $norm = self::normalizePosition($position);
        $symbol = match ($norm) {
            'GK' => 'pos-gk',
            'DF' => 'pos-df',
            'MF' => 'pos-mf',
            'FW' => 'pos-fw',
            'DS' => 'pos-ds',
            'DD' => 'pos-dd',
            'AS' => 'pos-as',
            'AD' => 'pos-ad',
            default => 'pos-unknown',
        };

        return self::sprite($symbol, 'Posizione ' . $norm, $size);
    }

    public static function renderPositionBadge(
        string $position,
        bool $withText = true,
        int $iconSize = 12,
        string $extraStyle = ''
    ): string {
        $norm = self::normalizePosition($position);
        $colors = self::positionColors($norm);
        $text = $withText ? '<span style="margin-left:.3rem">' . Html::encode($norm) . '</span>' : '';

        return '<span style="display:inline-flex;align-items:center;justify-content:center;'
            . 'font-size:.64rem;font-weight:800;letter-spacing:.04em;'
            . 'padding:.15rem .36rem;border-radius:.38rem;white-space:nowrap;'
            . 'color:#fff;background:' . $colors['bg'] . ';border:1px solid ' . $colors['border'] . ';'
            . $extraStyle . '">'
            . self::renderPositionIcon($norm, $iconSize) . $text . '</span>';
    }

    public static function renderTalentTypeIcon(string $code, int $size = 11): string
    {
        $k = strtolower(trim($code));
        $symbol = match ($k) {
            'creativita' => 'tal-creativita',
            'resistenza' => 'tal-resistenza',
            'dribbling' => 'tal-dribbling',
            'velocita' => 'tal-velocita',
            'visione' => 'tal-visione',
            'leadership' => 'tal-leadership',
            'marcatura' => 'tal-marcatura',
            'riflessi' => 'tal-riflessi',
            'finalizzazione' => 'tal-finalizzazione',
            'disciplina' => 'tal-disciplina',
            'tenacia' => 'tal-tenacia',
            'freddezza' => 'tal-freddezza',
            'calci_piazzati' => 'tal-calci-piazzati',
            default => 'tal-generic',
        };

        return self::sprite($symbol, 'Talento ' . ($k !== '' ? $k : 'generico'), $size);
    }

    public static function renderTalentBadge(string $code, string $label, int $level, int $size = 11): string
    {
        return '<span title="' . Html::encode($label) . ' Lv' . $level . '"'
            . ' style="display:inline-flex;align-items:center;gap:.22rem;font-size:.62rem;'
            . 'background:rgba(245,158,11,.14);color:var(--gold);border:1px solid rgba(245,158,11,.35);'
            . 'padding:.08rem .26rem;border-radius:.35rem;white-space:nowrap">'
            . self::renderTalentTypeIcon($code, $size)
            . '<span style="font-weight:800">Lv' . $level . '</span>'
            . '</span>';
    }

    /**
     * Renders talent as icon only; adds a small numeric badge when level >= 2.
     */
    public static function renderTalentIcon(string $code, string $label, int $level, int $size = 13): string
    {
        $badge = $level >= 2
            ? '<sup style="font-size:.52rem;font-weight:800;color:var(--gold);line-height:1;margin-left:-.1rem">'
              . $level . '</sup>'
            : '';
        return '<span title="' . Html::encode($label) . ' Lv' . $level . '"'
            . ' style="display:inline-flex;align-items:flex-start;gap:0;cursor:default">'
            . self::renderTalentTypeIcon($code, $size)
            . $badge
            . '</span>';
    }

    public static function renderRoleIcon(string $role, int $size = 12): string
    {
        $k = strtolower(trim($role));
        $symbol = match ($k) {
            'captain' => 'role-captain',
            'penalty' => 'role-penalty',
            'freekick' => 'role-freekick',
            'corner' => 'role-corner',
            default => 'role-captain',
        };
        return self::sprite($symbol, 'Ruolo ' . $k, $size);
    }

    public static function renderStaffRoleIcon(string $role, int $size = 14): string
    {
        $symbol = match ($role) {
            Staff::ROLE_HEAD_COACH => 'staff-head-coach',
            Staff::ROLE_ASSISTANT_COACH => 'staff-assistant-coach',
            Staff::ROLE_GOALKEEPING_COACH => 'staff-goalkeeping-coach',
            Staff::ROLE_FITNESS_COACH => 'staff-fitness-coach',
            Staff::ROLE_DOCTOR => 'staff-doctor',
            Staff::ROLE_SCOUT => 'staff-scout',
            default => 'staff-generic',
        };

        return self::sprite($symbol, 'Staff ' . $role, $size);
    }

    public static function renderFootIcon(string $foot, int $size = 14): string
    {
        $f = strtoupper(trim($foot));
        $symbol = match ($f) {
            'L' => 'foot-left',
            'R' => 'foot-right',
            default => 'foot-both',
        };
        $label = match ($f) {
            'L' => 'Sinistro',
            'R' => 'Destro',
            default => 'Ambidestro',
        };

        return self::sprite($symbol, $label, $size);
    }

    public static function footLabel(string $foot): string
    {
        $f = strtoupper(trim($foot));
        return match ($f) {
            'L' => 'Sinistro',
            'R' => 'Destro',
            default => 'Ambidestro',
        };
    }

    private static function sprite(string $symbol, string $label, int $size): string
    {
        $id = preg_replace('/[^a-z0-9_-]/i', '', $symbol) ?: 'tal-generic';
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24"'
            . ' xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" aria-hidden="true"'
            . ' style="display:inline-block;vertical-align:middle;fill:currentColor">'
            . '<title>' . Html::encode($label) . '</title>'
            . '<use href="' . self::SPRITE_URL . '#' . $id . '" xlink:href="' . self::SPRITE_URL . '#' . $id . '"></use>'
            . '</svg>';
    }

    private const FLAG_CODE = [
        'ITA' => 'it', 'ESP' => 'es', 'BRA' => 'br', 'ARG' => 'ar',
        'FRA' => 'fr', 'DEU' => 'de', 'ENG' => 'gb-eng', 'PRT' => 'pt',
        'NLD' => 'nl', 'HRV' => 'hr',
    ];

    public static function flagImg(string $nationality, int $width = 20): string
    {
        if ($nationality === '') return '';
        $cc = self::FLAG_CODE[$nationality] ?? strtolower(substr($nationality, 0, 2));
        $url = 'https://flagcdn.com/w40/' . $cc . '.svg';
        return '<img src="' . Html::encode($url) . '" width="' . $width . '"'
            . ' alt="' . Html::encode($nationality) . '"'
            . ' style="border-radius:2px;vertical-align:middle;box-shadow:0 0 0 1px rgba(255,255,255,.15)">';
    }

    private static function positionColors(string $position): array
    {
        return match ($position) {
            'GK' => ['bg' => 'rgba(249,115,22,.25)', 'border' => 'rgba(249,115,22,.5)'],
            'DF', 'DS', 'DD' => ['bg' => 'rgba(59,130,246,.25)', 'border' => 'rgba(59,130,246,.5)'],
            'MF' => ['bg' => 'rgba(34,197,94,.25)', 'border' => 'rgba(34,197,94,.5)'],
            'FW', 'AS', 'AD' => ['bg' => 'rgba(239,68,68,.25)', 'border' => 'rgba(239,68,68,.5)'],
            default => ['bg' => 'rgba(148,163,184,.2)', 'border' => 'rgba(148,163,184,.45)'],
        };
    }
}
