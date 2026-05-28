<?php

declare(strict_types=1);

/**
 * @var bool $showScorers
 * @var string $panelColumnClass
 */
$showScorers = isset($showScorers) ? (bool) $showScorers : true;
$panelColumnClass = $panelColumnClass ?? 'col-lg-5';
?>

<div class="<?= $panelColumnClass ?>" id="tab-formazioni-panel">
    <div class="gm-card">
        <div id="formations-loading" class="text-center py-4 text-muted-gm">
            <i class="bi bi-hourglass-split me-2"></i><?= Yii::t('app', 'Loading lineups...') ?>
        </div>
        <div id="formations-content" style="display:none">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-center mb-1">
                        <span id="form-home-name" class="fw-bold text-white gm-formations-name"></span>
                        <span id="form-home-module" class="text-muted-gm ms-2 gm-formations-module"></span>
                    </div>
                    <canvas id="pitch-home" width="280" height="380" class="gm-pitch-canvas"></canvas>
                    <div id="list-home" class="mt-2 gm-players-list"></div>
                </div>
                <div class="col-md-6">
                    <div class="text-center mb-1">
                        <span id="form-away-name" class="fw-bold text-white gm-formations-name"></span>
                        <span id="form-away-module" class="text-muted-gm ms-2 gm-formations-module"></span>
                    </div>
                    <canvas id="pitch-away" width="280" height="380" class="gm-pitch-canvas"></canvas>
                    <div id="list-away" class="mt-2 gm-players-list"></div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showScorers): ?>
        <div class="row g-3 mt-3">
            <div class="col-12">
                <div class="gm-card flex-shrink-0" id="scorer-sheet-card">
                    <h6 class="text-white fw-bold mb-2" style="font-size:.8rem">
                        <i class="bi bi-list-ol text-gold me-1"></i><?= Yii::t('app', 'Scorers') ?>
                    </h6>
                    <div id="scorer-sheet" class="gm-scorer-sheet">
                        <div class="text-muted-gm text-center py-1">—</div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

