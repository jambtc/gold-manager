<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\Player[] $players */
/** @var app\components\PlayerValuator $valuator */
/** @var array<int,array{goals:int,assists:int,yellow_cards:int,red_cards:int}> $seasonStatsMap */
/** @var int $season */

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\CharacterTraitHelper;
use app\components\PlayerAttributeHelper;
use app\components\UiIconHelper;

$this->title = Yii::t('app', 'Squad') . ': ' . $team->name;
$this->params['breadcrumbs'][] = $this->title;

?>
<style>
    .team-view .team-table-compact th,
    .team-view .team-table-compact td {
        padding: .55rem .5rem;
    }
    .team-view .team-table-compact .actions-col {
        width: 34px;
        min-width: 34px;
        max-width: 34px;
        padding-left: .2rem;
        padding-right: .2rem;
    }
</style>

<div class="team-view">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <?php
            $sumOvr = 0;
            foreach ($players as $_p) {
                $sumOvr += (int) $_p->getNaturalOverall();
            }
            $avgOvr = count($players) > 0 ? ($sumOvr / count($players)) : 0;
            ?>
            <p class="text-muted-gm"><?= count($players) ?> <?= Yii::t('app', 'Players') ?> • <?= Yii::t('app', 'Avg OVR') ?>: <?= number_format($avgOvr, 1) ?></p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-grid-3x3"></i> ' . Yii::t('app', 'Tactical Management'), ['/formation/view'], ['class' => 'btn btn-outline-gold']) ?>
            <?= Html::a('<i class="bi bi-plus-lg"></i> ' . Yii::t('app', 'Search Players'), ['/transfer/market'], ['class' => 'btn btn-gold']) ?>
        </div>
    </div>

    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm team-table-compact w-100 mb-0">
                <?php
                // Helper: color skill value
                $sc = fn(int $v): string => $v >= 70 ? 'var(--accent-green)' : ($v >= 50 ? 'var(--gold)' : 'var(--text-secondary)');
                $fc = fn(int $v): string => $v >= 80 ? 'var(--accent-green)' : ($v >= 55 ? 'var(--gold)' : 'var(--accent-red)');
                // Sort players by position order then general_skill
                $posOrder = ['GK' => 0, 'DF' => 1, 'MF' => 2, 'FW' => 3];
                usort($players, function($a, $b) use ($posOrder) {
                    $pa = $posOrder[$a->position] ?? 9;
                    $pb = $posOrder[$b->position] ?? 9;
                    return $pa !== $pb ? $pa - $pb : $b->getNaturalOverall() - $a->getNaturalOverall();
                });
                ?>
                <thead>
                    <tr>
                        <th style="width:44px"><?= Yii::t('app', 'Pos') ?></th>
                        <th><?= Yii::t('app', 'Player') ?></th>
                        <th class="text-center" style="width:32px" title="<?= Yii::t('app', 'Age') ?>"><?= Yii::t('app', 'Age') ?></th>
                        <th class="text-center" style="width:36px" title="<?= Yii::t('app', 'Role Value (Overall)') ?>"><?= Yii::t('app', 'Ovr') ?></th>
                        <th style="width:120px" title="<?= Yii::t('app', 'Player talents (separate from skills)') ?>"><?= Yii::t('app', 'Talents') ?></th>
                        <!-- Individual skills -->
                        <th class="text-center" style="width:32px;color:#ea580c" title="<?= Yii::t('app', 'Saves') ?>"><?= Yii::t('app', 'PO') ?></th>
                        <th class="text-center" style="width:32px;color:#2563eb" title="<?= Yii::t('app', 'Defence') ?>"><?= Yii::t('app', 'DF') ?></th>
                        <th class="text-center" style="width:32px;color:#16a34a" title="<?= Yii::t('app', 'Tackles') ?>"><?= Yii::t('app', 'CN') ?></th>
                        <th class="text-center" style="width:32px;color:#16a34a" title="<?= Yii::t('app', 'Passes') ?>"><?= Yii::t('app', 'PA') ?></th>
                        <th class="text-center" style="width:32px;color:#16a34a" title="<?= Yii::t('app', 'Playmaker') ?>"><?= Yii::t('app', 'RG') ?></th>
                        <th class="text-center" style="width:32px;color:var(--gold)" title="<?= Yii::t('app', 'Cross') ?>"><?= Yii::t('app', 'CR') ?></th>
                        <th class="text-center" style="width:32px;color:var(--gold)" title="<?= Yii::t('app', 'Technique') ?>"><?= Yii::t('app', 'TC') ?></th>
                        <th class="text-center" style="width:32px;color:#b91c1c" title="<?= Yii::t('app', 'Shot') ?>"><?= Yii::t('app', 'TR') ?></th>
                        <!-- Status -->
                        <th class="text-center" style="width:44px" title="<?= Yii::t('app', 'Form') ?>"><?= Yii::t('app', 'F%') ?></th>
                        <th class="text-center" style="width:44px" title="<?= Yii::t('app', 'Freshness') ?>"><?= Yii::t('app', 'Fr%') ?></th>
                        <th class="text-center" style="width:44px" title="<?= Yii::t('app', 'Condition') ?>"><?= Yii::t('app', 'Cond.') ?></th>
                        <th class="text-center" style="width:42px" title="<?= Yii::t('app', 'Season goals') ?>"><?= Yii::t('app', 'Goals') ?></th>
                        <th class="text-center" style="width:42px" title="<?= Yii::t('app', 'Season assists') ?>"><?= Yii::t('app', 'Ast') ?></th>
                        <th class="text-center" style="width:42px" title="<?= Yii::t('app', 'Season yellows') ?>">🟨</th>
                        <th class="text-center" style="width:42px" title="<?= Yii::t('app', 'Season reds') ?>">🟥</th>
                        <th class="text-end actions-col" style="position:sticky;right:0;background:var(--card-bg)"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($players as $player): ?>
                        <?php $ss = $seasonStatsMap[$player->id] ?? ['goals' => 0, 'assists' => 0, 'yellow_cards' => 0, 'red_cards' => 0]; ?>
                        <tr>
                            <td>
                                <?= UiIconHelper::renderPositionBadge((string) $player->position, true, 10, 'font-size:.62rem;padding:.14rem .34rem') ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-white" style="font-size:.85rem;white-space:nowrap">
                                    <?php if (!empty($player->nationality)): ?><?= UiIconHelper::flagImg((string)$player->nationality, 16) ?> <?php endif; ?><?= Html::encode($player->name) ?>
                                </div>
                                <div class="text-muted-gm" style="font-size:.65rem">
                                    <?= $player->age ?>a · <span style="display:inline-flex;align-items:center;gap:.2rem"><?= UiIconHelper::renderFootIcon((string) $player->foot, 11) ?><?= $player->foot === 'LR' ? 'Amb' : $player->foot ?></span>
                                    <?php if ($player->character): ?>
                                     · <em><?= Html::encode(CharacterTraitHelper::display((string) $player->character)) ?></em>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center text-muted-gm" style="font-size:.82rem"><?= $player->age ?></td>
                            <td class="text-center">
                                <span style="font-weight:800;font-size:.85rem;color:var(--gold)"><?= $player->getNaturalOverall() ?></span>
                            </td>
                            <td>
                                <?php $talents = PlayerAttributeHelper::talents($player, 2); ?>
                                <?php if ($talents): ?>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <?php foreach ($talents as $tal): ?>
                                            <?= UiIconHelper::renderTalentIcon((string) ($tal['code'] ?? ''), (string) ($tal['label'] ?? ''), (int) $tal['level'], 13) ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted-gm" style="font-size:.72rem">—</span>
                                <?php endif; ?>
                            </td>
                            <!-- Individual skill columns -->
                            <?php foreach ([
                                $player->skill_po, $player->skill_df, $player->skill_cn,
                                $player->skill_pa, $player->skill_rg, $player->skill_cr,
                                $player->skill_tc, $player->skill_tr,
                            ] as $sv): ?>
                            <td class="text-center" style="font-size:.78rem;font-weight:600;color:<?= $sc($sv) ?>"><?= $sv ?></td>
                            <?php endforeach; ?>
                            <!-- Forma / Freschezza / Condizione -->
                            <td class="text-center" style="font-size:.78rem;color:<?= $fc($player->form) ?>"><?= $player->form ?>%</td>
                            <td class="text-center" style="font-size:.78rem;color:<?= $fc($player->freshness) ?>"><?= $player->freshness ?>%</td>
                            <td class="text-center" style="font-size:.78rem;color:<?= $fc($player->condition) ?>"><?= $player->condition ?>%</td>
                            <td class="text-center" style="font-size:.78rem;font-weight:700;color:var(--gold)"><?= (int) $ss['goals'] ?></td>
                            <td class="text-center" style="font-size:.78rem"><?= (int) $ss['assists'] ?></td>
                            <td class="text-center" style="font-size:.78rem"><?= (int) $ss['yellow_cards'] ?></td>
                            <td class="text-center" style="font-size:.78rem"><?= (int) $ss['red_cards'] ?></td>
                            <td class="text-end actions-col" style="position:sticky;right:0;background:var(--card-bg)">
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted-gm p-0" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark gm-card">
                                        <li><?= Html::a('<i class="bi bi-person-lines-fill"></i> ' . Yii::t('app', 'Details'), ['player/view', 'id' => $player->id], ['class' => 'dropdown-item']) ?></li>
                                        <li><?= Html::a('<i class="bi bi-file-earmark-text"></i> ' . Yii::t('app', 'Contract'), ['contract/view', 'player_id' => $player->id], ['class' => 'dropdown-item']) ?></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                               onclick="openSellModal(<?= $player->id ?>, '<?= Html::encode(addslashes($player->name)) ?>', <?= (int)\Yii::$app->playerValuator->marketValue($player) ?>); return false;">
                                                <i class="bi bi-tag"></i> <?= Yii::t('app', 'Sell') ?>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Sell modal -->
<div id="sell-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.7);align-items:center;justify-content:center">
    <div class="gm-card" style="width:100%;max-width:420px;margin:1rem">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-tag me-2 text-gold"></i><?= Yii::t('app', 'List for sale') ?></h5>
        <p class="text-muted-gm small mb-3" id="sell-player-name"></p>
        <form id="sell-form" method="post" action="<?= Url::to(['/transfer/list-player']) ?>">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
            <input type="hidden" name="id" id="sell-player-id">
            <div class="mb-3">
                <label class="text-muted-gm small mb-1 d-block"><?= Yii::t('app', 'Asking price (€)') ?></label>
                <input type="number" name="asking_fee" id="sell-price" min="0" step="10000"
                       class="form-control" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem">
                <div id="sell-loan-hint" style="display:none;font-size:.7rem;color:var(--text-secondary);margin-top:.25rem"></div>
            </div>
            <div class="mb-3">
                <label class="text-muted-gm small mb-1 d-block"><?= Yii::t('app', 'Type') ?></label>
                <select name="transfer_type" id="sell-type" class="form-select" onchange="onSellTypeChange()"
                        style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem">
                    <option value="sale"><?= Yii::t('app', 'Sale') ?></option>
                    <option value="loan"><?= Yii::t('app', 'Loan') ?></option>
                </select>
            </div>
            <div id="sell-loan-fields" style="display:none">
                <div class="mb-3">
                    <label class="text-muted-gm small mb-1 d-block">
                        <?= Yii::t('app', 'Loan duration (days)') ?>
                        <span style="color:var(--text-secondary)"><?= Yii::t('app', '({min}–{max})', ['{min}' => (int)(getenv('GM_LOAN_MIN_DAYS') ?: 7), '{max}' => (int)(getenv('GM_LOAN_MAX_DAYS') ?: 365)]) ?></span>
                    </label>
                    <input type="number" name="loan_days" id="sell-loan-days"
                           min="<?= (int)(getenv('GM_LOAN_MIN_DAYS') ?: 7) ?>"
                           max="<?= (int)(getenv('GM_LOAN_MAX_DAYS') ?: 365) ?>"
                           value="30" step="1"
                           class="form-control" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem">
                    <div id="sell-loan-expires" style="font-size:.7rem;color:var(--text-secondary);margin-top:.25rem"></div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-gold fw-bold flex-grow-1"><?= Yii::t('app', 'Confirm') ?></button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('sell-modal').style.display='none'"><?= Yii::t('app', 'Cancel') ?></button>
            </div>
        </form>
    </div>
</div>
<script>
var _sellMarketValue = 0;
var _loanFeePercent  = <?= (int)(getenv('GM_LOAN_FEE_PERCENT') ?: 25) ?>;

function openSellModal(id, name, marketValue) {
    _sellMarketValue = marketValue;
    document.getElementById('sell-player-id').value = id;
    document.getElementById('sell-player-name').textContent = name + ' — <?= Yii::t('app', 'estimated value') ?> €' + marketValue.toLocaleString('it-IT');
    document.getElementById('sell-price').value = marketValue;
    document.getElementById('sell-type').value = 'sale';
    onSellTypeChange();
    document.getElementById('sell-modal').style.display = 'flex';
}
function onSellTypeChange() {
    var isLoan = document.getElementById('sell-type').value === 'loan';
    document.getElementById('sell-loan-fields').style.display = isLoan ? 'block' : 'none';
    var hint = document.getElementById('sell-loan-hint');
    if (isLoan && _sellMarketValue > 0) {
        var suggested = Math.round(_sellMarketValue * _loanFeePercent / 100 / 1000) * 1000;
        hint.textContent = '<?= Yii::t('app', 'Suggested loan fee') ?>: €' + suggested.toLocaleString('it-IT')
            + ' (' + _loanFeePercent + '% — <?= Yii::t('app', 'range') ?> 5–50%)';
        hint.style.display = 'block';
        document.getElementById('sell-price').value = suggested;
    } else {
        hint.style.display = 'none';
        document.getElementById('sell-price').value = _sellMarketValue;
    }
    updateLoanExpiry();
}
function updateLoanExpiry() {
    var days = parseInt(document.getElementById('sell-loan-days')?.value || 30);
    var el   = document.getElementById('sell-loan-expires');
    if (!el) return;
    var d = new Date(Date.now() + days * 86400000);
    el.textContent = '<?= Yii::t('app', 'Expires') ?>: ' + d.toLocaleDateString('it-IT', {day:'2-digit',month:'2-digit',year:'numeric'});
}
document.addEventListener('DOMContentLoaded', function() {
    var daysInput = document.getElementById('sell-loan-days');
    if (daysInput) daysInput.addEventListener('input', updateLoanExpiry);
});
</script>
