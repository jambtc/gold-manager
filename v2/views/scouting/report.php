<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\ScoutingReport $report */
/** @var array $data */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Rapporto: ' . ($data['name'] ?? 'Giocatore');
$this->params['breadcrumbs'][] = ['label' => 'Scouting', 'url' => ['/scouting/index']];
$this->params['breadcrumbs'][] = $this->title;

$posColors = ['GK' => '#ea580c', 'DF' => '#2563eb', 'MF' => '#15803d', 'FW' => '#b91c1c'];
$posColor  = $posColors[$data['position'] ?? 'MF'] ?? '#f59e0b';

// Style: approximate values (~) shown in amber
$val = fn(string $key): string => Html::encode($data[$key] ?? '?');
$isApprox = fn(string $key): bool => str_starts_with((string)($data[$key] ?? ''), '~');
$statColor = fn(string $key): string => $isApprox($key) ? 'var(--gold)' : 'var(--accent-green)';

$footLbl = ['R' => 'Destro', 'L' => 'Sinistro', 'LR' => 'Ambidestro'];
?>

<div class="py-2">
    <div class="mb-3">
        <a href="<?= Url::to(['/scouting/index']) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Tutti i rapporti
        </a>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="gm-card">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <span style="background:<?= $posColor ?>;color:#fff;font-size:.8rem;font-weight:800;padding:.3rem .7rem;border-radius:.4rem"><?= Html::encode($data['position'] ?? '?') ?></span>
                    <div>
                        <h2 class="mb-0 fw-black"><?= Html::encode($data['name'] ?? 'Sconosciuto') ?></h2>
                        <div class="text-muted-gm small">
                            <?= Html::encode($data['age'] ?? '?') ?> anni
                            · <?= Html::encode($footLbl[$data['foot'] ?? ''] ?? $data['foot'] ?? '?') ?>
                        </div>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="fw-black text-gold" style="font-size:1.8rem"><?= Html::encode($data['natural_overall'] ?? $data['general_skill'] ?? '?') ?></div>
                        <div class="text-muted-gm" style="font-size:.68rem">OVR STIMATO</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <?php foreach ([
                        ['PO', 'skill_po'], ['DF', 'skill_df'], ['CN', 'skill_cn'], ['PA', 'skill_pa'],
                        ['RG', 'skill_rg'], ['CR', 'skill_cr'], ['TC', 'skill_tc'], ['TR', 'skill_tr'],
                    ] as [$label, $key]): ?>
                    <div class="col-3">
                        <div class="text-center p-2 rounded-2" style="background:rgba(255,255,255,.04);border:1px solid var(--border)">
                            <div style="font-size:.58rem;color:var(--text-secondary);text-transform:uppercase"><?= $label ?></div>
                            <div style="font-weight:800;font-size:.9rem;color:<?= $statColor($key) ?>"><?= $val($key) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($data['note'])): ?>
                <div class="p-3 rounded-3" style="background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.2)">
                    <i class="bi bi-chat-quote text-gold me-2"></i>
                    <em style="color:var(--text-secondary);font-size:.85rem">"<?= Html::encode($data['note']) ?>"</em>
                </div>
                <?php endif; ?>

                <div class="mt-3" style="font-size:.68rem;color:var(--text-secondary)">
                    <i class="bi bi-info-circle me-1"></i>
                    I valori con ~ sono approssimati. Precisione basata sull'efficienza dello scout (<?= (int)$report->scout_eff ?>%).
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="gm-card">
                <h5 class="text-white fw-bold mb-3">Azioni</h5>
                <?= Html::a('<i class="bi bi-shop me-1"></i> Vai al mercato', ['/transfer/market'], ['class' => 'btn btn-gold w-100 mb-2', 'encode' => false]) ?>
                <?= Html::a('<i class="bi bi-person me-1"></i> Scheda giocatore', ['/player/view', 'id' => $report->player_id, 'back' => Url::to(['/scouting/index'])], ['class' => 'btn btn-outline-secondary w-100 mb-2', 'encode' => false]) ?>
                <?= Html::beginForm(['/scouting/dismiss', 'id' => $report->id], 'post') ?>
                <button type="submit" class="btn btn-outline-danger w-100 btn-sm">Archivia rapporto</button>
                <?= Html::endForm() ?>
            </div>
        </div>
    </div>
</div>
