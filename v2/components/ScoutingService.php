<?php

declare(strict_types=1);

namespace app\components;

use app\models\NewsItem;
use app\models\Player;
use app\models\ScoutingReport;
use app\models\Staff;
use app\models\Team;
use Yii;

class ScoutingService
{
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
                    '🔍',
                    "Rapporto scout: {$player->name}",
                    "Skill stimata {$data['general_skill']} — {$data['position']}, {$player->age} anni",
                    Yii::$app->urlManager->createUrl(['/scouting/report', 'id' => $report->id]),
                    1
                );
            }
            $count++;
        }

        return $count;
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
