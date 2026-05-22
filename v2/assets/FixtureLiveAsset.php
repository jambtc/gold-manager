<?php

declare(strict_types=1);

namespace app\assets;

class FixtureLiveAsset extends FixtureLiveReplayAsset
{
    public $css = [
        'bundles/fixture/live-replay-common.css',
        'bundles/fixture/live.css',
    ];

    public $js = [
        'bundles/fixture/live-replay-common.js',
        'bundles/fixture/live.js',
    ];
}
