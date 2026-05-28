<?php

declare(strict_types=1);

namespace app\components;

use app\models\NewsItem;
use app\models\Player;
use app\models\PlayerPool;
use app\models\ScoutingReport;
use app\models\ScoutingAlert;
use app\models\ScoutingNeed;
use app\models\Staff;
use app\models\Team;
use Yii;

class ScoutingService
{
    /**
     * @return string[]
     */
    public static function getNeedPositions(int $teamId): array
    {
        $rows = ScoutingNeed::find()
            ->select('position')
            ->where(['team_id' => $teamId])
            ->orderBy(['id' => SORT_ASC])
            ->column();
        return array_values(array_unique(array_map(static fn($p): string => strtoupper((string) $p), $rows)));
    }

    /**
     * Saves max 3 distinct target positions.
     *
     * @param string[] $positions
     */
    public static function saveNeedPositions(int $teamId, array $positions): void
    {
        $allowed = ['GK', 'DF', 'MF', 'FW'];
        $clean = [];
        foreach ($positions as $position) {
            $p = strtoupper(trim((string) $position));
            if (!in_array($p, $allowed, true)) {
                continue;
            }
            if (in_array($p, $clean, true)) {
                continue;
            }
            $clean[] = $p;
            if (count($clean) >= 3) {
                break;
            }
        }

        ScoutingNeed::deleteAll(['team_id' => $teamId]);
        $now = time();
        foreach ($clean as $position) {
            $need = new ScoutingNeed();
            $need->team_id = $teamId;
            $need->position = $position;
            $need->created_at = $now;
            $need->save(false);
        }
    }

    public static function canScout(int $teamId): bool
    {
        return Staff::find()
            ->where(['team_id' => $teamId, 'role' => Staff::ROLE_SCOUT])
            ->exists();
    }

    public static function getScoutEfficiency(int $teamId): int
    {
        $scout = Staff::findOne(['team_id' => $teamId, 'role' => Staff::ROLE_SCOUT]);
        return $scout ? (int) $scout->efficiency : 0;
    }

    public static function isPending(int $teamId, int $playerId): bool
    {
        return ScoutingReport::find()
            ->where(['team_id' => $teamId, 'player_id' => $playerId, 'status' => 'pending'])
            ->exists();
    }

    public static function queueReport(int $teamId, int $playerId): ScoutingReport
    {
        $eff  = self::getScoutEfficiency($teamId);
        $days = (int) ceil(7 - $eff / 20); // 5→1 days as eff goes 0→100

        $report = new ScoutingReport();
        $report->team_id   = $teamId;
        $report->player_id = $playerId;
        $report->status    = 'pending';
        $report->scout_eff = $eff;
        $report->ready_at  = time() + $days * 86400;
        $report->save(false);

        return $report;
    }

    public static function processReadyReports(): int
    {
        $reports = ScoutingReport::find()
            ->where(['status' => 'pending'])
            ->andWhere(['<=', 'ready_at', time()])
            ->all();

        $count = 0;
        foreach ($reports as $report) {
            $player = Player::findOne($report->player_id);
            if (!$player) {
                $report->status = 'dismissed';
                $report->save(false);
                continue;
            }

            $noise = (int) round(30 - $report->scout_eff * 0.25); // 5–30
            $data  = self::obfuscate($player, $noise);

            $report->report_text     = json_encode($data, JSON_UNESCAPED_UNICODE);
            $report->potential_score = $data['potential_score'];
            $report->revealed_talent = $data['position'];
            $report->status          = 'ready';
            $report->save(false);

            $team = Team::findOne($report->team_id);
            if ($team?->user_id) {
                NewsService::create(
                    (int) $team->user_id,
                    NewsItem::CAT_TRANSFER,
                    'S',
                    "Rapporto scout: {$player->name}",
                    "Skill stimata {$data['general_skill']} — {$data['position']}, {$player->age} anni",
                    self::safeUrl('/scouting/report', ['id' => (int) $report->id]),
                    1
                );
            }
            $count++;
        }

        return $count;
    }

    public static function notifyTeamsForNewMarketPlayer(Player $player, PlayerPool $marketEntry): int
    {
        $position = strtoupper((string) $player->position);
        if (!in_array($position, ['GK', 'DF', 'MF', 'FW'], true)) {
            return 0;
        }

        $notified = 0;
        $humanTeams = Team::find()->where(['is_cpu' => 0])->andWhere(['not', ['user_id' => null]])->all();
        foreach ($humanTeams as $team) {
            $teamId = (int) $team->id;
            $userId = (int) ($team->user_id ?? 0);
            if ($userId <= 0) {
                continue;
            }
            if (!self::canScout($teamId)) {
                continue;
            }

            $needs = self::getNeedPositions($teamId);
            if (empty($needs) || !in_array($position, $needs, true)) {
                continue;
            }

            if (ScoutingAlert::find()->where(['team_id' => $teamId, 'player_id' => (int) $player->id])->exists()) {
                continue;
            }

            $scoutEff = self::getScoutEfficiency($teamId);
            $minInterestingSkill = max(35, (int) round(68 - ($scoutEff * 0.30)));
            if ((int) $player->general_skill < $minInterestingSkill) {
                continue;
            }

            $hitChance = max(20, min(95, (int) round(25 + ($scoutEff * 0.70))));
            if (random_int(1, 100) > $hitChance) {
                continue;
            }

            $alert = new ScoutingAlert();
            $alert->team_id = $teamId;
            $alert->player_id = (int) $player->id;
            $alert->created_at = time();
            $alert->save(false);

            NewsService::create(
                $userId,
                NewsItem::CAT_TRANSFER,
                'S',
                "Scout segnala: {$player->name}",
                sprintf(
                    'Profilo %s interessante per le tue richieste scout. Skill %d · Età %d · Base asta €%s.',
                    $position,
                    (int) $player->general_skill,
                    (int) $player->age,
                    number_format((int) $marketEntry->asking_fee, 0, ',', '.')
                ),
                self::safeUrl('/transfer/market', ['tab' => 'listed', 'pos' => $position]),
                1
            );
            $notified++;
        }

        return $notified;
    }

    // Backward compatibility alias.
    public static function notifyTeamsForNewPoolPlayer(Player $player, PlayerPool $marketEntry): int
    {
        return self::notifyTeamsForNewMarketPlayer($player, $marketEntry);
    }

    /**
     * Builds route URL both in web and console context.
     *
     * @param array<string, scalar> $params
     */
    private static function safeUrl(string $route, array $params = []): string
    {
        if (Yii::$app instanceof \yii\console\Application) {
            $q = http_build_query($params);
            return $q !== '' ? ($route . '?' . $q) : $route;
        }
        return Yii::$app->urlManager->createUrl(array_merge([$route], $params));
    }

    private static function obfuscate(Player $player, int $noise): array
    {
        $fuzz  = fn(int $v): int => max(1, min(99, $v + mt_rand(-$noise, $noise)));
        $exact = $noise <= 5;
        // Round to nearest 5 for medium accuracy, 10 for low
        $round = $noise <= 10 ? 5 : 10;
        $fmt   = fn(int $v): string => $exact
            ? (string) $v
            : '~' . (string) ((int) round($fuzz($v) / $round) * $round);

        return [
            'name'            => $player->name,
            'age'             => $player->age,
            'position'        => $player->position,
            'foot'            => $player->foot,
            'general_skill'   => $fuzz($player->general_skill),
            'natural_overall' => $fuzz($player->getNaturalOverall()),
            'skill_po'        => $fmt($player->skill_po),
            'skill_df'        => $fmt($player->skill_df),
            'skill_cn'        => $fmt($player->skill_cn),
            'skill_pa'        => $fmt($player->skill_pa),
            'skill_rg'        => $fmt($player->skill_rg),
            'skill_cr'        => $fmt($player->skill_cr),
            'skill_tc'        => $fmt($player->skill_tc),
            'skill_tr'        => $fmt($player->skill_tr),
            'potential_score' => $fuzz($player->general_skill),
            'note'            => self::generateNote($player, $noise),
        ];
    }

    private static function generateNote(Player $player, int $noise): string
    {
        $adj = match (true) {
            $player->general_skill >= 75 => 'eccellente',
            $player->general_skill >= 60 => 'buono',
            $player->general_skill >= 45 => 'discreto',
            default                       => 'modesto',
        };

        if ($noise > 20) {
            return "Giocatore {$adj} per la categoria. Difficile valutare con precisione.";
        }

        $charNote = $player->character ? ' ' . ucfirst($player->character) . '.' : '';
        return "Giocatore {$adj}.{$charNote} Valutazione attendibile.";
    }
}
