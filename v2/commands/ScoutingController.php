<?php

declare(strict_types=1);

namespace app\commands;

use app\components\ScoutingService;
use yii\console\Controller;
use yii\console\ExitCode;

class ScoutingController extends Controller
{
    /**
     * Process ready scouting reports and deliver via news feed.
     * Usage: ./yii scouting/process
     */
    public function actionProcess(): int
    {
        $count = ScoutingService::processReadyReports();
        $this->stdout("🔍 Scouting: {$count} report consegnati.\n");
        return ExitCode::OK;
    }
}
