<?php

declare(strict_types=1);

namespace app\components;

use app\models\Competition;
use app\models\Fixture;
use app\models\Standing;
use yii\db\Query;

class TransferWindowService
{
    /**
     * Winter + summer windows.
     * Dev env can bypass with default open behavior.
     */
    public static function isOpen(?int $ts = null, ?int $teamId = null, ?int $competitionId = null): bool
    {
        $ts = $ts ?? time();
        // SIP-0087: market always open by default; set GM_TRANSFER_ALWAYS_OPEN=0 to re-enable windows.
        $forced = getenv('GM_TRANSFER_ALWAYS_OPEN');
        if ($forced === '0') {
            return false;
        }
        return true;

        $resolvedCompetitionId = $competitionId ?? self::resolveCompetitionIdForTeam($teamId);
        if ($resolvedCompetitionId === null) {
            return true;
        }

        $fixtures = self::competitionFixtures($resolvedCompetitionId, $ts);
        if (empty($fixtures)) {
            return true;
        }

        $first = $fixtures[0];
        if ($ts < $first) {
            // Pre-season: open until first competitive fixture.
            return true;
        }

        $winter = self::winterWindowBounds($fixtures);
        if ($winter === null) {
            return false;
        }

        [$winterOpen, $winterClose] = $winter;
        return $ts >= $winterOpen && $ts <= $winterClose;
    }

    public static function nextOpeningTimestamp(?int $ts = null, ?int $teamId = null, ?int $competitionId = null): int
    {
        $ts = $ts ?? time();
        if (self::isOpen($ts, $teamId, $competitionId)) {
            return $ts;
        }

        $resolvedCompetitionId = $competitionId ?? self::resolveCompetitionIdForTeam($teamId);
        if ($resolvedCompetitionId === null) {
            return $ts;
        }

        $fixtures = self::competitionFixtures($resolvedCompetitionId, $ts);
        if (empty($fixtures)) {
            return $ts;
        }

        $first = $fixtures[0];
        if ($ts < $first) {
            return $ts;
        }

        $winter = self::winterWindowBounds($fixtures);
        if ($winter !== null) {
            [$winterOpen, $winterClose] = $winter;
            if ($ts < $winterOpen) {
                return $winterOpen;
            }
            if ($ts <= $winterClose) {
                return $ts;
            }
        }

        // After winter window: next opening is day after last fixture (new pre-season).
        $last = $fixtures[count($fixtures) - 1];
        return $last + 86400;
    }

    /**
     * Ritorna i confini temporali della finestra di mercato corrente o futura.
     *
     * @return array{type:string, isOpen:bool, open_at:int, close_at:int}|null
     */
    public static function getActiveWindowBounds(?int $ts = null, ?int $teamId = null, ?int $competitionId = null): ?array
    {
        $ts = $ts ?? time();
        $resolvedCompetitionId = $competitionId ?? self::resolveCompetitionIdForTeam($teamId);
        if ($resolvedCompetitionId === null) {
            return null;
        }

        $fixtures = self::competitionFixtures($resolvedCompetitionId, $ts);
        if (empty($fixtures)) {
            return null;
        }

        $first = $fixtures[0];
        if ($ts < $first) {
            return [
                'type' => 'pre-season',
                'isOpen' => true,
                'open_at' => $first - (30 * 86400),
                'close_at' => $first,
            ];
        }

        $winter = self::winterWindowBounds($fixtures);
        if ($winter !== null) {
            [$winterOpen, $winterClose] = $winter;
            if ($ts >= $winterOpen && $ts <= $winterClose) {
                return [
                    'type' => 'winter',
                    'isOpen' => true,
                    'open_at' => $winterOpen,
                    'close_at' => $winterClose,
                ];
            }

            if ($ts < $winterOpen) {
                return [
                    'type' => 'winter',
                    'isOpen' => false,
                    'open_at' => $winterOpen,
                    'close_at' => $winterClose,
                ];
            }
        }

        $last = $fixtures[count($fixtures) - 1];
        return [
            'type' => 'pre-season',
            'isOpen' => false,
            'open_at' => $last + 86400,
            'close_at' => $last + 86400 + (30 * 86400),
        ];
    }

    private static function resolveCompetitionIdForTeam(?int $teamId): ?int
    {
        if (!$teamId) {
            return null;
        }

        $row = (new Query())
            ->select(['s.competition_id'])
            ->from(['s' => Standing::tableName()])
            ->innerJoin(['c' => Competition::tableName()], 'c.id = s.competition_id')
            ->where(['s.team_id' => (int) $teamId])
            ->andWhere(['!=', 'c.type', 'friendly'])
            ->orderBy(['c.season' => SORT_DESC, 'c.id' => SORT_DESC])
            ->one();

        return $row ? (int) $row['competition_id'] : null;
    }

    /**
     * @return int[] sorted match_date timestamps for near season horizon
     */
    private static function competitionFixtures(int $competitionId, int $ts): array
    {
        $from = $ts - (120 * 86400);
        $to = $ts + (240 * 86400);

        $rows = Fixture::find()
            ->select('match_date')
            ->where(['competition_id' => $competitionId])
            ->andWhere(['between', 'match_date', $from, $to])
            ->orderBy(['match_date' => SORT_ASC, 'id' => SORT_ASC])
            ->column();

        return array_values(array_map('intval', $rows));
    }

    /**
     * @param int[] $fixtures sorted by date asc
     * @return array{0:int,1:int}|null
     */
    private static function winterWindowBounds(array $fixtures): ?array
    {
        $total = count($fixtures);
        if ($total < 4) {
            return null;
        }

        $lastFirstLegIdx = intdiv($total, 2) - 1;
        $firstReturnIdx = $lastFirstLegIdx + 1;
        if (!isset($fixtures[$lastFirstLegIdx], $fixtures[$firstReturnIdx])) {
            return null;
        }

        $winterOpen = (int) $fixtures[$lastFirstLegIdx] + 86400;
        $winterClose = (int) $fixtures[$firstReturnIdx] - 86400;
        if ($winterClose < $winterOpen) {
            return null;
        }

        return [$winterOpen, $winterClose];
    }
}
