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
use app\components\PlayerAttributeHelper;
use app\components\UiIconHelper;

$this->title = 'Rosa: ' . $team->name;
$this->params['breadcrumbs'][] = $this->title;

?>

<div class="team-view">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted-gm"><?= count($players) ?> Giocatori • Media Skill: <?= number_format(array_sum(array_column($players, 'general_skill')) / max(1, count($players)), 1) ?></p>
        </div>
        <div class="d-flex gap-2">
            <?= Html::a('<i class="bi bi-grid-3x3"></i> Gestione Tattica', ['/formation/view'], ['class' => 'btn btn-outline-gold']) ?>
            <?= Html::a('<i class="bi bi-plus-lg"></i> Cerca Giocatori', ['/transfer/market'], ['class' => 'btn btn-gold']) ?>
        </div>
    </div>

    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm w-100 mb-0">
                <?php
                // Helper: color skill value
                $sc = fn(int $v): string => $v >= 70 ? 'var(--accent-green)' : ($v >= 50 ? 'var(--gold)' : 'var(--text-secondary)');
                $fc = fn(int $v): string => $v >= 80 ? 'var(--accent-green)' : ($v >= 55 ? 'var(--gold)' : 'var(--accent-red)');
                // Sort players by position order then general_skill
                $posOrder = ['GK' => 0, 'DF' => 1, 'MF' => 2, 'FW' => 3];
                usort($players, function($a, $b) use ($posOrder) {
                    $pa = $posOrder[$a->position] ?? 9;
                    $pb = $posOrder[$b->position] ?? 9;
                    return $pa !== $pb ? $pa - $pb : $b->general_skill - $a->general_skill;
                });
                ?>
                <thead>
                    <tr>
                        <th style="width:44px">Pos</th>
                        <th>Giocatore</th>
                        <th class="text-center" style="width:32px" title="Età">Età</th>
                        <th class="text-center" style="width:36px" title="Skill Generale">Gen</th>
                        <th style="width:170px" title="Talenti giocatore (separati dalle skill)">Talenti</th>
                        <!-- Individual skills -->
                        <th class="text-center" style="width:32px;color:#ea580c" title="Parate">PO</th>
                        <th class="text-center" style="width:32px;color:#2563eb" title="Difesa">DF</th>
                        <th class="text-center" style="width:32px;color:#16a34a" title="Contrasti">CN</th>
                        <th class="text-center" style="width:32px;color:#16a34a" title="Passaggi">PA</th>
                        <th class="text-center" style="width:32px;color:#16a34a" title="Regia">RG</th>
                        <th class="text-center" style="width:32px;color:var(--gold)" title="Cross">CR</th>
                        <th class="text-center" style="width:32px;color:var(--gold)" title="Tecnica">TC</th>
                        <th class="text-center" style="width:32px;color:#b91c1c" title="Tiro">TR</th>
                        <!-- Status -->
                        <th class="text-center" style="width:44px" title="Forma">F%</th>
                        <th class="text-center" style="width:44px" title="Freschezza">Fr%</th>
                        <th class="text-center" style="width:42px" title="Gol stagione">Gol</th>
                        <th class="text-center" style="width:42px" title="Assist stagione">Ass</th>
                        <th class="text-center" style="width:42px" title="Gialli stagione">🟨</th>
                        <th class="text-center" style="width:42px" title="Rossi stagione">🟥</th>
                        <th class="text-end" style="width:80px">Valore</th>
                        <th class="text-end" style="width:40px"></th>
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
                                    <?= Html::encode($player->name) ?>
                                </div>
                                <div class="text-muted-gm" style="font-size:.65rem">
                                    <?= $player->age ?>a · <?= $player->foot === 'LR' ? 'Amb' : $player->foot ?>
                                    <?php if ($player->character): ?>
                                     · <em><?= Html::encode(ucfirst($player->character)) ?></em>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center text-muted-gm" style="font-size:.82rem"><?= $player->age ?></td>
                            <td class="text-center">
                                <span style="font-weight:800;font-size:.85rem;color:var(--gold)"><?= $player->general_skill ?></span>
                            </td>
                            <td>
                                <?php $talents = PlayerAttributeHelper::talents($player, 2); ?>
                                <?php if ($talents): ?>
                                    <div class="d-flex flex-wrap gap-1">
                                        <?php foreach ($talents as $tal): ?>
                                            <span title="<?= Html::encode((string) $tal['label']) ?> Lv<?= (int) $tal['level'] ?>"
                                                  style="display:inline-flex;align-items:center;gap:.2rem;font-size:.62rem;background:rgba(245,158,11,.14);color:var(--gold);border:1px solid rgba(245,158,11,.35);padding:.08rem .26rem;border-radius:.35rem;white-space:nowrap">
                                                <?= UiIconHelper::renderTalentTypeIcon((string) ($tal['code'] ?? ''), 10) ?>
                                                <span style="font-weight:800">Lv<?= (int) $tal['level'] ?></span>
                                            </span>
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
                            <!-- Forma / Freschezza -->
                            <td class="text-center" style="font-size:.78rem;color:<?= $fc($player->form) ?>"><?= $player->form ?>%</td>
                            <td class="text-center" style="font-size:.78rem;color:<?= $fc($player->freshness) ?>"><?= $player->freshness ?>%</td>
                            <td class="text-center" style="font-size:.78rem;font-weight:700;color:var(--gold)"><?= (int) $ss['goals'] ?></td>
                            <td class="text-center" style="font-size:.78rem"><?= (int) $ss['assists'] ?></td>
                            <td class="text-center" style="font-size:.78rem"><?= (int) $ss['yellow_cards'] ?></td>
                            <td class="text-center" style="font-size:.78rem"><?= (int) $ss['red_cards'] ?></td>
                            <td class="text-end" style="font-size:.78rem;color:var(--gold);white-space:nowrap">
                                €<?= number_format($valuator->marketValue($player), 0, ',', '.') ?>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-link text-muted-gm p-0" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark gm-card">
                                        <li><?= Html::a('<i class="bi bi-person-lines-fill"></i> Dettagli', ['player/view', 'id' => $player->id], ['class' => 'dropdown-item']) ?></li>
                                        <li><?= Html::a('<i class="bi bi-file-earmark-text"></i> Contratto', ['contract/view', 'player_id' => $player->id], ['class' => 'dropdown-item']) ?></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#"
                                               onclick="openSellModal(<?= $player->id ?>, '<?= Html::encode(addslashes($player->name)) ?>', <?= (int)\Yii::$app->playerValuator->marketValue($player) ?>); return false;">
                                                <i class="bi bi-tag"></i> Vendi
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
    <div class="gm-card" style="width:100%;max-width:400px;margin:1rem">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-tag me-2 text-gold"></i>Metti in vendita</h5>
        <p class="text-muted-gm small mb-3" id="sell-player-name"></p>
        <form id="sell-form" method="post" action="<?= Url::to(['/transfer/list-player']) ?>">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
            <input type="hidden" name="id" id="sell-player-id">
            <div class="mb-3">
                <label class="text-muted-gm small mb-1 d-block">Prezzo richiesto (€)</label>
                <input type="number" name="asking_fee" id="sell-price" min="0" step="10000"
                       class="form-control" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem">
            </div>
            <div class="mb-4">
                <label class="text-muted-gm small mb-1 d-block">Tipo</label>
                <select name="transfer_type" class="form-select" style="background:rgba(255,255,255,.06);border:1px solid var(--border);color:#fff;border-radius:.5rem">
                    <option value="sale">Vendita</option>
                    <option value="loan">Prestito</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-gold fw-bold flex-grow-1">Conferma</button>
                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('sell-modal').style.display='none'">Annulla</button>
            </div>
        </form>
    </div>
</div>
<script>
function openSellModal(id, name, marketValue) {
    document.getElementById('sell-player-id').value = id;
    document.getElementById('sell-player-name').textContent = name + ' — valore stimato €' + marketValue.toLocaleString('it-IT');
    document.getElementById('sell-price').value = marketValue;
    document.getElementById('sell-modal').style.display = 'flex';
}
</script>
