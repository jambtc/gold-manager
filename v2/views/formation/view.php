<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\Formation $formation */
/** @var app\models\FormationSlot[] $slots */
/** @var app\models\Player[] $players */
/** @var array<string, array> $roleSummary */
/** @var array<string, array{label:string,level:int}> $trainedTactics */

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\CharacterTraitHelper;
use app\components\FormationAutoHelper;
use app\components\FormationRoleHelper;
use app\components\PlayerAttributeHelper;
use app\components\UiIconHelper;

$this->title = 'Tattica: ' . $team->name;
$this->params['breadcrumbs'][] = ['label' => 'Squadra', 'url' => ['/team/view']];
$this->params['breadcrumbs'][] = 'Tattica';

$activeSlots = [];
foreach ($slots as $slot) {
    $activeSlots[$slot->zone] = $slot;
}

$playerInSlot = [];
foreach ($slots as $s) {
    $playerInSlot[$s->player_id] = true;
}

// ── Team strength calculator ─────────────────────────────────────────
// Groups players on pitch by position, computes department averages
$deptSkills  = ['GK' => [], 'DF' => [], 'MF' => [], 'FW' => []];
$deptWeights = ['GK' => 15, 'DF' => 30, 'MF' => 30, 'FW' => 25];
foreach ($slots as $slot) {
    $p = $slot->player;
    if ($p && isset($deptSkills[$p->position])) {
        $deptSkills[$p->position][] = $p->general_skill;
    }
}
$deptAvg = [];
foreach ($deptSkills as $dept => $skills) {
    $deptAvg[$dept] = count($skills) ? round(array_sum($skills) / count($skills)) : 0;
}
$overall = 0;
$totalWeight = 0;
foreach ($deptAvg as $dept => $avg) {
    if ($avg > 0) {
        $overall     += $avg * $deptWeights[$dept];
        $totalWeight += $deptWeights[$dept];
    }
}
$overall      = $totalWeight > 0 ? (int) round($overall / $totalWeight) : 0;
$startersCount = count($slots);

$formationId = $formation->id;

$moduleOptions = FormationAutoHelper::moduleOptions();
$tacticOptions = [
    'balanced' => 'Bilanciata',
    'ultra_defensive' => 'Difensiva',
    'all_out_attack' => 'Offensiva',
];

$currentModule = '4-4-2';
if (preg_match('/(\d-\d-\d(?:-\d)?)/', (string) $formation->name, $m) === 1) {
    $maybeModule = $m[1];
    if (in_array($maybeModule, $moduleOptions, true)) {
        $currentModule = $maybeModule;
    }
}
$currentTactic = in_array((string) $formation->tactic, array_keys($tacticOptions), true)
    ? (string) $formation->tactic
    : 'balanced';
$currentMarking = in_array((string) $formation->marking, ['zone', 'man'], true)
    ? (string) $formation->marking
    : 'zone';
$currentOffside = ((int) ($formation->offside_trap ?? 1)) > 0 ? 1 : 0;
$trainedTactics = is_array($trainedTactics ?? null) ? $trainedTactics : [];
$currentTrainedTactic = (string) ($formation->trained_tactic ?? '');
if ($currentTrainedTactic === '' || !isset($trainedTactics[$currentTrainedTactic])) {
    $currentTrainedTactic = (string) (array_key_first($trainedTactics) ?? '');
}

$roleSummary = is_array($roleSummary ?? null) ? $roleSummary : (new FormationRoleHelper())->resolveRolePlayers($formation);
$roleIcons = [
    'captain'  => UiIconHelper::renderRoleIcon('captain', 12),
    'penalty'  => UiIconHelper::renderRoleIcon('penalty', 12),
    'freekick' => UiIconHelper::renderRoleIcon('freekick', 12),
    'corner'   => UiIconHelper::renderRoleIcon('corner', 12),
];
$roleLabels = [
    'captain'  => 'Capitano',
    'penalty'  => 'Rigorista',
    'freekick' => 'Punizioni',
    'corner'   => 'Angoli',
];
$effectiveRoleByPlayer = [];
foreach ($roleSummary as $role => $data) {
    $pid = isset($data['effective_id']) ? (int) $data['effective_id'] : 0;
    if ($pid > 0) {
        $effectiveRoleByPlayer[$pid][] = $role;
    }
}

$shortPitchName = static function (string $fullName): string {
    $name = trim($fullName);
    if ($name === '') {
        return '—';
    }
    $parts = preg_split('/\s+/', $name) ?: [];
    if (count($parts) <= 1) {
        return $parts[0];
    }
    $firstInitial = strtoupper(substr((string) $parts[0], 0, 1));
    $lastName = (string) $parts[count($parts) - 1];
    return $firstInitial . '. ' . $lastName;
};

// Jersey colors by position
$jerseyColors = [
    'GK' => ['body' => '#b45309', 'shine' => '#f59e0b', 'text' => '#fff'],
    'DF' => ['body' => '#1d4ed8', 'shine' => '#60a5fa', 'text' => '#fff'],
    'MF' => ['body' => '#15803d', 'shine' => '#4ade80', 'text' => '#fff'],
    'FW' => ['body' => '#b91c1c', 'shine' => '#f87171', 'text' => '#fff'],
];

/**
 * Renders a jersey SVG with the player number.
 * viewBox 0 0 44 52
 */
$jersey = function (string $position, int $number, float $scale = 1.0) use ($jerseyColors): string {
    $c     = $jerseyColors[$position] ?? $jerseyColors['MF'];
    $body  = $c['body'];
    $shine = $c['shine'];
    $text  = $c['text'];
    $w     = round(44 * $scale);
    $h     = round(52 * $scale);
    return <<<SVG
<svg width="{$w}" height="{$h}" viewBox="0 0 44 52" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="jg{$number}{$position}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$shine}"/>
      <stop offset="100%" stop-color="{$body}"/>
    </linearGradient>
  </defs>
  <!-- shadow -->
  <ellipse cx="22" cy="51" rx="13" ry="1.5" fill="rgba(0,0,0,0.35)"/>
  <!-- left sleeve -->
  <path d="M1,11 L4,22 L15,19 L13,6" fill="url(#jg{$number}{$position})" stroke="rgba(0,0,0,0.25)" stroke-width="0.6"/>
  <!-- right sleeve -->
  <path d="M43,11 L40,22 L29,19 L31,6" fill="url(#jg{$number}{$position})" stroke="rgba(0,0,0,0.25)" stroke-width="0.6"/>
  <!-- body -->
  <path d="M13,6 L15,19 L12,48 L32,48 L29,19 L31,6 C26,10 18,10 13,6 Z" fill="url(#jg{$number}{$position})" stroke="rgba(0,0,0,0.25)" stroke-width="0.6"/>
  <!-- collar V -->
  <path d="M17,5 L22,13 L27,5" fill="{$body}" stroke="rgba(0,0,0,0.4)" stroke-width="0.8"/>
  <!-- number -->
  <text x="22" y="35" text-anchor="middle" dominant-baseline="middle"
        fill="{$text}" font-size="11" font-weight="900"
        font-family="system-ui,-apple-system,sans-serif"
        style="text-shadow:0 1px 2px rgba(0,0,0,0.5)">{$number}</text>
</svg>
SVG;
};
?>

<div class="formation-view">
    <div class="row g-4">
        <!-- ── Lista giocatori (sx) ─── -->
        <div class="col-lg-4">
            <div class="gm-card h-100 d-flex flex-column" id="player-list-panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0 text-white fw-bold">Forza squadra</h2>
                    <span class="badge-gm px-2 py-1" id="count-badge"
                        style="background:rgba(245,158,11,.15);color:var(--gold);border:1px solid rgba(245,158,11,.3);font-size:.72rem">
                        <?= $overall > 0 ? $overall : '—' ?>
                    </span>
                </div>
                <!-- Team strength calculator -->
                <div>
                    <?php foreach (
                        [
                            ['GK', 'Portiere',   'var(--accent-red)',   '#ea580c'],
                            ['DF', 'Difesa',      'var(--accent-blue)',  '#2563eb'],
                            ['MF', 'Centrocampo', 'var(--accent-green)', '#15803d'],
                            ['FW', 'Attacco',    'var(--accent-red)',   '#b91c1c'],
                        ] as [$dept, $label, $color, $barColor]
                    ): ?>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span style="font-size:.65rem;width:66px;color:var(--text-secondary)"><?= $label ?></span>
                            <div style="flex:1;background:rgba(255,255,255,.06);border-radius:3px;height:6px;overflow:hidden">
                                <div style="height:100%;width:<?= $deptAvg[$dept] ?>%;background:<?= $barColor ?>;border-radius:3px;transition:width .4s ease"></div>
                            </div>
                            <span style="font-size:.7rem;font-weight:700;min-width:22px;text-align:right;color:<?= $deptAvg[$dept] > 0 ? $color : 'var(--text-secondary)' ?>">
                                <?= $deptAvg[$dept] > 0 ? $deptAvg[$dept] : '—' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="h5 mb-0 text-white fw-bold">Rosa</h2>
                    <span class="badge-gm px-2 py-1" id="count-badge"
                        style="background:rgba(245,158,11,.15);color:var(--gold);border:1px solid rgba(245,158,11,.3);font-size:.72rem">
                        <?= $startersCount ?>/11
                    </span>
                </div>

                <div id="selection-status" class="mb-2">
                    <div style="padding:.4rem .8rem;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.5rem;font-size:.75rem;color:var(--text-secondary)">
                        <i class="bi bi-hand-index"></i> Trascina o clicca dopo aver selezionato una zona
                    </div>
                </div>

                <!-- Drop zone: trascina qui per rimuovere dal campo -->
                <div id="list-drop-zone"
                    ondragover="event.preventDefault();this.classList.add('list-dz-active')"
                    ondragleave="this.classList.remove('list-dz-active')"
                    ondrop="window.dropOnList(event);this.classList.remove('list-dz-active')"
                    class="player-list overflow-auto flex-grow-1"
                    style="max-height:560px;scrollbar-width:thin;scrollbar-color:var(--gold) transparent;
                            border:2px dashed transparent;border-radius:.6rem;transition:border-color .15s,background .15s;padding:2px">
                    <?php
                    $roleGroups = [
                        'GK' => 'Portieri',
                        'DF' => 'Difensori',
                        'MF' => 'Centrocampisti',
                        'FW' => 'Attaccanti',
                    ];
                    $groupedPlayers = [];
                    foreach ($players as $player) {
                        $key = array_key_exists($player->position, $roleGroups) ? $player->position : 'OTHER';
                        $groupedPlayers[$key][] = $player;
                    }
                    $sorted = [];
                    foreach (array_keys($roleGroups) as $roleCode) {
                        foreach ($groupedPlayers[$roleCode] ?? [] as $p) {
                            $sorted[] = $p;
                        }
                    }
                    foreach ($groupedPlayers['OTHER'] ?? [] as $p) {
                        $sorted[] = $p;
                    }
                    $currentGroup = null;
                    ?>
                    <?php
                    // ── SIP-0032: helpers ──────────────────────────────────
                    $statBar = function (int $val, string $color): string {
                        return '<div style="background:rgba(255,255,255,.08);border-radius:2px;height:3px;overflow:hidden">'
                            . '<div style="width:' . $val . '%;height:100%;background:' . $color . ';border-radius:2px"></div>'
                            . '</div>';
                    };
                    $skillBadge = function (string $icon, string $label): string {
                        return '<span title="' . $label . '" style="font-size:.6rem;background:rgba(255,255,255,.07);'
                            . 'border:1px solid rgba(255,255,255,.15);border-radius:.25rem;padding:.05rem .3rem">'
                            . $icon . '</span>';
                    };
                    $suggestZone = function (\app\models\Player $p): array {
                        if ($p->skill_po > 70) return ['🥅', 'Portiere'];
                        if ($p->skill_df >= max($p->skill_pa, $p->skill_tr) && $p->skill_tc >= 45) return ['🛡️', 'Difensore'];
                        if ($p->skill_rg >= 58 && $p->skill_pa >= 52) return ['⚙️', 'Regia/Mezzala'];
                        if ($p->skill_cr >= 58) return ['⚡', 'Ala (fascia)'];
                        if ($p->skill_tr >= 58) return ['⚽', 'Attaccante'];
                        return ['🔄', 'Jolly'];
                    };
                    ?>
                    <?php foreach ($sorted as $player):
                        $groupKey = array_key_exists($player->position, $roleGroups) ? $player->position : 'OTHER';
                        if ($groupKey !== $currentGroup) {
                            $currentGroup = $groupKey;
                            $groupLabel = $groupKey === 'OTHER' ? 'Altri ruoli' : $roleGroups[$groupKey];
                            echo '<div class="text-muted-gm mt-2 mb-1" style="font-size:.65rem;letter-spacing:.08em;text-transform:uppercase">'
                                . \yii\helpers\Html::encode($groupLabel)
                                . '</div>';
                        }
                        $inFormation = isset($playerInSlot[$player->id]);
                        $formColor   = $player->form >= 80 ? 'var(--accent-green)' : ($player->form >= 55 ? 'var(--gold)' : 'var(--accent-red)');
                        $freshColor  = $player->freshness >= 80 ? 'var(--accent-blue)' : ($player->freshness >= 55 ? 'var(--gold)' : 'var(--accent-red)');
                        $condColor   = $player->condition >= 80 ? '#f97316' : ($player->condition >= 55 ? 'var(--gold)' : 'var(--accent-red)');
                        [$sugIcon, $sugLabel] = $suggestZone($player);
                        // Talenti centralizzati (max 2, livello 1..3)
                        $badges = '';
                        foreach (PlayerAttributeHelper::talents($player, 2) as $talent) {
                            $badges .= $skillBadge(
                                UiIconHelper::renderTalentTypeIcon((string) ($talent['code'] ?? ''), 10),
                                (string) $talent['label'] . ' Lv' . (int) $talent['level']
                            );
                        }
                    ?>
                        <div draggable="true"
                            id="player-btn-<?= $player->id ?>"
                            data-player-id="<?= $player->id ?>"
                            onclick="window.assignPlayer(<?= $player->id ?>)"
                            ondragstart="window.dragFromList(event,<?= $player->id ?>)"
                            style="width:100%;text-align:left;
                                background:<?= $inFormation ? 'rgba(245,158,11,0.06)' : 'rgba(255,255,255,0.02)' ?>;
                                border:1px solid <?= $inFormation ? 'rgba(245,158,11,0.25)' : 'var(--border)' ?>;
                                border-radius:.6rem;padding:.5rem .65rem;margin-bottom:.3rem;
                                cursor:grab;display:flex;align-items:center;gap:.5rem;
                                <?= $inFormation ? 'opacity:.65' : '' ?>">
                            <div style="flex-shrink:0"><?= $jersey($player->position, (int)$player->number, 0.5) ?></div>
                            <div style="flex:1;min-width:0">
                                <!-- Row 1: position badge + name + check -->
                                <div style="display:flex;align-items:center;gap:.35rem;margin-bottom:.1rem">
                                    <?= UiIconHelper::renderPositionBadge((string) $player->position, true, 10, 'font-size:.53rem;padding:.08rem .3rem') ?>
                                    <span style="font-weight:700;font-size:.78rem;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Html::encode($player->name) ?></span>
                                    <?php if ($inFormation): ?><i class="bi bi-check-circle-fill" style="color:var(--gold);font-size:.6rem;flex-shrink:0"></i><?php endif; ?>
                                </div>
                                <!-- Row 2: stat bars -->
                                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:.25rem .5rem;margin-bottom:.15rem">
                                    <div>
                                        <div style="display:flex;justify-content:space-between;font-size:.55rem;color:var(--text-secondary);margin-bottom:1px">
                                            <span>Forma</span><span style="color:<?= $formColor ?>"><?= $player->form ?>%</span>
                                        </div>
                                        <?= $statBar($player->form, $formColor) ?>
                                    </div>
                                    <div>
                                        <div style="display:flex;justify-content:space-between;font-size:.55rem;color:var(--text-secondary);margin-bottom:1px">
                                            <span>Fresc.</span><span style="color:<?= $freshColor ?>"><?= $player->freshness ?>%</span>
                                        </div>
                                        <?= $statBar($player->freshness, $freshColor) ?>
                                    </div>
                                    <div>
                                        <div style="display:flex;justify-content:space-between;font-size:.55rem;color:var(--text-secondary);margin-bottom:1px">
                                            <span>Cond.</span><span style="color:<?= $condColor ?>"><?= $player->condition ?>%</span>
                                        </div>
                                        <?= $statBar($player->condition, $condColor) ?>
                                    </div>
                                </div>
                                <!-- Row 3: age/foot, suggestion, badges -->
                                <div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap">
                                    <span style="font-size:.6rem;color:var(--text-secondary)"><?= $player->age ?>a · <?= $player->foot === 'LR' ? 'Amb' : $player->foot ?></span>
                                    <?php if (!empty($player->character)): ?>
                                        <span title="<?= Html::encode(CharacterTraitHelper::matchTooltip((string) $player->character)) ?>" style="font-size:.6rem;background:rgba(255,255,255,.06);border-radius:.2rem;padding:.05rem .3rem;color:var(--text-secondary)">
                                            🧠 <?= Html::encode(CharacterTraitHelper::display((string) $player->character)) ?>
                                        </span>
                                    <?php endif; ?>
                                    <span title="Zona suggerita: <?= $sugLabel ?>" style="font-size:.6rem;background:rgba(255,255,255,.06);border-radius:.2rem;padding:.05rem .3rem;color:var(--text-secondary)"><?= $sugIcon ?> <?= $sugLabel ?></span>
                                    <?= $badges ?>
                                    <?php if (($player->injury_weeks ?? 0) > 0): ?>
                                        <?php $injLabel = match ($player->injury_type) {
                                            'medio' => '🩹',
                                            'grave' => '🚑',
                                            default => '🏥'
                                        }; ?>
                                        <span title="Infortunato: <?= $player->injury_type ?> (<?= $player->injury_weeks ?> sett.)" style="font-size:.6rem;background:rgba(220,38,38,.18);color:#fca5a5;border-radius:.2rem;padding:.05rem .35rem;font-weight:700"><?= $injLabel ?> <?= $player->injury_weeks ?>sett.</span>
                                    <?php elseif (($player->suspended_matches ?? 0) > 0): ?>
                                        <span title="Squalificato per <?= $player->suspended_matches ?> gara/e" style="font-size:.6rem;background:rgba(220,38,38,.18);color:#f87171;border-radius:.2rem;padding:.05rem .35rem;font-weight:700">🚫 SQ</span>
                                    <?php elseif (($player->yellow_cards ?? 0) >= 2): ?>
                                        <span title="<?= $player->yellow_cards ?> gialli stagionali — a rischio squalifica" style="font-size:.6rem;background:rgba(245,158,11,.15);color:#fbbf24;border-radius:.2rem;padding:.05rem .35rem;font-weight:700">⚠️ <?= $player->yellow_cards ?>🟨</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="flex-shrink:0;text-align:right">
                                <div style="font-weight:900;font-size:.9rem;color:var(--gold)"><?= $player->general_skill ?></div>
                                <div style="font-size:.55rem;color:var(--text-secondary)">#<?= $player->number ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                    <button class="btn btn-outline-danger w-100 btn-sm" onclick="window.clearZone()">
                        <i class="bi bi-trash3 me-1"></i>Rimuovi da zona selezionata
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Campo (dx) ─── -->
        <div class="col-lg-8">
            <div class="gm-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0 text-white">Campo di Gioco</h2>
                    <span class="badge bg-gold text-dark"><?= count($slots) ?> / 11 Giocatori</span>
                </div>
                <div class="row g-3">
                    <div class="col-xl-8">
                        <!-- Orientation label: attack -->
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-1" style="font-size:.7rem;color:rgba(255,255,255,.45);letter-spacing:.08em;text-transform:uppercase">
                            <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                            <i class="bi bi-arrow-up-circle" style="color:var(--accent-red)"></i>
                            <span style="color:var(--accent-red)">ATTACCO</span>
                            <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                        </div>

                        <div class="pitch-container">
                            <div class="pitch-grid" id="pitch-grid">
                                <div class="pitch-midline"></div>

                                <?php
                                // 7 cols × 9 rows = zones 1-63
                                // Col 4 (of 7) is the center column
                                for ($i = 1; $i <= 63; $i++):
                                    $col = (($i - 1) % 7) + 1; // 1-7
                                    $isCenter = ($col === 4);
                                ?>
                                    <div class="pitch-zone <?= $isCenter ? 'pitch-zone-center' : '' ?>"
                                        data-zone="<?= $i ?>"
                                        data-player-id="<?= isset($activeSlots[$i]) ? (int)$activeSlots[$i]->player_id : 0 ?>"
                                        onclick="window.selectZone(<?= $i ?>)"
                                        ondragover="window.zoneDragOver(event,<?= $i ?>)"
                                        ondragleave="window.zoneDragLeave(event,<?= $i ?>)"
                                        ondrop="window.dropOnZone(event,<?= $i ?>)"
                                        <?php if (isset($activeSlots[$i])): ?>
                                        draggable="true"
                                        ondragstart="window.dragFromZone(event,<?= $i ?>,<?= $activeSlots[$i]->player_id ?>)"
                                        <?php endif; ?>>
                                        <?php if (isset($activeSlots[$i])): ?>
                                            <?php $p = $activeSlots[$i]->player; ?>
                                            <div class="pitch-jersey" title="<?= Html::encode($p->name) ?> (#<?= $p->number ?>)">
                                                <?= $jersey($p->position, (int)$p->number, 0.72) ?>
                                                <?php if (!empty($effectiveRoleByPlayer[(int) $p->id] ?? [])): ?>
                                                    <div style="position:absolute;top:-2px;right:-2px;display:flex;gap:2px">
                                                        <?php foreach (($effectiveRoleByPlayer[(int) $p->id] ?? []) as $r): ?>
                                                            <span style="font-size:.52rem;line-height:1;border:1px solid rgba(245,158,11,.5);background:rgba(15,23,42,.95);color:var(--gold);border-radius:999px;padding:1px 4px;display:inline-flex;align-items:center;justify-content:center"><?= $roleIcons[$r] ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php $zoneTalents = PlayerAttributeHelper::talents($p, 2); ?>
                                                <?php if (!empty($zoneTalents)): ?>
                                                    <div style="position:absolute;left:-2px;bottom:-2px;display:flex;gap:1px">
                                                        <?php foreach ($zoneTalents as $talent): ?>
                                                            <span title="<?= Html::encode((string) $talent['label']) ?> Lv<?= (int) $talent['level'] ?>"
                                                                  style="display:inline-flex;align-items:center;justify-content:center;border:1px solid rgba(245,158,11,.45);background:rgba(15,23,42,.95);border-radius:999px;padding:1px">
                                                                <?= UiIconHelper::renderTalentTypeIcon((string) ($talent['code'] ?? ''), 9) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="player-name"><?= Html::encode($shortPitchName((string) $p->name)) ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <!-- Orientation label: defend -->
                        <div class="d-flex align-items-center justify-content-center gap-2 mt-1" style="font-size:.7rem;color:rgba(255,255,255,.45);letter-spacing:.08em;text-transform:uppercase">
                            <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                            <i class="bi bi-shield-fill" style="color:var(--accent-blue)"></i>
                            <span style="color:var(--accent-blue)">DIFESA</span>
                            <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                        </div>

                        <p class="text-center text-muted-gm mt-2 mb-0" style="font-size:.7rem">
                            <i class="bi bi-info-circle"></i> Clicca zona → seleziona giocatore
                        </p>
                    </div>
                    <div class="col-xl-4">
                        <div class="p-3 rounded-3 mb-3" style="background:rgba(255,255,255,.03);border:1px solid var(--border)">
                            <div class="text-muted-gm mb-2" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em">Impostazioni tattiche</div>
                            <div class="mb-2">
                                <label class="form-label text-muted-gm small mb-1">Modulo</label>
                                <select id="auto-module" class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <?php foreach ($moduleOptions as $module): ?>
                                        <option value="<?= Html::encode($module) ?>" <?= $currentModule === $module ? 'selected' : '' ?>>
                                            <?= Html::encode($module) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted-gm small mb-1">Stile gara</label>
                                <select id="auto-tactic" class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <?php foreach ($tacticOptions as $value => $label): ?>
                                        <option value="<?= Html::encode($value) ?>" <?= $currentTactic === $value ? 'selected' : '' ?>>
                                            <?= Html::encode($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted-gm small mb-1">Marcatura</label>
                                <select id="marking-type" class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <option value="zone" <?= $currentMarking === 'zone' ? 'selected' : '' ?>>A zona</option>
                                    <option value="man" <?= $currentMarking === 'man' ? 'selected' : '' ?>>A uomo</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted-gm small mb-1">Trappola fuorigioco</label>
                                <select id="offside-trap" class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <option value="1" <?= $currentOffside === 1 ? 'selected' : '' ?>>Sì</option>
                                    <option value="0" <?= $currentOffside === 0 ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label text-muted-gm small mb-1">Tattica allenata focus</label>
                                <select id="trained-tactic" class="form-select form-select-sm bg-dark text-white border-secondary">
                                    <?php foreach ($trainedTactics as $key => $info): ?>
                                        <option value="<?= Html::encode($key) ?>" <?= $currentTrainedTactic === $key ? 'selected' : '' ?>>
                                            <?= Html::encode($info['label']) ?> (<?= (int) $info['level'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3" style="display:flex;flex-wrap:wrap;gap:.3rem">
                                <?php foreach ($trainedTactics as $key => $info): ?>
                                    <span style="font-size:.62rem;border:1px solid <?= $currentTrainedTactic === $key ? 'rgba(245,158,11,.55)' : 'rgba(255,255,255,.15)' ?>;color:<?= $currentTrainedTactic === $key ? 'var(--gold)' : 'var(--text-secondary)' ?>;background:<?= $currentTrainedTactic === $key ? 'rgba(245,158,11,.14)' : 'rgba(255,255,255,.03)' ?>;border-radius:.35rem;padding:.12rem .36rem">
                                        <?= Html::encode($info['label']) ?> <?= (int) $info['level'] ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-gold btn-sm" onclick="window.saveFormationSettings()">
                                    <i class="bi bi-save me-1"></i> Salva impostazioni
                                </button>
                                <button type="button" class="btn btn-gold btn-sm" onclick="window.autoAssignFormation()">
                                    <i class="bi bi-magic me-1"></i> Auto formazione
                                </button>
                            </div>
                        </div>

                        <div class="p-3 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid var(--border)">
                            <div class="text-muted-gm mb-2" style="font-size:.68rem;text-transform:uppercase;letter-spacing:.06em">Ruoli speciali</div>
                            <div id="role-hint" style="display:none;font-size:.68rem;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.4);border-radius:.4rem;padding:.3rem .5rem;color:var(--gold);margin-bottom:.5rem;text-align:center"></div>
                            <?php foreach (['captain', 'penalty', 'freekick', 'corner'] as $r):
                                $item = $roleSummary[$r] ?? [];
                                $player = $item['effective_player'] ?? null;
                                $isAuto = (bool) ($item['is_auto'] ?? false);
                            ?>
                                <div class="d-flex align-items-center justify-content-between mb-1" style="cursor:pointer"
                                    id="role-row-<?= $r ?>"
                                    onclick="window.activateRoleMode('<?= $r ?>')"
                                    title="Clicca per assegnare <?= $roleLabels[$r] ?>">
                                    <span style="font-size:.72rem;font-weight:600;color:var(--text-secondary)"
                                        id="role-btn-<?= $r ?>"><span style="display:inline-flex;align-items:center;gap:.28rem"><?= $roleIcons[$r] ?> <?= $roleLabels[$r] ?></span></span>
                                    <span style="font-size:.68rem;color:#e2e8f0;max-width:60%;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                        <?php if ($player): ?>
                                            <?= Html::encode($player->name) ?><?= $isAuto ? ' <em style="color:var(--text-secondary);font-size:.6rem">(auto)</em>' : '' ?>
                                        <?php else: ?>
                                            <span style="color:var(--text-secondary)">—</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
$saveUrl   = \yii\helpers\Url::to(['formation/save-slot']);
$autoUrl   = Url::to(['formation/auto-assign']);
$saveSettingsUrl = Url::to(['formation/save-settings']);
$setRoleUrl = Url::to(['formation/set-role']);
$csrfName  = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();
$captainRoleId  = (int) ($roleSummary['captain']['effective_id']  ?? 0);
$penaltyRoleId  = (int) ($roleSummary['penalty']['effective_id']  ?? 0);
$freekickRoleId = (int) ($roleSummary['freekick']['effective_id'] ?? 0);
$cornerRoleId   = (int) ($roleSummary['corner']['effective_id']   ?? 0);
$js = <<<JS
window._gm = {
    selectedZone : null,
    drag         : null,   // {type:'list'|'zone', playerId, fromZone}
    activeRole   : null,   // role currently awaiting pitch-click assignment
    formationId  : $formationId,
    csrfToken    : '$csrfToken',
    csrfName     : '$csrfName',
    saveUrl      : '$saveUrl',
    autoUrl      : '$autoUrl',
    saveSettingsUrl : '$saveSettingsUrl',
    setRoleUrl   : '$setRoleUrl',
    roleCurrentIds: {
        captain  : $captainRoleId,
        penalty  : $penaltyRoleId,
        freekick : $freekickRoleId,
        corner   : $cornerRoleId,
    },
};

/* ── Core save ───────────────────────────────────────────────── */
window.saveSlot = function(zoneId, playerId, cb) {
    var gm   = window._gm;
    var body = gm.csrfName + '=' + encodeURIComponent(gm.csrfToken)
             + '&formation_id=' + gm.formationId
             + '&zone=' + zoneId
             + '&player_id=' + (playerId || '');
    fetch(gm.saveUrl, {
        method  : 'POST',
        headers : { 'Content-Type': 'application/x-www-form-urlencoded' },
        body    : body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            if (cb) cb(data); else window.location.reload();
        } else {
            alert(data.message || 'Errore sconosciuto');
        }
    })
    .catch(function() { alert('Errore di rete. Riprova.'); });
};

/* ── Modalità assegnazione ruolo ─────────────────────────────── */
window.activateRoleMode = function(role) {
    var gm = window._gm;
    var roleLabels = { captain:'Capitano', penalty:'Rigorista', freekick:'Punizioni', corner:'Angoli' };
    var roleIcons  = { captain:'🛡', penalty:'⚽', freekick:'🎯', corner:'🚩' };

    if (gm.activeRole === role) {
        // second click on same role = cancel
        gm.activeRole = null;
        document.querySelectorAll('[id^="role-row-"]').forEach(function(el) {
            el.style.background = '';
            el.style.borderRadius = '';
        });
        var hint = document.getElementById('role-hint');
        if (hint) hint.style.display = 'none';
        return;
    }

    gm.activeRole = role;
    // highlight active role row
    document.querySelectorAll('[id^="role-row-"]').forEach(function(el) { el.style.background = ''; });
    var row = document.getElementById('role-row-' + role);
    if (row) { row.style.background = 'rgba(245,158,11,.12)'; row.style.borderRadius = '.4rem'; }

    var hint = document.getElementById('role-hint');
    if (hint) {
        hint.textContent = roleIcons[role] + ' Clicca un giocatore in campo per assegnarlo come ' + roleLabels[role];
        hint.style.display = 'block';
    }
};

/* ── Click sul campo ─────────────────────────────────────────── */
window.selectZone = function(zoneId) {
    var gm = window._gm;

    // If role-assign mode is active, intercept click → assign role to player in zone
    if (gm.activeRole) {
        var zoneEl = document.querySelector('.pitch-zone[data-zone="' + zoneId + '"]');
        var playerIdAttr = zoneEl ? zoneEl.getAttribute('data-player-id') : null;
        var playerId = playerIdAttr ? parseInt(playerIdAttr, 10) : 0;
        if (playerId > 0) {
            window.setRole(gm.activeRole, playerId);
        } else {
            var hint = document.getElementById('role-hint');
            if (hint) hint.textContent = '⚠️ Nessun giocatore in quella zona';
        }
        return;
    }

    gm.selectedZone = zoneId;
    document.querySelectorAll('.pitch-zone').forEach(function(z) { z.classList.remove('active'); });
    var selZone = document.querySelector('.pitch-zone[data-zone="' + zoneId + '"]');
    if (selZone) selZone.classList.add('active');
    var el = document.getElementById('selection-status');
    if (el) el.innerHTML = '<div style="padding:.35rem .8rem;background:rgba(245,158,11,.1);border:1px solid var(--gold);border-radius:.5rem;color:var(--gold);font-size:.78rem">Zona <strong>#' + zoneId + '</strong> selezionata</div>';
};

/* ── Click sulla lista ───────────────────────────────────────── */
window.assignPlayer = function(playerId) {
    if (!window._gm.selectedZone) {
        var el = document.getElementById('selection-status');
        if (el) el.innerHTML = '<div style="padding:.35rem .8rem;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.4);border-radius:.5rem;color:#f87171;font-size:.78rem">Seleziona prima una zona sul campo!</div>';
        return;
    }
    window.saveSlot(window._gm.selectedZone, playerId);
};

window.clearZone = function() {
    if (!window._gm.selectedZone) { alert('Seleziona prima una zona.'); return; }
    window.saveSlot(window._gm.selectedZone, '');
};

/* ── Drag from list ──────────────────────────────────────────── */
window.dragFromList = function(e, playerId) {
    window._gm.drag = { type: 'list', playerId: playerId, fromZone: null };
    e.dataTransfer.effectAllowed = 'move';
    e.target.style.opacity = '0.4';
    setTimeout(function() { e.target.style.opacity = ''; }, 0);
};

/* ── Drag from zone (player already on pitch) ────────────────── */
window.dragFromZone = function(e, fromZone, playerId) {
    window._gm.drag = { type: 'zone', playerId: playerId, fromZone: fromZone };
    e.dataTransfer.effectAllowed = 'move';
    e.stopPropagation();
};

/* ── Drag over / leave zone ──────────────────────────────────── */
window.zoneDragOver = function(e, zoneId) {
    if (!window._gm.drag) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    var z = document.querySelector('.pitch-zone[data-zone="' + zoneId + '"]');
    if (z) z.classList.add('drag-over');
};

window.zoneDragLeave = function(e, zoneId) {
    var z = document.querySelector('.pitch-zone[data-zone="' + zoneId + '"]');
    if (z) z.classList.remove('drag-over');
};

/* ── Drop on zone ────────────────────────────────────────────── */
window.dropOnZone = function(e, zoneId) {
    e.preventDefault();
    e.stopPropagation();
    var z = document.querySelector('.pitch-zone[data-zone="' + zoneId + '"]');
    if (z) z.classList.remove('drag-over');
    var drag = window._gm.drag;
    if (!drag) return;
    window._gm.drag = null;
    window.saveSlot(zoneId, drag.playerId);
};

/* ── Drop on list = rimuovi dal campo ───────────────────────── */
window.dropOnList = function(e) {
    e.preventDefault();
    var drag = window._gm.drag;
    if (!drag || drag.type !== 'zone') { window._gm.drag = null; return; }
    window._gm.drag = null;
    window.saveSlot(drag.fromZone, '');
};

window.autoAssignFormation = function() {
    var gm = window._gm;
    var moduleEl = document.getElementById('auto-module');
    var tacticEl = document.getElementById('auto-tactic');
    var markingEl = document.getElementById('marking-type');
    var offsideEl = document.getElementById('offside-trap');
    var trainedEl = document.getElementById('trained-tactic');
    var moduleVal = moduleEl ? moduleEl.value : '4-4-2';
    var tacticVal = tacticEl ? tacticEl.value : 'balanced';
    var markingVal = markingEl ? markingEl.value : 'zone';
    var offsideVal = offsideEl ? offsideEl.value : '1';
    var trainedVal = trainedEl ? trainedEl.value : '';

    var body = gm.csrfName + '=' + encodeURIComponent(gm.csrfToken)
        + '&formation_id=' + gm.formationId
        + '&module=' + encodeURIComponent(moduleVal)
        + '&tactic=' + encodeURIComponent(tacticVal)
        + '&marking=' + encodeURIComponent(markingVal)
        + '&offside_trap=' + encodeURIComponent(offsideVal)
        + '&trained_tactic=' + encodeURIComponent(trainedVal);

    fetch(gm.autoUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert(data.message || 'Errore auto formazione');
            return;
        }
        window.location.reload();
    })
    .catch(function() {
        alert('Errore di rete. Riprova.');
    });
};

window.saveFormationSettings = function() {
    var gm = window._gm;
    var tacticEl = document.getElementById('auto-tactic');
    var markingEl = document.getElementById('marking-type');
    var offsideEl = document.getElementById('offside-trap');
    var trainedEl = document.getElementById('trained-tactic');
    var tacticVal = tacticEl ? tacticEl.value : 'balanced';
    var markingVal = markingEl ? markingEl.value : 'zone';
    var offsideVal = offsideEl ? offsideEl.value : '1';
    var trainedVal = trainedEl ? trainedEl.value : '';

    var body = gm.csrfName + '=' + encodeURIComponent(gm.csrfToken)
        + '&formation_id=' + gm.formationId
        + '&tactic=' + encodeURIComponent(tacticVal)
        + '&marking=' + encodeURIComponent(markingVal)
        + '&offside_trap=' + encodeURIComponent(offsideVal)
        + '&trained_tactic=' + encodeURIComponent(trainedVal);

    fetch(gm.saveSettingsUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert(data.message || 'Errore salvataggio impostazioni');
            return;
        }
        window.location.reload();
    })
    .catch(function() {
        alert('Errore di rete. Riprova.');
    });
};

window.setRole = function(role, playerId) {
    var gm = window._gm;
    var current = gm.roleCurrentIds[role] || 0;
    var nextPlayerId = parseInt(current, 10) === parseInt(playerId, 10) ? '' : playerId;
    // Exit role-assign mode
    gm.activeRole = null;
    document.querySelectorAll('[id^="role-row-"]').forEach(function(el) { el.style.background = ''; });
    var hint = document.getElementById('role-hint');
    if (hint) hint.style.display = 'none';
    var body = gm.csrfName + '=' + encodeURIComponent(gm.csrfToken)
        + '&formation_id=' + gm.formationId
        + '&role=' + encodeURIComponent(role)
        + '&player_id=' + encodeURIComponent(nextPlayerId);

    fetch(gm.setRoleUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (!data.success) {
            alert(data.message || 'Errore salvataggio ruolo');
            return;
        }
        window.location.reload();
    })
    .catch(function() {
        alert('Errore di rete. Riprova.');
    });
};
JS;
$this->registerJs($js);
?>
