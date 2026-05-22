<?php

declare(strict_types=1);

namespace app\components;

final class CharacterTraitHelper
{
    /** @return array<string, array{label:string,match:string,training:string}> */
    public static function definitions(): array
    {
        return [
            'grintoso' => [
                'label' => 'Grintoso',
                'match' => '+10% quando in svantaggio',
                'training' => '+10% su difesa/contrasti/tiro',
            ],
            'ambizioso' => [
                'label' => 'Ambizioso',
                'match' => '+5% rendimento generale',
                'training' => '+8% XP generale',
            ],
            'razionale' => [
                'label' => 'Razionale',
                'match' => '+5% in trasferta e rigorista più preciso',
                'training' => 'XP stabile',
            ],
            'diligente' => [
                'label' => 'Diligente',
                'match' => '+condizione, ma si affatica di più',
                'training' => '+15% XP generale',
            ],
            'corretto' => [
                'label' => 'Corretto',
                'match' => '-30% rischio cartellino',
                'training' => 'XP normale, rischio infortuni ridotto',
            ],
            'duttile' => [
                'label' => 'Duttile',
                'match' => 'meno penalità fuori ruolo',
                'training' => '+5% apprendimento skill',
            ],
            'inflessibile' => [
                'label' => 'Inflessibile',
                'match' => '+10% in ruolo, penalità fuori ruolo',
                'training' => '+primaria / -20% non primaria',
            ],
            'introverso' => [
                'label' => 'Introverso',
                'match' => 'rende meglio con sforzo basso',
                'training' => '-5% XP',
            ],
            'carismatico' => [
                'label' => 'Carismatico',
                'match' => 'aura su compagni vicini (capitano)',
                'training' => '+5% XP compagni se capitano',
            ],
            'popolare' => [
                'label' => 'Popolare',
                'match' => 'boost morale squadra',
                'training' => '+5% XP con forma squadra alta',
            ],
            'costante' => [
                'label' => 'Costante',
                'match' => 'varianza forma ridotta',
                'training' => 'XP regolare',
            ],
            'irrequieto' => [
                'label' => 'Irrequieto',
                'match' => '+10% in casa, più nervoso fuori',
                'training' => '-10% XP se resta fuori',
            ],
        ];
    }

    public static function normalize(string $character): string
    {
        return strtolower(trim($character));
    }

    public static function display(string $character): string
    {
        $key = self::normalize($character);
        return self::definitions()[$key]['label'] ?? ucfirst($key);
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
        return "{$def['label']}: {$def['match']} · {$def['training']}";
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

