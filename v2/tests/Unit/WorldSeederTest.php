<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\components\WorldData;
use app\components\seeders\FixtureSeeder;
use app\components\seeders\PlayerSeeder;

final class WorldSeederTest extends \Codeception\Test\Unit
{
    public mixed $tester = null;

    // ── WorldData ────────────────────────────────────────────────────────

    public function testCharacterListIsNotEmpty(): void
    {
        verify(WorldData::CHARACTERS)->notEmpty();
    }

    public function testTeamNamesHasAtLeastSixty(): void
    {
        self::assertGreaterThanOrEqual(60, count(WorldData::TEAM_NAMES));
    }

    public function testFirstNamesAndLastNamesNotEmpty(): void
    {
        verify(WorldData::FIRST_NAMES)->notEmpty();
        verify(WorldData::LAST_NAMES)->notEmpty();
    }

    public function testStatProfileReturnsAllEightStats(): void
    {
        $allStats = ['skill_po', 'skill_df', 'skill_cn', 'skill_pa', 'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr'];

        foreach (['GK', 'DF', 'MF', 'FW'] as $pos) {
            $profile = WorldData::statProfile($pos);
            $covered = array_merge($profile['primary'], $profile['secondary'], $profile['low']);
            sort($covered);
            sort($allStats);
            verify($covered)->equals($allStats, "Position $pos does not cover all 8 stats");
        }
    }

    public function testStatProfileThrowsOnUnknownPosition(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        WorldData::statProfile('XX');
    }

    public function testRosterCompositionSumsToTwenty(): void
    {
        verify(array_sum(WorldData::rosterComposition()))->equals(20);
    }

    // ── PlayerSeeder ─────────────────────────────────────────────────────

    public function testPlayerAttributesStatsWithinRange(): void
    {
        $seeder = new PlayerSeeder();

        foreach (['GK', 'DF', 'MF', 'FW'] as $pos) {
            $attrs = $seeder->buildAttributes($pos, 1, 'Mario Rossi');

            foreach (['skill_po', 'skill_df', 'skill_cn', 'skill_pa', 'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr'] as $stat) {
                self::assertGreaterThanOrEqual(1, $attrs[$stat], "$pos.$stat < 1");
                self::assertLessThanOrEqual(99, $attrs[$stat], "$pos.$stat > 99");
            }
        }
    }

    public function testPlayerAttributesGeneralSkillWithinRange(): void
    {
        $seeder = new PlayerSeeder();

        foreach (['GK', 'DF', 'MF', 'FW'] as $pos) {
            $attrs = $seeder->buildAttributes($pos, 1, 'Marco Bianchi');
            self::assertGreaterThanOrEqual(1, $attrs['general_skill']);
            self::assertLessThanOrEqual(99, $attrs['general_skill']);
        }
    }

    public function testPlayerAttributesCharacterIsFromApprovedList(): void
    {
        $seeder = new PlayerSeeder();

        for ($i = 0; $i < 20; $i++) {
            $attrs = $seeder->buildAttributes('MF', $i + 1, "Player $i");
            verify(in_array($attrs['character'], WorldData::CHARACTERS, true))->true();
        }
    }

    public function testPlayerAttributesFootIsValid(): void
    {
        $seeder   = new PlayerSeeder();
        $validFeet = ['R', 'L', 'LR'];

        for ($i = 0; $i < 30; $i++) {
            $attrs = $seeder->buildAttributes('FW', 9, 'Test Player');
            verify(in_array($attrs['foot'], $validFeet, true))->true();
        }
    }

    public function testPlayerAttributesFormFreshnessConditionAreHundred(): void
    {
        $seeder = new PlayerSeeder();
        $attrs  = $seeder->buildAttributes('DF', 5, 'Luigi Ferrari');

        verify($attrs['form'])->equals(100);
        verify($attrs['freshness'])->equals(100);
        verify($attrs['condition'])->equals(100);
    }

    public function testPlayerAttributesAgeWithinRoleRange(): void
    {
        $seeder = new PlayerSeeder();
        $ranges = [
            'GK' => [22, 35],
            'DF' => [20, 32],
            'MF' => [19, 31],
            'FW' => [19, 30],
        ];

        foreach ($ranges as $pos => [$min, $max]) {
            for ($i = 0; $i < 10; $i++) {
                $attrs = $seeder->buildAttributes($pos, 1, 'Test Player');
                self::assertGreaterThanOrEqual($min, $attrs['age'], "$pos age < $min");
                self::assertLessThanOrEqual($max, $attrs['age'], "$pos age > $max");
            }
        }
    }

    public function testRosterCompositionByPosition(): void
    {
        $seeder     = new PlayerSeeder();
        $roster     = WorldData::rosterComposition();
        $namesByPos = [];
        $number     = 1;

        foreach ($roster as $position => $count) {
            for ($i = 0; $i < $count; $i++) {
                $attrs = $seeder->buildAttributes($position, $number, "Player $number");
                $namesByPos[$position][] = $attrs['position'];
                $number++;
            }
        }

        verify(count($namesByPos['GK']))->equals(2);
        verify(count($namesByPos['DF']))->equals(6);
        verify(count($namesByPos['MF']))->equals(6);
        verify(count($namesByPos['FW']))->equals(6);
    }

    // ── FixtureSeeder (pure logic) ────────────────────────────────────────

    public function testRoundRobinProducesCorrectFixtureCount(): void
    {
        $seeder = new FixtureSeeder();

        // Build fake team-like objects with an 'id'
        $teams = [];
        for ($i = 1; $i <= 16; $i++) {
            $t     = new \stdClass();
            $t->id = $i;
            $teams[] = $t;
        }

        /** @phpstan-ignore-next-line */
        $rounds = $seeder->roundRobin($teams);

        $n              = count($teams);
        $expectedRounds = $n - 1;
        $matchesPerRound = $n / 2;

        verify(count($rounds))->equals($expectedRounds);

        foreach ($rounds as $round) {
            verify(count($round))->equals($matchesPerRound);
        }
    }

    public function testRoundRobinNoTeamPlaysItselfInARound(): void
    {
        $seeder = new FixtureSeeder();
        $teams  = [];

        for ($i = 1; $i <= 16; $i++) {
            $t     = new \stdClass();
            $t->id = $i;
            $teams[] = $t;
        }

        /** @phpstan-ignore-next-line */
        $rounds = $seeder->roundRobin($teams);

        foreach ($rounds as $ri => $round) {
            foreach ($round as [$home, $away]) {
                verify($home->id)->notEquals($away->id, "Round $ri: team plays itself");
            }
        }
    }

    public function testRoundRobinEachTeamPlaysOncePerRound(): void
    {
        $seeder = new FixtureSeeder();
        $teams  = [];

        for ($i = 1; $i <= 16; $i++) {
            $t     = new \stdClass();
            $t->id = $i;
            $teams[] = $t;
        }

        /** @phpstan-ignore-next-line */
        $rounds = $seeder->roundRobin($teams);

        foreach ($rounds as $ri => $round) {
            $seen = [];
            foreach ($round as [$home, $away]) {
                $seen[] = $home->id;
                $seen[] = $away->id;
            }
            verify(count($seen))->equals(count(array_unique($seen)), "Round $ri: duplicate team");
        }
    }
}
