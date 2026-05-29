<?php

declare(strict_types=1);

namespace app\components\seeders;

use app\models\Competition;
use app\models\Fixture;
use app\models\Team;

class FixtureSeeder
{
    private const DEFAULT_LEAGUE_DAYS = [3, 6]; // Wed + Sat

    /**
     * Kickoff slots within a round base day (offsets in seconds from 00:00).
     * Gold Manager currently schedules every fixture at 15:00.
     */
    private const KICKOFF_SLOTS = [
        15 * 3600,
    ];

    /**
     * Generates a full double round-robin fixture calendar.
     *
     * @param Team[]      $teams
     * @param Competition $competition
     * @param int         $kickoffBase  Unix timestamp of the Saturday of the first round (midnight)
     * @param int         $roundSpacing Seconds between rounds (default 7 days)
     */
    public function seed(
        array $teams,
        Competition $competition,
        ?int $kickoffBase = null,
        int $roundSpacing = 604800
    ): void {
        $kickoffBase ??= $this->nextSaturday();

        $pairs  = $this->roundRobin($teams);   // first leg
        $pairs  = $this->enforceHomeAwayBalance($pairs);
        $rounds = count($pairs);               // N-1 rounds

        $roundDates = $this->buildRoundDates($rounds * 2, $kickoffBase);

        // First leg
        foreach ($pairs as $round => $matches) {
            $matchDate = $roundDates[$round] ?? ($kickoffBase + ($round * $roundSpacing));
            $this->insertRound($matches, $competition->id, $matchDate);
        }

        // Second leg: reverse home/away based on balanced first leg
        foreach ($pairs as $round => $matches) {
            $matchDate = $roundDates[$rounds + $round] ?? ($kickoffBase + (($rounds + $round) * $roundSpacing));
            $reversed  = array_map(fn($m) => [$m[1], $m[0]], $matches);
            $this->insertRound($reversed, $competition->id, $matchDate);
        }
    }

    /**
     * Berger round-robin: returns array of rounds, each round is array of [homeTeam, awayTeam].
     *
     * @param Team[] $teams
     * @return array
     */
    public function roundRobin(array $teams): array
    {
        $n = count($teams);
        if ($n % 2 !== 0) {
            $teams[] = null; // bye
            $n++;
        }

        $fixed    = $teams[0];
        $rotating = array_slice($teams, 1);
        $rounds   = [];

        for ($r = 0; $r < $n - 1; $r++) {
            $circle = array_merge([$fixed], $rotating);
            $round  = [];

            for ($i = 0; $i < $n / 2; $i++) {
                $home = $circle[$i];
                $away = $circle[$n - 1 - $i];
                if ($home !== null && $away !== null) {
                    $round[] = [$home, $away];
                }
            }

            $rounds[] = $round;

            // rotate: move last element to front of rotating array
            array_unshift($rotating, array_pop($rotating));
        }

        return $rounds;
    }

    /**
     * @param array $matches    Each element: [Team, Team]
     * @param int   $weekendBase Unix timestamp of Saturday midnight for this round
     */
    private function insertRound(array $matches, int $competitionId, int $weekendBase): void
    {
        $slots = self::KICKOFF_SLOTS;

        foreach ($matches as $idx => [$home, $away]) {
            // Cycle through slots; if more than 8 matches, wrap around
            $offset   = $slots[$idx % count($slots)];
            $kickoff  = $weekendBase + $offset;

            $fixture = new Fixture();
            $fixture->competition_id = $competitionId;
            $fixture->home_team_id   = $home->id;
            $fixture->away_team_id   = $away->id;
            $fixture->match_date     = $kickoff;
            $fixture->status         = Fixture::STATUS_SCHEDULED;
            $fixture->language       = Fixture::langFromHomeTeam((int) $home->id);

            if (!$fixture->save()) {
                throw new \RuntimeException(
                    "FixtureSeeder: failed for {$home->id} vs {$away->id}: " . json_encode($fixture->errors)
                );
            }
        }
    }

    private function nextSaturday(): int
    {
        $ts = strtotime('next Saturday midnight');
        return $ts !== false ? $ts : (time() + 86400);
    }

    /**
     * Returns round dates based on configured league weekdays.
     * Env: GM_LEAGUE_MATCH_DAYS="3,6" (Mon=1..Sun=7)
     *
     * @return int[] unix timestamps at 00:00 for each round base day
     */
    private function buildRoundDates(int $totalRounds, int $seedBaseTs): array
    {
        $days = $this->leagueMatchDays();
        if (empty($days) || $totalRounds <= 0) {
            return [];
        }

        $dates = [];
        $current = $this->firstMatchDayOnOrAfter($seedBaseTs, $days);
        $dates[] = $current;

        for ($i = 1; $i < $totalRounds; $i++) {
            $currentDow = (int) date('N', $current);
            $nextDow = null;
            foreach ($days as $day) {
                if ($day > $currentDow) {
                    $nextDow = $day;
                    break;
                }
            }
            $deltaDays = $nextDow !== null
                ? ($nextDow - $currentDow)
                : ((7 - $currentDow) + $days[0]);

            $current += $deltaDays * 86400;
            $dates[] = $current;
        }

        return $dates;
    }

    /**
     * @return int[] sorted unique days Mon=1..Sun=7
     */
    private function leagueMatchDays(): array
    {
        $raw = trim((string) getenv('GM_LEAGUE_MATCH_DAYS'));
        if ($raw === '') {
            return self::DEFAULT_LEAGUE_DAYS;
        }

        $parsed = [];
        foreach (explode(',', $raw) as $chunk) {
            $d = (int) trim($chunk);
            if ($d >= 1 && $d <= 7) {
                $parsed[] = $d;
            }
        }

        $parsed = array_values(array_unique($parsed));
        sort($parsed);
        return !empty($parsed) ? $parsed : self::DEFAULT_LEAGUE_DAYS;
    }

    private function firstMatchDayOnOrAfter(int $baseTs, array $days): int
    {
        $dayStart = strtotime(date('Y-m-d 00:00:00', $baseTs));
        $baseDow = (int) date('N', $dayStart);

        foreach ($days as $d) {
            if ($d >= $baseDow) {
                return $dayStart + (($d - $baseDow) * 86400);
            }
        }

        return $dayStart + (((7 - $baseDow) + $days[0]) * 86400);
    }

    /**
     * Attempts to alternate home/away assignments round by round.
     *
     * @param array<int, array<int, array{0:?Team,1:?Team}>> $rounds
     * @return array
     */
    private function enforceHomeAwayBalance(array $rounds): array
    {
        $lastVenue = [];

        foreach ($rounds as $roundIndex => $matches) {
            foreach ($matches as $matchIndex => $pair) {
                [$home, $away] = $pair;
                if (!$home || !$away) {
                    continue;
                }
                $homeId = (int) $home->id;
                $awayId = (int) $away->id;

                $currentPenalty = max(
                    $this->projectedStreak($lastVenue[$homeId] ?? null, 'home'),
                    $this->projectedStreak($lastVenue[$awayId] ?? null, 'away')
                );
                $swapPenalty = max(
                    $this->projectedStreak($lastVenue[$homeId] ?? null, 'away'),
                    $this->projectedStreak($lastVenue[$awayId] ?? null, 'home')
                );

                if ($swapPenalty < $currentPenalty) {
                    [$home, $away] = [$away, $home];
                    $rounds[$roundIndex][$matchIndex] = [$home, $away];
                    $homeId = (int) $home->id;
                    $awayId = (int) $away->id;
                }

                $lastVenue[$homeId] = $this->updatedStreak($lastVenue[$homeId] ?? null, 'home');
                $lastVenue[$awayId] = $this->updatedStreak($lastVenue[$awayId] ?? null, 'away');
            }
        }

        return $rounds;
    }

    private function projectedStreak(?array $last, string $nextVenue): int
    {
        if (!$last || ($last['venue'] ?? null) !== $nextVenue) {
            return 1;
        }
        return (int)($last['count'] ?? 0) + 1;
    }

    private function updatedStreak(?array $last, string $venue): array
    {
        if ($last && ($last['venue'] ?? null) === $venue) {
            $count = (int)($last['count'] ?? 0) + 1;
        } else {
            $count = 1;
        }

        return ['venue' => $venue, 'count' => $count];
    }
}
