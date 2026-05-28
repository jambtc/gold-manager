<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var bool $hasScout */
/** @var app\models\ScoutingReport[] $pending */
/** @var app\models\ScoutingReport[] $ready */
/** @var app\models\ScoutingReport[] $archived */
/** @var string[] $needPositions */
/** @var int $scoutEff */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Scouting');
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="py-2">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h1 class="h3 fw-black mb-0"><i class="bi bi-binoculars text-gold me-2"></i><?= Yii::t('app', 'Scouting') ?></h1>
        <?php if (!$hasScout): ?>
        <a href="<?= Url::to(['/staff/view']) ?>" class="btn btn-outline-gold btn-sm">
            <i class="bi bi-person-plus me-1"></i><?= Yii::t('app', 'Hire a scout') ?>
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
        <h3 class="text-white mb-2"><?= Yii::t('app', 'No scout in squad') ?></h3>
        <p class="text-muted-gm mb-4"><?= Yii::t('app', 'Hire a scout to start scouting opponent players.') ?></p>
        <?= Html::a('<i class="bi bi-person-plus me-1"></i> ' . Yii::t('app', 'Go to staff'), ['/staff/view'], ['class' => 'btn btn-gold', 'encode' => false]) ?>
    </div>
    <?php else: ?>

    <div class="gm-card mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h5 class="text-white fw-bold mb-0"><i class="bi bi-funnel text-gold me-2"></i><?= Yii::t('app', 'Scout Requests') ?></h5>
            <span class="text-muted-gm" style="font-size:.75rem"><?= Yii::t('app', 'Scout efficiency') ?>: <?= (int) $scoutEff ?></span>
        </div>
        <p class="text-muted-gm mb-3" style="font-size:.78rem">
            <?= Yii::t('app', 'Select up to 3 roles: when an interesting profile enters the single market, you receive an automatic alert.') ?>
        </p>
        <?= Html::beginForm(['/scouting/save-needs'], 'post', ['class' => 'd-flex flex-wrap align-items-center gap-3']) ?>
            <?php foreach (['GK' => Yii::t('app', 'Goalkeeper'), 'DF' => Yii::t('app', 'Defender'), 'MF' => Yii::t('app', 'Midfielder'), 'FW' => Yii::t('app', 'Forward')] as $code => $label): ?>
                <label class="d-inline-flex align-items-center gap-1 text-white" style="font-size:.82rem">
                    <input type="checkbox"
                           name="need_positions[]"
                           value="<?= Html::encode($code) ?>"
                           <?= in_array($code, $needPositions ?? [], true) ? 'checked' : '' ?>>
                    <span><?= Html::encode($label) ?></span>
                </label>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-gold btn-sm ms-auto"><?= Yii::t('app', 'Save requests') ?></button>
        <?= Html::endForm() ?>
    </div>

    <!-- Pending -->
    <?php if (!empty($pending)): ?>
    <div class="gm-card mb-4">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-hourglass-split text-muted-gm me-2"></i><?= Yii::t('app', 'Pending') ?> (<?= count($pending) ?>)</h5>
        <?php foreach ($pending as $i => $r): ?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.6rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>">
            <span style="font-size:1.2rem">🔍</span>
            <div style="flex:1">
                <div class="fw-bold text-white" style="font-size:.85rem"><?= Html::encode($r->player->name ?? 'Giocatore #'.$r->player_id) ?></div>
                <div class="text-muted-gm" style="font-size:.72rem">
                    <?= Yii::t('app', 'Report ready in') ?> <?= max(1, (int)ceil(($r->ready_at - time()) / 86400)) ?> <?= Yii::t('app', 'day(s)') ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Ready -->
    <?php if (!empty($ready)): ?>
    <div class="gm-card mb-4">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-check-circle text-accent-green me-2"></i><?= Yii::t('app', 'Ready') ?> (<?= count($ready) ?>)</h5>
        <?php foreach ($ready as $i => $r): ?>
        <?php $d = $r->report_text ? (json_decode($r->report_text, true) ?? []) : []; ?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.6rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>">
            <span style="font-size:1.2rem">📋</span>
            <div style="flex:1;min-width:0">
                <div class="fw-bold text-white" style="font-size:.85rem"><?= Html::encode($d['name'] ?? 'Giocatore') ?></div>
                <div class="text-muted-gm" style="font-size:.72rem">
                    <?= Html::encode($d['position'] ?? '') ?> · <?= Html::encode($d['age'] ?? '') ?> <?= Yii::t('app', 'years') ?> · OVR ~<?= Html::encode($d['natural_overall'] ?? $d['general_skill'] ?? '?') ?>
                </div>
            </div>
            <div class="d-flex gap-1">
                <?= Html::a(Yii::t('app', 'View'), ['/scouting/report', 'id' => $r->id], ['class' => 'btn btn-gold btn-sm']) ?>
                <?= Html::beginForm(['/scouting/dismiss', 'id' => $r->id], 'post', ['class' => 'd-inline']) ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm"><?= Yii::t('app', 'Archive') ?></button>
                <?= Html::endForm() ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php elseif (empty($pending)): ?>
    <div class="gm-card text-center py-4 text-muted-gm mb-4">
        <i class="bi bi-file-earmark-text d-block mb-2" style="font-size:2rem;opacity:.4"></i>
        <?= Yii::t('app', 'No report available. Scout a player from') ?> <a href="<?= Url::to(['/transfer/market']) ?>" style="color:var(--gold)"><?= Yii::t('app', 'Market') ?></a>.
    </div>
    <?php endif; ?>

    <!-- Archived -->
    <?php if (!empty($archived)): ?>
    <div class="gm-card">
        <h5 class="text-white fw-bold mb-3"><i class="bi bi-archive text-muted-gm me-2"></i><?= Yii::t('app', 'Archive') ?></h5>
        <?php foreach ($archived as $i => $r): ?>
        <?php $d = $r->report_text ? (json_decode($r->report_text, true) ?? []) : []; ?>
        <div style="display:flex;align-items:center;gap:.8rem;padding:.4rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>;opacity:.6">
            <span>📁</span>
            <div style="flex:1;font-size:.78rem;color:var(--text-secondary)">
                <?= Html::encode($d['name'] ?? $r->player->name ?? '#'.$r->player_id) ?>
                — OVR ~<?= Html::encode($d['natural_overall'] ?? $d['general_skill'] ?? '?') ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
