<?php

declare(strict_types=1);

namespace app\components\seeders;

use app\components\WorldData;
use app\models\Contract;
use app\models\Player;

class PlayerSeeder
{
    /**
     * Builds a Player and its Contract for a team, without saving.
     * Returns ['player' => Player, 'contract' => Contract].
     */
    public function buildPlayer(int $teamId, string $position, int $number, string $name): array
    {
        $attrs = $this->buildAttributes($position, $number, $name);

        $player = new Player();
        $player->team_id = $teamId;
        $player->setAttributes($attrs, false);

        $salary = $this->salaryFromSkill($attrs['general_skill']);
        $duration = random_int(1, 4);

        $contract = new Contract();
        $contract->team_id     = $teamId;
        $contract->salary      = $salary;
        $contract->season_start = 1;
        $contract->season_end   = $duration;
        $contract->status       = Contract::STATUS_ACTIVE;

        return ['player' => $player, 'contract' => $contract];
    }

    /**
     * Pure function: builds attribute array for a player of given position.
     * No DB access. Useful for unit testing.
     */
    public function buildAttributes(string $position, int $number, string $name): array
    {
        $profile = WorldData::statProfile($position);
        [$ageMin, $ageMax] = WorldData::ageRange($position);
        $age = random_int($ageMin, $ageMax);

        $ageMod = $age >= 30 ? 5 : ($age <= 22 ? -5 : 0);

        $stats = [];
        foreach ($profile['primary'] as $stat) {
            $stats[$stat] = $this->clamp(random_int(55, 80) + $ageMod);
        }
        foreach ($profile['secondary'] as $stat) {
            $stats[$stat] = $this->clamp(random_int(35, 60));
        }
        foreach ($profile['low'] as $stat) {
            $stats[$stat] = $this->clamp(random_int(15, 40));
        }

        $allStats = ['skill_po', 'skill_df', 'skill_cn', 'skill_pa', 'skill_rg', 'skill_cr', 'skill_tc', 'skill_tr'];
        $generalSkill = (int) round(array_sum(array_map(fn($k) => $stats[$k], $allStats)) / 8);

        $foot = $this->randomFoot();
        $experience = $this->clamp(random_int(5, 40) + (int) round(($age - 19) * 1.5), 1, 99);
        $character = WorldData::CHARACTERS[array_rand(WorldData::CHARACTERS)];

        return array_merge($stats, [
            'name'          => $name,
            'number'        => $number,
            'position'      => $position,
            'age'           => $age,
            'foot'          => $foot,
            'experience'    => $experience,
            'general_skill' => $generalSkill,
            'form'          => 100,
            'freshness'     => 100,
            'condition'     => 100,
            'character'     => $character,
        ]);
    }

    private function randomFoot(): string
    {
        $roll = random_int(1, 100);
        if ($roll <= 80) return 'R';
        if ($roll <= 95) return 'L';
        return 'LR';
    }

    private function salaryFromSkill(int $generalSkill): int
    {
        return $generalSkill * 80;
    }

    private function clamp(int $value, int $min = 1, int $max = 99): int
    {
        return max($min, min($max, $value));
    }
}
