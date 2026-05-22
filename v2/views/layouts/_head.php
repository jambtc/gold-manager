<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use app\assets\AppAsset;
use yii\helpers\Html;

AppAsset::register($this);

// Bootstrap Icons — local copy (no CDN)
$this->registerLinkTag(['rel' => 'stylesheet', 'href' => Yii::getAlias('@web/css/bootstrap-icons.css')]);

$this->registerCsrfMetaTags();
$this->registerMetaTag(['charset' => Yii::$app->charset], 'charset');
$this->registerMetaTag(['name' => 'viewport', 'content' => 'width=device-width, initial-scale=1'], 'viewport');

if (!empty($this->params['meta_description'])) {
    $this->registerMetaTag(['name' => 'description', 'content' => $this->params['meta_description']], 'description');
}

$this->registerLinkTag([
    'rel' => 'icon',
    'type' => 'image/x-icon',
    'href' => Yii::getAlias('@web/favicon.ico'),
]);
?>
<title><?= Html::encode($this->title ? $this->title . ' | ' . Yii::$app->name : Yii::$app->name) ?></title>
<?php $this->head() ?>
