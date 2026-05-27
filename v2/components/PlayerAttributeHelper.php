<?php

declare(strict_types=1);

namespace app\components;

use app\models\Player;
use app\models\PlayerTalent;

final class PlayerAttributeHelper
{
    /** @var array<string,array<int,string>> */
    private const TALENT_ALLOC_MAP = [
        'creativita'     => ['alloc_rg', 'alloc_pa'],
        'resistenza'     => ['alloc_cond', 'alloc_forma'],
        'dribbling'      => ['alloc_tc', 'alloc_cr'],
        'velocita'       => ['alloc_forma', 'alloc_cond'],
        'visione'        => ['alloc_pa', 'alloc_rg'],
        'leadership'     => ['alloc_rg', 'alloc_forma'],
        'marcatura'      => ['alloc_df', 'alloc_cn'],
        'riflessi'       => ['alloc_po'],
        'finalizzazione' => ['alloc_tr', 'alloc_tc'],
        'disciplina'     => ['alloc_cond', 'alloc_df'],
        'tenacia'        => ['alloc_cn', 'alloc_cond'],
        'freddezza'      => ['alloc_tc', 'alloc_cond'],
        'calci_piazzati' => ['alloc_calci_piazzati', 'alloc_tc'],
    ];

    /** @return array<int,array{key:string,label:string,value:int}> */
    public static function technicalAttributes(Player $player): array
    {
        return [
            ['key' => 'skill_po', 'label' => 'Parate',    'value' => (int) $player->skill_po],
            ['key' => 'skill_df', 'label' => 'Difesa',    'value' => (int) $player->skill_df],
            ['key' => 'skill_cn', 'label' => 'Contrasti', 'value' => (int) $player->skill_cn],
            ['key' => 'skill_pa', 'label' => 'Passaggi',  'value' => (int) $player->skill_pa],
            ['key' => 'skill_rg', 'label' => 'Regia',     'value' => (int) $player->skill_rg],
            ['key' => 'skill_cr', 'label' => 'Cross',     'value' => (int) $player->skill_cr],
            ['key' => 'skill_tc', 'label' => 'Tecnica',   'value' => (int) $player->skill_tc],
            ['key' => 'skill_tr', 'label' => 'Tiro',      'value' => (int) $player->skill_tr],
        ];
    }

    /** @return array<int,array{key:string,label:string,value:int,is_percent:bool}> */
    public static function formAttributes(Player $player): array
    {
        return [
            ['key' => 'form',       'label' => 'Forma',      'value' => (int) $player->form,      'is_percent' => true],
            ['key' => 'freshness',  'label' => 'Freschezza', 'value' => (int) $player->freshness, 'is_percent' => true],
            ['key' => 'condition',  'label' => 'Condizione', 'value' => (int) $player->condition, 'is_percent' => true],
            ['key' => 'experience', 'label' => 'Esperienza', 'value' => (int) $player->experience,'is_percent' => false],
        ];
    }

    /**
     * Talenti indipendenti dal carattere.
     * Max 2 per giocatore, livello 1..3.
     *
     * @return array<int,array{code:string,label:string,level:int,score:int}>
     */
    public static function talents(Player $player, int $max = 2): array
    {
        $max = max(1, min(2, $max));
        if ((int) $player->id <= 0) {
            return [];
        }

        $rows = PlayerTalent::find()
            ->where(['player_id' => (int) $player->id])
            ->orderBy(['level' => SORT_DESC, 'code' => SORT_ASC])
            ->all();

        $out = [];
        foreach ($rows as $talent) {
            $score = (int) round(((int) $talent->level / 3) * 100);
            $out[] = [
                'code'  => (string) $talent->code,
                'label' => self::talentDefinitions()[(string) $talent->code]['label'] ?? ucfirst((string) $talent->code),
                'level' => max(1, min(3, (int) $talent->level)),
                'score' => $score,
            ];
        }

        return array_slice($out, 0, $max);
    }

    /** Assign initial random talents (0..2) only if player has none yet. */
    public static function ensureInitialTalents(Player $player): int
    {
        if ((int) $player->id <= 0) {
            return 0;
        }

        $existing = (int) PlayerTalent::find()->where(['player_id' => (int) $player->id])->count();
        if ($existing > 0) {
            return 0;
        }

        $roll = random_int(1, 100);
        $count = $roll <= 35 ? 0 : ($roll <= 82 ? 1 : 2);
        if ($count === 0) {
            return 0;
        }

        $codes = array_keys(self::talentDefinitions());
        shuffle($codes);
        $picked = array_slice($codes, 0, $count);
        $now = time();
        $created = 0;

        foreach ($picked as $code) {
            $t = new PlayerTalent();
            $t->player_id = (int) $player->id;
            $t->code = (string) $code;
            $t->level = 1;
            $t->progress_weeks = 0;
            $t->created_at = $now;
            $t->updated_at = $now;
            if ($t->save()) {
                $created++;
            }
        }

        return $created;
    }

    /**
     * Advance one training week for talents.
     * Level up requires full season.
     * Growth only when mapped training allocation is strictly > threshold.
     *
     * @param array<string,mixed> $trainingRow
     */
    public static function progressTalentsWeekly(Player $player, array $trainingRow, int $threshold = 80): int
    {
        if ((int) $player->id <= 0) {
            return 0;
        }

        $weeksPerLevel = self::weeksPerTalentLevel();
        $leveled = 0;
        $talents = PlayerTalent::find()->where(['player_id' => (int) $player->id])->all();
        foreach ($talents as $talent) {
            $level = max(1, min(3, (int) $talent->level));
            if ($level >= 3) {
                continue;
            }
            $trainedAlloc = self::maxTrainingAllocForTalent((string) $talent->code, $trainingRow);
            if ($trainedAlloc <= $threshold) {
                continue;
            }

            $chance = self::weeklyTalentProgressChance($trainedAlloc, $threshold);
            if (random_int(1, 100) > $chance) {
                continue;
            }

            $progress = max(0, (int) $talent->progress_weeks) + 1;
            if ($progress >= $weeksPerLevel) {
                $talent->level = min(3, $level + 1);
                $talent->progress_weeks = 0;
                $leveled++;
            } else {
                $talent->progress_weeks = $progress;
            }
            $talent->updated_at = time();
            $talent->save(false, ['level', 'progress_weeks', 'updated_at']);
        }

        return $leveled;
    }

    /**
     * Advance talents on a daily cadence.
     * Keeps "season-long growth" behavior by scaling weekly chance.
     *
     * @param array<string,mixed> $trainingRow
     */
    public static function progressTalentsDaily(Player $player, array $trainingRow, int $threshold = 80): int
    {
        if ((int) $player->id <= 0) {
            return 0;
        }

        $dailyScale = self::talentDailyScale();
        $weeksPerLevel = self::weeksPerTalentLevel();
        $leveled = 0;
        $talents = PlayerTalent::find()->where(['player_id' => (int) $player->id])->all();
        foreach ($talents as $talent) {
            $level = max(1, min(3, (int) $talent->level));
            if ($level >= 3) {
                continue;
            }
            $trainedAlloc = self::maxTrainingAllocForTalent((string) $talent->code, $trainingRow);
            if ($trainedAlloc <= $threshold) {
                continue;
            }

            $weeklyChance = self::weeklyTalentProgressChance($trainedAlloc, $threshold);
            $dailyChance = max(1, min(100, (int) round($weeklyChance * $dailyScale)));
            if (random_int(1, 100) > $dailyChance) {
                continue;
            }

            $progress = max(0, (int) $talent->progress_weeks) + 1;
            if ($progress >= $weeksPerLevel) {
                $talent->level = min(3, $level + 1);
                $talent->progress_weeks = 0;
                $leveled++;
            } else {
                $talent->progress_weeks = $progress;
            }
            $talent->updated_at = time();
            $talent->save(false, ['level', 'progress_weeks', 'updated_at']);
        }

        return $leveled;
    }

    /** @param array<string,mixed> $trainingRow */
    private static function maxTrainingAllocForTalent(string $code, array $trainingRow): int
    {
        $keys = self::TALENT_ALLOC_MAP[$code] ?? [];
        $max = 0;
        foreach ($keys as $key) {
            $val = (int) ($trainingRow[$key] ?? 0);
            $max = max($max, $val);
        }
        return $max;
    }

    private static function weeklyTalentProgressChance(int $allocValue, int $threshold): int
    {
        $baseRaw = trim((string) getenv('GM_TALENT_GROWTH_CHANCE_BASE'));
        $bonusRaw = trim((string) getenv('GM_TALENT_GROWTH_CHANCE_MAX_BONUS'));
        $base = ctype_digit($baseRaw) ? (int) $baseRaw : 35;
        $maxBonus = ctype_digit($bonusRaw) ? (int) $bonusRaw : 20;

        $base = max(1, min(100, $base));
        $maxBonus = max(0, min(80, $maxBonus));

        $range = max(1, 100 - $threshold);
        $surplus = max(0, min(100, $allocValue) - $threshold);
        $bonus = (int) round(($surplus / $range) * $maxBonus);

        return max(1, min(100, $base + $bonus));
    }

    public static function weeksPerTalentLevel(): int
    {
        $raw = trim((string) getenv('GM_TALENT_WEEKS_PER_LEVEL'));
        if ($raw !== '' && ctype_digit($raw)) {
            $v = (int) $raw;
            if ($v >= 4 && $v <= 52) {
                return $v;
            }
        }
        return SponsorService::WEEKS_PER_SEASON;
    }

    private static function talentDailyScale(): float
    {
        $raw = trim((string) getenv('GM_TALENT_DAILY_SCALE'));
        if ($raw !== '' && is_numeric($raw)) {
            $v = (float) $raw;
            if ($v > 0.0 && $v <= 2.0) {
                return $v;
            }
        }
        return 1 / 7;
    }

    /** @return array<string,array{label:string}> */
    public static function talentDefinitions(): array
    {
        return [
            'creativita'     => ['label' => 'Creativita'],
            'resistenza'     => ['label' => 'Resistenza'],
            'dribbling'      => ['label' => 'Dribbling'],
            'velocita'       => ['label' => 'Velocita'],
            'visione'        => ['label' => 'Visione'],
            'leadership'     => ['label' => 'Leadership'],
            'marcatura'      => ['label' => 'Marcatura'],
            'riflessi'       => ['label' => 'Riflessi'],
            'finalizzazione' => ['label' => 'Finalizzazione'],
            'disciplina'     => ['label' => 'Disciplina'],
            'tenacia'        => ['label' => 'Tenacia'],
            'freddezza'      => ['label' => 'Freddezza'],
            'calci_piazzati' => ['label' => 'Calci piazzati'],
        ];
    }

    /** @return array<string,array<int,string>> */
    public static function talentAllocMap(): array
    {
        return self::TALENT_ALLOC_MAP;
    }
}
