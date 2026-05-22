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

use yii\helpers\Html;
use app\components\PlayerAttributeHelper;
use app\components\SvgIcons;

$this->title = $player->name;
$this->params['breadcrumbs'][] = ['label' => 'Squadra', 'url' => ['/team/view']];
$this->params['breadcrumbs'][] = $this->title;

$skillColor = fn(int $v): string => $v >= 75 ? 'var(--accent-green)' : ($v >= 50 ? 'var(--gold)' : 'var(--accent-red)');

$posColors = ['GK' => '#ea580c', 'DF' => '#2563eb', 'MF' => '#15803d', 'FW' => '#b91c1c'];
$posColor  = $posColors[$player->position] ?? '#f59e0b';

$footLabel = match($player->foot) {
    'R'  => 'Destro',
    'L'  => 'Sinistro',
    'LR' => 'Ambidestro',
    default => $player->foot,
};
$footIcon = SvgIcons::foot((string) $player->foot, 16);

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
$playerFootIcon = SvgIcons::foot((string) $player->foot, 16);
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
                                <stop offset="0%" stop-color="<?= $posColor ?>cc"/>
                                <stop offset="100%" stop-color="<?= $posColor ?>"/>
                            </linearGradient>
                        </defs>
                        <ellipse cx="22" cy="51" rx="13" ry="1.5" fill="rgba(0,0,0,0.35)"/>
                        <path d="M1,11 L4,22 L15,19 L13,6" fill="url(#jgh)" stroke="rgba(0,0,0,0.25)" stroke-width="0.6"/>
                        <path d="M43,11 L40,22 L29,19 L31,6" fill="url(#jgh)" stroke="rgba(0,0,0,0.25)" stroke-width="0.6"/>
                        <path d="M13,6 L15,19 L12,48 L32,48 L29,19 L31,6 C26,10 18,10 13,6 Z" fill="url(#jgh)" stroke="rgba(0,0,0,0.25)" stroke-width="0.6"/>
                        <path d="M17,5 L22,13 L27,5" fill="<?= $posColor ?>aa" stroke="rgba(0,0,0,0.4)" stroke-width="0.8"/>
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
                        <?= SvgIcons::position((string) $player->position, 20) ?>
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

    <div class="row g-4">

        <!-- Skill grid -->
        <div class="col-lg-8">
            <div class="gm-card h-100">
                <h3 class="h5 mb-4 text-white"><i class="bi bi-bar-chart text-gold me-2"></i>Attributi Tecnici</h3>

                <div class="skill-grid mb-4">
                    <?php foreach ($technicalAttrs as $attr):
                        $label = (string) $attr['label'];
                        $val = (int) $attr['value'];
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

                <!-- Condition bars (SIP-0032) -->
                <div class="p-3 rounded-3" style="background:rgba(255,255,255,.02);border:1px solid var(--border)">
                    <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:.65rem;padding:.1rem 0">
                        <?= $pctCard('Forma', (int) $player->form, $formColor) ?>
                        <?= $pctCard('Freschezza', (int) $player->freshness, $freshColor) ?>
                        <?= $pctCard('Condizione', (int) $player->condition, $condColor) ?>
                        <div style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.75rem;padding:.65rem .7rem;display:flex;flex-direction:column;justify-content:center">
                            <div style="font-size:.72rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary);text-align:center">
                                Esperienza
                            </div>
                            <div style="font-size:1.55rem;line-height:1.1;font-weight:900;color:var(--text-secondary);text-align:center;margin:.35rem 0 0 0">
                                <?= (int) $player->experience ?>
                            </div>
                            <div style="font-size:.66rem;color:var(--text-secondary);text-align:center;margin-top:.2rem">XP</div>
                        </div>
                    </div>

                    <div style="margin-top:.95rem">
                        <div style="font-size:.78rem;font-weight:800;letter-spacing:.05em;text-transform:uppercase;color:var(--text-secondary);margin-bottom:.55rem">
                            Talenti
                        </div>
                        <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.5rem .8rem">
                            <?php foreach ($playerTalents as $talent):
                                $talentCode = (string) $talent['code'];
                                $talentLabel = (string) $talent['label'];
                                $talentLevel = max(1, min(3, (int) $talent['level']));
                                $talentValue = (int) round(($talentLevel / 3) * 100);
                                $talentColor = $skillColor($talentValue);
                            ?>
                            <div>
                                <div style="display:flex;justify-content:space-between;align-items:center;font-size:.72rem;margin-bottom:.15rem">
                                    <span style="color:var(--text-secondary)"><?= Html::encode($talentLabel) ?></span>
                                    <span style="display:inline-flex;align-items:center;gap:.22rem;font-weight:800;color:<?= $talentColor ?>">
                                        <?= SvgIcons::skill($talentCode, 12) ?> Lv <?= $talentLevel ?>/3
                                    </span>
                                </div>
                                <div style="background:rgba(255,255,255,.12);border-radius:4px;height:5px;overflow:hidden">
                                    <div style="width:<?= $talentValue ?>%;height:100%;background:<?= $talentColor ?>;border-radius:4px"></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php if (empty($playerTalents)): ?>
                            <div class="text-muted-gm" style="font-size:.72rem">Nessun talento definito</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Info + Contract -->
        <div class="col-lg-4 d-flex flex-column gap-4">

            <!-- Player info -->
            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-person-badge text-gold me-2"></i>Info</h3>
                <ul class="attribute-list">
                    <li><span class="text-muted-gm">Numero maglia</span> <span class="fw-bold">#<?= $player->number ?></span></li>
                    <li><span class="text-muted-gm">Piede</span> <span class="fw-bold" style="display:inline-flex;align-items:center;gap:.3rem"><?= $playerFootIcon ?> <?= Html::encode($footLabel) ?></span></li>
                    <li><span class="text-muted-gm">Età</span> <span class="fw-bold"><?= $player->age ?> anni</span></li>
                    <li><span class="text-muted-gm">Carattere</span> <span class="fw-bold" style="font-style:italic"><?= Html::encode(ucfirst($player->character ?? '—')) ?></span></li>
                    <?php if (($player->injury_weeks ?? 0) > 0): ?>
                    <li>
                        <span class="text-muted-gm">Infortunio</span>
                        <span class="fw-bold" style="color:#fca5a5">
                            <?= match($player->injury_type) { 'lieve' => '🏥 Lieve', 'medio' => '🩹 Medio', 'grave' => '🚑 Grave', default => '🏥' } ?>
                            — <?= $player->injury_weeks ?> settimane
                        </span>
                    </li>
                    <?php endif; ?>
                    <li>
                        <span class="text-muted-gm">Cartellini</span>
                        <span class="fw-bold">
                            🟨 <?= (int)($player->yellow_cards ?? 0) ?>
                            &nbsp;🟥 <?= (int)($player->red_cards ?? 0) ?>
                            <?php if (($player->suspended_matches ?? 0) > 0): ?>
                            &nbsp;<span style="color:#f87171;font-size:.8rem">🚫 <?= $player->suspended_matches ?> gara squalifica</span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li><span class="text-muted-gm">Esperienza</span> <span class="fw-bold"><?= number_format($player->experience) ?> XP</span></li>
                </ul>
            </div>

            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-graph-up text-gold me-2"></i>Statistiche Stagione <?= $season ?></h3>
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
                <h3 class="h5 mb-3 text-white"><i class="bi bi-clock-history text-gold me-2"></i>Storico Allenamento</h3>
                <?php if (empty($trainingLogs)): ?>
                    <p class="text-muted-gm small mb-0">Nessun log allenamento disponibile.</p>
                <?php else: ?>
                    <div style="max-height:230px;overflow:auto;scrollbar-width:thin">
                        <table class="table-gm w-100 mb-0">
                            <thead>
                                <tr>
                                    <th style="font-size:.66rem">Settimana</th>
                                    <th style="font-size:.66rem">Stat</th>
                                    <th style="font-size:.66rem">XP</th>
                                    <th style="font-size:.66rem">Nuovo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($trainingLogs as $log): ?>
                                    <tr>
                                        <td style="font-size:.68rem"><?= (int) $log['season'] ?>·<?= (int) $log['week'] ?></td>
                                        <td style="font-size:.68rem"><?= Html::encode((string) $log['stat']) ?></td>
                                        <td style="font-size:.68rem;color:<?= ((float) $log['xp_gained']) >= 0 ? 'var(--accent-green)' : 'var(--accent-red)' ?>">
                                            <?= number_format((float) $log['xp_gained'], 2, ',', '.') ?>
                                        </td>
                                        <td style="font-size:.68rem"><?= (int) $log['new_value'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Contract -->
            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-file-earmark-text text-gold me-2"></i>Contratto</h3>
                <?php if ($contract): ?>
                <ul class="attribute-list mb-3">
                    <li><span class="text-muted-gm">Stipendio</span> <span class="fw-bold">€<?= number_format($contract->salary, 0, ',', '.') ?>/stagione</span></li>
                    <li><span class="text-muted-gm">Scadenza</span> <span class="fw-bold">Stagione <?= $contract->season_end ?></span></li>
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

            <!-- Transfer (if not my player) -->
            <?php if (!$isMyPlayer): ?>
            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-shop text-gold me-2"></i>Acquisto</h3>
                <ul class="attribute-list mb-3">
                    <li>
                        <span class="text-muted-gm">Valore stimato</span>
                        <span class="text-gold fw-bold">€<?= number_format($valuator->marketValue($player), 0, ',', '.') ?></span>
                    </li>
                    <?php if ($activeTransfer): ?>
                    <li>
                        <span class="text-muted-gm">Prezzo richiesto</span>
                        <span class="fw-bold text-white">€<?= number_format((int)($activeTransfer->asking_fee ?: $activeTransfer->fee), 0, ',', '.') ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Tipo</span>
                        <span class="fw-bold"><?= $activeTransfer->transfer_type === 'loan' ? 'Prestito' : 'Vendita' ?></span>
                    </li>
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
                <p class="text-muted-gm small mb-3">Non in vendita al momento.</p>
                <?= Html::a('<i class="bi bi-shop"></i> Vai al mercato', ['/transfer/market'], ['class' => 'btn btn-outline-secondary w-100 btn-sm', 'encode' => false]) ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
