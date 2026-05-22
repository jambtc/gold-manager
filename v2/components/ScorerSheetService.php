<?php

declare(strict_types=1);

namespace app\components;

use app\models\MatchEvent;
use app\models\Player;

class ScorerSheetService
{
    /**
     * Build scorer sheet from match_event rows for a fixture.
     * Returns ['home' => [...], 'away' => [...]]
     */
    public static function buildForFixture(int $fixtureId, ?int $upToMinute = null): array
    {
        $query = MatchEvent::find()
            ->where(['fixture_id' => $fixtureId, 'type' => 'goal'])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC]);

        if ($upToMinute !== null) {
            $query->andWhere(['<=', 'minute', $upToMinute]);
        }

        $events = $query->all();
        return self::buildFromEvents($events);
    }

    /** @param MatchEvent[] $events */
    public static function buildFromEvents(array $events): array
    {
        $sheet = ['home' => [], 'away' => []];
        $playerIds = [];
        foreach ($events as $ev) {
            if (!empty($ev->player_id)) {
                $playerIds[] = (int) $ev->player_id;
            }
        }
        $playerIds = array_values(array_unique($playerIds));

        $playerNameById = [];
        if (!empty($playerIds)) {
            $rows = Player::find()
                ->select(['id', 'name'])
                ->where(['id' => $playerIds])
                ->asArray()
                ->all();
            foreach ($rows as $row) {
                $playerNameById[(int) $row['id']] = (string) $row['name'];
            }
        }

        foreach ($events as $ev) {
            $detail     = $ev->detail ? (json_decode($ev->detail, true) ?? []) : [];
            $scorerName = isset($detail['scorer_name']) ? (string) $detail['scorer_name'] : null;
            if (($scorerName === null || $scorerName === '') && !empty($ev->player_id)) {
                $scorerName = $playerNameById[(int) $ev->player_id] ?? null;
            }
            $isPenalty  = (bool) ($detail['is_penalty'] ?? false);
            $origin     = (string) ($detail['origin'] ?? 'open_play');
            $homeScore  = $detail['home_score'] ?? ($detail['home'] ?? null);
            $awayScore  = $detail['away_score'] ?? ($detail['away'] ?? null);

            $side = $ev->team_side === 'home' ? 'home' : 'away';

            $sheet[$side][] = [
                'player_id'   => $ev->player_id,
                'player_name' => $scorerName ?: 'Autore sconosciuto',
                'minute'      => (int) $ev->minute,
                'is_penalty'  => $isPenalty,
                'origin'      => $origin,
                'score_home'  => $homeScore,
                'score_away'  => $awayScore,
            ];
        }

        return $sheet;
    }

    /** Compact string: "Rossi 12', Bianchi 77' | Verdi 54'" */
    public static function compactString(array $sheet): string
    {
        $fmt = fn(array $scorers) => implode(', ', array_map(
            fn($s) => $s['player_name'] . ' ' . $s['minute'] . "'" . ($s['is_penalty'] ? ' (R)' : ''),
            $scorers
        ));

        $home = $fmt($sheet['home']);
        $away = $fmt($sheet['away']);

        if (!$home && !$away) return '';
        if (!$away) return $home;
        if (!$home) return '— | ' . $away;
        return $home . ' | ' . $away;
    }
}
