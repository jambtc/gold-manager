<?php

declare(strict_types=1);

/**
 * @var \app\models\Fixture $fixture
 * @var string $weatherLabel
 * @var string $spectatorsLabel
 * @var bool $showLiveDebugControls
 */

$showLiveDebugControls = isset($showLiveDebugControls) ? (bool) $showLiveDebugControls : false;
?>

<div class="gm-card flex-shrink-0">
    <h6 class="text-white fw-bold mb-2" style="font-size:.8rem">
        <i class="bi bi-info-circle text-gold me-1"></i>Info
    </h6>
    <ul class="attribute-list" style="font-size:.75rem">
        <li>
            <span class="text-muted-gm">Stadio</span>
            <span class="text-white" style="font-size:.72rem"><?= \yii\helpers\Html::encode($fixture->homeTeam->stadium?->name ?? 'Stadio Comunale') ?></span>
        </li>
        <li>
            <span class="text-muted-gm">Competizione</span>
            <span class="text-gold" style="font-size:.72rem"><?= \yii\helpers\Html::encode($fixture->competition->name) ?></span>
        </li>
        <li>
            <span class="text-muted-gm">Meteo</span>
            <span id="info-weather" class="text-white" style="font-size:.72rem"><?= \yii\helpers\Html::encode($weatherLabel) ?></span>
        </li>
        <li>
            <span class="text-muted-gm">Spettatori</span>
            <span id="info-spectators" class="text-white" style="font-size:.72rem"><?= \yii\helpers\Html::encode($spectatorsLabel) ?></span>
        </li>
    </ul>

    <?php if ($showLiveDebugControls): ?>
        <div class="live-status-wrap">
            <span id="live-transport-badge" class="live-transport-badge">LIVE: INIT</span>
            <button type="button" id="live-debug-toggle" class="live-debug-btn">DBG OFF</button>
        </div>
    <?php endif; ?>
</div>
