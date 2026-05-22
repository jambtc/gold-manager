<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class FixtureLiveReplayAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'bundles/fixture/live-replay-common.css',
    ];
    public $js = [
        'bundles/fixture/live-replay-common.js',
    ];
    public $depends = [
        AppAsset::class,
    ];
}

