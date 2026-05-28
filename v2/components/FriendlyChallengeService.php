<?php

declare(strict_types=1);

namespace app\components;

use app\models\Competition;
use app\models\Fixture;
use app\models\FriendlyChallenge;
use app\models\NewsItem;
use app\models\Team;
use app\components\TelegramService;

class FriendlyChallengeService
{
    private const DEFAULT_MAX_FRIENDLIES_PER_WEEK = 1000;
    private const DEFAULT_FRIENDLY_DAY = 4;   // Thu
    private const DEFAULT_FRIENDLY_HOUR = 15; // 15:00

    public static function maxFriendliesPerWeek(): int
    {
        $raw = getenv('GM_MAX_FRIENDLIES_PER_WEEK');
        if ($raw === false || $raw === '') {
            return self::DEFAULT_MAX_FRIENDLIES_PER_WEEK;
        }
        $parsed = (int) $raw;
        return max(1, $parsed);
    }

    public static function nextFriendlySlot(?int $fromTs = null): int
    {
        $ts = $fromTs ?? time();
        $today = strtotime(date('Y-m-d 00:00:00', $ts));
        $dow = (int) date('N', $today); // 1=Mon ... 7=Sun
        $friendlyDow = self::friendlyDayOfWeek();
        $friendlyHour = self::friendlyHour();
        $daysUntilFriendly = ($friendlyDow - $dow + 7) % 7;
        $candidate = $today + ($daysUntilFriendly * 86400) + ($friendlyHour * 3600);
        if ($candidate <= $ts + 3600) {
            $candidate += 7 * 86400;
        }
        return $candidate;
    }

    private static function friendlyDayOfWeek(): int
    {
        $raw = trim((string) getenv('GM_FRIENDLY_SLOT_DOW'));
        if ($raw === '') {
            return self::DEFAULT_FRIENDLY_DAY;
        }
        $d = (int) $raw;
        return ($d >= 1 && $d <= 7) ? $d : self::DEFAULT_FRIENDLY_DAY;
    }

    private static function friendlyHour(): int
    {
        $raw = trim((string) getenv('GM_FRIENDLY_SLOT_HOUR'));
        if ($raw === '') {
            return self::DEFAULT_FRIENDLY_HOUR;
        }
        $h = (int) $raw;
        return ($h >= 0 && $h <= 23) ? $h : self::DEFAULT_FRIENDLY_HOUR;
    }

    /**
     * @return array{0:int,1:int}
     */
    public static function weekBounds(int $ts): array
    {
        $dayStart = strtotime(date('Y-m-d 00:00:00', $ts));
        $dow = (int) date('N', $dayStart);
        $weekStart = $dayStart - (($dow - 1) * 86400);
        $weekEnd = $weekStart + (7 * 86400) - 1;
        return [$weekStart, $weekEnd];
    }

    public static function hasWeeklyFriendlyCommitment(int $teamId, int $ts, ?int $excludeChallengeId = null): bool
    {
        [$start, $end] = self::weekBounds($ts);
        $maxWeekly = self::maxFriendliesPerWeek();

        $fixtureCount = (int) Fixture::find()
            ->alias('f')
            ->innerJoin(Competition::tableName() . ' c', 'c.id = f.competition_id')
            ->where(['c.type' => 'friendly'])
            ->andWhere(['between', 'f.match_date', $start, $end])
            ->andWhere(['or', ['f.home_team_id' => $teamId], ['f.away_team_id' => $teamId]])
            ->count();
        if ($fixtureCount >= $maxWeekly) {
            return true;
        }

        $challengeCount = (int) FriendlyChallenge::find()
            ->where([
                'or',
                ['status' => FriendlyChallenge::STATUS_PENDING],
                [
                    'and',
                    ['status' => FriendlyChallenge::STATUS_ACCEPTED],
                    ['fixture_id' => null],
                ],
            ])
            ->andWhere(['between', 'proposed_at', $start, $end])
            ->andWhere(['or', ['challenger_id' => $teamId], ['challenged_id' => $teamId]])
            ->andFilterWhere(['!=', 'id', $excludeChallengeId])
            ->count();

        return ($fixtureCount + $challengeCount) >= $maxWeekly;
    }

    public static function averageFreshness(Team $team): float
    {
        $players = $team->players;
        if (empty($players)) {
            return 100.0;
        }
        $sum = 0;
        foreach ($players as $p) {
            $sum += (int) $p->freshness;
        }
        return $sum / max(1, count($players));
    }

    public static function expirePendingChallenges(): int
    {
        $deadline = time() - 86400;
        $expiredItems = FriendlyChallenge::find()
            ->with(['challenger'])
            ->where(['status' => FriendlyChallenge::STATUS_PENDING])
            ->andWhere(['<=', 'created_at', $deadline])
            ->all();

        $count = 0;
        foreach ($expiredItems as $challenge) {
            $challenge->status = FriendlyChallenge::STATUS_EXPIRED;
            $challenge->decline_reason = 'Nessuna risposta entro 24 ore';
            $challenge->responded_at = time();
            if (!$challenge->save(false, ['status', 'decline_reason', 'responded_at'])) {
                continue;
            }
            $count++;

            if ($challenge->challenger && $challenge->challenger->user_id) {
                NewsService::create(
                    (int) $challenge->challenger->user_id,
                    NewsItem::CAT_FRIENDLY,
                    'T',
                    'Sfida amichevole scaduta',
                    'La tua sfida non ha ricevuto risposta entro 24 ore.',
                    \Yii::$app->urlManager->createUrl(['/friendly/index'])
                );
                TelegramService::sendToUser(
                    (int) $challenge->challenger->user_id,
                    "📩 <b>Sfida amichevole scaduta</b>\nLa tua sfida non ha ricevuto risposta entro 24 ore."
                );
            }
        }

        return $count;
    }

    public static function ensureFriendlyCompetition(): Competition
    {
        $comp = Competition::findOne(['type' => 'friendly']);
        if ($comp) {
            return $comp;
        }

        $comp = new Competition();
        $comp->name = 'Amichevoli';
        $comp->type = 'friendly';
        $comp->season = 1;
        $comp->tier = Competition::TIER_C;
        $comp->group_number = 0;
        $comp->save(false);

        return $comp;
    }

    public static function createFixtureForChallenge(FriendlyChallenge $challenge): Fixture
    {
        $comp = self::ensureFriendlyCompetition();

        $stadium = \app\models\Stadium::findOne(['team_id' => $challenge->challenger_id]);
        $ticketPrice = (int) ($stadium?->friendly_ticket_price ?? 10);

        $fixture = new Fixture();
        $fixture->competition_id = (int) $comp->id;
        $fixture->home_team_id = (int) $challenge->challenger_id;
        $fixture->away_team_id = (int) $challenge->challenged_id;
        $fixture->match_date = (int) $challenge->proposed_at;
        $fixture->status = Fixture::STATUS_SCHEDULED;
        $fixture->home_score = 0;
        $fixture->away_score = 0;
        $fixture->friendly_ticket_price = $ticketPrice;
        $fixture->save(false);

        return $fixture;
    }

    public static function syncPlayedStatus(): int
    {
        $accepted = FriendlyChallenge::find()
            ->alias('fc')
            ->innerJoin(Fixture::tableName() . ' f', 'f.id = fc.fixture_id')
            ->where(['fc.status' => FriendlyChallenge::STATUS_ACCEPTED, 'f.status' => Fixture::STATUS_FINISHED])
            ->all();

        $count = 0;
        foreach ($accepted as $item) {
            $item->status = FriendlyChallenge::STATUS_PLAYED;
            if ($item->save(false, ['status'])) {
                $count++;
            }
        }

        return $count;
    }
}
