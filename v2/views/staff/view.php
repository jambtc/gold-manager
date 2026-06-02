<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\Staff[] $staff */
/** @var app\models\StaffMarket[] $market */
/** @var app\models\StaffHistory[] $history */
/** @var array<int, app\models\MarketBid> $pendingByCandidate */
/** @var int $currentSeason */

use app\assets\StaffViewAsset;
use app\models\Staff;
use app\components\UiIconHelper;
use yii\helpers\Html;

StaffViewAsset::register($this);

$this->title = Yii::t('app', 'Staff Management');
$this->params['breadcrumbs'][] = $this->title;

$roleLabel = static function (string $role): string {
    return match ($role) {
        Staff::ROLE_HEAD_COACH => Yii::t('app', 'Coach'),
        Staff::ROLE_ASSISTANT_COACH => Yii::t('app', 'Deputy'),
        Staff::ROLE_GOALKEEPING_COACH => Yii::t('app', 'GK Coach'),
        Staff::ROLE_DOCTOR => Yii::t('app', 'Doctor'),
        Staff::ROLE_FITNESS_COACH => Yii::t('app', 'Physiotherapist'),
        Staff::ROLE_SCOUT => Yii::t('app', 'Scout'),
        default => $role,
    };
};
$roleIcon = static fn(string $role): string => UiIconHelper::renderStaffRoleIcon($role, 13);
$dotAttempts = static function (int $n): string {
    $full = str_repeat('●', max(0, min(4, $n)));
    $empty = str_repeat('○', max(0, 4 - max(0, min(4, $n))));
    return $full . $empty;
};
$staffWages = (int) array_sum(array_map(static fn($s) => (int) $s->salary, $staff));
$staffByRole = [];
foreach ($staff as $member) {
    $staffByRole[$member->role] = $member;
}
$orderedRoles = [
    Staff::ROLE_HEAD_COACH,
    Staff::ROLE_ASSISTANT_COACH,
    Staff::ROLE_GOALKEEPING_COACH,
    Staff::ROLE_DOCTOR,
    Staff::ROLE_FITNESS_COACH,
    Staff::ROLE_SCOUT,
];
?>

<div class="staff-view">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted-gm mb-0"><?= Yii::t('app', 'Negotiate, hire, renew and manage your staff.') ?></p>
        </div>
        <div class="text-end">
            <small class="d-block text-muted-gm"><?= Yii::t('app', 'Staff wages / season') ?></small>
            <span class="h4 text-gold">€<?= number_format($staffWages, 0, ',', '.') ?></span>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="staffTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button" role="tab">
                <?= Yii::t('app', 'Current Staff') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="market-tab" data-bs-toggle="tab" data-bs-target="#market" type="button" role="tab">
                <?= Yii::t('app', 'Staff Market') ?>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                <?= Yii::t('app', 'History') ?>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="current" role="tabpanel" aria-labelledby="current-tab">
            <div class="row g-3">
                <?php foreach ($orderedRoles as $role):
                    $member = $staffByRole[$role] ?? null;
                ?>
                    <div class="col-lg-6">
                        <div class="gm-card h-100">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="fw-bold text-white">
                                        <span class="text-gold me-1"><?= $roleIcon($role) ?></span>
                                        <?= Html::encode($roleLabel($role)) ?>
                                    </div>
                                    <div class="text-muted-gm small">
                                        <?php if ($member): ?>
                                            <?php if (!empty($member->nationality)): ?><?= UiIconHelper::flagImg((string)$member->nationality, 14) ?> <?php endif; ?><?= Html::encode((string) $member->name) ?>
                                        <?php else: ?><?= Yii::t('app', 'None') ?><?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?php if ($member): ?>
                                        <div class="text-gold fw-bold"><?= Yii::t('app', 'Eff.') ?> <?= (int) $member->efficiency ?></div>
                                        <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'End') ?>: S<?= (int) $member->contract_ends ?></div>
                                    <?php else: ?>
                                        <div class="text-muted-gm small"><?= Yii::t('app', 'No contract') ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($member): ?>
                                <div class="small text-muted-gm mb-3">
                                    <?= Yii::t('app', 'Skills') ?> <?= (int) $member->ability ?> · <?= Yii::t('app', 'Motivation') ?> <?= (int) $member->motivation ?> · <?= Yii::t('app', 'Experience') ?> <?= (int) $member->experience ?>
                                </div>
                                <?php $proFee = $member->currentTerminationFee($currentSeason) ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small">
                                        <span class="text-muted-gm"><?= Yii::t('app', 'Wage') ?>:</span>
                                        <span class="text-white fw-bold">€<?= number_format((int) $member->salary, 0, ',', '.') ?></span>
                                        <?php if ($proFee > 0): ?>
                                        <br><span class="text-muted-gm" style="font-size:.70rem"><?= Yii::t('app', 'Term. fee') ?>: <span class="text-warning">€<?= number_format($proFee, 0, ',', '.') ?></span></span>
                                        <?php endif ?>
                                    </div>
                                    <?= Html::beginForm(['/staff/fire', 'id' => $member->id], 'post', ['class' => 'd-inline']) ?>
                                        <button type="submit"
                                                class="btn btn-outline-danger btn-sm"
                                                data-confirm="<?= Html::encode(Yii::t('app', 'Fire {name}? Termination fee: €{fee}', ['name' => $member->name, 'fee' => number_format($proFee, 0, ',', '.')])) ?>">
                                            <?= Yii::t('app', 'Fire') ?>
                                        </button>
                                    <?= Html::endForm() ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted-gm small mb-3"><?= Yii::t('app', 'Assign a') ?> <?= strtolower($roleLabel($role)) ?> <?= Yii::t('app', 'from the market') ?>.</div>
                                <a class="btn btn-outline-gold btn-sm" href="#" onclick="document.getElementById('market-tab').click();return false;"><?= Yii::t('app', 'Go to market') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="market" role="tabpanel" aria-labelledby="market-tab">
            <?php if (empty($market)): ?>
                <div class="gm-card text-muted-gm"><?= Yii::t('app', 'No candidates available.') ?></div>
            <?php else: ?>
                <div class="gm-card p-0 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table-gm w-100 mb-0">
                            <thead>
                                <tr>
                                    <th><?= Yii::t('app', 'Candidate') ?></th>
                                    <th><?= Yii::t('app', 'Role') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Ab') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Exp') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Mot') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Eff.') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Sea.') ?></th>
                                    <th><?= Yii::t('app', 'Expiry') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Request') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($market as $cand): ?>
                                <?php $pendingBid = $pendingByCandidate[(int) $cand->id] ?? null; ?>
                                <tr<?= ((int) $cand->expires_at > 0) ? ' data-expire-lock="order"' : '' ?>>
                                    <td>
                                        <div class="fw-bold text-white"><?php if (!empty($cand->nationality)): ?><?= UiIconHelper::flagImg((string)$cand->nationality, 14) ?> <?php endif; ?><?= Html::encode((string) $cand->name) ?></div>
                                        <div class="text-muted-gm small"><?= Yii::t('app', 'Attempts') ?>: <span class="text-gold fw-bold"><?= $dotAttempts((int) $cand->negotiations) ?></span></div>
                                    </td>
                                    <td>
                                        <span class="me-1 text-gold"><?= $roleIcon((string) $cand->role) ?></span>
                                        <?= Html::encode($roleLabel((string) $cand->role)) ?>
                                    </td>
                                    <td class="text-center"><?= (int) $cand->ability ?></td>
                                    <td class="text-center"><?= (int) $cand->experience ?></td>
                                    <td class="text-center"><?= (int) $cand->motivation ?></td>
                                    <td class="text-center text-gold fw-bold"><?= number_format($cand->getEfficiencyPreview(), 1) ?></td>
                                    <td class="text-center"><?= (int) $cand->contract_length ?></td>
                                    <td>
                                        <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'expires') ?> <?= date('d/m/Y H:i', (int) $cand->expires_at) ?></div>
                                        <div class="auction-countdown text-warning" data-expires="<?= (int) $cand->expires_at ?>" data-prefix="<?= Yii::t('app', 'tra ') ?>" data-expire-lock="order">--</div>
                                        <?php if ($pendingBid): ?>
                                            <div class="text-warning" style="font-size:.72rem">
                                                <?= Yii::t('app', 'Your') ?>: €<?= number_format((int) $pendingBid->bid_amount, 0, ',', '.') ?>
                                                · <span class="auction-countdown" data-expires="<?= (int) $pendingBid->expires_at ?>" data-prefix="<?= Yii::t('app', 'scade tra ') ?>">--</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end text-white fw-bold">€<?= number_format((int) $cand->salary, 0, ',', '.') ?></td>
                                    <td class="text-end">
                                        <button type="button"
                                                class="btn btn-gold btn-sm js-bid-open"
                                                data-candidate="<?= Html::encode((string) $cand->name) ?>"
                                                data-role="<?= Html::encode($roleLabel((string) $cand->role)) ?>"
                                                data-action="<?= Html::encode(\yii\helpers\Url::to(['/staff/negotiate', 'id' => (int) $cand->id])) ?>"
                                                data-ask="<?= (int) $cand->salary ?>"
                                                data-current-bid="<?= $pendingBid ? (int) $pendingBid->bid_amount : 0 ?>"
                                                data-expires="<?= (int) $cand->expires_at ?>">
                                            <?= $pendingBid ? Yii::t('app', 'Edit offer') : Yii::t('app', 'Bid') ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
            <?php if (empty($history)): ?>
                <div class="gm-card text-muted-gm"><?= Yii::t('app', 'Staff history empty.') ?></div>
            <?php else: ?>
                <div class="gm-card">
                    <div class="table-responsive">
                        <table class="table-gm w-100 mb-0">
                            <?php
                            $reasonBadge = static function (string $reason): string {
                                return match ($reason) {
                                    'hired'   => '<span class="badge" style="background:rgba(34,197,94,.15);color:#22c55e;font-size:.65rem">↗ ' . Yii::t('app', 'Hired') . '</span>',
                                    'fired'   => '<span class="badge" style="background:rgba(239,68,68,.12);color:#ef4444;font-size:.65rem">↙ ' . Yii::t('app', 'Fired') . '</span>',
                                    'expired' => '<span class="badge" style="background:rgba(156,163,175,.10);color:#9ca3af;font-size:.65rem">⏱ ' . Yii::t('app', 'Expired') . '</span>',
                                    default   => Html::encode($reason),
                                };
                            };
                            ?>
                            <thead>
                                <tr>
                                    <th><?= Yii::t('app', 'Name') ?></th>
                                    <th><?= Yii::t('app', 'Role') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Eff.') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Season') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Event') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?= Html::encode((string) $row->name) ?></td>
                                    <td><?= Html::encode($roleLabel((string) $row->role)) ?></td>
                                    <td class="text-center text-gold fw-bold"><?= (int) $row->efficiency ?></td>
                                    <td class="text-center text-muted-gm">S<?= (int) $row->left_season ?></td>
                                    <td class="text-end"><?= $reasonBadge((string) $row->left_reason) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="staffBidModal" tabindex="-1"
     aria-labelledby="staffBidModalLabel" aria-hidden="true"
     data-expired-text="<?= Html::encode(Yii::t('app', 'expired')) ?>">
    <div class="modal-dialog modal-sm">
        <div class="modal-content" style="background:#1a2235;border:1px solid var(--border)">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title text-white mb-0" id="staffBidModalLabel">—</h5>
                    <div class="text-muted-gm" style="font-size:.72rem" id="staffBidModalRole"></div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="staffBidModalForm" method="post">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->csrfToken ?>">
                <input type="hidden" name="offered_fee" id="staffBidModalFee" value="0">
                <input type="hidden" name="contract_length" id="staffBidModalContractLength" value="1">
                <div class="modal-body pt-3">
                    <div class="d-flex justify-content-between mb-3 pb-2" style="border-bottom:1px solid var(--border)">
                        <div>
                            <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Base salary requested') ?></div>
                            <div class="text-gold fw-bold fs-5" id="staffBidModalAsk">—</div>
                        </div>
                        <div id="staffBidModalCurrentWrap" style="display:none;text-align:right">
                            <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Your current offer') ?></div>
                            <div class="text-warning fw-bold fs-5" id="staffBidModalCurrent">—</div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="text-muted-gm small mb-1"><?= Yii::t('app', 'Contract duration') ?></div>
                        <div class="btn-group w-100" id="staffContractLengthGroup">
                            <button type="button" class="btn btn-gold btn-sm js-contract-len" data-len="1" data-discount="1.00">1 <?= Yii::t('app', 'season') ?></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-contract-len" data-len="2" data-discount="0.90">2 <?= Yii::t('app', 'seasons') ?> <span class="text-success small">−10%</span></button>
                            <button type="button" class="btn btn-outline-secondary btn-sm js-contract-len" data-len="3" data-discount="0.82">3 <?= Yii::t('app', 'seasons') ?> <span class="text-success small">−18%</span></button>
                        </div>
                    </div>

                    <label class="text-muted-gm small d-block mb-1" for="staffBidModalAmount"><?= Yii::t('app', 'Your offer (€/year)') ?></label>
                    <input type="number" id="staffBidModalAmount" min="1" step="1000" value="0"
                           class="form-control bg-dark text-white border-secondary">

                    <div class="d-flex justify-content-between mt-2" style="font-size:.72rem;color:var(--text-secondary)">
                        <span><?= Yii::t('app', 'Est. termination fee') ?>: <span id="staffBidFeePreview" class="text-warning">—</span></span>
                        <span><?= Yii::t('app', 'Total') ?>: <span id="staffBidTotalPreview" class="text-gold">—</span></span>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 gap-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><?= Yii::t('app', 'Cancel') ?></button>
                    <button type="button" class="btn btn-outline-gold btn-sm" id="staffBidRaise15">+15%</button>
                    <button type="button" class="btn btn-gold btn-sm" id="staffBidModalConfirm"><?= Yii::t('app', 'Confirm') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS in web/bundles/staff/view.js via StaffViewAsset -->
