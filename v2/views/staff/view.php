<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\Staff[] $staff */
/** @var app\models\StaffMarket[] $market */
/** @var app\models\StaffHistory[] $history */
/** @var array<int, app\models\MarketBid> $pendingByCandidate */

use app\models\Staff;
use app\components\UiIconHelper;
use yii\helpers\Html;

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
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small">
                                        <span class="text-muted-gm"><?= Yii::t('app', 'Wage') ?>:</span>
                                        <span class="text-white fw-bold">€<?= number_format((int) $member->salary, 0, ',', '.') ?></span>
                                    </div>
                                    <?= Html::beginForm(['/staff/fire', 'id' => $member->id], 'post', ['class' => 'd-inline']) ?>
                                        <button type="submit"
                                                class="btn btn-outline-danger btn-sm"
                                                data-confirm="<?= Yii::t('app', 'Dismiss') ?> <?= Html::encode((string) $member->name) ?>? (<?= Yii::t('app', 'Severance: 1 week') ?>)">
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
                                        <div class="d-inline-flex gap-1 align-items-center">
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
                                            <?= Html::beginForm(['/staff/raise-offer', 'id' => $cand->id], 'post', ['class' => 'd-inline']) ?>
                                            <button class="btn btn-outline-gold btn-sm"
                                                    type="submit"
                                                    data-confirm="<?= Yii::t('app', 'Raise the offer by 15%?') ?>">
                                                +15%
                                            </button>
                                            <?= Html::endForm() ?>
                                        </div>
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
                            <thead>
                                <tr>
                                    <th><?= Yii::t('app', 'Name') ?></th>
                                    <th><?= Yii::t('app', 'Role') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Eff.') ?></th>
                                    <th class="text-center"><?= Yii::t('app', 'Exit season') ?></th>
                                    <th class="text-end"><?= Yii::t('app', 'Reason') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($history as $row): ?>
                                <tr>
                                    <td><?= Html::encode((string) $row->name) ?></td>
                                    <td><?= Html::encode($roleLabel((string) $row->role)) ?></td>
                                    <td class="text-center text-gold fw-bold"><?= (int) $row->efficiency ?></td>
                                    <td class="text-center">S<?= (int) $row->left_season ?></td>
                                    <td class="text-end text-muted-gm"><?= Html::encode((string) $row->left_reason) ?></td>
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

<div class="modal fade" id="staffBidModal" tabindex="-1" aria-labelledby="staffBidModalLabel" aria-hidden="true">
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
                <div class="modal-body pt-3">
                    <div class="d-flex justify-content-between mb-3 pb-2" style="border-bottom:1px solid var(--border)">
                        <div>
                            <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Current request') ?></div>
                            <div class="text-gold fw-bold fs-5" id="staffBidModalAsk">—</div>
                        </div>
                        <div id="staffBidModalCurrentWrap" style="display:none;text-align:right">
                            <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Your bid') ?></div>
                            <div class="text-warning fw-bold fs-5" id="staffBidModalCurrent">—</div>
                        </div>
                    </div>
                    <label class="text-muted-gm small d-block mb-1" for="staffBidModalAmount"><?= Yii::t('app', 'Your bid (€)') ?></label>
                    <input type="number" id="staffBidModalAmount" min="1" step="1000" value="0"
                           class="form-control bg-dark text-white border-secondary">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><?= Yii::t('app', 'Cancel') ?></button>
                    <button type="button" class="btn btn-gold btn-sm" id="staffBidModalConfirm"><?= Yii::t('app', 'Confirm') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->registerJs(<<<JS
(function () {
    var expiredText = <?= \yii\helpers\Json::encode(Yii::t('app', 'expired')) ?>;

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

    function lockExpiredOrder(el) {
        var row = el.closest('[data-expire-lock=\"order\"]');
        if (!row || row.dataset.orderLocked === '1') {
            return;
        }
        row.dataset.orderLocked = '1';
        row.querySelectorAll('input, button, select, textarea').forEach(function (ctrl) {
            ctrl.disabled = true;
            if (ctrl.classList && ctrl.classList.contains('btn-gold')) {
                ctrl.classList.remove('btn-gold');
                ctrl.classList.add('btn-outline-secondary');
            }
        });
    }

    function tickCountdowns() {
        var now = Math.floor(Date.now() / 1000);
        document.querySelectorAll('.auction-countdown[data-expires]').forEach(function (el) {
            var exp = parseInt(el.getAttribute('data-expires') || '0', 10);
            var pref = el.getAttribute('data-prefix') || '';
            if (!exp || isNaN(exp)) { el.textContent = '--'; return; }
            var rem = exp - now;
            if (rem <= 0) {
                el.textContent = expiredText;
                el.classList.remove('text-warning');
                el.classList.add('text-danger');
                lockExpiredOrder(el);
                return;
            }
            el.textContent = pref + fmtRemaining(rem);
        });
    }
    tickCountdowns();
    setInterval(tickCountdowns, 1000);

    var modalEl = document.getElementById('staffBidModal');
    if (!modalEl || !window.bootstrap) {
        return;
    }
    var modal = new bootstrap.Modal(modalEl);
    var fmtEuro = function (n) { return '€' + Number(n || 0).toLocaleString('it-IT'); };

    document.querySelectorAll('.js-bid-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var now = Math.floor(Date.now() / 1000);
            var exp = parseInt(btn.dataset.expires || '0', 10);
            if (exp > 0 && exp <= now) {
                return;
            }

            var ask = parseInt(btn.dataset.ask || '0', 10);
            var current = parseInt(btn.dataset.currentBid || '0', 10);
            document.getElementById('staffBidModalLabel').textContent = btn.dataset.candidate || '—';
            document.getElementById('staffBidModalRole').textContent = btn.dataset.role || '';
            document.getElementById('staffBidModalAsk').textContent = fmtEuro(ask);
            document.getElementById('staffBidModalForm').action = btn.dataset.action || '';
            document.getElementById('staffBidModalAmount').value = current > 0 ? current : ask;

            var wrap = document.getElementById('staffBidModalCurrentWrap');
            if (current > 0) {
                wrap.style.display = '';
                document.getElementById('staffBidModalCurrent').textContent = fmtEuro(current);
            } else {
                wrap.style.display = 'none';
            }

            modal.show();
            setTimeout(function () {
                var amountEl = document.getElementById('staffBidModalAmount');
                if (amountEl) amountEl.focus();
            }, 250);
        });
    });

    document.getElementById('staffBidModalConfirm').addEventListener('click', function () {
        var amount = parseInt(document.getElementById('staffBidModalAmount').value, 10);
        if (!amount || amount < 1) {
            return;
        }
        document.getElementById('staffBidModalFee').value = amount;
        document.getElementById('staffBidModalForm').submit();
    });
}());
JS); ?>
