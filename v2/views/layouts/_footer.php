<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;

?>
<footer id="footer" class="mt-auto py-4">
    <div class="container text-center">
        <div class="mb-3">
            <span class="h4 text-gold fw-bold">GOLD</span> <span class="h4 text-white">MANAGER</span>
        </div>
        <div class="text-muted-gm small mb-4">
            <?= Yii::t('app', 'The football simulator. Train, sell, win.') ?>
        </div>
        <div class="d-flex justify-content-center gap-4 mb-4">
            <a href="#" class="text-secondary fs-4"><i class="bi bi-discord"></i></a>
            <a href="#" class="text-secondary fs-4"><i class="bi bi-instagram"></i></a>
            <a href="#" class="text-secondary fs-4"><i class="bi bi-twitter-x"></i></a>
        </div>
        <div class="pt-4 border-top border-secondary">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start small text-muted-gm">
                    &copy; <?= date('Y') ?> Gold Manager Engine. <?= Yii::t('app', 'All rights reserved.') ?>
                </div>
                <div class="col-md-6 text-md-end small text-muted-gm">
                    <?= Yii::t('app', 'Powered by') ?> <span class="text-white fw-bold">Yii2 Framework</span>
                </div>
            </div>
        </div>
    </div>
</footer>
