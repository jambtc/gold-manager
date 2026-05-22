<?php

declare(strict_types=1);

namespace app\components;

use app\models\Fixture;
use app\models\MatchEvent;
use app\models\Team;
use yii\helpers\Html;

final class FixtureViewHelper
{
    public const LIVE_SIGNIFICANT_TYPES = [
        'pre_match',
        'goal',
        'gk_save',
        'near_miss',
        'substitution',
        'tactic_change',
        'half_time',
        'full_time',
        'kickoff',
        'second_half_start',
    ];

    private function __construct()
    {
    }

    public static function ensureUiColors(Fixture $fixture): void
    {
        if ($fixture->homeTeam) {
            $fixture->homeTeam->ensureUiColors();
        }
        if ($fixture->awayTeam) {
            $fixture->awayTeam->ensureUiColors();
        }
    }

    public static function ensurePreMatchEvent(Fixture $fixture): void
    {
        if ((int) $fixture->status === Fixture::STATUS_FINISHED) {
            return;
        }

        $preExists = MatchEvent::find()
            ->where(['fixture_id' => $fixture->id, 'type' => 'pre_match'])
            ->exists();
        if ($preExists) {
            return;
        }

        try {
            $weather = ['soleggiato', 'nuvoloso', 'piovoso', 'ventoso'][mt_rand(0, 3)];
            $cap = $fixture->homeTeam->stadium?->capacity ?? 20000;
            $spectators = mt_rand((int) ($cap * 0.55), $cap);
            $home = (string) $fixture->homeTeam->name;
            $away = (string) $fixture->awayTeam->name;

            $pre = new MatchEvent();
            $pre->fixture_id = $fixture->id;
            $pre->minute = 0;
            $pre->type = 'pre_match';
            $pre->team_side = 'home';
            $pre->detail = json_encode([
                'description' => "{$home} e {$away} pronti a scendere in campo. {$spectators} spettatori allo stadio. Meteo: {$weather}.",
                'weather' => $weather,
                'spectators' => $spectators,
            ], JSON_UNESCAPED_UNICODE);
            $pre->save(false);
        } catch (\Throwable) {
            // Race condition: another request created pre_match meanwhile.
        }
    }

    /**
     * @return MatchEvent[]
     */
    public static function loadLiveSeedEvents(int $fixtureId): array
    {
        return MatchEvent::find()
            ->where(['fixture_id' => $fixtureId])
            ->andWhere(['type' => self::LIVE_SIGNIFICANT_TYPES])
            ->orderBy(['id' => SORT_DESC])
            ->all();
    }

    public static function computeStrength(Team $team): array
    {
        $players = $team->players;
        usort($players, static fn($a, $b) => $b->general_skill <=> $a->general_skill);
        $best = array_slice($players, 0, 11);
        $dept = ['GK' => [], 'DF' => [], 'MF' => [], 'FW' => []];
        foreach ($best as $player) {
            if (isset($dept[$player->position])) {
                $dept[$player->position][] = $player->general_skill;
            }
        }
        $avg = static fn(array $scores) => count($scores) ? (int) round(array_sum($scores) / count($scores)) : 0;

        return [
            'po' => $avg($dept['GK']),
            'def' => $avg($dept['DF']),
            'mid' => $avg($dept['MF']),
            'att' => $avg($dept['FW']),
            'ovr' => count($best) ? (int) round(array_sum(array_column($best, 'general_skill')) / count($best)) : 0,
        ];
    }

    public static function renderShieldSvg(
        string $leftColor,
        string $rightColor,
        string $letter,
        int $width = 36,
        int $height = 40,
        string $prefix = 'shield'
    ): string {
        static $id = 0;
        $id++;
        $clipLeft = $prefix . 'L' . $id;
        $clipRight = $prefix . 'R' . $id;
        $safeLetter = Html::encode($letter);

        return '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 36 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
            . '<defs>'
            . '<clipPath id="' . $clipLeft . '"><rect x="0" y="0" width="18" height="40"/></clipPath>'
            . '<clipPath id="' . $clipRight . '"><rect x="18" y="0" width="18" height="40"/></clipPath>'
            . '</defs>'
            . '<path d="M18 2 L34 8 L34 22 Q34 34 18 39 Q2 34 2 22 L2 8 Z" fill="' . $leftColor . '" clip-path="url(#' . $clipLeft . ')" opacity=".95"/>'
            . '<path d="M18 2 L34 8 L34 22 Q34 34 18 39 Q2 34 2 22 L2 8 Z" fill="' . $rightColor . '" clip-path="url(#' . $clipRight . ')" opacity=".95"/>'
            . '<path d="M18 2 L34 8 L34 22 Q34 34 18 39 Q2 34 2 22 L2 8 Z" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="1.2"/>'
            . '<path d="M18 5 L31 10 L31 22 Q31 32 18 37 Q5 32 5 22 L5 10 Z" fill="rgba(255,255,255,.08)"/>'
            . '<text x="18" y="24" text-anchor="middle" dominant-baseline="middle" fill="#fff" font-weight="900" font-size="13" font-family="system-ui">' . $safeLetter . '</text>'
            . '</svg>';
    }

    public static function renderStrengthBar(string $label, int $value): string
    {
        $color = $value >= 65 ? 'var(--accent-green)' : ($value >= 50 ? 'var(--gold)' : 'var(--text-secondary)');
        $safeLabel = Html::encode($label);
        $shownValue = $value > 0 ? (string) $value : '—';

        return '<div style="display:flex;align-items:center;gap:.3rem;white-space:nowrap">'
            . '<span style="font-size:.58rem;color:var(--text-secondary);min-width:22px">' . $safeLabel . '</span>'
            . '<div style="background:rgba(255,255,255,.1);border-radius:2px;height:3px;flex:1;min-width:20px">'
            . '<div style="height:100%;width:' . max(0, min(100, $value)) . '%;background:' . $color . ';border-radius:2px"></div></div>'
            . '<span style="font-size:.6rem;font-weight:700;color:' . $color . ';min-width:18px;text-align:right">' . $shownValue . '</span>'
            . '</div>';
    }
}
