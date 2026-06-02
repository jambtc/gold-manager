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

$this->registerLinkTag(['rel' => 'icon', 'type' => 'image/svg+xml', 'href' => Yii::getAlias('@web/img/icon-192.svg')]);
$this->registerLinkTag(['rel' => 'manifest', 'href' => Yii::getAlias('@web/manifest.json')]);
$this->registerMetaTag(['name' => 'theme-color', 'content' => '#f59e0b'], 'theme-color');
$this->registerMetaTag(['name' => 'mobile-web-app-capable', 'content' => 'yes'], 'mobile-web-app');
$this->registerMetaTag(['name' => 'apple-mobile-web-app-capable', 'content' => 'yes'], 'apple-mobile');
$this->registerMetaTag(['name' => 'apple-mobile-web-app-status-bar-style', 'content' => 'black-translucent'], 'apple-status');
$this->registerJs("if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js')}", \yii\web\View::POS_END, 'sw-reg');
?>
<title><?= Html::encode($this->title ? $this->title . ' | ' . Yii::$app->name : Yii::$app->name) ?></title>
<?php $this->head() ?>
