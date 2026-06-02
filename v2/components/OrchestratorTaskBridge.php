<?php

declare(strict_types=1);

namespace app\components;

use Yii;

/**
 * SIP-0077: Bridge between the Go orchestrator and PHP command logic.
 * Delegates to canonical command actions, avoiding duplicate business logic.
 * Console STDOUT/STDERR/STDIN are silenced in web context.
 */
final class OrchestratorTaskBridge
{
    // ── CLI constant setup ────────────────────────────────────────────────────

    private static function boot(): void
    {
        if (!defined('STDOUT')) define('STDOUT', fopen('/dev/null', 'w'));
        if (!defined('STDERR')) define('STDERR', fopen('/dev/null', 'w'));
        if (!defined('STDIN'))  define('STDIN',  fopen('/dev/null', 'r'));
    }

    private static function economy(): \app\commands\EconomyController
    {
        self::boot();
        return new \app\commands\EconomyController('economy', Yii::$app);
    }

    private static function game(): \app\commands\GameController
    {
        self::boot();
        return new \app\commands\GameController('game', Yii::$app);
    }

    private static function notification(): \app\commands\NotificationController
    {
        self::boot();
        return new \app\commands\NotificationController('notification', Yii::$app);
    }

    private static function scouting(): \app\commands\ScoutingController
    {
        self::boot();
        return new \app\commands\ScoutingController('scouting', Yii::$app);
    }

    // ── Market ────────────────────────────────────────────────────────────────

    public static function resolveMarketAuctions(): array
    {
        $code = self::economy()->actionResolveMarketAuctions();
        return ['exit_code' => $code];
    }

    public static function marketRefresh(): array
    {
        $code = self::economy()->actionGenerateYouth();
        return ['exit_code' => $code];
    }

    // ── Fixtures ──────────────────────────────────────────────────────────────

    public static function runFixtures(): array
    {
        $code = self::game()->actionRunFixtures();
        return ['exit_code' => $code];
    }

    // ── Notifications ─────────────────────────────────────────────────────────

    public static function notificationDispatch(): array
    {
        $code = self::notification()->actionDispatch();
        return ['exit_code' => $code];
    }

    public static function preMatchNotifications(): array
    {
        $code = self::economy()->actionSendPreMatchNotifications();
        return ['exit_code' => $code];
    }

    public static function dailyDigest(): array
    {
        $code = self::economy()->actionSendDailyDigest();
        return ['exit_code' => $code];
    }

    // ── Economy ───────────────────────────────────────────────────────────────

    public static function payWages(): array
    {
        $code = self::economy()->actionPayWages();
        return ['exit_code' => $code];
    }

    public static function dailyTraining(): array
    {
        $code = self::economy()->actionApplyDailyTraining();
        return ['exit_code' => $code];
    }

    public static function weeklyRecovery(): array
    {
        $code = self::economy()->actionApplyWeeklyRecovery();
        return ['exit_code' => $code];
    }

    public static function loanReturns(): array
    {
        $code = self::economy()->actionProcessLoanReturns();
        return ['exit_code' => $code];
    }

    public static function autoRollover(): array
    {
        $code = self::economy()->actionAutoRollover();
        return ['exit_code' => $code];
    }

    public static function expansionPool(): array
    {
        $code = self::economy()->actionEnsureExpansionPool();
        return ['exit_code' => $code];
    }

    // ── Game ──────────────────────────────────────────────────────────────────

    public static function cpuFormations(): array
    {
        $code = self::game()->actionRefreshCpuFormations();
        return ['exit_code' => $code];
    }

    public static function scoutingProcess(): array
    {
        $code = self::scouting()->actionProcess();
        return ['exit_code' => $code];
    }

    public static function expireFriendlyChallenges(): array
    {
        $code = self::economy()->actionExpireFriendlyChallenges();
        return ['exit_code' => $code];
    }
}
