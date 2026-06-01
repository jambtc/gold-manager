<?php

declare(strict_types=1);

namespace app\components;

use app\models\Competition;
use app\models\Standing;
use app\models\Team;
use app\models\User;
use Yii;

class TeamAssigner
{
    /**
     * Assigns a free Serie C team to the given user.
     *
     * Flow:
     * 1. If the world is empty → seed A + B + C (all CPU).
     * 2. Find a free CPU team in any existing Serie C group.
     * 3. If no free slot → create a new Serie C girone (expansion).
     * 4. Claim the team: is_cpu=0, user_id=$userId.
     */
    public function assign(int $userId): Team
    {
        $user = User::findOne($userId);
        if ($user === null) {
            throw new \RuntimeException("TeamAssigner: user #{$userId} not found.");
        }

        $countryCode = CountryContext::normalize((string) ($user->country_code ?? CountryContext::DEFAULT_COUNTRY));

        if (!$this->hasTeamsForCountry($countryCode)) {
            (new WorldSeeder())->run(null, $countryCode);
        }

        $team = $this->findFreeSerieC($countryCode);

        if ($team === null) {
            $team = $this->expandSerieC($countryCode);
        }

        $team->is_cpu  = 0;
        $team->user_id = $userId;
        $team->ensureUiColors(false);

        if (!$team->save()) {
            throw new \RuntimeException('TeamAssigner: failed to assign team: ' . json_encode($team->errors));
        }

        return $team;
    }

    private function hasTeamsForCountry(string $countryCode): bool
    {
        return Team::find()->where(['country_code' => $countryCode])->exists();
    }

    /**
     * Returns the first CPU team in any Serie C group that has no user assigned.
     */
    private function findFreeSerieC(string $countryCode): ?Team
    {
        // Get all Serie C competition IDs
        $competitionIds = Competition::find()
            ->select('id')
            ->where([
                'tier' => Competition::TIER_C,
                'country_code' => $countryCode,
            ])
            ->column();

        if (empty($competitionIds)) {
            return null;
        }

        // Find a CPU team (user_id IS NULL) that has a standing in any Serie C group
        return Team::find()
            ->innerJoin(
                Standing::tableName() . ' s',
                's.team_id = ' . Team::tableName() . '.id'
            )
            ->where([
                Team::tableName() . '.is_cpu'   => 1,
                Team::tableName() . '.user_id'  => null,
                Team::tableName() . '.country_code' => $countryCode,
                's.competition_id'              => $competitionIds,
            ])
            ->one();
    }

    /**
     * Creates a new Serie C girone and returns one of its CPU teams.
     */
    private function expandSerieC(string $countryCode): Team
    {
        $nextGroup = (int) Competition::find()
            ->where(['tier' => Competition::TIER_C, 'country_code' => $countryCode])
            ->max('group_number') + 1;

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $competition = (new WorldSeeder())->seedLeague(Competition::TIER_C, $nextGroup, null, 16, $countryCode);
            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw new \RuntimeException('TeamAssigner: expansion failed: ' . $e->getMessage(), 0, $e);
        }

        $team = Team::find()
            ->innerJoin(
                Standing::tableName() . ' s',
                's.team_id = ' . Team::tableName() . '.id'
            )
            ->where([
                Team::tableName() . '.is_cpu'  => 1,
                Team::tableName() . '.user_id' => null,
                Team::tableName() . '.country_code' => $countryCode,
                's.competition_id'             => $competition->id,
            ])
            ->one();

        if ($team === null) {
            throw new \RuntimeException('TeamAssigner: no team found after expansion.');
        }

        return $team;
    }
}
