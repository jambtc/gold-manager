<?php

declare(strict_types=1);

namespace app\components;

use app\models\Formation;
use app\models\FormationSlot;
use app\models\Player;
use app\models\Team;
use Yii;

class FormationAutoHelper
{
    /** @var array<string, array<int, array{zone:int, role:string}>> */
    private const MODULE_TEMPLATE = [
        '4-4-2' => [
            ['zone' => 60, 'role' => 'GK'],
            ['zone' => 51, 'role' => 'DF'], ['zone' => 52, 'role' => 'DF'], ['zone' => 54, 'role' => 'DF'], ['zone' => 55, 'role' => 'DF'],
            ['zone' => 37, 'role' => 'MF'], ['zone' => 38, 'role' => 'MF'], ['zone' => 40, 'role' => 'MF'], ['zone' => 41, 'role' => 'MF'],
            ['zone' => 17, 'role' => 'FW'], ['zone' => 19, 'role' => 'FW'],
        ],
        '4-3-3' => [
            ['zone' => 60, 'role' => 'GK'],
            ['zone' => 51, 'role' => 'DF'], ['zone' => 52, 'role' => 'DF'], ['zone' => 54, 'role' => 'DF'], ['zone' => 55, 'role' => 'DF'],
            ['zone' => 37, 'role' => 'MF'], ['zone' => 39, 'role' => 'MF'], ['zone' => 41, 'role' => 'MF'],
            ['zone' => 16, 'role' => 'FW'], ['zone' => 18, 'role' => 'FW'], ['zone' => 20, 'role' => 'FW'],
        ],
        '3-5-2' => [
            ['zone' => 60, 'role' => 'GK'],
            ['zone' => 51, 'role' => 'DF'], ['zone' => 53, 'role' => 'DF'], ['zone' => 55, 'role' => 'DF'],
            ['zone' => 30, 'role' => 'MF'], ['zone' => 37, 'role' => 'MF'], ['zone' => 39, 'role' => 'MF'], ['zone' => 41, 'role' => 'MF'], ['zone' => 48, 'role' => 'MF'],
            ['zone' => 17, 'role' => 'FW'], ['zone' => 19, 'role' => 'FW'],
        ],
        '5-3-2' => [
            ['zone' => 60, 'role' => 'GK'],
            ['zone' => 50, 'role' => 'DF'], ['zone' => 51, 'role' => 'DF'], ['zone' => 53, 'role' => 'DF'], ['zone' => 55, 'role' => 'DF'], ['zone' => 56, 'role' => 'DF'],
            ['zone' => 37, 'role' => 'MF'], ['zone' => 39, 'role' => 'MF'], ['zone' => 41, 'role' => 'MF'],
            ['zone' => 17, 'role' => 'FW'], ['zone' => 19, 'role' => 'FW'],
        ],
        '4-2-3-1' => [
            ['zone' => 60, 'role' => 'GK'],
            ['zone' => 51, 'role' => 'DF'], ['zone' => 52, 'role' => 'DF'], ['zone' => 54, 'role' => 'DF'], ['zone' => 55, 'role' => 'DF'],
            ['zone' => 38, 'role' => 'MF'], ['zone' => 40, 'role' => 'MF'],
            ['zone' => 23, 'role' => 'MF'], ['zone' => 25, 'role' => 'MF'], ['zone' => 27, 'role' => 'MF'],
            ['zone' => 18, 'role' => 'FW'],
        ],
    ];

    /** @return string[] */
    public static function moduleOptions(): array
    {
        return array_keys(self::MODULE_TEMPLATE);
    }

    /** @return string[] */
    public static function tacticOptions(): array
    {
        return ['balanced', 'ultra_defensive', 'all_out_attack'];
    }

    public static function normalizeModule(string $module): string
    {
        $module = trim($module);
        return isset(self::MODULE_TEMPLATE[$module]) ? $module : '4-4-2';
    }

    public static function normalizeTactic(string $tactic): string
    {
        $tactic = trim($tactic);
        return in_array($tactic, self::tacticOptions(), true) ? $tactic : 'balanced';
    }

    public function normalizeMarking(string $marking): string
    {
        $marking = trim($marking);
        return in_array($marking, ['zone', 'man'], true) ? $marking : 'zone';
    }

    public function normalizeTrainedTactic(string $trainedTactic): ?string
    {
        $allowed = ['pressing', 'contropiede', 'possesso', 'palla_bassa', 'lancio_lungo', 'catenaccio', 'fuorigioco', 'calci_piazzati'];
        $trainedTactic = trim($trainedTactic);
        return in_array($trainedTactic, $allowed, true) ? $trainedTactic : null;
    }

    /**
     * Auto-assign 11 starters based on module + tactic + player readiness.
     *
     * @return array{assigned:int,module:string,tactic:string}
     */
    public function autoAssign(
        Formation $formation,
        Team $team,
        string $module,
        string $tactic,
        string $marking = 'zone',
        int $offsideTrap = 1,
        string $trainedTactic = ''
    ): array
    {
        $module = self::normalizeModule($module);
        $tactic = self::normalizeTactic($tactic);
        $marking = $this->normalizeMarking($marking);
        $offsideTrap = $offsideTrap > 0 ? 1 : 0;
        $trainedTactic = $this->normalizeTrainedTactic($trainedTactic);
        $slotsTemplate = self::MODULE_TEMPLATE[$module];

        $players = Player::find()
            ->where(['team_id' => $team->id])
            ->orderBy(['general_skill' => SORT_DESC, 'position' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        if (empty($players)) {
            FormationSlot::deleteAll(['formation_id' => $formation->id]);
            $formation->name = 'Auto ' . $module;
            $formation->tactic = $tactic;
            $formation->marking = $marking;
            $formation->offside_trap = $offsideTrap;
            $formation->trained_tactic = $trainedTactic;
            $formation->save(false, ['name', 'tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);
            return ['assigned' => 0, 'module' => $module, 'tactic' => $tactic];
        }

        $tacticTraining = $this->loadTacticTraining($team->id);
        $pairs = [];
        foreach ($slotsTemplate as $tpl) {
            $zone = (int) $tpl['zone'];
            $role = $tpl['role'];
            foreach ($players as $player) {
                $score = $player->getOverallForPosition($zone);
                $score += $this->roleFitScore($player, $role);
                $score += $this->readinessScore($player);
                $score += $this->tacticScore($player, $role, $tactic, $tacticTraining);
                $pairs[] = [
                    'zone' => $zone,
                    'player_id' => (int) $player->id,
                    'score' => round($score, 3),
                ];
            }
        }

        usort($pairs, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        $assignedPlayers = [];
        $assignedZones = [];
        $assignments = [];
        foreach ($pairs as $pair) {
            $playerId = (int) $pair['player_id'];
            $zone = (int) $pair['zone'];
            if (isset($assignedPlayers[$playerId]) || isset($assignedZones[$zone])) {
                continue;
            }
            $assignedPlayers[$playerId] = true;
            $assignedZones[$zone] = true;
            $assignments[] = ['zone' => $zone, 'player_id' => $playerId];
            if (count($assignments) >= 11) {
                break;
            }
        }

        $tx = Yii::$app->db->beginTransaction();
        try {
            FormationSlot::deleteAll(['formation_id' => $formation->id]);
            foreach ($assignments as $row) {
                Yii::$app->db->createCommand()->insert(FormationSlot::tableName(), [
                    'formation_id' => $formation->id,
                    'zone' => $row['zone'],
                    'player_id' => $row['player_id'],
                ])->execute();
            }

            $formation->name = 'Auto ' . $module;
            $formation->tactic = $tactic;
            $formation->marking = $marking;
            $formation->offside_trap = $offsideTrap;
            $formation->trained_tactic = $trainedTactic;
            $formation->updated_at = time();
            $formation->save(false, ['name', 'tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);
            $tx->commit();
        } catch (\Throwable $e) {
            $tx->rollBack();
            throw $e;
        }

        return ['assigned' => count($assignments), 'module' => $module, 'tactic' => $tactic];
    }

    private function roleFitScore(Player $player, string $role): float
    {
        return match ($role) {
            'GK' => $player->position === 'GK' ? 25.0 : -25.0,
            'DF' => $player->position === 'DF' ? 10.0 : ($player->position === 'MF' ? 1.0 : -8.0),
            'MF' => $player->position === 'MF' ? 10.0 : ($player->position === 'DF' || $player->position === 'FW' ? 1.0 : -8.0),
            'FW' => $player->position === 'FW' ? 10.0 : ($player->position === 'MF' ? 2.0 : -8.0),
            default => 0.0,
        };
    }

    private function readinessScore(Player $player): float
    {
        return (($player->form - 50) * 0.08)
            + (($player->freshness - 50) * 0.06)
            + (($player->condition - 50) * 0.06);
    }

    /**
     * @param array<string, int> $training
     */
    private function tacticScore(Player $player, string $role, string $tactic, array $training): float
    {
        if ($tactic === 'all_out_attack') {
            $base = ($player->skill_tr * 0.05) + ($player->skill_tc * 0.04) + ($player->skill_cr * 0.03);
            $roleBonus = $role === 'FW' ? 4.0 : ($role === 'MF' ? 1.8 : 0.0);
            $trainBonus = (($training['contropiede'] ?? 0) + ($training['palla_bassa'] ?? 0)) * 0.02;
            return $base + $roleBonus + $trainBonus;
        }

        if ($tactic === 'ultra_defensive') {
            $base = ($player->skill_df * 0.06) + ($player->skill_cn * 0.04) + ($player->skill_po * 0.05);
            $roleBonus = ($role === 'DF' || $role === 'GK') ? 4.0 : 0.0;
            $trainBonus = (($training['catenaccio'] ?? 0) + ($training['fuorigioco'] ?? 0)) * 0.02;
            return $base + $roleBonus + $trainBonus;
        }

        $base = ($player->skill_pa * 0.04) + ($player->skill_rg * 0.04) + ($player->skill_cn * 0.02);
        $roleBonus = $role === 'MF' ? 2.0 : 0.0;
        $trainBonus = (($training['possesso'] ?? 0) + ($training['pressing'] ?? 0)) * 0.015;
        return $base + $roleBonus + $trainBonus;
    }

    /**
     * @return array<string, int>
     */
    private function loadTacticTraining(int $teamId): array
    {
        $row = Yii::$app->db->createCommand(
            'SELECT pressing, contropiede, possesso, palla_bassa, lancio_lungo, catenaccio, fuorigioco
             FROM {{%training_tactic}}
             WHERE team_id = :teamId
             ORDER BY season DESC
             LIMIT 1',
            [':teamId' => $teamId]
        )->queryOne();

        if (!$row) {
            return [];
        }

        return [
            'pressing' => (int) ($row['pressing'] ?? 0),
            'contropiede' => (int) ($row['contropiede'] ?? 0),
            'possesso' => (int) ($row['possesso'] ?? 0),
            'palla_bassa' => (int) ($row['palla_bassa'] ?? 0),
            'lancio_lungo' => (int) ($row['lancio_lungo'] ?? 0),
            'catenaccio' => (int) ($row['catenaccio'] ?? 0),
            'fuorigioco' => (int) ($row['fuorigioco'] ?? 0),
        ];
    }
}
