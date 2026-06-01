<?php

declare(strict_types=1);

namespace app\components\seeders;

use app\components\WorldData;
use app\models\Team;

class TeamSeeder
{
    /**
     * Creates N CPU teams + one human team for the given userId.
     * Returns array of saved Team models (human team is last).
     *
     * @param int      $cpuCount
     * @param int|null $userId   null = no human team created
     * @param int      $budget
     * @return Team[]
     */
    public function seed(int $cpuCount, ?int $userId, int $budget = 5_000_000): array
    {
        return $this->seedByCountry($cpuCount, $userId, $budget, \app\components\CountryContext::DEFAULT_COUNTRY);
    }

    /**
     * @param int      $cpuCount
     * @param int|null $userId
     * @param int      $budget
     * @param string   $countryCode
     * @return Team[]
     */
    public function seedByCountry(int $cpuCount, ?int $userId, int $budget = 5_000_000, string $countryCode = 'IT'): array
    {
        $countryCode = \app\components\CountryContext::normalize($countryCode);
        $used = Team::find()->select('name')->column();

        $available = array_values(array_diff(WorldData::TEAM_NAMES, $used));
        shuffle($available);

        $need  = $cpuCount + ($userId !== null ? 1 : 0);
        $names = [];
        
        if (count($available) >= $need) {
            $names = array_slice($available, 0, $need);
        } else {
            // Fill with available
            $names = $available;
            // Generate generic ones for the rest
            $remaining = $need - count($available);
            for ($i = 1; $i <= $remaining; $i++) {
                $names[] = "FC " . WorldData::LAST_NAMES[array_rand(WorldData::LAST_NAMES)] . " " . rand(100, 999);
            }
        }

        $teams = [];
        foreach ($names as $i => $name) {
            $isHuman = ($userId !== null && $i === count($names) - 1);

            $team = new Team();
            $team->name    = $name;
            $team->is_cpu  = $isHuman ? 0 : 1;
            $team->user_id = $isHuman ? $userId : null;
            $team->budget  = $budget;
            $team->country_code = $countryCode;
            [$team->color_left, $team->color_right] = Team::randomUiColors();

            if (!$team->save()) {
                throw new \RuntimeException("TeamSeeder: failed to save team '$name': " . json_encode($team->errors));
            }

            $teams[] = $team;
        }

        return $teams;
    }
}
