<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Player $player */
/** @var app\models\Contract|null $contract */
/** @var app\components\PlayerValuator $valuator */
/** @var app\models\Team|null $myTeam */
/** @var bool $isMyPlayer */
/** @var array<string,mixed>|false $seasonStats */
/** @var int $season */
/** @var string $backUrl */
/** @var app\models\Transfer|null $activeTransfer */
/** @var array<string, array{official_credits:int, friendly_credits:int, bonus:float}> $quadrantHeatmap */
/** @var array<int, array{official_credits:int, friendly_credits:int, bonus:float}> $cellHeatmap */

use yii\helpers\Html;
use app\components\PlayerAttributeHelper;
use app\components\UiIconHelper;
use app\components\QuadrantHelper;
use app\components\PitchZoneHelper;

$this->title = $player->name;
$this->params['breadcrumbs'][] = ['label' => 'Squadra', 'url' => ['/team/view']];
$this->params['breadcrumbs'][] = $this->title;

$skillColor = fn(int $v): string => $v >= 75 ? 'var(--accent-green)' : ($v >= 50 ? 'var(--gold)' : 'var(--accent-red)');

$posColors = ['GK' => '#ea580c', 'DF' => '#2563eb', 'MF' => '#15803d', 'FW' => '#b91c1c'];
$posColor  = $posColors[$player->position] ?? '#f59e0b';

$footLabel = match ($player->foot) {
    'R'  => 'Destro',
    'L'  => 'Sinistro',
    'LR' => 'Ambidestro',
    default => $player->foot,
};

$pctCard = static function (string $label, int $val, string $color): string {
    $val = max(1, min(100, $val));
    return '<div style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.75rem;padding:.65rem .7rem">'
        . '<div style="font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary);text-align:center">'
        . Html::encode($label)
        . '</div>'
        . '<div style="font-size:2rem;line-height:1.05;font-weight:900;color:' . $color . ';text-align:center;margin:.2rem 0 .4rem 0">'
        . $val
        . '</div>'
        . '<div style="background:rgba(255,255,255,.12);border-radius:4px;height:5px;overflow:hidden">'
        . '<div style="width:' . $val . '%;height:100%;background:' . $color . ';border-radius:4px"></div>'
        . '</div>'
        . '</div>';
};

$formColor = $player->form >= 80 ? 'var(--accent-green)' : ($player->form >= 55 ? 'var(--gold)' : 'var(--accent-red)');
$freshColor = $player->freshness >= 80 ? 'var(--accent-blue)' : ($player->freshness >= 55 ? 'var(--gold)' : 'var(--accent-red)');
$condColor = $player->condition >= 80 ? '#f97316' : ($player->condition >= 55 ? 'var(--gold)' : 'var(--accent-red)');
$technicalAttrs = PlayerAttributeHelper::technicalAttributes($player);
$playerTalents  = PlayerAttributeHelper::talents($player, 2);
$playerFootIcon = UiIconHelper::renderFootIcon((string) $player->foot, 16);
$trainingLogs = Yii::$app->db->createCommand(
    'SELECT week, season, stat, xp_gained, new_value, created_at
     FROM {{%training_log}}
     WHERE player_id=:p
     ORDER BY id DESC
     LIMIT 12',
    [':p' => (int) $player->id]
)->queryAll();
?>

<div class="player-view">

    <?php if (!empty($backUrl)): ?>
        <div class="mb-3">
            <a href="<?= Html::encode($backUrl) ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Torna indietro
            </a>
        </div>
    <?php endif; ?>

    <!-- Hero -->
    <div class="player-hero mb-4">
        <div class="row align-items-center position-relative" style="z-index:1">

            <!-- Jersey + skill badge -->
            <div class="col-md-auto text-center mb-3 mb-md-0 me-md-3">
                <div style="position:relative;display:inline-block">
                    <!-- Big jersey SVG -->
                    <svg width="90" height="104" viewBox="0 0 44 52" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="jgh" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="<?= $posColor ?>cc" />
                                <stop offset="100%" stop-color="<?= $posColor ?>" />
                            </linearGradient>
                        </defs>
                        <ellipse cx="22" cy="51" rx="13" ry="1.5" fill="rgba(0,0,0,0.35)" />
                        <path d="M1,11 L4,22 L15,19 L13,6" fill="url(#jgh)" stroke="rgba(0,0,0,0.25)" stroke-width="0.6" />
                        <path d="M43,11 L40,22 L29,19 L31,6" fill="url(#jgh)" stroke="rgba(0,0,0,0.25)" stroke-width="0.6" />
                        <path d="M13,6 L15,19 L12,48 L32,48 L29,19 L31,6 C26,10 18,10 13,6 Z" fill="url(#jgh)" stroke="rgba(0,0,0,0.25)" stroke-width="0.6" />
                        <path d="M17,5 L22,13 L27,5" fill="<?= $posColor ?>aa" stroke="rgba(0,0,0,0.4)" stroke-width="0.8" />
                        <text x="22" y="35" text-anchor="middle" dominant-baseline="middle" fill="#fff" font-size="11" font-weight="900" font-family="system-ui,sans-serif"><?= $player->number ?></text>
                    </svg>
                    <!-- Skill circle -->
                    <div style="position:absolute;top:-8px;right:-12px;width:36px;height:36px;border-radius:50%;background:var(--gold);display:flex;align-items:center;justify-content:center;font-weight:900;font-size:.9rem;color:#000;border:2px solid rgba(0,0,0,0.3)">
                        <?= $player->general_skill ?>
                    </div>
                </div>
            </div>

            <!-- Name + badges -->
            <div class="col-md">
                <div class="text-muted-gm small mb-1" style="letter-spacing:.05em;text-transform:uppercase">
                    <?= Html::encode($player->team->name ?? 'Svincolato') ?>
                </div>
                <h1 class="fw-black mb-2" style="font-size:2rem;letter-spacing:-.03em"><?= Html::encode($player->name) ?></h1>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.2rem .55rem;border-radius:.5rem;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.03);font-size:.72rem;font-weight:700;letter-spacing:.06em">
                        <?= UiIconHelper::renderPositionIcon((string) $player->position, 20) ?>
                        <span><?= Html::encode(strtoupper((string) $player->position)) ?></span>
                    </span>
                    <span class="text-muted-gm small"><i class="bi bi-calendar3"></i> <?= $player->age ?> anni</span>
                    <span class="text-muted-gm small" style="display:inline-flex;align-items-center;gap:.3rem">
                        <?= $playerFootIcon ?> <?= Html::encode($footLabel) ?>
                    </span>
                    <span class="text-muted-gm small" style="font-style:italic"><?= Html::encode(ucfirst($player->character ?? '')) ?></span>
                </div>
            </div>

            <!-- Market value -->
            <div class="col-md-auto text-md-end mt-3 mt-md-0">
                <div class="text-gold fw-black" style="font-size:1.6rem">
                    €<?= number_format($valuator->marketValue($player), 0, ',', '.') ?>
                </div>
                <div class="text-muted-gm" style="font-size:.72rem">Valore di mercato</div>
            </div>
        </div>
    </div>

    <?php
    // ── SIP-0068 helpers (shared across columns) ──────────────────────────
    $qBonusMap = [];
    foreach ($quadrantHeatmap as $q => $qd) {
        $qBonusMap[$q] = (float) $qd['bonus'];
    }
    $heatCellVal = function (int $displayZone) use ($player, $cellHeatmap, $qBonusMap): array {
        $engineZone = PitchZoneHelper::normalizeDisplayZone($displayZone);
        $quadrant   = QuadrantHelper::zoneToQuadrant($engineZone);
        $base       = round($player->getOverallForPosition($engineZone), 1);
        $bonusQ     = round($qBonusMap[$quadrant] ?? 0.0, 1);
        $bonusC     = round((float) ($cellHeatmap[$displayZone]['bonus'] ?? 0.0), 1);
        $total      = round(max(1.0, min(99.0, $base + $bonusQ + $bonusC)), 1);
        return ['base' => $base, 'bonusQ' => $bonusQ, 'bonusC' => $bonusC, 'total' => $total];
    };
    $cellColor = static function (float $v): string {
        if ($v >= 70) return 'var(--accent-green)';
        if ($v >= 45) return 'var(--gold)';
        if ($v >= 25) return '#60a5fa';
        return 'rgba(255,255,255,.35)';
    };
    $cellBgAlpha = static function (float $v): string {
        $a = max(0.0, min(0.35, ($v / 99) * 0.35));
        if ($v >= 70) return 'rgba(34,197,94,' . round($a, 3) . ')';
        if ($v >= 45) return 'rgba(245,158,11,' . round($a, 3) . ')';
        if ($v >= 25) return 'rgba(96,165,250,' . round($a, 3) . ')';
        return 'transparent';
    };
    ?>

    <div class="row g-4 align-items-start">

        <!-- ── COL 3: Attributi Tecnici ─────────────────────────────────── -->
        <div class="col-lg-3">

            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-bar-chart text-gold me-2"></i>Attributi</h3>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.8rem">
                    <?php foreach ($technicalAttrs as $attr):
                        $label = (string) $attr['label'];
                        $val   = (int)   $attr['value'];
                    ?>
                        <div class="skill-item">
                            <span class="skill-label"><?= $label ?></span>
                            <span class="skill-value" style="color:<?= $skillColor($val) ?>"><?= $val ?></span>
                            <div class="skill-bar mt-2">
                                <div class="skill-progress" style="width:<?= $val ?>%;background:<?= $skillColor($val) ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Forma / Freschezza / Condizione / XP -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem;margin-bottom:.8rem">
                    <?= $pctCard('Forma',      (int) $player->form,      $formColor)  ?>
                    <?= $pctCard('Freschezza', (int) $player->freshness, $freshColor) ?>
                    <?= $pctCard('Condizione', (int) $player->condition, $condColor)  ?>
                    <?= $pctCard('XP',         min(100, (int) $player->experience),  'var(--text-secondary)') ?>
                </div>

                <!-- Talenti -->
                <?php if (!empty($playerTalents)): ?>
                    <div style="border-top:1px solid var(--border);padding-top:.7rem">
                        <div style="font-size:.7rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary);margin-bottom:.5rem">Talenti</div>
                        <div style="display:flex;flex-direction:column;gap:.4rem">
                            <?php foreach ($playerTalents as $talent):
                                $tc = (string) $talent['code'];
                                $tl = (string) $talent['label'];
                                $tv = max(1, min(3, (int) $talent['level']));
                                $tp = (int) round(($tv / 3) * 100);
                                $tkc = $skillColor($tp);
                            ?>
                                <div>
                                    <div style="display:flex;align-items:center;gap:.35rem;font-size:.68rem;margin-bottom:.2rem">
                                        <?= UiIconHelper::renderTalentTypeIcon($tc, 13) ?>
                                        <span style="color:var(--text-secondary);flex:1"><?= Html::encode($tl) ?></span>
                                    </div>
                                    <div style="background:rgba(255,255,255,.12);border-radius:4px;height:5px;overflow:hidden">
                                        <div style="width:<?= $tp ?>%;height:100%;background:<?= $tkc ?>;border-radius:4px"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <!-- ── COL 6: Esperienza in campo ──────────────────────────────── -->
        <div class="col-lg-6">
            <div class="gm-card h-100">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-map text-gold me-2"></i>Esperienza in campo</h3>

                <div class="d-flex align-items-center justify-content-center gap-2 mb-1"
                    style="font-size:.7rem;color:rgba(255,255,255,.45);letter-spacing:.08em;text-transform:uppercase">
                    <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                    <i class="bi bi-arrow-up-circle" style="color:var(--accent-red)"></i>
                    <span style="color:var(--accent-red)">ATTACCO</span>
                    <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                </div>

                <div class="pitch-container">
                    <div class="pitch-grid">
                        <?php for ($legRow = 1; $legRow <= 9; $legRow++): ?>
                            <?php for ($col = 1; $col <= 7; $col++): ?>
                                <?php
                                $dz  = ($legRow - 1) * 7 + $col;
                                $cv  = $heatCellVal($dz);
                                $clr = $cellColor($cv['total']);
                                $bg  = $cellBgAlpha($cv['total']);
                                $tip = 'Base: ' . $cv['base'] . ' | Q:+' . $cv['bonusQ'] . ' | C:+' . $cv['bonusC'] . ' | Tot: ' . $cv['total'];
                                $centerCls = ($col >= 3 && $col <= 5) ? ' pitch-zone-center' : '';
                                ?>
                                <div class="pitch-zone<?= $centerCls ?>"
                                    title="<?= Html::encode($tip) ?>"
                                    style="background:<?= $bg ?>;flex-direction:column;gap:0;cursor:default">
                                    <div style="font-size:.75rem;font-weight:900;color:<?= $clr ?>;line-height:1;text-shadow:0 1px 3px rgba(0,0,0,.8)">
                                        <?= $cv['total'] ?>
                                    </div>
                                    <?php if (($cv['bonusQ'] + $cv['bonusC']) > 0): ?>
                                        <div style="font-size:.45rem;color:rgba(255,255,255,.6);line-height:1;text-shadow:0 1px 2px rgba(0,0,0,.8)">
                                            +<?= round($cv['bonusQ'] + $cv['bonusC'], 1) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endfor; ?>
                        <?php endfor; ?>
                    </div>

                    <?php
                    $gkDz  = PitchZoneHelper::DISPLAY_GK_ZONE;
                    $gkCv  = $heatCellVal($gkDz);
                    $gkClr = $cellColor($gkCv['total']);
                    $gkBg  = $cellBgAlpha($gkCv['total']);
                    $gkTip = 'Base: ' . $gkCv['base'] . ' | Q:+' . $gkCv['bonusQ'] . ' | C:+' . $gkCv['bonusC'] . ' | Tot: ' . $gkCv['total'];
                    ?>
                    <div class="pitch-gk-row">
                        <div class="pitch-zone pitch-zone-gk"
                            title="<?= Html::encode($gkTip) ?>"
                            style="background:<?= $gkBg ?>;flex-direction:column;gap:0;cursor:default">
                            <div style="font-size:.75rem;font-weight:900;color:<?= $gkClr ?>;line-height:1;text-shadow:0 1px 3px rgba(0,0,0,.8)">
                                <?= $gkCv['total'] ?>
                            </div>
                            <?php if (($gkCv['bonusQ'] + $gkCv['bonusC']) > 0): ?>
                                <div style="font-size:.45rem;color:rgba(255,255,255,.6);line-height:1;text-shadow:0 1px 2px rgba(0,0,0,.8)">
                                    +<?= round($gkCv['bonusQ'] + $gkCv['bonusC'], 1) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-center gap-2 mt-1"
                    style="font-size:.7rem;color:rgba(255,255,255,.45);letter-spacing:.08em;text-transform:uppercase">
                    <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                    <i class="bi bi-shield-fill" style="color:var(--accent-blue)"></i>
                    <span style="color:var(--accent-blue)">DIFESA</span>
                    <span style="flex:1;height:1px;background:rgba(255,255,255,.1)"></span>
                </div>

                <p class="text-center text-muted-gm mt-2 mb-0" style="font-size:.65rem">
                    Valore = base overall + exp quadrante (max +10) + exp cella (max +3) · hover per dettaglio
                </p>
            </div>
        </div>

        <!-- ── COL 3: Info ──────────────────────────────────────────────── -->
        <div class="col-lg-3 d-flex flex-column gap-4">

            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-person-badge text-gold me-2"></i>Info</h3>
                <ul class="attribute-list">
                    <li><span class="text-muted-gm">Maglia</span> <span class="fw-bold">#<?= $player->number ?></span></li>
                    <li><span class="text-muted-gm">Piede</span> <span class="fw-bold" style="display:inline-flex;align-items:center;gap:.3rem"><?= $playerFootIcon ?> <?= Html::encode($footLabel) ?></span></li>
                    <li><span class="text-muted-gm">Età</span> <span class="fw-bold"><?= $player->age ?> anni</span></li>
                    <li><span class="text-muted-gm">Carattere</span> <span class="fw-bold" style="font-style:italic"><?= Html::encode(ucfirst($player->character ?? '—')) ?></span></li>
                    <?php if (($player->injury_weeks ?? 0) > 0): ?>
                        <li>
                            <span class="text-muted-gm">Infortunio</span>
                            <span class="fw-bold" style="color:#fca5a5">
                                <?= match ($player->injury_type) {
                                    'lieve' => '🏥 Lieve',
                                    'medio' => '🩹 Medio',
                                    'grave' => '🚑 Grave',
                                    default => '🏥'
                                } ?>
                                — <?= $player->injury_weeks ?> sett.
                            </span>
                        </li>
                    <?php endif; ?>
                    <li>
                        <span class="text-muted-gm">Cartellini</span>
                        <span class="fw-bold">
                            🟨 <?= (int)($player->yellow_cards ?? 0) ?>
                            &nbsp;🟥 <?= (int)($player->red_cards ?? 0) ?>
                            <?php if (($player->suspended_matches ?? 0) > 0): ?>
                                &nbsp;<span style="color:#f87171;font-size:.78rem">🚫 <?= $player->suspended_matches ?>g</span>
                            <?php endif; ?>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-graph-up text-gold me-2"></i>Stagione <?= $season ?></h3>
                <ul class="attribute-list">
                    <li><span class="text-muted-gm">Partite</span> <span class="fw-bold"><?= (int)($seasonStats['matches'] ?? 0) ?></span></li>
                    <li><span class="text-muted-gm">Minuti</span> <span class="fw-bold"><?= (int)($seasonStats['minutes_played'] ?? 0) ?></span></li>
                    <li><span class="text-muted-gm">Gol / Assist</span> <span class="fw-bold text-gold"><?= (int)($seasonStats['goals'] ?? 0) ?> / <?= (int)($seasonStats['assists'] ?? 0) ?></span></li>
                    <li><span class="text-muted-gm">Cartellini</span> <span class="fw-bold">🟨 <?= (int)($seasonStats['yellow_cards'] ?? 0) ?> · 🟥 <?= (int)($seasonStats['red_cards'] ?? 0) ?></span></li>
                    <?php if ($player->position === 'GK'): ?>
                        <li><span class="text-muted-gm">Parate / CS</span> <span class="fw-bold"><?= (int)($seasonStats['saves'] ?? 0) ?> / <?= (int)($seasonStats['clean_sheets'] ?? 0) ?></span></li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-activity text-gold me-2"></i>Allenamento</h3>
                <?php if (empty($trainingLogs)): ?>
                    <p class="text-muted-gm small mb-0">Nessun log disponibile.</p>
                <?php else: ?>
                    <?php
                    // Build chart data: group by stat, oldest→newest
                    $logReversed = array_reverse($trainingLogs);
                    $statSeries  = []; // stat => [ [label, new_value], ... ]
                    foreach ($logReversed as $log) {
                        $s = (string) $log['stat'];
                        $statSeries[$s][] = [
                            'x' => 'S' . (int)$log['season'] . 'W' . (int)$log['week'],
                            'y' => (int) $log['new_value'],
                        ];
                    }
                    // Chart.js colors per stat
                    $statColors = [
                        'skill_po' => '#f97316',
                        'skill_df' => '#3b82f6',
                        'skill_cn' => '#22c55e',
                        'skill_pa' => '#a855f7',
                        'skill_rg' => '#06b6d4',
                        'skill_cr' => '#ef4444',
                        'skill_tc' => '#eab308',
                        'skill_tr' => '#ec4899',
                    ];
                    $defaultColors = ['#60a5fa', '#34d399', '#f59e0b', '#f87171', '#a78bfa', '#38bdf8'];
                    $colorIdx = 0;
                    $datasets = [];
                    foreach ($statSeries as $stat => $points) {
                        $shortLabel = str_replace('skill_', '', $stat);
                        $color = $statColors[$stat] ?? $defaultColors[$colorIdx++ % count($defaultColors)];
                        $datasets[] = [
                            'label'           => strtoupper($shortLabel),
                            'data'            => array_column($points, 'y'),
                            'borderColor'     => $color,
                            'backgroundColor' => $color . '22',
                            'pointRadius'     => 2,
                            'pointHoverRadius' => 4,
                            'borderWidth'     => 2,
                            'tension'         => 0.3,
                            'fill'            => false,
                        ];
                    }
                    // x labels: use entries from first stat (all share same timeline order)
                    $firstStat  = array_key_first($statSeries);
                    $xLabels    = array_column($statSeries[$firstStat] ?? [], 'x');
                    $chartJson  = json_encode(['labels' => $xLabels, 'datasets' => $datasets], JSON_UNESCAPED_UNICODE);
                    $chartId    = 'trainChart_' . $player->id;
                    ?>
                    <canvas id="<?= $chartId ?>" height="160"></canvas>
                    <script>
                        (function() {
                            var load = function() {
                                if (typeof Chart === 'undefined') {
                                    setTimeout(load, 80);
                                    return;
                                }
                                var d = <?= $chartJson ?>;
                                new Chart(document.getElementById('<?= $chartId ?>'), {
                                    type: 'line',
                                    data: d,
                                    options: {
                                        responsive: true,
                                        animation: false,
                                        plugins: {
                                            legend: {
                                                display: true,
                                                position: 'top',
                                                labels: {
                                                    color: 'rgba(255,255,255,.55)',
                                                    font: {
                                                        size: 9
                                                    },
                                                    boxWidth: 10,
                                                    padding: 6
                                                }
                                            },
                                            tooltip: {
                                                mode: 'index',
                                                intersect: false
                                            }
                                        },
                                        scales: {
                                            x: {
                                                ticks: {
                                                    color: 'rgba(255,255,255,.35)',
                                                    font: {
                                                        size: 8
                                                    },
                                                    maxRotation: 0
                                                },
                                                grid: {
                                                    color: 'rgba(255,255,255,.05)'
                                                }
                                            },
                                            y: {
                                                ticks: {
                                                    color: 'rgba(255,255,255,.35)',
                                                    font: {
                                                        size: 8
                                                    }
                                                },
                                                grid: {
                                                    color: 'rgba(255,255,255,.05)'
                                                }
                                            }
                                        }
                                    }
                                });
                            };
                            load();
                        }());
                    </script>
                <?php endif; ?>
            </div>

            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-file-earmark-text text-gold me-2"></i>Contratto</h3>
                <?php if ($contract): ?>
                    <ul class="attribute-list mb-3">
                        <li><span class="text-muted-gm">Stipendio</span> <span class="fw-bold">€<?= number_format($contract->salary, 0, ',', '.') ?>/st.</span></li>
                        <li><span class="text-muted-gm">Scadenza</span> <span class="fw-bold">St. <?= $contract->season_end ?></span></li>
                        <li>
                            <span class="text-muted-gm">Clausola</span>
                            <span class="text-gold fw-bold"><?= $contract->release_clause ? '€' . number_format($contract->release_clause, 0, ',', '.') : 'Nessuna' ?></span>
                        </li>
                    </ul>
                    <?php if ($isMyPlayer): ?>
                        <?= Html::a('Rinnova contratto', ['#'], ['class' => 'btn btn-outline-gold w-100 btn-sm']) ?>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted-gm small">Nessun contratto attivo.</p>
                    <?php if ($isMyPlayer): ?>
                        <?= Html::a('Offri contratto', ['#'], ['class' => 'btn btn-gold w-100 btn-sm']) ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <?php if (!$isMyPlayer): ?>
                <div class="gm-card">
                    <h3 class="h5 mb-3 text-white"><i class="bi bi-shop text-gold me-2"></i>Acquisto</h3>
                    <ul class="attribute-list mb-3">
                        <li>
                            <span class="text-muted-gm">Valore</span>
                            <span class="text-gold fw-bold">€<?= number_format($valuator->marketValue($player), 0, ',', '.') ?></span>
                        </li>
                        <?php if ($activeTransfer): ?>
                            <li><span class="text-muted-gm">Prezzo</span> <span class="fw-bold text-white">€<?= number_format((int)($activeTransfer->asking_fee ?: $activeTransfer->fee), 0, ',', '.') ?></span></li>
                            <li><span class="text-muted-gm">Tipo</span> <span class="fw-bold"><?= $activeTransfer->transfer_type === 'loan' ? 'Prestito' : 'Vendita' ?></span></li>
                        <?php endif; ?>
                    </ul>
                    <?php if ($activeTransfer): ?>
                        <?= Html::beginForm(['/transfer/make-offer', 'id' => $activeTransfer->id], 'post', ['class' => 'd-flex gap-2']) ?>
                        <input type="number" name="offered_fee"
                            value="<?= (int)($activeTransfer->asking_fee ?: $activeTransfer->fee) ?>"
                            min="1" step="10000"
                            style="flex:1;background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem;padding:.4rem .6rem;font-size:.85rem">
                        <button type="submit" class="btn btn-gold btn-sm px-3">
                            <i class="bi bi-cart-plus me-1"></i>Offerta
                        </button>
                        <?= Html::endForm() ?>
                    <?php else: ?>
                        <p class="text-muted-gm small mb-3">Non in vendita.</p>
                        <?= Html::a('<i class="bi bi-shop"></i> Mercato', ['/transfer/market'], ['class' => 'btn btn-outline-secondary w-100 btn-sm', 'encode' => false]) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>