<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SIP-0077: Seed all orchestrator schedules (replacing PHP cron).
 */
class m260602_300000_seed_orchestrator_schedules extends Migration
{
    private const JOBS = [
        // job_name                    interval_seconds  description
        ['game.run_fixtures',          300,  'Start scheduled fixtures (every 5 min)'],
        ['notification.dispatch',       60,  'Dispatch pending Telegram notifications (every 1 min)'],
        ['notification.pre_match',      60,  'Pre-match alerts 15 min before kickoff (every 1 min)'],
        ['economy.loan_returns',       300,  'Process expired loans (every 5 min)'],
        ['economy.market_refresh',   14400,  'Top-up player/staff market pool (every 4h)'],
        ['economy.pay_wages',        86400,  'Pay wages + weekly revenue (daily)'],
        ['economy.daily_training',   86400,  'Apply daily training + snapshot (daily)'],
        ['economy.weekly_recovery',  604800, 'Weekly player freshness recovery (weekly)'],
        ['economy.daily_digest',     86400,  'Send daily manager digest at noon (daily)'],
        ['economy.auto_rollover',    86400,  'Auto season rollover when all fixtures done (daily)'],
        ['economy.expansion_pool',   86400,  'Pre-seed new Serie C groups when free slots low (daily)'],
        ['game.cpu_formations',      86400,  'Refresh CPU team formations (daily)'],
        ['scouting.process',          1800,  'Process scouting jobs (every 30 min)'],
        ['economy.expire_friendlies', 3600,  'Expire unanswered friendly challenges (every 1h)'],
    ];

    public function safeUp(): void
    {
        $now = time();
        foreach (self::JOBS as [$jobName, $interval]) {
            $existing = (new \yii\db\Query())
                ->from('{{%orchestrator_schedule}}')
                ->where(['job_name' => $jobName])
                ->one();

            if ($existing) {
                continue;
            }

            $this->insert('{{%orchestrator_schedule}}', [
                'job_name'         => $jobName,
                'enabled'          => 1,
                'cron_expr'        => null,
                'interval_seconds' => $interval,
                'timezone'         => 'Europe/Rome',
                'next_run_at'      => $now + min(300, (int) floor($interval * 0.1)),
                'payload_json'     => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);
        }
    }

    public function safeDown(): void
    {
        $jobNames = array_column(self::JOBS, 0);
        $this->delete('{{%orchestrator_schedule}}', ['job_name' => $jobNames]);
    }
}
