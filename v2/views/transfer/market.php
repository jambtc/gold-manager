<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team|null $team */
/** @var app\models\Transfer[] $transfers */
/** @var app\models\PlayerPool[] $poolPlayers */
/** @var app\models\TransferOffer[] $myOffersSent */
/** @var app\models\TransferOffer[] $incomingOffers */
/** @var app\components\PlayerValuator $valuator */
/** @var string $q */
/** @var string $pos */
/** @var int $minSkill */
/** @var int $maxFee */
/** @var int $maxAge */
/** @var bool $windowOpen */
/** @var int $nextOpenAt */
/** @var int $pendingCount */
/** @var string $activeTab */

use app\models\Transfer;
use app\models\TransferOffer;
use app\components\PlayerAttributeHelper;
use app\components\UiIconHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Mercato Trasferimenti';
$this->params['breadcrumbs'][] = $this->title;

// URL corrente con tutti i filtri — passato come `back` al player/view
$backUrl = Url::to([
    '/transfer/market',
    'q' => $q,
    'pos' => $pos,
    'min_skill' => $minSkill,
    'max_fee' => $maxFee,
    'max_age' => $maxAge,
    'tab' => $activeTab,
]);
$footLbl = static fn(string $f): string => match ($f) {
    'R' => 'Destro',
    'L' => 'Sinistro',
    'LR' => 'Amb.',
    default => $f,
};
?>

<div class="transfer-market">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="mb-0 fw-black">Mercato Trasferimenti</h1>
            <p class="text-muted-gm mb-0">
                <?= count($transfers) ?> in vendita · <?= count($poolPlayers) ?> pool svincolati
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php if ($team): ?>
                <div class="gm-card py-2 px-3 text-end" style="min-width:170px">
                    <div class="text-gold fw-black fs-5">€<?= number_format((int) $team->budget, 0, ',', '.') ?></div>
                    <div class="text-muted-gm" style="font-size:.7rem">Budget disponibile</div>
                </div>
            <?php endif; ?>
            <div class="gm-card py-2 px-3 text-end" style="min-width:185px">
                <?php if ($windowOpen): ?>
                    <div class="text-success fw-bold">Finestra aperta</div>
                    <div class="text-muted-gm" style="font-size:.7rem">Offerte abilitate</div>
                <?php else: ?>
                    <div class="text-danger fw-bold">Finestra chiusa</div>
                    <div class="text-muted-gm" style="font-size:.7rem">
                        riapre <?= date('d/m/Y', $nextOpenAt) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <form method="get" action="<?= Html::encode(Url::to(['/transfer/market'])) ?>" class="gm-card py-3 px-4 mb-4 d-flex align-items-center gap-3 flex-wrap">
        <input type="hidden" name="tab" id="filter-tab" value="<?= Html::encode($activeTab) ?>">
        <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width:220px;background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.6rem;padding:.5rem 1rem">
            <i class="bi bi-search text-muted-gm"></i>
            <input type="text" name="q" value="<?= Html::encode($q) ?>"
                   placeholder="Cerca per nome…"
                   class="bg-transparent border-0 text-white w-100"
                   style="outline:none;font-size:.9rem">
        </div>
        <?php $selStyle = 'background:#0f172a;border:1px solid var(--border);color:#fff;border-radius:.6rem;padding:.5rem .75rem;font-size:.85rem'; ?>
        <select name="pos" style="<?= $selStyle ?>">
            <option value="" <?= $pos === '' ? 'selected' : '' ?> style="background:#0f172a">Ruolo</option>
            <option value="GK" <?= $pos === 'GK' ? 'selected' : '' ?> style="background:#0f172a">GK</option>
            <option value="DF" <?= $pos === 'DF' ? 'selected' : '' ?> style="background:#0f172a">DF</option>
            <option value="MF" <?= $pos === 'MF' ? 'selected' : '' ?> style="background:#0f172a">MF</option>
            <option value="FW" <?= $pos === 'FW' ? 'selected' : '' ?> style="background:#0f172a">FW</option>
        </select>
        <input type="number" name="min_skill" value="<?= (int) $minSkill ?>" min="0" max="99" placeholder="Skill min"
               style="width:100px;<?= $selStyle ?>">
        <input type="number" name="max_fee" value="<?= (int) $maxFee ?>" min="0" placeholder="Prezzo max"
               style="width:130px;<?= $selStyle ?>">
        <input type="number" name="max_age" value="<?= (int) $maxAge ?>" min="0" max="45" placeholder="Età max"
               style="width:100px;<?= $selStyle ?>">
        <button type="submit" class="btn btn-gold btn-sm px-4">Filtra</button>
        <a href="<?= Html::encode(Url::to(['/transfer/market', 'tab' => $activeTab])) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
    </form>

    <?php
    $tabs = [
        'listed' => 'Giocatori in vendita',
        'pool'   => 'Free agent pool',
        'offers' => 'Le mie offerte',
    ];
    ?>
    <ul class="nav nav-tabs mb-4" role="tablist">
        <?php foreach ($tabs as $tabId => $tabLabel): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === $tabId ? 'active' : '' ?>"
                    data-bs-toggle="tab"
                    data-bs-target="#<?= $tabId ?>"
                    data-tab-id="<?= $tabId ?>"
                    type="button"
                    onclick="document.getElementById('filter-tab').value='<?= $tabId ?>'">
                <?= Html::encode($tabLabel) ?>
                <?php if ($tabId === 'offers' && $pendingCount > 0): ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;background:var(--accent-red);color:#fff;border-radius:50%;font-size:.6rem;font-weight:900;width:16px;height:16px;margin-left:.3rem"><?= $pendingCount ?></span>
                <?php endif; ?>
            </button>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade <?= $activeTab === 'listed' ? 'show active' : '' ?>" id="listed" role="tabpanel">
            <?php if (empty($transfers)): ?>
                <div class="gm-card p-5 text-center">
                    <i class="bi bi-shop-window d-block mb-3 text-muted-gm" style="font-size:3rem;opacity:.4"></i>
                    <h3 class="text-white mb-2">Nessun giocatore in vendita</h3>
                    <p class="text-muted-gm mb-0">Prova con filtri diversi o attendi nuove inserzioni.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($transfers as $transfer): ?>
                        <?php $p = $transfer->player; if (!$p) { continue; } ?>
                        <?php $isOwn = $team && (int) $transfer->from_team_id === (int) $team->id; ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="gm-card d-flex flex-column" style="gap:0">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <?= UiIconHelper::renderPositionBadge((string) $p->position, true, 10, 'font-size:.62rem;padding:.14rem .34rem') ?>
                                    <span class="text-muted-gm" style="font-size:.68rem"><?= date('d M', (int) $transfer->listed_at) ?></span>
                                </div>

                                <div class="text-center mb-3">
                                    <h4 class="mb-1 fw-bold text-white"><?= Html::encode($p->name) ?></h4>
                                    <div class="text-muted-gm" style="font-size:.75rem">
                                        <?= (int) $p->age ?> anni
                                        · <?= Html::encode($footLbl((string) $p->foot)) ?>
                                        <?php if ($p->character): ?>
                                        · <span style="font-style:italic"><?= Html::encode(ucfirst($p->character)) ?></span>
                                        <?php endif; ?>
                                        <?php if ($transfer->fromTeam): ?>
                                        · <?= Html::encode($transfer->fromTeam->name) ?>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <?php foreach ([
                                        ['Skill', (string) $p->general_skill, 'var(--gold)'],
                                        ['Forma', $p->form . '%', $p->form >= 75 ? 'var(--accent-green)' : ($p->form >= 50 ? 'var(--gold)' : 'var(--accent-red)')],
                                        ['Fr.', $p->freshness . '%', 'var(--accent-blue)'],
                                        ['Cond.', $p->condition . '%', '#f59e0b'],
                                    ] as [$lbl, $val, $color]): ?>
                                        <div class="col-3">
                                            <div class="text-center p-1 rounded-2" style="background:rgba(255,255,255,.04);border:1px solid var(--border)">
                                                <div style="font-size:.58rem;color:var(--text-secondary);text-transform:uppercase"><?= $lbl ?></div>
                                                <div style="font-weight:800;font-size:.85rem;color:<?= $color ?>"><?= Html::encode($val) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <?php
                                $specials = PlayerAttributeHelper::talents($p, 2);
                                if (!empty($specials)): ?>
                                <div class="d-flex flex-wrap gap-1 mb-3">
                                    <?php foreach ($specials as $tal): ?>
                                    <span title="<?= Html::encode((string) $tal['label']) ?>"
                                          style="font-size:.65rem;background:rgba(245,158,11,.12);color:var(--gold);border:1px solid rgba(245,158,11,.25);border-radius:.3rem;padding:.1rem .4rem;white-space:nowrap">
                                        <?= UiIconHelper::renderTalentTypeIcon((string) ($tal['code'] ?? ''), 10) ?> <?= Html::encode((string) $tal['label']) ?> Lv<?= (int) $tal['level'] ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>

                                <?php $ask = (int) ($transfer->asking_fee ?: $transfer->fee); ?>
                                <div class="text-center mb-3">
                                    <div class="fee-badge">Richiesta €<?= number_format($ask, 0, ',', '.') ?></div>
                                    <div class="text-muted-gm mt-1" style="font-size:.68rem">
                                        <?= $transfer->transfer_type === Transfer::TYPE_LOAN ? 'Prestito' : 'Vendita' ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2 mt-auto">
                                    <?= Html::a('<i class="bi bi-person"></i>', ['/player/view', 'id' => $p->id, 'back' => $backUrl], [
                                        'class' => 'btn btn-outline-secondary btn-sm',
                                        'encode' => false,
                                        'title' => 'Scheda giocatore',
                                    ]) ?>
                                    <?php if ($team && !$isOwn && \app\components\ScoutingService::canScout((int)$team->id)): ?>
                                    <?= Html::beginForm(['/scouting/scout'], 'post', ['class' => 'd-inline']) ?>
                                    <input type="hidden" name="player_id" value="<?= $p->id ?>">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Osserva">🔍</button>
                                    <?= Html::endForm() ?>
                                    <?php endif; ?>

                                    <?php if ($isOwn): ?>
                                        <?= Html::beginForm(['/transfer/delist', 'id' => $transfer->id], 'post', ['class' => 'flex-grow-1']) ?>
                                        <button class="btn btn-outline-danger w-100 btn-sm" type="submit">Ritira</button>
                                        <?= Html::endForm() ?>
                                    <?php elseif (!$windowOpen): ?>
                                        <button class="btn btn-outline-secondary flex-grow-1 btn-sm" disabled>Finestra chiusa</button>
                                    <?php else: ?>
                                        <?= Html::beginForm(['/transfer/make-offer', 'id' => $transfer->id], 'post', ['class' => 'flex-grow-1 d-flex gap-1']) ?>
                                        <input type="number" name="offered_fee" min="1" step="1000" value="<?= $ask ?>"
                                               class="form-control form-control-sm bg-dark text-white border-secondary" style="max-width:125px">
                                        <button type="submit" class="btn btn-gold btn-sm flex-grow-1">Fai offerta</button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'pool' ? 'show active' : '' ?>" id="pool" role="tabpanel">
            <?php if (empty($poolPlayers)): ?>
                <div class="gm-card p-5 text-center">
                    <i class="bi bi-people d-block mb-3 text-muted-gm" style="font-size:3rem;opacity:.4"></i>
                    <h3 class="text-white mb-2">Pool svincolati vuoto</h3>
                    <p class="text-muted-gm mb-0">Nessun free agent disponibile ora.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($poolPlayers as $pool): ?>
                        <?php $p = $pool->player; if (!$p) { continue; } ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="gm-card d-flex flex-column" style="gap:0">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <?= UiIconHelper::renderPositionBadge((string) $p->position, true, 10, 'font-size:.62rem;padding:.14rem .34rem') ?>
                                    <span class="text-muted-gm" style="font-size:.68rem">pool</span>
                                </div>
                                <div class="text-center mb-3">
                                    <h4 class="mb-1 fw-bold text-white"><?= Html::encode($p->name) ?></h4>
                                    <div class="text-muted-gm" style="font-size:.75rem">
                                        <?= (int) $p->age ?> anni · <?= Html::encode($footLbl((string) $p->foot)) ?> · Skill <span class="text-gold fw-bold"><?= (int) $p->general_skill ?></span>
                                        <?php if ($p->character): ?>
                                        · <span style="font-style:italic"><?= Html::encode(ucfirst($p->character)) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php
                                $poolSpecials = PlayerAttributeHelper::talents($p, 2);
                                if (!empty($poolSpecials)): ?>
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php foreach ($poolSpecials as $tal): ?>
                                    <span title="<?= Html::encode((string) $tal['label']) ?>"
                                          style="font-size:.65rem;background:rgba(245,158,11,.12);color:var(--gold);border:1px solid rgba(245,158,11,.25);border-radius:.3rem;padding:.1rem .4rem">
                                        <?= UiIconHelper::renderTalentTypeIcon((string) ($tal['code'] ?? ''), 10) ?> <?= Html::encode((string) $tal['label']) ?> Lv<?= (int) $tal['level'] ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                <div class="small text-muted-gm mb-3 text-center">
                                    Richiesta salariale: <span class="text-white fw-bold">€<?= number_format((int) $pool->salary_ask, 0, ',', '.') ?></span>
                                </div>
                                <div class="d-flex gap-2 mt-auto">
                                    <?= Html::a('<i class="bi bi-person"></i>', ['/player/view', 'id' => $p->id, 'back' => $backUrl], [
                                        'class' => 'btn btn-outline-secondary btn-sm',
                                        'encode' => false,
                                    ]) ?>
                                    <?php if (!$windowOpen): ?>
                                        <button class="btn btn-outline-secondary flex-grow-1 btn-sm" disabled>Finestra chiusa</button>
                                    <?php else: ?>
                                        <?= Html::beginForm(['/transfer/sign-free-agent', 'id' => $pool->id], 'post', ['class' => 'flex-grow-1']) ?>
                                        <button type="submit" class="btn btn-gold w-100 btn-sm"
                                                data-confirm="Firmare <?= Html::encode($p->name) ?> a parametro zero?">
                                            Firma
                                        </button>
                                        <?= Html::endForm() ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'offers' ? 'show active' : '' ?>" id="offers" role="tabpanel">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="gm-card h-100">
                        <h3 class="h6 text-white mb-3">Offerte ricevute</h3>
                        <?php if (empty($incomingOffers)): ?>
                            <p class="text-muted-gm small mb-0">Nessuna offerta in attesa.</p>
                        <?php else: ?>
                            <?php foreach ($incomingOffers as $offer): ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--border)!important">
                                    <div>
                                        <div class="text-white small fw-bold"><?= Html::encode($offer->player->name ?? ('Player #' . $offer->player_id)) ?></div>
                                        <div class="text-muted-gm" style="font-size:.72rem">
                                            da <?= Html::encode($offer->fromTeam->name ?? ('Team #' . $offer->from_team_id)) ?> ·
                                            €<?= number_format((int) $offer->offered_fee, 0, ',', '.') ?>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <?= Html::beginForm(['/transfer/accept-offer', 'id' => $offer->id], 'post', ['class' => 'd-inline']) ?>
                                        <button class="btn btn-success btn-sm" type="submit">Accetta</button>
                                        <?= Html::endForm() ?>
                                        <?= Html::beginForm(['/transfer/reject-offer', 'id' => $offer->id], 'post', ['class' => 'd-inline']) ?>
                                        <button class="btn btn-outline-danger btn-sm" type="submit">Rifiuta</button>
                                        <?= Html::endForm() ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="gm-card h-100">
                        <h3 class="h6 text-white mb-3">Offerte inviate</h3>
                        <?php if (empty($myOffersSent)): ?>
                            <p class="text-muted-gm small mb-0">Nessuna offerta inviata.</p>
                        <?php else: ?>
                            <?php foreach ($myOffersSent as $offer): ?>
                                <?php
                                $statusColor = match ($offer->status) {
                                    TransferOffer::STATUS_ACCEPTED => 'text-success',
                                    TransferOffer::STATUS_REJECTED => 'text-danger',
                                    TransferOffer::STATUS_WITHDRAWN => 'text-muted-gm',
                                    default => 'text-warning',
                                };
                                ?>
                                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--border)!important">
                                    <div>
                                        <div class="text-white small fw-bold"><?= Html::encode($offer->player->name ?? ('Player #' . $offer->player_id)) ?></div>
                                        <div class="text-muted-gm" style="font-size:.72rem">
                                            a <?= Html::encode($offer->toTeam->name ?? ('Team #' . $offer->to_team_id)) ?> ·
                                            €<?= number_format((int) $offer->offered_fee, 0, ',', '.') ?>
                                        </div>
                                    </div>
                                    <span class="<?= $statusColor ?>" style="font-size:.75rem;font-weight:700"><?= Html::encode($offer->status) ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
