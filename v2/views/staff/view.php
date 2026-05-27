<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\Staff[] $staff */
/** @var app\models\StaffMarket[] $market */
/** @var app\models\StaffHistory[] $history */

use app\models\Staff;
use app\components\UiIconHelper;
use yii\helpers\Html;

$this->title = 'Gestione Staff';
$this->params['breadcrumbs'][] = $this->title;

$roleLabel = static function (string $role): string {
    return match ($role) {
        Staff::ROLE_HEAD_COACH => 'Allenatore',
        Staff::ROLE_ASSISTANT_COACH => 'Vice',
        Staff::ROLE_GOALKEEPING_COACH => 'All. Portieri',
        Staff::ROLE_DOCTOR => 'Medico',
        Staff::ROLE_FITNESS_COACH => 'Fisioterapista',
        Staff::ROLE_SCOUT => 'Scout',
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
            <p class="text-muted-gm mb-0">Contratta, assumi, rinnova e gestisci il tuo staff.</p>
        </div>
        <div class="text-end">
            <small class="d-block text-muted-gm">Stipendi staff / stagione</small>
            <span class="h4 text-gold">€<?= number_format($staffWages, 0, ',', '.') ?></span>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4" id="staffTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="current-tab" data-bs-toggle="tab" data-bs-target="#current" type="button" role="tab">
                Staff Corrente
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="market-tab" data-bs-toggle="tab" data-bs-target="#market" type="button" role="tab">
                Mercato Staff
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">
                Storico
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
                                        <?= $member ? Html::encode((string) $member->name) : 'Nessuno' ?>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <?php if ($member): ?>
                                        <div class="text-gold fw-bold">Eff. <?= (int) $member->efficiency ?></div>
                                        <div class="text-muted-gm" style="font-size:.72rem">Fine: S<?= (int) $member->contract_ends ?></div>
                                    <?php else: ?>
                                        <div class="text-muted-gm small">Nessun contratto</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($member): ?>
                                <div class="small text-muted-gm mb-3">
                                    Abilità <?= (int) $member->ability ?> · Motivazione <?= (int) $member->motivation ?> · Esperienza <?= (int) $member->experience ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="small">
                                        <span class="text-muted-gm">Stipendio:</span>
                                        <span class="text-white fw-bold">€<?= number_format((int) $member->salary, 0, ',', '.') ?></span>
                                    </div>
                                    <?= Html::beginForm(['/staff/fire', 'id' => $member->id], 'post', ['class' => 'd-inline']) ?>
                                        <button type="submit"
                                                class="btn btn-outline-danger btn-sm"
                                                data-confirm="Licenziare <?= Html::encode((string) $member->name) ?>? (Indennizzo: 1 settimana)">
                                            Licenzia
                                        </button>
                                    <?= Html::endForm() ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted-gm small mb-3">Assegna un <?= strtolower($roleLabel($role)) ?> dal mercato.</div>
                                <a class="btn btn-outline-gold btn-sm" data-bs-toggle="tab" href="#market" role="tab">Vai al mercato</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="market" role="tabpanel" aria-labelledby="market-tab">
            <?php if (empty($market)): ?>
                <div class="gm-card text-muted-gm">Nessun candidato disponibile.</div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($market as $cand): ?>
                        <div class="col-lg-6">
                            <div class="gm-card h-100">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <div class="fw-bold text-white"><?= Html::encode((string) $cand->name) ?></div>
                                        <div class="text-muted-gm small">
                                            <span class="me-1 text-gold"><?= $roleIcon((string) $cand->role) ?></span>
                                            <?= Html::encode($roleLabel((string) $cand->role)) ?>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="text-gold fw-bold">Eff. <?= number_format($cand->getEfficiencyPreview(), 1) ?></div>
                                        <div class="text-muted-gm" style="font-size:.72rem">Contratto: <?= (int) $cand->contract_length ?> stag.</div>
                                    </div>
                                </div>

                                <div class="small text-muted-gm mb-2">
                                    Ab. <?= (int) $cand->ability ?> · Mot. <?= (int) $cand->motivation ?> · Exp <?= (int) $cand->experience ?>
                                </div>
                                <div class="small mb-3">
                                    <span class="text-muted-gm">Richiesta:</span>
                                    <span class="text-white fw-bold">€<?= number_format((int) $cand->salary, 0, ',', '.') ?></span>
                                    <span class="ms-2 text-muted-gm">Tentativi:</span>
                                    <span class="text-gold fw-bold"><?= $dotAttempts((int) $cand->negotiations) ?></span>
                                </div>

                                <div class="d-flex gap-2">
                                    <?= Html::beginForm(['/staff/negotiate', 'id' => $cand->id], 'post', ['class' => 'd-inline']) ?>
                                        <button class="btn btn-gold btn-sm" type="submit" <?= (int) $cand->negotiations <= 0 ? 'disabled' : '' ?>>
                                            Contratta
                                        </button>
                                    <?= Html::endForm() ?>

                                    <?= Html::beginForm(['/staff/raise-offer', 'id' => $cand->id], 'post', ['class' => 'd-inline']) ?>
                                        <button class="btn btn-outline-gold btn-sm"
                                                type="submit"
                                                <?= (int) $cand->raise_used > 0 ? 'disabled' : '' ?>
                                                data-confirm="Alzare stipendio e resettare tentativi a 4?">
                                            Rialza offerta
                                        </button>
                                    <?= Html::endForm() ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="history" role="tabpanel" aria-labelledby="history-tab">
            <?php if (empty($history)): ?>
                <div class="gm-card text-muted-gm">Storico staff vuoto.</div>
            <?php else: ?>
                <div class="gm-card">
                    <div class="table-responsive">
                        <table class="table-gm w-100 mb-0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Ruolo</th>
                                    <th class="text-center">Eff.</th>
                                    <th class="text-center">Stagione uscita</th>
                                    <th class="text-end">Motivo</th>
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
