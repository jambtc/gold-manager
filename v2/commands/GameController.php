<?php

declare(strict_types=1);

namespace app\commands;

use app\components\CpuFormationService;
use app\components\WorldSeeder;
use app\models\Competition;
use app\models\Fixture;
use app\models\MatchState;
use app\models\Team;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Game engine console commands.
 *
 * Cron setup (every real minute):
 *   * * * * * docker compose -f /var/www/gold-manager/docker-compose.yml \
 *             exec -T php ./yii game/run-fixtures >> /var/log/gold-manager-cron.log 2>&1
 */
class GameController extends Controller
{
    private CpuFormationService $cpuFormationService;

    public function init(): void
    {
        parent::init();
        $this->cpuFormationService = new CpuFormationService();
    }

    /**
     * Advances every active fixture by one tick (one minute of match time).
     *
     * Called by cron once per real-world minute.
     * Fixtures that haven't reached their match_date yet are skipped.
     * Finished fixtures are skipped.
     */
    public function actionRunFixtures(): int
    {
        $now = time();

        // Find fixtures that should be running: scheduled and past start time,
        // or already in progress (status = playing).
        $fixtures = Fixture::find()
            ->where(['status' => [Fixture::STATUS_SCHEDULED, Fixture::STATUS_PLAYING]])
            ->andWhere(['<=', 'match_date', $now])
            ->all();

        if (empty($fixtures)) {
            $this->stdout("No active fixtures to process.\n");
            return ExitCode::OK;
        }

        /** @var \app\components\MatchEngine $engine */
        $engine = Yii::$app->matchEngine;

        foreach ($fixtures as $fixture) {
            $this->ensureCpuFixtureReady($fixture);

            // Mark as playing on first tick
            if ($fixture->status === Fixture::STATUS_SCHEDULED) {
                $fixture->status = Fixture::STATUS_PLAYING;
                $fixture->save();
            }

            $events = $engine->advanceTick($fixture);

            foreach ($events as $event) {
                $icon = $this->iconFor($event->type);
                $this->stdout(sprintf(
                    "[Fixture #%d] %2d' %s %s (%s)\n",
                    $fixture->id,
                    $event->minute,
                    $icon,
                    $event->type,
                    $event->team_side
                ));
            }
        }

        return ExitCode::OK;
    }

    /**
     * Simulates an entire fixture instantly (for testing / CPU matches).
     * Usage: ./yii game/simulate-fixture <fixture_id>
     *
     * @param int $fixtureId
     */
    public function actionSimulateFixture(int $fixtureId): int
    {
        $fixture = Fixture::findOne($fixtureId);
        if (!$fixture) {
            $this->stderr("Fixture #$fixtureId not found.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($fixture->status === Fixture::STATUS_FINISHED) {
            $this->stdout("Fixture #$fixtureId already finished.\n");
            return ExitCode::OK;
        }

        /** @var \app\components\MatchEngine $engine */
        $engine = Yii::$app->matchEngine;

        $fixture->status = Fixture::STATUS_PLAYING;
        $fixture->save();

        $this->stdout("Simulating Fixture #{$fixtureId}: {$fixture->homeTeam->name} vs {$fixture->awayTeam->name}\n");
        $this->stdout(str_repeat('─', 60) . "\n");

        $state = null;
        $maxTicks = 110; // safety cap (90 min + half-time ticks)

        for ($tick = 0; $tick < $maxTicks; $tick++) {
            $events = $engine->advanceTick($fixture);

            // Re-fetch state to check phase
            $state = MatchState::findOne(['fixture_id' => $fixtureId]);

            foreach ($events as $event) {
                $icon = $this->iconFor($event->type);
                $detail = $event->getDetailArray();
                $suffix = '';
                if ($event->type === 'goal' && isset($detail['home_score'])) {
                    $suffix = " → {$detail['home_score']}:{$detail['away_score']}";
                }
                $this->stdout(sprintf(
                    "  %2d' %s %-18s [%s]%s\n",
                    $event->minute,
                    $icon,
                    $event->type,
                    $event->team_side,
                    $suffix
                ));
            }

            if ($state && $state->phase === MatchState::PHASE_FINISHED) {
                break;
            }
        }

        $fixture->refresh();
        $this->stdout(str_repeat('─', 60) . "\n");
        $this->stdout("RESULT: {$fixture->homeTeam->name} {$fixture->home_score} - {$fixture->away_score} {$fixture->awayTeam->name}\n");

        return ExitCode::OK;
    }

    /**
     * Shows the current live state of a fixture.
     * Usage: ./yii game/status <fixture_id>
     *
     * @param int $fixtureId
     */
    public function actionStatus(int $fixtureId): int
    {
        $state = MatchState::findOne(['fixture_id' => $fixtureId]);
        if (!$state) {
            $this->stdout("No match state found for fixture #$fixtureId.\n");
            return ExitCode::OK;
        }

        $fixture = $state->fixture;
        $this->stdout("\nFixture #{$fixtureId}: {$fixture->homeTeam->name} vs {$fixture->awayTeam->name}\n");
        $this->stdout("Phase  : {$state->phase}\n");
        $this->stdout("Minute : {$state->current_minute}'\n");
        $this->stdout("Score  : {$state->home_score} - {$state->away_score}\n");
        $this->stdout("Subs   : home={$state->home_subs_used}/3  away={$state->away_subs_used}/3\n");

        return ExitCode::OK;
    }

    /**
     * Seeds the world: Serie A, B, C — each with 16 CPU teams, full fixture calendar.
     * Aborts if the database already contains teams.
     * Users are assigned to Serie C on registration via TeamAssigner.
     *
     * Usage: ./yii game/seed
     */
    public function actionSeed(): int
    {
        $this->stdout("Starting world generation...\n");
        $this->stdout(str_repeat('─', 50) . "\n");

        try {
            (new WorldSeeder())->run(function (string $msg): void {
                $this->stdout($msg . "\n");
            });
        } catch (\RuntimeException $e) {
            $this->stderr($e->getMessage() . "\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout(str_repeat('─', 50) . "\n");
        $this->stdout("Done. Register a user to get assigned to Serie C.\n");

        return ExitCode::OK;
    }

    /**
     * Creates and simulates a friendly match instantly between two teams.
     * Does not affect standings.
     *
     * Usage: ./yii game/friendly <homeTeamId> <awayTeamId>
     *
     * @param int $homeTeamId
     * @param int $awayTeamId
     */
    public function actionFriendly(int $homeTeamId, int $awayTeamId): int
    {
        $home = Team::findOne($homeTeamId);
        $away = Team::findOne($awayTeamId);

        if (!$home || !$away) {
            $this->stderr("One or both teams not found.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($homeTeamId === $awayTeamId) {
            $this->stderr("Home and away team must be different.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->ensureCpuTeamReady($home);
        $this->ensureCpuTeamReady($away);

        // Get or create a "Amichevoli" competition (type=friendly, not in standings)
        $comp = Competition::findOne(['type' => 'friendly']);
        if (!$comp) {
            $comp = new Competition();
            $comp->name         = 'Amichevoli';
            $comp->type         = 'friendly';
            $comp->season       = 1;
            $comp->tier         = Competition::TIER_C;
            $comp->group_number = 0;
            $comp->save(false);
        }

        $fixture = new Fixture();
        $fixture->competition_id = $comp->id;
        $fixture->home_team_id   = $homeTeamId;
        $fixture->away_team_id   = $awayTeamId;
        $fixture->match_date     = time() - 60;
        $fixture->status         = Fixture::STATUS_PLAYING;
        $fixture->home_score     = 0;
        $fixture->away_score     = 0;
        $fixture->save(false);

        $this->stdout(str_repeat('─', 60) . "\n");
        $this->stdout("AMICHEVOLE: {$home->name} vs {$away->name}\n");
        $this->stdout(str_repeat('─', 60) . "\n");

        /** @var \app\components\MatchEngine $engine */
        $engine = Yii::$app->matchEngine;

        for ($tick = 0; $tick < 110; $tick++) {
            $events = $engine->advanceTick($fixture);
            foreach ($events as $event) {
                $icon   = $this->iconFor($event->type);
                $detail = $event->getDetailArray();
                $suffix = '';
                if ($event->type === 'goal' && isset($detail['home_score'])) {
                    $suffix = " → {$detail['home_score']}:{$detail['away_score']}";
                }
                $this->stdout(sprintf(
                    "  %2d' %s %-20s [%s]%s\n",
                    $event->minute, $icon, $event->type, $event->team_side, $suffix
                ));
            }
            $state = MatchState::findOne(['fixture_id' => $fixture->id]);
            if ($state && $state->phase === MatchState::PHASE_FINISHED) {
                break;
            }
        }

        $fixture->refresh();
        $this->stdout(str_repeat('─', 60) . "\n");
        $this->stdout("RISULTATO: {$home->name} {$fixture->home_score} - {$fixture->away_score} {$away->name}\n");
        $this->stdout("Fixture ID: #{$fixture->id} (vedi: /fixture/view?id={$fixture->id})\n");

        return ExitCode::OK;
    }

    /**
     * Rebuild active formation for every CPU team (daily maintenance job).
     * Usage: ./yii game/refresh-cpu-formations
     */
    public function actionRefreshCpuFormations(): int
    {
        $cpuTeams = Team::find()->where(['is_cpu' => 1])->orderBy(['id' => SORT_ASC])->all();
        if (empty($cpuTeams)) {
            $this->stdout("No CPU teams found.\n");
            return ExitCode::OK;
        }

        $updated = 0;
        foreach ($cpuTeams as $team) {
            $result = $this->cpuFormationService->ensureTeamReady($team);
            if ($result['updated']) {
                $updated++;
                $this->stdout(sprintf(
                    "[CPU #%d] %-24s -> %s / %s (%d titolari)\n",
                    $team->id,
                    substr((string) $team->name, 0, 24),
                    $result['module'],
                    $result['tactic'],
                    $result['assigned']
                ));
            }
        }

        $this->stdout(sprintf("CPU formations refreshed: %d/%d updated.\n", $updated, count($cpuTeams)));
        return ExitCode::OK;
    }

    // ───────────────────────────────────────────────────────────────────
    //  Helpers
    // ───────────────────────────────────────────────────────────────────

    private function iconFor(string $type): string
    {
        return match ($type) {
            'goal'             => '⚽',
            'gk_save'          => '🧤',
            'near_miss'        => '💨',
            'substitution'     => '🔄',
            'tactic_change'    => '📋',
            'half_time'        => '🔔',
            'full_time'        => '🏁',
            'second_half_start'=> '▶️ ',
            'kickoff'          => '🎯',
            default            => '·',
        };
    }

    private function ensureCpuFixtureReady(Fixture $fixture): void
    {
        $home = Team::findOne((int) $fixture->home_team_id);
        $away = Team::findOne((int) $fixture->away_team_id);
        if ($home) {
            $this->ensureCpuTeamReady($home);
        }
        if ($away) {
            $this->ensureCpuTeamReady($away);
        }
    }

    private function ensureCpuTeamReady(Team $team): void
    {
        if ((int) $team->is_cpu !== 1) {
            return;
        }

        try {
            $result = $this->cpuFormationService->ensureTeamReady($team);
            if ($result['updated']) {
                $this->stdout(sprintf(
                    "[CPU auto-form] Team #%d %s -> %s / %s (%d)\n",
                    $team->id,
                    $team->name,
                    $result['module'],
                    $result['tactic'],
                    $result['assigned']
                ));
            }
        } catch (\Throwable $e) {
            Yii::error(
                sprintf('CPU auto-formation failed for team %d: %s', $team->id, $e->getMessage()),
                __METHOD__
            );
        }
    }
}
