<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\TeamSponsor|null $activeSponsor */
/** @var app\models\Sponsor[] $availableSponsors */
/** @var app\models\TeamSponsor[] $history */
/** @var int $prestige */
/** @var array<int, app\models\MarketBid> $pendingBySponsor */

use app\models\TeamSponsor;
use yii\helpers\Html;

$this->title = Yii::t('app', 'Commercial Office: Sponsors');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="sponsor-index">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted-gm mb-0"><?= Yii::t('app', 'Manage club partnerships and commercial renewals.') ?></p>
        </div>
        <div class="text-end text-gold">
            <small class="d-block text-muted-gm"><?= Yii::t('app', 'Club Prestige') ?></small>
            <span class="h6 d-block mb-1"><?= (int) $prestige ?>/100</span>
            <small class="d-block text-muted-gm"><?= Yii::t('app', 'Current Budget') ?></small>
            <span class="h4">€<?= number_format((int) $team->budget, 0, ',', '.') ?></span>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sp-active" type="button"><?= Yii::t('app', 'Active') ?></button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sp-offers" type="button"><?= Yii::t('app', 'Offers') ?></button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sp-history" type="button"><?= Yii::t('app', 'History') ?></button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="sp-active">
            <div class="gm-card mb-4" style="border-left: 5px solid var(--gold);">
                <h3 class="h5 mb-4 text-white"><i class="bi bi-briefcase"></i> <?= Yii::t('app', 'Official Sponsor') ?></h3>
                <?php if ($activeSponsor && $activeSponsor->sponsor): ?>
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="sponsor-logo-placeholder mx-auto mb-0" style="width: 100px; height: 100px; border-color: var(--gold);">
                                <i class="bi bi-buildings text-gold display-4"></i>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h2 class="text-white mb-1"><?= Html::encode($activeSponsor->sponsor->name) ?></h2>
                            <div class="text-gold small fw-bold"><?= Yii::t('app', 'ACTIVE CONTRACT') ?></div>
                            <p class="text-muted-gm small mt-2 mb-1"><?= Yii::t('app', 'Expiry') ?>: <?= date('d M Y', (int) $activeSponsor->ends_at) ?></p>
                            <p class="text-muted-gm small mb-0"><?= Yii::t('app', 'Signed') ?>: <?= date('d M Y', (int) $activeSponsor->signed_at) ?></p>
                        </div>
                        <div class="col-md-5">
                            <div class="row text-center g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-dark border border-secondary">
                                        <div class="text-muted-gm smaller"><?= Yii::t('app', 'BASE / SEASON') ?></div>
                                        <div class="text-white fw-bold">€<?= number_format((int) $activeSponsor->sponsor->base_payment, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-dark border border-secondary">
                                        <div class="text-muted-gm smaller"><?= Yii::t('app', 'WIN BONUS') ?></div>
                                        <div class="text-success fw-bold">€<?= number_format((int) $activeSponsor->sponsor->win_bonus, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <?= Html::a(Yii::t('app', 'RENEW'), ['sponsor/renew'], [
                                    'class' => 'btn btn-outline-warning btn-sm w-100',
                                    'data-method' => 'post',
                                    'data-confirm' => Yii::t('app', 'Confirm sponsor contract renewal?')
                                ]) ?>
                                <?= Html::a(Yii::t('app', 'TERMINATE'), ['sponsor/terminate'], [
                                    'class' => 'btn btn-outline-danger btn-sm w-100',
                                    'data-method' => 'post',
                                    'data-confirm' => Yii::t('app', 'Confirm termination? A penalty will be applied.')
                                ]) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-patch-question text-muted-gm display-1 mb-3"></i>
                        <h4 class="text-muted-gm"><?= Yii::t('app', 'No active sponsor') ?></h4>
                        <p class="text-muted-gm small mb-0"><?= Yii::t('app', 'Go to Offers and sign a new contract.') ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="sp-offers">
            <h3 class="h5 mb-3 text-white"><i class="bi bi-file-earmark-plus"></i> <?= Yii::t('app', 'Available Offers') ?></h3>
            <p class="text-muted-gm small mb-4"><?= Yii::t('app', 'Offers depend on club prestige and change at the start of the season.') ?></p>

            <?php if (empty($availableSponsors)): ?>
                <div class="gm-card text-center py-4">
                    <i class="bi bi-hourglass-split text-muted-gm display-6 d-block mb-2"></i>
                    <div class="text-muted-gm"><?= Yii::t('app', 'No offers available at the moment.') ?></div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($availableSponsors as $sponsor): ?>
                        <?php $pendingBid = $pendingBySponsor[(int) $sponsor->id] ?? null; ?>
                        <div class="col-lg-6">
                            <div class="sponsor-card h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="sponsor-logo-placeholder">
                                        <i class="bi bi-buildings text-secondary fs-2"></i>
                                    </div>
                                    <div class="text-end">
                                        <span class="bonus-tag"><?= Yii::t('app', 'SIGNING BONUS') ?>: €<?= number_format((int) $sponsor->base_payment * 0.1, 0, ',', '.') ?></span>
                                    </div>
                                </div>

                                <h4 class="text-white mb-2"><?= Html::encode($sponsor->name) ?></h4>

                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="text-muted-gm smaller mb-1 text-uppercase"><?= Yii::t('app', 'Base Payment') ?></div>
                                        <div class="h5 text-white mb-0">€<?= number_format((int) $sponsor->base_payment, 0, ',', '.') ?></div>
                                    </div>
                                    <div class="col-6 border-start border-secondary ps-3">
                                        <div class="text-muted-gm smaller mb-1 text-uppercase"><?= Yii::t('app', 'Win Prize') ?></div>
                                        <div class="h5 text-success mb-0">+€<?= number_format((int) $sponsor->win_bonus, 0, ',', '.') ?></div>
                                    </div>
                                </div>

                                <div class="text-muted-gm small mb-3">
                                    <?= Yii::t('app', 'Duration') ?>: <?= (int) $sponsor->duration_seasons ?> <?= Yii::t('app', 'season(s)') ?>
                                    · <?= Yii::t('app', 'Prestige req.') ?>: <?= (int) $sponsor->prestige_required ?>
                                </div>
                                <?php if ($pendingBid): ?>
                                <div class="text-warning small mb-3">
                                    <?= Yii::t('app', 'Your bid') ?>: €<?= number_format((int) $pendingBid->bid_amount, 0, ',', '.') ?>
                                    · <span class="auction-countdown" data-expires="<?= (int) $pendingBid->expires_at ?>"><?= Yii::t('app', 'expires') ?> <?= date('d/m H:i', (int) $pendingBid->expires_at) ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="mt-auto">
                                    <?= Html::beginForm(['sponsor/sign', 'id' => $sponsor->id], 'post', ['class' => 'd-flex gap-2']) ?>
                                    <input type="number"
                                           name="offered_fee"
                                           min="1"
                                           step="1000"
                                           value="<?= (int) ($pendingBid ? $pendingBid->bid_amount : max(1000, (int) round((int) $sponsor->base_payment * 0.10))) ?>"
                                           class="form-control form-control-sm bg-dark text-white border-secondary"
                                           style="max-width:150px">
                                    <button type="submit"
                                            class="<?= ($activeSponsor && (int) $activeSponsor->sponsor_id !== (int) $sponsor->id) ? 'btn btn-outline-warning flex-grow-1 fw-bold btn-sm' : 'btn btn-gold flex-grow-1 fw-bold btn-sm' ?>"
                                            data-confirm="<?= Yii::t('app', 'Confirm sending sponsor offer for') ?> <?= Html::encode($sponsor->name) ?>?">
                                        <?= Yii::t('app', 'OFFER') ?>
                                    </button>
                                    <?= Html::endForm() ?>
                                    <?php if ($pendingBid): ?>
                                    <?= Html::beginForm(['sponsor/raise-offer', 'id' => $sponsor->id], 'post') ?>
                                    <button type="submit" class="btn btn-outline-warning btn-sm fw-bold"
                                            title="<?= Yii::t('app', 'Raise +15%') ?>"
                                            data-confirm="<?= Yii::t('app', 'Auto-raise your bid by 15%?') ?>">
                                        <i class="bi bi-arrow-up-circle"></i>
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

        <div class="tab-pane fade" id="sp-history">
            <h3 class="h5 mb-3 text-white"><i class="bi bi-clock-history"></i> <?= Yii::t('app', 'Contract History') ?></h3>
            <div class="gm-card">
                <?php if (empty($history)): ?>
                    <div class="text-muted-gm"><?= Yii::t('app', 'No contract recorded.') ?></div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th><?= Yii::t('app', 'Sponsor') ?></th>
                                <th><?= Yii::t('app', 'Sign') ?></th>
                                <th><?= Yii::t('app', 'End') ?></th>
                                <th><?= Yii::t('app', 'Status') ?></th>
                                <th class="text-end"><?= Yii::t('app', 'Season Base') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?= Html::encode($row->sponsor?->name ?? 'N/D') ?></td>
                                    <td><?= date('d/m/Y', (int) $row->signed_at) ?></td>
                                    <td><?= date('d/m/Y', (int) $row->ends_at) ?></td>
                                    <td>
                                        <?php
                                        $badge = match ($row->status) {
                                            TeamSponsor::STATUS_ACTIVE => 'success',
                                            TeamSponsor::STATUS_EXPIRED => 'secondary',
                                            default => 'warning',
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badge ?>"><?= strtoupper(Html::encode($row->status)) ?></span>
                                    </td>
                                    <td class="text-end">€<?= number_format((int) ($row->sponsor->base_payment ?? 0), 0, ',', '.') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
