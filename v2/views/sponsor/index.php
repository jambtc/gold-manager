<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\TeamSponsor|null $activeSponsor */
/** @var app\models\Sponsor[] $availableSponsors */
/** @var app\models\TeamSponsor[] $history */
/** @var int $prestige */

use app\models\TeamSponsor;
use yii\helpers\Html;

$this->title = 'Ufficio Commerciale: Sponsor';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="sponsor-index">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted-gm mb-0">Gestisci partnership e rinnovi commerciali del club.</p>
        </div>
        <div class="text-end text-gold">
            <small class="d-block text-muted-gm">Prestigio Club</small>
            <span class="h6 d-block mb-1"><?= (int) $prestige ?>/100</span>
            <small class="d-block text-muted-gm">Budget Attuale</small>
            <span class="h4">€<?= number_format((int) $team->budget, 0, ',', '.') ?></span>
        </div>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#sp-active" type="button">Attivo</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sp-offers" type="button">Offerte</button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sp-history" type="button">Storico</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="sp-active">
            <div class="gm-card mb-4" style="border-left: 5px solid var(--gold);">
                <h3 class="h5 mb-4 text-white"><i class="bi bi-briefcase"></i> Sponsor Ufficiale</h3>
                <?php if ($activeSponsor && $activeSponsor->sponsor): ?>
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <div class="sponsor-logo-placeholder mx-auto mb-0" style="width: 100px; height: 100px; border-color: var(--gold);">
                                <i class="bi bi-buildings text-gold display-4"></i>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <h2 class="text-white mb-1"><?= Html::encode($activeSponsor->sponsor->name) ?></h2>
                            <div class="text-gold small fw-bold">CONTRATTO ATTIVO</div>
                            <p class="text-muted-gm small mt-2 mb-1">Scadenza: <?= date('d M Y', (int) $activeSponsor->ends_at) ?></p>
                            <p class="text-muted-gm small mb-0">Firmato: <?= date('d M Y', (int) $activeSponsor->signed_at) ?></p>
                        </div>
                        <div class="col-md-5">
                            <div class="row text-center g-2 mb-3">
                                <div class="col-6">
                                    <div class="p-2 rounded bg-dark border border-secondary">
                                        <div class="text-muted-gm smaller">BASE / STAGIONE</div>
                                        <div class="text-white fw-bold">€<?= number_format((int) $activeSponsor->sponsor->base_payment, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-2 rounded bg-dark border border-secondary">
                                        <div class="text-muted-gm smaller">BONUS VITTORIA</div>
                                        <div class="text-success fw-bold">€<?= number_format((int) $activeSponsor->sponsor->win_bonus, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <?= Html::a('RINNOVA', ['sponsor/renew'], [
                                    'class' => 'btn btn-outline-warning btn-sm w-100',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Confermi il rinnovo del contratto sponsor?'
                                ]) ?>
                                <?= Html::a('RESCINDI', ['sponsor/terminate'], [
                                    'class' => 'btn btn-outline-danger btn-sm w-100',
                                    'data-method' => 'post',
                                    'data-confirm' => 'Confermi la rescissione? Verrà applicata una penale.'
                                ]) ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-patch-question text-muted-gm display-1 mb-3"></i>
                        <h4 class="text-muted-gm">Nessuno sponsor attivo</h4>
                        <p class="text-muted-gm small mb-0">Vai su Offerte e firma un nuovo contratto.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="sp-offers">
            <h3 class="h5 mb-3 text-white"><i class="bi bi-file-earmark-plus"></i> Offerte Disponibili</h3>
            <p class="text-muted-gm small mb-4">Le offerte dipendono dal prestigio del club e cambiano a inizio stagione.</p>

            <?php if (empty($availableSponsors)): ?>
                <div class="gm-card text-center py-4">
                    <i class="bi bi-hourglass-split text-muted-gm display-6 d-block mb-2"></i>
                    <div class="text-muted-gm">Nessuna offerta disponibile al momento.</div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($availableSponsors as $sponsor): ?>
                        <div class="col-lg-6">
                            <div class="sponsor-card h-100">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="sponsor-logo-placeholder">
                                        <i class="bi bi-buildings text-secondary fs-2"></i>
                                    </div>
                                    <div class="text-end">
                                        <span class="bonus-tag">BONUS FIRMA: €<?= number_format((int) $sponsor->base_payment * 0.1, 0, ',', '.') ?></span>
                                    </div>
                                </div>

                                <h4 class="text-white mb-2"><?= Html::encode($sponsor->name) ?></h4>

                                <div class="row g-3 mb-4">
                                    <div class="col-6">
                                        <div class="text-muted-gm smaller mb-1 text-uppercase">Pagamento Base</div>
                                        <div class="h5 text-white mb-0">€<?= number_format((int) $sponsor->base_payment, 0, ',', '.') ?></div>
                                    </div>
                                    <div class="col-6 border-start border-secondary ps-3">
                                        <div class="text-muted-gm smaller mb-1 text-uppercase">Premio Vittoria</div>
                                        <div class="h5 text-success mb-0">+€<?= number_format((int) $sponsor->win_bonus, 0, ',', '.') ?></div>
                                    </div>
                                </div>

                                <div class="text-muted-gm small mb-3">
                                    Durata: <?= (int) $sponsor->duration_seasons ?> stagione/i
                                    · Prestige req: <?= (int) $sponsor->prestige_required ?>
                                </div>

                                <div class="mt-auto">
                                    <?= Html::a(
                                        ($activeSponsor && (int) $activeSponsor->sponsor_id !== (int) $sponsor->id) ? 'CAMBIA SPONSOR' : 'FIRMA CONTRATTO',
                                        ['sponsor/sign', 'id' => $sponsor->id],
                                        [
                                            'class' => ($activeSponsor && (int) $activeSponsor->sponsor_id !== (int) $sponsor->id)
                                                ? 'btn btn-outline-warning w-100 fw-bold'
                                                : 'btn btn-gold w-100 fw-bold',
                                            'data-method' => 'post',
                                            'data-confirm' => ($activeSponsor && (int) $activeSponsor->sponsor_id !== (int) $sponsor->id)
                                                ? "Confermi cambio sponsor con {$sponsor->name}? È prevista penale di rescissione."
                                                : "Confermi firma contratto con {$sponsor->name}?"
                                        ]
                                    ) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="sp-history">
            <h3 class="h5 mb-3 text-white"><i class="bi bi-clock-history"></i> Storico Contratti</h3>
            <div class="gm-card">
                <?php if (empty($history)): ?>
                    <div class="text-muted-gm">Nessun contratto registrato.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-dark table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Sponsor</th>
                                <th>Firma</th>
                                <th>Fine</th>
                                <th>Stato</th>
                                <th class="text-end">Base Stagione</th>
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
