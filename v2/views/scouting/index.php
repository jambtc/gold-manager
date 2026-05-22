<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var bool $hasScout */
/** @var app\models\ScoutingReport[] $pending */
/** @var app\models\ScoutingReport[] $ready */
/** @var app\models\ScoutingReport[] $archived */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Scouting';
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="py-2">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h1 class="h3 fw-black mb-0"><i class="bi bi-binoculars text-gold me-2"></i>Scouting</h1>
        <?php if (!$hasScout): ?>
        <a href="<?= Url::to(['/staff/view']) ?>" class="btn btn-outline-gold btn-sm">
            <i class="bi bi-person-plus me-1"></i>Assumi uno scout
        </a>
        <?php endif; ?>
    </div>

    <?php foreach (['success','error','info'] as $type): ?>
    <?php if (Yii::$app->session->hasFlash($type)): ?>
    <div class="gm-card mb-3 p-3" style="border-color:rgba(<?= $type==='success'?'16,185,129':($type==='error'?'239,68,68':'245,158,11') ?>,.4);background:rgba(<?= $type==='success'?'16,185,129':($type==='error'?'239,68,68':'245,158,11') ?>,.08)">
        <?= Html::encode(Yii::$app->session->getFlash($type)) ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!$hasScout): ?>
    <div class="gm-card text-center py-5">
        <i class="bi bi-binoculars d-block mb-3 text-muted-gm" style="font-size:3rem;opacity:.4"></i>
        <h3 class="text-white mb-2">Nessuno scout in rosa</h3>
        <p class="text-muted-gm mb-4">Assumi uno scout per iniziare ad osservare i giocatori avversari.</p>
        <?= Html::a('<i class="bi bi-person-plus me-1"></i> Vai allo staff', ['/staff/view'], ['class' => 'btn btn-gold', 'encode' => false]) ?>
    </div>
    <?php else: ?>

    <!-- Pending -->
    <?php if (!empty($pending)): ?>
    <div class="gm-card mb-4">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-hourglass-split text-muted-gm me-2"></i>In attesa (<?= count($pending) ?>)</h5>
        <?php foreach ($pending as $i => $r): ?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.6rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>">
            <span style="font-size:1.2rem">🔍</span>
            <div style="flex:1">
                <div class="fw-bold text-white" style="font-size:.85rem"><?= Html::encode($r->player->name ?? 'Giocatore #'.$r->player_id) ?></div>
                <div class="text-muted-gm" style="font-size:.72rem">
                    Rapporto pronto tra <?= max(1, (int)ceil(($r->ready_at - time()) / 86400)) ?> giorno/i
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Ready -->
    <?php if (!empty($ready)): ?>
    <div class="gm-card mb-4">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-check-circle text-accent-green me-2"></i>Pronti (<?= count($ready) ?>)</h5>
        <?php foreach ($ready as $i => $r): ?>
        <?php $d = $r->report_text ? (json_decode($r->report_text, true) ?? []) : []; ?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.6rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>">
            <span style="font-size:1.2rem">📋</span>
            <div style="flex:1;min-width:0">
                <div class="fw-bold text-white" style="font-size:.85rem"><?= Html::encode($d['name'] ?? 'Giocatore') ?></div>
                <div class="text-muted-gm" style="font-size:.72rem">
                    <?= Html::encode($d['position'] ?? '') ?> · <?= Html::encode($d['age'] ?? '') ?> anni · Skill ~<?= Html::encode($d['general_skill'] ?? '?') ?>
                </div>
            </div>
            <div class="d-flex gap-1">
                <?= Html::a('Vedi', ['/scouting/report', 'id' => $r->id], ['class' => 'btn btn-gold btn-sm']) ?>
                <?= Html::beginForm(['/scouting/dismiss', 'id' => $r->id], 'post', ['class' => 'd-inline']) ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Archivia</button>
                <?= Html::endForm() ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (empty($pending)): ?>
    <div class="gm-card text-center py-4 text-muted-gm mb-4">
        <i class="bi bi-file-earmark-text d-block mb-2" style="font-size:2rem;opacity:.4"></i>
        Nessun rapporto disponibile. Osserva un giocatore dal <a href="<?= Url::to(['/transfer/market']) ?>" style="color:var(--gold)">Mercato</a>.
    </div>
    <?php endif; ?>

    <!-- Archived -->
    <?php if (!empty($archived)): ?>
    <div class="gm-card">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-archive text-muted-gm me-2"></i>Archivio</h5>
        <?php foreach ($archived as $i => $r): ?>
        <?php $d = $r->report_text ? (json_decode($r->report_text, true) ?? []) : []; ?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.4rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>;opacity:.6">
            <span>📁</span>
            <div style="flex:1;font-size:.78rem;color:var(--text-secondary)">
                <?= Html::encode($d['name'] ?? $r->player->name ?? '#'.$r->player_id) ?>
                — Skill ~<?= Html::encode($d['general_skill'] ?? '?') ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
