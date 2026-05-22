<?php

declare(strict_types=1);

namespace app\components\seeders;

use app\models\Stadium;
use app\models\Team;

class StadiumSeeder
{
    /**
     * Creates one Stadium per team.
     *
     * @param Team[] $teams
     */
    public function seed(array $teams): void
    {
        foreach ($teams as $team) {
            $rawCapacity  = random_int(10, 50) * 500; // 5000–25000, step 500
            $upgradeBase  = 500_000;

            $stadium = new Stadium();
            $stadium->team_id       = $team->id;
            $stadium->name          = 'Stadio di ' . $team->name;
            $stadium->capacity      = $rawCapacity;
            $stadium->level         = 1;
            $stadium->upgrade_cost  = $upgradeBase;
            $stadium->ticket_price  = 15;
            $stadium->season_revenue = 0;

            if (!$stadium->save()) {
                throw new \RuntimeException("StadiumSeeder: failed for team #{$team->id}: " . json_encode($stadium->errors));
            }
        }
    }
}
