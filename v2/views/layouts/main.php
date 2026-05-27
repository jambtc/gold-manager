<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="h-100">
<head>
    <?= $this->render('_head') ?>
</head>
<body class="d-flex flex-column h-100">
<?php $this->beginBody() ?>

<?= $this->render('_header') ?>

<main id="main" class="flex-grow-1 pt-5" role="main">
    <div class="container pt-4 mb-5">

        <?= Alert::widget() ?>
        
        <div class="animate__animated animate__fadeIn">
            <?= $content ?>
        </div>
    </div>
</main>

<?= $this->render('_footer') ?>

<?php $this->endBody() ?>
<script>
(function(){
    function updateCountdowns(){
        document.querySelectorAll('.auction-countdown[data-expires]').forEach(function(el){
            var exp = parseInt(el.dataset.expires, 10) * 1000;
            var diff = exp - Date.now();
            if(diff <= 0){
                el.textContent = 'Scaduta';
                el.style.color = 'var(--accent-red)';
                return;
            }
            var h = Math.floor(diff / 3600000);
            var m = Math.floor((diff % 3600000) / 60000);
            el.textContent = 'Scade tra ' + (h > 0 ? h + 'h ' : '') + m + 'm';
            if(diff < 900000)       el.style.color = 'var(--accent-red)';
            else if(diff < 3600000) el.style.color = 'var(--gold)';
            else                    el.style.color = '';
        });
    }
    updateCountdowns();
    setInterval(updateCountdowns, 30000);
})();
</script>
</body>
</html>
<?php $this->endPage() ?>
