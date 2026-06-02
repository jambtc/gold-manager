<?php

declare(strict_types=1);

namespace app\assets;

use yii\web\AssetBundle;

class StaffViewAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl  = '@web';
    public $js       = ['bundles/staff/view.js'];
    public $depends  = [AppAsset::class];
}
