<?php

/** @var yii\web\View $this */
/** @var string $content */

use yii\helpers\Html;

$this->beginPage();
?>
<!doctype html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <?= Html::csrfMetaTags() ?>
    <?php $this->head() ?>
    <style>
        html, body { margin: 0; padding: 0; background: #0f172a; color: #e2e8f0; font-family: Inter, Arial, sans-serif; }
        #swagger-ui { min-height: 100vh; }
    </style>
</head>
<body>
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>

