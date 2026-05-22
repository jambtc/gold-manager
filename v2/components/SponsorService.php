<?php

declare(strict_types=1);

namespace app\components;

use app\models\Competition;
use app\models\NewsItem;
use app\models\Sponsor;
use app\models\Standing;
use app\models\Team;
use app\models\TeamSponsor;
use Yii;

class SponsorService
{
    public const WEEKS_PER_SEASON = 26;

    public static function contractDurationSeconds(int $durationSeasons): int
    {
        $seasons = max(1, $durationSeasons);
        return 7 * 24 * 3600 * self::WEEKS_PER_SEASON * $seasons;
    }

    public static function expireContracts(bool $withNews = true): int
    {
        $now = time();
        $contracts = TeamSponsor::find()
            ->alias('ts')
            ->with(['team', 'sponsor'])
            ->where(['ts.status' => TeamSponsor::STATUS_ACTIVE])
            ->andWhere(['<=', 'ts.ends_at', $now])
            ->all();

        $expired = 0;
        foreach ($contracts as $contract) {
            $contract->status = TeamSponsor::STATUS_EXPIRED;
            if (!$contract->save(false, ['status'])) {
                continue;
            }
            $expired++;

            if ($withNews && $contract->team?->user_id && $contract->sponsor) {
                NewsService::create(
                    (int) $contract->team->user_id,
                    NewsItem::CAT_FINANCE,
                    '!',
                    'Contratto sponsor scaduto',
                    sprintf('Il contratto con %s è terminato. Valuta una nuova offerta sponsor.', $contract->sponsor->name),
                    Yii::$app->urlManager->createUrl(['/sponsor/index']),
                    1
                );
            }
        }

        return $expired;
    }

    public static function getActiveContractForTeam(int $teamId): ?TeamSponsor
    {
        return TeamSponsor::find()
            ->with('sponsor')
            ->where(['team_id' => $teamId, 'status' => TeamSponsor::STATUS_ACTIVE])
            ->andWhere(['>', 'ends_at', time()])
            ->orderBy(['ends_at' => SORT_DESC, 'id' => SORT_DESC])
            ->one();
    }

    /**
     * Team prestige score 0..100 based on tier, ranking and budget.
     */
    public static function computeTeamPrestige(Team $team): int
    {
        $standing = Standing::find()
            ->with('competition')
            ->where(['team_id' => $team->id])
            ->orderBy(['updated_at' => SORT_DESC, 'id' => SORT_DESC])
            ->one();

        $tierScore = 30;
        $rankScore = 10;
        if ($standing && $standing->competition) {
            $tierScore = match ((int) $standing->competition->tier) {
                Competition::TIER_A => 70,
                Competition::TIER_B => 50,
                default => 30,
            };

            $table = Standing::find()
                ->where(['competition_id' => $standing->competition_id])
                ->orderBy([
                    'points' => SORT_DESC,
                    new \yii\db\Expression('(goals_for - goals_against) DESC'),
                    'goals_for' => SORT_DESC,
                    'team_id' => SORT_ASC,
                ])
                ->all();

            $position = 1;
            $count = count($table);
            foreach ($table as $idx => $row) {
                if ((int) $row->team_id === (int) $team->id) {
                    $position = $idx + 1;
                    break;
                }
            }

            if ($count > 1) {
                $ratio = 1 - (($position - 1) / ($count - 1)); // 1=first, 0=last
                $rankScore = (int) round($ratio * 20);
            } else {
                $rankScore = 20;
            }
        }

        $budgetScore = (int) min(10, floor(max(0, (int) $team->budget) / 1_000_000));

        return max(0, min(100, $tierScore + $rankScore + $budgetScore));
    }

    public static function findAvailableSponsorsForTeam(Team $team): array
    {
        $prestige = self::computeTeamPrestige($team);

        return Sponsor::find()
            ->where(['<=', 'prestige_required', $prestige])
            ->orderBy(['base_payment' => SORT_DESC, 'id' => SORT_ASC])
            ->all();
    }

    public static function weeklyInstallment(?TeamSponsor $contract): int
    {
        if (!$contract || !$contract->sponsor) {
            return 0;
        }
        return (int) round(((int) $contract->sponsor->base_payment) / self::WEEKS_PER_SEASON);
    }
}
