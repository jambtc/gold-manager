<?php

declare(strict_types=1);

namespace app\components;

use app\components\PlayerAttributeHelper;
use app\components\seeders\CompetitionSeeder;
use app\components\seeders\FixtureSeeder;
use app\components\seeders\PlayerSeeder;
use app\components\seeders\StadiumSeeder;
use app\components\seeders\TeamSeeder;
use app\models\Competition;
use Yii;

class WorldSeeder
{
    private TeamSeeder        $teamSeeder;
    private PlayerSeeder      $playerSeeder;
    private StadiumSeeder     $stadiumSeeder;
    private CompetitionSeeder $competitionSeeder;
    private FixtureSeeder     $fixtureSeeder;

    public function __construct()
    {
        $this->teamSeeder        = new TeamSeeder();
        $this->playerSeeder      = new PlayerSeeder();
        $this->stadiumSeeder     = new StadiumSeeder();
        $this->competitionSeeder = new CompetitionSeeder();
        $this->fixtureSeeder     = new FixtureSeeder();
    }

    /**
     * Seeds the entire world: one group each for Serie A, B, C.
     * All teams are CPU. TeamAssigner assigns users to Serie C slots.
     */
    public function run(?callable $log = null): void
    {
        $this->assertEmptyDatabase();

        $log ??= static function (string $msg): void {};

        $transaction = Yii::$app->db->beginTransaction();

        try {
            foreach ([Competition::TIER_A, Competition::TIER_B, Competition::TIER_C] as $tier) {
                $label = Competition::TIER_NAMES[$tier];
                $log("Seeding $label...");
                $this->seedLeague($tier, 1, $log);
            }

            $transaction->commit();
            $log("World generation complete.");
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * Creates a new league group: teams, players, stadiums, competition, fixtures.
     * Used by WorldSeeder::run() for initial seeding and by TeamAssigner for expansion.
     *
     * @param int           $tier        Competition::TIER_A/B/C
     * @param int           $groupNumber Girone number within the tier
     * @param callable|null $log
     * @param int           $teamCount   Teams per group (default 16)
     * @return Competition
     */
    public function seedLeague(int $tier, int $groupNumber, ?callable $log = null, int $teamCount = 16): Competition
    {
        $log ??= static function (string $msg): void {};

        $teams = $this->teamSeeder->seed($teamCount, null);
        $log(sprintf("  Created %d CPU teams.", count($teams)));

        foreach ($teams as $team) {
            $this->seedPlayersForTeam($team->id);
        }
        $log(sprintf("  Created %d players.", count($teams) * array_sum(WorldData::rosterComposition())));

        $this->stadiumSeeder->seed($teams);
        $log(sprintf("  Created %d stadiums.", count($teams)));

        $competition = $this->competitionSeeder->seed($teams, $tier, $groupNumber);
        $log(sprintf("  Competition '%s' created.", $competition->getLabel()));

        $this->fixtureSeeder->seed($teams, $competition);
        $n = count($teams);
        $log(sprintf("  Generated %d fixtures.", $n * ($n - 1)));

        return $competition;
    }

    /**
     * Seeds a fresh CPU team into an existing competition (used by promotion/relegation refresh).
     * Creates team, players, stadium, and a zeroed standing row.
     */
    public function seedCpuTeamForCompetition(Competition $competition, int $budget = 3_000_000): \app\models\Team
    {
        $teams = $this->teamSeeder->seed(1, null, $budget);
        $team  = $teams[0];

        $this->seedPlayersForTeam($team->id);
        $this->stadiumSeeder->seed([$team]);

        $standing = new \app\models\Standing();
        $standing->competition_id = $competition->id;
        $standing->team_id        = $team->id;
        $standing->points         = 0;
        $standing->played         = 0;
        $standing->won            = 0;
        $standing->drawn          = 0;
        $standing->lost           = 0;
        $standing->goals_for      = 0;
        $standing->goals_against  = 0;
        $standing->save(false);

        return $team;
    }

    public function seedPlayersForTeam(int $teamId): void
    {
        $roster    = WorldData::rosterComposition();
        $number    = 1;
        $usedNames = [];

        foreach ($roster as $position => $count) {
            for ($i = 0; $i < $count; $i++) {
                $nationality = WorldData::pickNationality();
                $name        = $this->uniqueName($usedNames, $nationality);
                $usedNames[] = $name;

                ['player' => $player, 'contract' => $contract] =
                    $this->playerSeeder->buildPlayer($teamId, $position, $number, $name, $nationality);

                if (!$player->save()) {
                    throw new \RuntimeException("WorldSeeder: player save failed: " . json_encode($player->errors));
                }
                PlayerAttributeHelper::ensureInitialTalents($player);

                $contract->player_id = $player->id;
                if (!$contract->save()) {
                    throw new \RuntimeException("WorldSeeder: contract save failed: " . json_encode($contract->errors));
                }

                $number++;
            }
        }
    }

    private function uniqueName(array $usedNames, string $nationality = 'ITA'): string
    {
        $attempts = 0;
        do {
            $first = WorldData::randomFirstName($nationality);
            $last  = WorldData::randomLastName($nationality);
            $name  = "$first $last";
            $attempts++;
            if ($attempts > 50) {
                $name .= ' ' . $attempts;
            }
        } while (in_array($name, $usedNames, true));

        return $name;
    }

    private function assertEmptyDatabase(): void
    {
        $count = (int) \app\models\Team::find()->count();
        if ($count > 0) {
            throw new \RuntimeException(
                "WorldSeeder: database is not empty ($count teams found). Aborting."
            );
        }
    }
}
