<?php

declare(strict_types=1);

namespace app\assets;

class FixtureReplayAsset extends FixtureLiveReplayAsset
{
    public $css = [
        'bundles/fixture/live-replay-common.css',
        'bundles/fixture/replay.css',
    ];

    public $js = [
        'bundles/fixture/live-replay-common.js',
        'bundles/fixture/replay.js',
    ];
}

