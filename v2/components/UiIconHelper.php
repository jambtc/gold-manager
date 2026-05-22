<?php

declare(strict_types=1);

namespace app\components;

use yii\helpers\Html;

final class UiIconHelper
{
    private function __construct()
    {
    }

    public static function normalizePosition(string $position): string
    {
        $p = strtoupper(trim($position));
        return match (true) {
            in_array($p, ['PO', 'GK'], true) => 'GK',
            in_array($p, ['D', 'DS', 'DD', 'DC', 'DF'], true) => 'DF',
            in_array($p, ['C', 'CS', 'CD', 'CC', 'MF'], true) => 'MF',
            in_array($p, ['A', 'AS', 'AD', 'AC', 'FW'], true) => 'FW',
            default => $p !== '' ? $p : '—',
        };
    }

    public static function renderPositionBadge(
        string $position,
        bool $withText = true,
        int $iconSize = 12,
        string $extraStyle = ''
    ): string {
        $norm = self::normalizePosition($position);
        $colors = self::positionColors($norm);
        $icon = self::renderPositionSvg($norm, $iconSize, $iconSize);
        $text = $withText ? '<span style="margin-left:.3rem">' . Html::encode($norm) . '</span>' : '';

        return '<span style="display:inline-flex;align-items:center;justify-content:center;'
            . 'font-size:.64rem;font-weight:800;letter-spacing:.04em;'
            . 'padding:.15rem .36rem;border-radius:.38rem;white-space:nowrap;'
            . 'color:#fff;background:' . $colors['bg'] . ';border:1px solid ' . $colors['border'] . ';'
            . $extraStyle . '">'
            . $icon . $text . '</span>';
    }

    public static function renderTalentIcon(int $size = 11): string
    {
        return self::svg(
            $size,
            $size,
            '<path d="M6 0.8 7.8 4.5 11.9 5.1 8.9 8 9.6 12 6 10.1 2.4 12 3.1 8 0.1 5.1 4.2 4.5Z" fill="#f59e0b" stroke="rgba(245,158,11,.55)" stroke-width=".35"/>'
        );
    }

    public static function renderTalentTypeIcon(string $code, int $size = 11): string
    {
        $k = strtolower(trim($code));
        $shape = match ($k) {
            'creativita' =>
                '<circle cx="6" cy="6" r="5.2" fill="#a855f7" stroke="rgba(168,85,247,.65)" stroke-width=".45"/><path d="M6 2.2v7.6M2.2 6h7.6" stroke="#fff" stroke-width="1"/>',
            'resistenza' =>
                '<rect x="1" y="1" width="10" height="10" rx="2.1" fill="#22c55e" stroke="rgba(34,197,94,.65)" stroke-width=".45"/><path d="M3.2 6h5.6" stroke="#fff" stroke-width="1.2"/>',
            'dribbling' =>
                '<circle cx="6" cy="6" r="5.2" fill="#06b6d4" stroke="rgba(6,182,212,.65)" stroke-width=".45"/><path d="M2.2 8.4c1.2-2 2.6-3.4 4.3-4.8 1 .7 1.8 1.8 3.3 2.8" stroke="#fff" stroke-width=".9" fill="none"/>',
            'velocita' =>
                '<path d="M1 6 6 .9 4.8 4.5H11L6 11l1.1-4.2H1Z" fill="#f97316" stroke="rgba(249,115,22,.65)" stroke-width=".45"/>',
            'visione' =>
                '<ellipse cx="6" cy="6" rx="5.2" ry="3.5" fill="#3b82f6" stroke="rgba(59,130,246,.65)" stroke-width=".45"/><circle cx="6" cy="6" r="1.4" fill="#fff"/>',
            'leadership' =>
                '<path d="M6 .8 7.8 4.2 11.6 4.7 8.8 7.4 9.5 11 6 9.2 2.5 11 3.2 7.4.4 4.7 4.2 4.2Z" fill="#eab308" stroke="rgba(234,179,8,.65)" stroke-width=".45"/>',
            'marcatura' =>
                '<path d="M6 .8 11.2 2.7v4.5c0 2.2-1.5 3.8-5.2 5.4C2.3 11 0.8 9.4 0.8 7.2V2.7Z" fill="#2563eb" stroke="rgba(37,99,235,.65)" stroke-width=".45"/>',
            'riflessi' =>
                '<circle cx="6" cy="6" r="5.2" fill="#14b8a6" stroke="rgba(20,184,166,.65)" stroke-width=".45"/><path d="M6 2.2v3.8l2.4 1.5" stroke="#fff" stroke-width="1"/>',
            'finalizzazione' =>
                '<circle cx="6" cy="6" r="5.2" fill="#ef4444" stroke="rgba(239,68,68,.65)" stroke-width=".45"/><circle cx="6" cy="6" r="1.6" fill="#fff"/>',
            'disciplina' =>
                '<rect x="1.2" y="1.2" width="9.6" height="9.6" rx="1.8" fill="#334155" stroke="rgba(148,163,184,.65)" stroke-width=".45"/><path d="M3.2 6.1 5.1 8 8.8 4.3" stroke="#f8fafc" stroke-width="1"/>',
            'tenacia' =>
                '<circle cx="6" cy="6" r="5.2" fill="#84cc16" stroke="rgba(132,204,22,.65)" stroke-width=".45"/><path d="M3 6h6" stroke="#fff" stroke-width="1.2"/>',
            'freddezza' =>
                '<path d="M6 .8 10.8 3.7v4.6L6 11.2 1.2 8.3V3.7Z" fill="#60a5fa" stroke="rgba(96,165,250,.65)" stroke-width=".45"/><path d="M6 2.4v7.2" stroke="#eff6ff" stroke-width=".9"/>',
            default =>
                '<path d="M6 0.8 7.8 4.5 11.9 5.1 8.9 8 9.6 12 6 10.1 2.4 12 3.1 8 0.1 5.1 4.2 4.5Z" fill="#f59e0b" stroke="rgba(245,158,11,.55)" stroke-width=".35"/>',
        };

        return self::svg($size, $size, $shape);
    }

    public static function renderRoleIcon(string $role, int $size = 12): string
    {
        $k = strtolower(trim($role));
        $shape = match ($k) {
            'captain' => '<path d="M6 .8 11.2 2.7v4.5c0 2.2-1.5 3.8-5.2 5.4C2.3 11 0.8 9.4 0.8 7.2V2.7Z" fill="#3b82f6" stroke="rgba(59,130,246,.65)" stroke-width=".45"/>',
            'penalty' => '<circle cx="6" cy="6" r="5.2" fill="#ef4444" stroke="rgba(239,68,68,.65)" stroke-width=".45"/><circle cx="6" cy="6" r="1.6" fill="#fff"/>',
            'freekick' => '<circle cx="6" cy="6" r="5.2" fill="#f59e0b" stroke="rgba(245,158,11,.65)" stroke-width=".45"/><path d="M6 2.1v7.8M2.1 6h7.8" stroke="#fff" stroke-width="1"/>',
            'corner' => '<path d="M2 11.2V.8" stroke="#22c55e" stroke-width="1"/><path d="M2 1.2 10.8 3.3 2 5.3Z" fill="#22c55e" stroke="rgba(34,197,94,.65)" stroke-width=".45"/>',
            default => '<circle cx="6" cy="6" r="5.2" fill="#94a3b8" stroke="rgba(148,163,184,.65)" stroke-width=".45"/>',
        };
        return self::svg($size, $size, $shape);
    }

    private static function renderPositionSvg(string $position, int $w, int $h): string
    {
        $shape = match ($position) {
            'GK' => '<path d="M1.2 2h2.2v8H1.2zM8.6 2h2.2v8H8.6zM3.2 3h5.6v6H3.2z" fill="#f97316" stroke="rgba(249,115,22,.65)" stroke-width=".45"/>',
            'DF' => '<path d="M6 .8 11.2 2.7v4.5c0 2.2-1.5 3.8-5.2 5.4C2.3 11 0.8 9.4 0.8 7.2V2.7Z" fill="#3b82f6" stroke="rgba(59,130,246,.65)" stroke-width=".45"/>',
            'MF' => '<path d="M3 1.2h6l3 4.8-3 4.8H3L0 6z" fill="#22c55e" stroke="rgba(34,197,94,.65)" stroke-width=".45"/>',
            'FW' => '<circle cx="6" cy="6" r="5.2" fill="#ef4444" stroke="rgba(239,68,68,.65)" stroke-width=".45"/><circle cx="6" cy="6" r="2.1" fill="#fff"/>',
            default => '<circle cx="6" cy="6" r="5.2" fill="#94a3b8" stroke="rgba(148,163,184,.65)" stroke-width=".45"/>',
        };
        return self::svg($w, $h, $shape);
    }

    private static function positionColors(string $position): array
    {
        return match ($position) {
            'GK' => ['bg' => 'rgba(249,115,22,.25)', 'border' => 'rgba(249,115,22,.5)'],
            'DF' => ['bg' => 'rgba(59,130,246,.25)', 'border' => 'rgba(59,130,246,.5)'],
            'MF' => ['bg' => 'rgba(34,197,94,.25)', 'border' => 'rgba(34,197,94,.5)'],
            'FW' => ['bg' => 'rgba(239,68,68,.25)', 'border' => 'rgba(239,68,68,.5)'],
            default => ['bg' => 'rgba(148,163,184,.2)', 'border' => 'rgba(148,163,184,.45)'],
        };
    }

    private static function svg(int $w, int $h, string $content): string
    {
        return '<svg width="' . $w . '" height="' . $h . '" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="display:inline-block;vertical-align:middle">'
            . $content
            . '</svg>';
    }
}
