<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<string, app\models\NewsItem[]> $grouped */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Notizie';
$this->params['breadcrumbs'][] = $this->title;

$priorityColors = [0 => 'var(--text-secondary)', 1 => 'var(--gold)', 2 => 'var(--accent-red)'];

$dayLabel = function(string $date): string {
    $ts    = strtotime($date);
    $today = date('Y-m-d');
    $yest  = date('Y-m-d', strtotime('-1 day'));
    if ($date === $today) return 'Oggi';
    if ($date === $yest)  return 'Ieri';
    return date('d M Y', $ts);
};
?>

<div class="py-2">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-black mb-0"><i class="bi bi-bell text-gold me-2"></i>Notizie</h1>
    </div>

    <?php if (empty($grouped)): ?>
    <div class="gm-card text-center py-5 text-muted-gm">
        <i class="bi bi-bell-slash fs-1 d-block mb-2"></i>
        Nessuna notizia per ora.
    </div>
    <?php else: ?>
    <?php foreach ($grouped as $date => $items): ?>
    <div class="mb-1" style="font-size:.68rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.08em;padding:.4rem 0">
        <?= $dayLabel($date) ?>
    </div>
    <div class="gm-card mb-4 p-0" style="overflow:hidden">
        <?php foreach ($items as $i => $item): ?>
        <div style="display:flex;align-items:flex-start;gap:.8rem;padding:.85rem 1rem;
                    <?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>;
                    background:<?= $item->is_read ? 'transparent' : 'rgba(245,158,11,.04)' ?>">
            <div style="font-size:1.35rem;flex-shrink:0;margin-top:.1rem"><?= Html::encode($item->icon) ?></div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:700;font-size:.85rem;color:<?= $priorityColors[$item->priority] ?? 'var(--text-primary)' ?>;margin-bottom:.15rem">
                    <?= Html::encode($item->title) ?>
                </div>
                <?php if ($item->body): ?>
                <div style="font-size:.78rem;color:var(--text-secondary);margin-bottom:.2rem">
                    <?= Html::encode($item->body) ?>
                </div>
                <?php endif; ?>
                <?php if ($item->link_url): ?>
                <a href="<?= Html::encode($item->link_url) ?>" class="btn btn-outline-secondary btn-sm py-0 px-2" style="font-size:.7rem">
                    Vedi →
                </a>
                <?php endif; ?>
            </div>
            <div style="flex-shrink:0;font-size:.65rem;color:var(--text-secondary);white-space:nowrap;margin-top:.2rem">
                <?= date('H:i', $item->created_at) ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
