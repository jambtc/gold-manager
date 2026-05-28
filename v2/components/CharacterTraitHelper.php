<?php

declare(strict_types=1);

namespace app\components;

final class CharacterTraitHelper
{
    /** @return array<string, array{label:string,match:string,training:string}> */
    public static function definitions(): array
    {
        return [
            'grintoso'    => ['label' => 'Gritty',       'match' => '+10% when losing',              'training' => '+10% defence/tackles/shot'],
            'ambizioso'   => ['label' => 'Ambitious',    'match' => '+5% overall performance',        'training' => '+8% general XP'],
            'razionale'   => ['label' => 'Rational',     'match' => '+5% away & more precise penalty','training' => 'Stable XP'],
            'diligente'   => ['label' => 'Diligent',     'match' => '+condition, tires faster',        'training' => '+15% general XP'],
            'corretto'    => ['label' => 'Fair',         'match' => '-30% card risk',                 'training' => 'Normal XP, lower injury risk'],
            'duttile'     => ['label' => 'Versatile',    'match' => 'lower off-role penalty',         'training' => '+5% skill learning'],
            'inflessibile'=> ['label' => 'Inflexible',   'match' => '+10% in role, penalty off-role', 'training' => '+primary / -20% non-primary'],
            'introverso'  => ['label' => 'Introverted',  'match' => 'better under low effort',        'training' => '-5% XP'],
            'carismatico' => ['label' => 'Charismatic',  'match' => 'aura on nearby teammates (captain)', 'training' => '+5% XP teammates if captain'],
            'popolare'    => ['label' => 'Popular',      'match' => 'team morale boost',              'training' => '+5% XP with high team form'],
            'costante'    => ['label' => 'Consistent',   'match' => 'reduced form variance',          'training' => 'Regular XP'],
            'irrequieto'  => ['label' => 'Restless',     'match' => '+10% at home, nervier away',     'training' => '-10% XP if benched'],
        ];
    }

    public static function normalize(string $character): string
    {
        return strtolower(trim($character));
    }

    public static function display(string $character): string
    {
        $key = self::normalize($character);
        $label = self::definitions()[$key]['label'] ?? ucfirst($key);
        return \Yii::t('app', $label);
    }

    public static function matchTooltip(string $character): string
    {
        $key = self::normalize($character);
        if ($key === '') {
            return '';
        }
        $def = self::definitions()[$key] ?? null;
        if (!$def) {
            return ucfirst($key);
        }
        $label = \Yii::t('app', $def['label']);
        $match = \Yii::t('app', $def['match']);
        $training = \Yii::t('app', $def['training']);
        return "{$label}: {$match} · {$training}";
    }

    public static function trainingModifier(
        string $character,
        string $stat,
        string $position = '',
        bool $isCaptain = false,
        int $teamAverageForm = 50,
        int $benchedStreak = 0
    ): float {
        $character = self::normalize($character);
        $physicalStats = ['skill_df', 'skill_cn', 'skill_tr'];
        $primaryByPosition = [
            'GK' => 'skill_po',
            'DF' => 'skill_df',
            'MF' => 'skill_pa',
            'FW' => 'skill_tr',
        ];
        $primaryStat = $primaryByPosition[strtoupper($position)] ?? '';

        return match ($character) {
            'grintoso' => in_array($stat, $physicalStats, true) ? 1.10 : 1.00,
            'ambizioso' => 1.08,
            'diligente' => 1.15,
            'duttile' => 1.05,
            'inflessibile' => ($primaryStat !== '' && $stat === $primaryStat) ? 1.05 : 0.80,
            'introverso' => 0.95,
            'carismatico' => $isCaptain ? 1.05 : 1.00,
            'popolare' => $teamAverageForm >= 70 ? 1.05 : 1.00,
            'irrequieto' => $benchedStreak >= 2 ? 0.90 : 1.00,
            default => 1.00,
        };
    }

    public static function disciplineRiskModifier(string $character): float
    {
        return match (self::normalize($character)) {
            'corretto' => 0.70,
            'grintoso', 'irrequieto' => 1.20,
            default => 1.00,
        };
    }
}

