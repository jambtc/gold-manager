<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class TrainingProgressAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        'bundles/training/progression.js',
    ];
    public $jsOptions = ['appendTimestamp' => true];
    public $depends = [
        AppAsset::class,
    ];
}

