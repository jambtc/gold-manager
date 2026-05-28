<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team|null $team */
/** @var array<int,array<string,mixed>> $marketRows */
/** @var app\models\TransferOffer[] $myOffersSent */
/** @var app\models\TransferOffer[] $incomingOffers */
/** @var array<int, app\models\MarketBid> $marketBidMap */
/** @var app\models\MarketBid[] $myAuctionBids */
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
/** @var int $perPage */
/** @var int $marketTotal */
/** @var int $marketPage */
/** @var int $marketPages */

use app\models\Transfer;
use app\models\TransferOffer;
use app\components\FixtureViewHelper;
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
$pagerUrl = static function (array $extra = []) use ($q, $pos, $minSkill, $maxFee, $maxAge, $activeTab): string {
    return Url::to(array_merge([
        '/transfer/market',
        'q' => $q,
        'pos' => $pos,
        'min_skill' => $minSkill,
        'max_fee' => $maxFee,
        'max_age' => $maxAge,
        'tab' => $activeTab,
    ], $extra));
};
?>

<div class="transfer-market">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="mb-0 fw-black">Mercato Trasferimenti</h1>
            <p class="text-muted-gm mb-0">
                <?= (int) $marketTotal ?> giocatori in lista unica
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
        <input type="number" name="min_skill" value="<?= (int) $minSkill ?>" min="0" max="99" placeholder="OVR min"
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
        'listed' => 'Lista mercato',
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
                <?php
                $offersBadge = $pendingCount + count($myAuctionBids);
                if ($tabId === 'offers' && $offersBadge > 0):
                ?>
                <span style="display:inline-flex;align-items:center;justify-content:center;background:var(--accent-red);color:#fff;border-radius:50%;font-size:.6rem;font-weight:900;width:16px;height:16px;margin-left:.3rem"><?= $offersBadge ?></span>
                <?php endif; ?>
            </button>
        </li>
        <?php endforeach; ?>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade <?= $activeTab === 'listed' ? 'show active' : '' ?>" id="listed" role="tabpanel">
            <?php if (empty($marketRows)): ?>
                <div class="gm-card p-5 text-center">
                    <i class="bi bi-shop-window d-block mb-3 text-muted-gm" style="font-size:3rem;opacity:.4"></i>
                    <h3 class="text-white mb-2">Nessun giocatore in lista</h3>
                    <p class="text-muted-gm mb-0">Nessun elemento disponibile nel mercato al momento.</p>
                </div>
            <?php else: ?>
                <div class="gm-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table-gm w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th>Giocatore</th>
                                    <th class="text-center">OVR</th>
                                    <th class="text-center">Forma</th>
                                    <th class="text-center">Fr.</th>
                                    <th class="text-center">Cond.</th>
                                    <th>Squadra</th>
                                    <th>Talenti</th>
                                    <th>Tipo</th>
                                    <th>Scadenza</th>
                                    <th class="text-end">Azioni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($marketRows as $row): ?>
                                    <?php
                                    /** @var app\models\Player|null $p */
                                    $p = $row['player'] ?? null;
                                    if (!$p) { continue; }
                                    /** @var app\models\Transfer|null $transfer */
                                    $transfer = $row['transfer'] ?? null;
                                    /** @var app\models\PlayerPool|null $marketEntry */
                                    $marketEntry = $row['marketEntry'] ?? null;
                                    /** @var app\models\Team|null $sourceTeam */
                                    $sourceTeam = $row['team'] ?? null;
                                    $kind = (string) ($row['kind'] ?? 'listed');
                                    $isOwn = (bool) ($row['isOwn'] ?? false);
                                    $ask = (int) ($row['askingFee'] ?? 0);
                                    $salaryAsk = (int) ($row['salaryAsk'] ?? 0);
                                    $expiresAt = (int) ($row['expiresAt'] ?? 0);
                                    $myMarketBid = $marketEntry ? ($marketBidMap[(int) $marketEntry->id] ?? null) : null;
                                    $talents = PlayerAttributeHelper::talents($p, 2);
                                    ?>
                                    <tr<?= $expiresAt > 0 ? ' data-expire-lock="order"' : '' ?>>
                                        <td><?= UiIconHelper::renderPositionBadge((string) $p->position, true, 10, 'font-size:.62rem;padding:.14rem .34rem') ?></td>
                                        <td>
                                            <div class="fw-semibold text-white" style="font-size:.85rem"><?= Html::encode($p->name) ?></div>
                                            <div class="text-muted-gm" style="font-size:.7rem">
                                                <?= (int) $p->age ?>a · <span style="display:inline-flex;align-items:center;gap:.2rem"><?= UiIconHelper::renderFootIcon((string) $p->foot, 11) ?><?= Html::encode($footLbl((string) $p->foot)) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center text-gold fw-bold"><?= (int) $p->getNaturalOverall() ?></td>
                                        <td class="text-center"><?= (int) $p->form ?>%</td>
                                        <td class="text-center"><?= (int) $p->freshness ?>%</td>
                                        <td class="text-center"><?= (int) $p->condition ?>%</td>
                                        <td>
                                            <?php if ($sourceTeam): ?>
                                                <?php
                                                $tl = (string) ($sourceTeam->color_left ?: '#f59e0b');
                                                $tr = (string) ($sourceTeam->color_right ?: '#dc2626');
                                                $letter = strtoupper(substr((string) $sourceTeam->name, 0, 1));
                                                ?>
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    <?= FixtureViewHelper::renderShieldSvg($tl, $tr, $letter, 18, 20, 'marketTeam') ?>
                                                    <span><?= Html::encode($sourceTeam->name) ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted-gm">Svincolato</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($talents): ?>
                                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                                    <?php foreach ($talents as $tal): ?>
                                                        <?= UiIconHelper::renderTalentIcon((string) ($tal['code'] ?? ''), (string) ($tal['label'] ?? ''), (int) ($tal['level'] ?? 1), 12) ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted-gm">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $kind === 'market' ? 'Asta svincolato' : ($transfer && $transfer->transfer_type === Transfer::TYPE_LOAN ? 'Prestito' : 'Vendita') ?></td>
                                        <td>
                                            <?php if ($expiresAt > 0): ?>
                                                <div class="text-muted-gm" style="font-size:.7rem">scade <?= date('d/m/Y H:i', $expiresAt) ?></div>
                                                <div class="auction-countdown text-warning" style="font-size:.72rem" data-expires="<?= $expiresAt ?>" data-prefix="tra " data-expire-lock="order">--</div>
                                            <?php else: ?>
                                                <span class="text-muted-gm">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1 align-items-center">
                                                <?= Html::a('<i class="bi bi-person"></i>', ['/player/view', 'id' => $p->id, 'back' => $backUrl], ['class' => 'btn btn-outline-secondary btn-sm', 'encode' => false, 'title' => 'Scheda']) ?>
                                                <?php if ($team && !$isOwn && \app\components\ScoutingService::canScout((int)$team->id)): ?>
                                                    <?= Html::beginForm(['/scouting/scout'], 'post', ['class' => 'd-inline']) ?>
                                                    <input type="hidden" name="player_id" value="<?= $p->id ?>">
                                                    <button type="submit" class="btn btn-outline-secondary btn-sm" title="Osserva">🔍</button>
                                                    <?= Html::endForm() ?>
                                                <?php endif; ?>
                                                <?php if ($isOwn): ?>
                                                    <?= Html::beginForm(['/transfer/delist', 'id' => (int) $transfer?->id], 'post', ['class' => 'd-inline']) ?>
                                                    <button class="btn btn-outline-danger btn-sm" type="submit">Ritira</button>
                                                    <?= Html::endForm() ?>
                                                <?php elseif (!$windowOpen): ?>
                                                    <button class="btn btn-outline-secondary btn-sm" disabled>Chiusa</button>
                                                <?php elseif ($kind === 'market' && $marketEntry): ?>
                                                    <button type="button" class="btn btn-gold btn-sm js-bid-open"
                                                            data-player="<?= Html::encode($p->name) ?>"
                                                            data-action="<?= Html::encode(\yii\helpers\Url::to(['/transfer/sign-free-agent', 'id' => (int) $marketEntry->id])) ?>"
                                                            data-ask="<?= $ask ?>"
                                                            data-type="Asta svincolato"
                                                            data-current-bid="<?= $myMarketBid ? (int) $myMarketBid->bid_amount : 0 ?>">
                                                        <?= $myMarketBid ? 'Modifica offerta' : 'Offri' ?>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-gold btn-sm js-bid-open"
                                                            data-player="<?= Html::encode($p->name) ?>"
                                                            data-action="<?= Html::encode(\yii\helpers\Url::to(['/transfer/make-offer', 'id' => (int) $transfer?->id])) ?>"
                                                            data-ask="<?= $ask ?>"
                                                            data-type="<?= $transfer && $transfer->transfer_type === Transfer::TYPE_LOAN ? 'Prestito' : 'Vendita' ?>"
                                                            data-current-bid="0">
                                                        Offri
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php if (($marketPages ?? 1) > 1): ?>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-2 pb-2">
                        <span class="text-muted-gm" style="font-size:.72rem">Totale <?= (int) $marketTotal ?> · pagina <?= (int) $marketPage ?>/<?= (int) $marketPages ?></span>
                        <div class="btn-group btn-group-sm">
                            <?= Html::a('«', $pagerUrl(['page' => max(1, (int) $marketPage - 1)]), ['class' => 'btn btn-outline-secondary' . ((int)$marketPage <= 1 ? ' disabled' : '')]) ?>
                            <?= Html::a('»', $pagerUrl(['page' => min((int) $marketPages, (int) $marketPage + 1)]), ['class' => 'btn btn-outline-secondary' . ((int)$marketPage >= (int)$marketPages ? ' disabled' : '')]) ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade <?= $activeTab === 'offers' ? 'show active' : '' ?>" id="offers" role="tabpanel">
            <?php if (!empty($myAuctionBids)): ?>
            <div class="gm-card mb-4">
                <h3 class="h6 text-white mb-3"><i class="bi bi-hammer"></i> Le mie aste (svincolati)</h3>
                <?php foreach ($myAuctionBids as $bid):
                    $bidPool = \app\models\PlayerPool::findOne($bid->market_ref_id);
                    $bidPlayer = $bidPool?->player;
                    if (!$bidPlayer) { continue; }
                ?>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom" style="border-color:var(--border)!important">
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <?= \app\components\UiIconHelper::renderPositionBadge((string) $bidPlayer->position, true, 9, 'font-size:.6rem;padding:.1rem .3rem') ?>
                            <span class="text-white small fw-bold"><?= Html::encode($bidPlayer->name) ?></span>
                        </div>
                        <div class="text-muted-gm" style="font-size:.72rem">
                            La tua offerta: <span class="text-warning fw-bold">€<?= number_format((int) $bid->bid_amount, 0, ',', '.') ?></span>
                            · Richiesta: €<?= number_format((int) ($bidPool->asking_fee ?? 0), 0, ',', '.') ?>
                            <br>
                            <span class="text-muted-gm">asta scade <?= date('d/m/Y H:i', (int) $bidPool->expires_at) ?></span>
                            <span class="auction-countdown text-warning ms-1" data-expires="<?= (int) $bidPool->expires_at ?>" data-prefix="tra ">--</span>
                        </div>
                    </div>
                    <div class="d-flex gap-1">
                        <button type="button" class="btn btn-outline-warning btn-sm js-bid-open"
                                data-player="<?= Html::encode($bidPlayer->name) ?>"
                                data-action="<?= Html::encode(\yii\helpers\Url::to(['/transfer/sign-free-agent', 'id' => (int) $bid->market_ref_id])) ?>"
                                data-ask="<?= (int) ($bidPool->asking_fee ?? 0) ?>"
                                data-type="Asta svincolato"
                                data-current-bid="<?= (int) $bid->bid_amount ?>">
                            Modifica
                        </button>
                        <?= Html::beginForm(['/transfer/withdraw-bid', 'id' => (int) $bid->id], 'post', ['class' => 'd-inline']) ?>
                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                data-confirm="Ritirare l'offerta su <?= Html::encode($bidPlayer->name) ?>?">
                            Ritira
                        </button>
                        <?= Html::endForm() ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

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
                                            <?php if ((string) $offer->status === TransferOffer::STATUS_PENDING && (int) ($offer->expires_at ?? 0) > 0): ?>
                                                <br><span class="text-muted-gm">scade <?= date('d/m/Y H:i', (int) $offer->expires_at) ?></span>
                                                <span class="auction-countdown text-warning" style="margin-left:.3rem" data-expires="<?= (int) $offer->expires_at ?>" data-prefix="tra ">--</span>
                                            <?php endif; ?>
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
                                            <?php if ((string) $offer->status === TransferOffer::STATUS_PENDING && (int) ($offer->expires_at ?? 0) > 0): ?>
                                                <br><span class="text-muted-gm">scade <?= date('d/m/Y H:i', (int) $offer->expires_at) ?></span>
                                                <span class="auction-countdown text-warning" style="margin-left:.3rem" data-expires="<?= (int) $offer->expires_at ?>" data-prefix="tra ">--</span>
                                            <?php endif; ?>
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

<!-- Modale offerta mercato -->
<div class="modal fade" id="bidModal" tabindex="-1" aria-labelledby="bidModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="background:#1a2235;border:1px solid var(--border)">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title text-white mb-0" id="bidModalLabel">—</h5>
                    <div class="text-muted-gm" style="font-size:.72rem" id="bidModalType"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="bidModalForm" method="post">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                <input type="hidden" name="offered_fee" id="bidModalFee" value="0">
                <div class="modal-body pt-3">
                    <div class="d-flex justify-content-between mb-3 pb-2" style="border-bottom:1px solid var(--border)">
                        <div>
                            <div class="text-muted-gm" style="font-size:.72rem">Richiesta attuale</div>
                            <div class="text-gold fw-bold fs-5" id="bidModalAsk">—</div>
                        </div>
                        <div id="bidModalCurrentBidWrap" style="display:none;text-align:right">
                            <div class="text-muted-gm" style="font-size:.72rem">Tua offerta</div>
                            <div class="text-warning fw-bold fs-5" id="bidModalCurrentBid">—</div>
                        </div>
                    </div>
                    <label class="text-muted-gm small d-block mb-1" for="bidModalAmount">La tua offerta (€)</label>
                    <input type="number" id="bidModalAmount" min="1" step="1000" value="0"
                           class="form-control bg-dark text-white border-secondary">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                    <button type="button" class="btn btn-gold btn-sm" id="bidModalConfirm">Conferma</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->registerJs(<<<JS
(function () {
    function fmtRemaining(sec) {
        sec = Math.max(0, sec | 0);
        var d = Math.floor(sec / 86400);
        var h = Math.floor((sec % 86400) / 3600);
        var m = Math.floor((sec % 3600) / 60);
        var s = sec % 60;
        if (d > 0) return d + 'g ' + h + 'h ' + m + 'm';
        if (h > 0) return h + 'h ' + m + 'm ' + s + 's';
        if (m > 0) return m + 'm ' + s + 's';
        return s + 's';
    }

    function tickCountdowns() {
        var now = Math.floor(Date.now() / 1000);
        document.querySelectorAll('.auction-countdown[data-expires]').forEach(function (el) {
            var exp = parseInt(el.getAttribute('data-expires') || '0', 10);
            var pref = el.getAttribute('data-prefix') || '';
            if (!exp || isNaN(exp)) { el.textContent = '--'; return; }
            var rem = exp - now;
            if (rem <= 0) {
                el.textContent = 'scaduta';
                el.classList.remove('text-warning');
                el.classList.add('text-danger');
                return;
            }
            el.textContent = pref + fmtRemaining(rem);
        });
    }
    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    var bidModalEl = document.getElementById('bidModal');
    if (bidModalEl) {
        var bidModal = new bootstrap.Modal(bidModalEl);
        document.querySelectorAll('.js-bid-open').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var ask        = parseInt(btn.dataset.ask || '0', 10);
                var currentBid = parseInt(btn.dataset.currentBid || '0', 10);
                var fmt        = function (n) { return '€' + n.toLocaleString('it-IT'); };

                document.getElementById('bidModalLabel').textContent = btn.dataset.player || '—';
                document.getElementById('bidModalType').textContent  = btn.dataset.type   || '';
                document.getElementById('bidModalAsk').textContent   = fmt(ask);
                document.getElementById('bidModalForm').action       = btn.dataset.action || '';
                document.getElementById('bidModalAmount').value      = currentBid > 0 ? currentBid : ask;

                var wrap = document.getElementById('bidModalCurrentBidWrap');
                if (currentBid > 0) {
                    wrap.style.display = '';
                    document.getElementById('bidModalCurrentBid').textContent = fmt(currentBid);
                } else {
                    wrap.style.display = 'none';
                }

                bidModal.show();
                setTimeout(function () { document.getElementById('bidModalAmount').focus(); }, 300);
            });
        });

        document.getElementById('bidModalConfirm').addEventListener('click', function () {
            var amount = parseInt(document.getElementById('bidModalAmount').value, 10);
            if (!amount || amount < 1) { return; }
            document.getElementById('bidModalFee').value = amount;
            document.getElementById('bidModalForm').submit();
        });
    }
}());
JS); ?>
