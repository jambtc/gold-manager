<?php

/** @var yii\web\View $this */

use yii\helpers\Url;

$this->title = Yii::t('app', 'Preparing team…');
$statusUrl   = Url::to(['/api/v1/user/status']);
$homeUrl     = Url::to(['/site/index']);
?>

<div class="auth-page">
    <div class="auth-card text-center" style="max-width:480px">

        <!-- Animated badge -->
        <div class="mb-4 position-relative d-inline-block">
            <div style="
                width:90px;height:90px;
                border-radius:50%;
                background:rgba(245,158,11,.1);
                border:2px solid var(--gold);
                display:flex;align-items:center;justify-content:center;
                margin:0 auto;
                animation: pulse-gold 2s infinite;
            ">
                <i class="bi bi-shield-shaded text-gold" style="font-size:2.5rem"></i>
            </div>
        </div>

        <h2 class="fw-black text-white mb-2"><?= Yii::t('app', 'Your team is being created') ?></h2>
        <p class="text-muted-gm mb-4" style="font-size:.92rem">
            <?= Yii::t('app', 'We are generating the game world, teams, players and calendar.') ?><br>
            <?= Yii::t('app', 'It will only take a few seconds.') ?>
        </p>

        <!-- Progress bar -->
        <div class="mb-4" style="background:rgba(255,255,255,.06);border-radius:4px;height:6px;overflow:hidden">
            <div id="progress-bar" style="
                height:100%;
                background:var(--gold);
                border-radius:4px;
                width:10%;
                transition:width 0.4s ease;
                box-shadow:0 0 8px var(--gold-glow);
            "></div>
        </div>

        <p class="text-muted-gm small" id="status-text"><?= Yii::t('app', 'Initialising…') ?></p>

        <!-- Error state (hidden by default) -->
        <div id="error-box" style="display:none" class="mt-3">
            <div class="gm-card p-3" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08)">
                <p class="text-danger small mb-2"><?= Yii::t('app', 'An error occurred during generation.') ?></p>
                <a href="<?= Url::to(['/site/login']) ?>" class="btn btn-outline-danger btn-sm"><?= Yii::t('app', 'Back to login') ?></a>
            </div>
        </div>

    </div>
</div>

<script>
(function () {
    const statusUrl  = '<?= $statusUrl ?>';
    const homeUrl    = '<?= $homeUrl ?>';
    const bar        = document.getElementById('progress-bar');
    const statusText = document.getElementById('status-text');
    const errorBox   = document.getElementById('error-box');

    const messages = <?= \yii\helpers\Json::htmlEncode([
        Yii::t('app', 'Generating CPU teams...'),
        Yii::t('app', 'Creating player squads...'),
        Yii::t('app', 'Assigning player stats...'),
        Yii::t('app', 'Building stadiums...'),
        Yii::t('app', 'Generating fixture calendar...'),
        Yii::t('app', 'Assigning your team...'),
        Yii::t('app', 'Almost ready...'),
    ]) ?>;
    let msgIdx   = 0;
    let progress = 10;

    const msgTimer = setInterval(() => {
        if (msgIdx < messages.length) {
            statusText.textContent = messages[msgIdx++];
            progress = Math.min(90, progress + 12);
            bar.style.width = progress + '%';
        }
    }, 1200);

    function poll() {
        fetch(statusUrl, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                if (data.ready) {
                    clearInterval(msgTimer);
                    bar.style.width = '100%';
                    statusText.textContent = 'Squadra pronta! Reindirizzamento…';
                    setTimeout(() => { window.location.href = homeUrl; }, 600);
                } else if (data.status === 'error') {
                    clearInterval(msgTimer);
                    statusText.textContent = '';
                    errorBox.style.display = 'block';
                } else {
                    setTimeout(poll, 2000);
                }
            })
            .catch(() => setTimeout(poll, 3000));
    }

    setTimeout(poll, 2000);
})();
</script>
