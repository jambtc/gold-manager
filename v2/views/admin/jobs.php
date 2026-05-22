<?php

/** @var yii\web\View $this */
/** @var array[] $waiting */
/** @var array[] $reserved */
/** @var array[] $failed */
/** @var string $channel */

use yii\helpers\Html;

$this->title = 'Queue Jobs';
?>

<div class="py-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-black mb-0">
            <i class="bi bi-cpu text-gold me-2"></i>Queue Jobs
        </h1>
        <?= Html::a('<i class="bi bi-arrow-left"></i> Admin', ['/admin/index'], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false]) ?>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php foreach ([
            ['bi-hourglass-split', 'In attesa',    count($waiting),  'var(--gold)'],
            ['bi-play-circle',     'In esecuzione', count($reserved), 'var(--accent-green)'],
            ['bi-x-circle',        'Falliti',       count($failed),   'var(--accent-red)'],
        ] as [$icon, $label, $count, $color]): ?>
        <div class="col-md-4">
            <div class="gm-card d-flex align-items-center gap-3 py-3 px-4">
                <i class="bi <?= $icon ?> fs-3" style="color:<?= $color ?>"></i>
                <div>
                    <div class="fw-black fs-4 text-white"><?= $count ?></div>
                    <div class="text-muted-gm" style="font-size:.75rem"><?= $label ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Running -->
    <?php if (!empty($reserved)): ?>
    <h2 class="h5 fw-bold text-white mb-2">
        <span class="pulse d-inline-block me-2" style="width:8px;height:8px;border-radius:50%;background:var(--accent-green)"></span>
        In esecuzione
    </h2>
    <?= $this->render('_jobs_table', ['jobs' => $reserved, 'emptyText' => '']) ?>
    <?php endif; ?>

    <!-- Waiting -->
    <h2 class="h5 fw-bold text-white mb-2 mt-4">In attesa</h2>
    <?php if (empty($waiting)): ?>
        <div class="gm-card text-muted-gm small py-3 px-4">Nessun job in coda.</div>
    <?php else: ?>
        <?= $this->render('_jobs_table', ['jobs' => $waiting, 'emptyText' => '']) ?>
    <?php endif; ?>

    <!-- Failed -->
    <h2 class="h5 fw-bold text-white mb-2 mt-4">
        <i class="bi bi-exclamation-triangle text-danger me-1"></i>Falliti
        <span class="text-muted-gm" style="font-size:.75rem;font-weight:400">(ultimi 50)</span>
    </h2>
    <?php if (empty($failed)): ?>
        <div class="gm-card text-muted-gm small py-3 px-4">Nessun job fallito.</div>
    <?php else: ?>
        <?= $this->render('_jobs_table', ['jobs' => $failed, 'emptyText' => '']) ?>
    <?php endif; ?>

    <p class="text-muted-gm small mt-4">
        <i class="bi bi-info-circle me-1"></i>
        Canale Redis: <code style="color:var(--gold)"><?= Html::encode($channel) ?></code> ·
        La pagina non si aggiorna automaticamente —
        <?= Html::a('ricarica', ['/admin/jobs'], ['class' => 'text-gold']) ?> per aggiornare.
    </p>
</div>
