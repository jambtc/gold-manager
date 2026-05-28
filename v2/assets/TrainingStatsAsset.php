<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class TrainingStatsAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $js = [
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        'bundles/training/statistics.js',
    ];
    public $jsOptions = ['appendTimestamp' => true];
    public $depends = [
        AppAsset::class,
    ];
}

