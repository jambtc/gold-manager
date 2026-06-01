<?php

declare(strict_types=1);

namespace app\components\seeders;

use app\models\Competition;
use app\models\Standing;
use app\models\Team;

class CompetitionSeeder
{
    /**
     * Creates one Competition and one Standing row per team.
     *
     * @param Team[] $teams
     * @param int    $tier        Competition::TIER_A/B/C
     * @param int    $groupNumber Girone number within the tier
     * @param int    $season
     * @return Competition
     */
    public function seed(
        array $teams,
        int $tier = Competition::TIER_C,
        int $groupNumber = 1,
        int $season = 1,
        string $countryCode = 'IT'
    ): Competition
    {
        $countryCode = \app\components\CountryContext::normalize($countryCode);
        $tierName = Competition::TIER_NAMES[$tier];
        $name     = $groupNumber > 1 ? "{$countryCode} $tierName - " . \Yii::t('app', 'Group') . " $groupNumber" : "{$countryCode} $tierName";

        $competition = new Competition();
        $competition->name         = $name;
        $competition->season       = $season;
        $competition->type         = 'league';
        $competition->tier         = $tier;
        $competition->group_number = $groupNumber;
        $competition->country_code = $countryCode;

        if (!$competition->save()) {
            throw new \RuntimeException('CompetitionSeeder: failed to save competition: ' . json_encode($competition->errors));
        }

        foreach ($teams as $team) {
            $standing = new Standing();
            $standing->competition_id = $competition->id;
            $standing->team_id        = $team->id;
            $standing->points         = 0;
            $standing->played         = 0;
            $standing->won            = 0;
            $standing->drawn          = 0;
            $standing->lost           = 0;
            $standing->goals_for      = 0;
            $standing->goals_against  = 0;

            if (!$standing->save()) {
                throw new \RuntimeException("CompetitionSeeder: failed to save standing for team #{$team->id}: " . json_encode($standing->errors));
            }
        }

        return $competition;
    }
}
