<?php

declare(strict_types=1);

namespace app\components;

use Yii;

final class OrchestratorTaskBridge
{
    /**
     * @return array{resolved:int,won:int,lost:int}
     */
    public static function resolveMarketAuctions(): array
    {
        // Reuse canonical EconomyController private resolver to avoid duplicate business logic.
        $command = new \app\commands\EconomyController('economy', Yii::$app);
        $reflection = new \ReflectionMethod($command, 'resolveExpiredMarketBids');
        $reflection->setAccessible(true);
        /** @var array{resolved:int,won:int,lost:int} $result */
        $result = $reflection->invoke($command);
        return $result;
    }
}

