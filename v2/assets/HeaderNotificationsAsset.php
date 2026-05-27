<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class HeaderNotificationsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'bundles/layout/header-notifications.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}

