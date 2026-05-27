<?php

declare(strict_types=1);

namespace app\components;

use app\models\Formation;
use app\models\FormationSlot;
use app\models\Player;
use app\models\PlayerTalent;
use app\models\Team;
use Yii;

class FormationAutoHelper
{
    /** @var array<string, array<int, array{zone:int, role:string}>>|null */
    private static ?array $moduleTemplateCache = null;

    private static function moduleTemplate(): array
    {
        if (self::$moduleTemplateCache !== null) {
            return self::$moduleTemplateCache;
        }

        $z = static fn(int $row, int $lane): int => PitchZoneHelper::zone($row, $lane);

        self::$moduleTemplateCache = [
            '4-4-2' => [
                ['zone' => PitchZoneHelper::GK_ZONE, 'role' => 'GK'],
                ['zone' => $z(3, 1), 'role' => 'DF'],
                ['zone' => $z(2, 2), 'role' => 'DF'],
                ['zone' => $z(3, 2), 'role' => 'DF'],
                ['zone' => $z(3, 3), 'role' => 'DF'],
                ['zone' => $z(6, 1), 'role' => 'MF'],
                ['zone' => $z(6, 2), 'role' => 'MF'],
                ['zone' => $z(7, 2), 'role' => 'MF'],
                ['zone' => $z(6, 3), 'role' => 'MF'],
                ['zone' => $z(8, 2), 'role' => 'FW'],
                ['zone' => $z(9, 2), 'role' => 'FW'],
            ],
            '4-3-3' => [
                ['zone' => PitchZoneHelper::GK_ZONE, 'role' => 'GK'],
                ['zone' => $z(3, 1), 'role' => 'DF'],
                ['zone' => $z(2, 2), 'role' => 'DF'],
                ['zone' => $z(3, 2), 'role' => 'DF'],
                ['zone' => $z(3, 3), 'role' => 'DF'],
                ['zone' => $z(6, 1), 'role' => 'MF'],
                ['zone' => $z(6, 2), 'role' => 'MF'],
                ['zone' => $z(6, 3), 'role' => 'MF'],
                ['zone' => $z(8, 1), 'role' => 'FW'],
                ['zone' => $z(9, 2), 'role' => 'FW'],
                ['zone' => $z(8, 3), 'role' => 'FW'],
            ],
            '3-5-2' => [
                ['zone' => PitchZoneHelper::GK_ZONE, 'role' => 'GK'],
                ['zone' => $z(3, 1), 'role' => 'DF'],
                ['zone' => $z(3, 2), 'role' => 'DF'],
                ['zone' => $z(3, 3), 'role' => 'DF'],
                ['zone' => $z(4, 1), 'role' => 'MF'],
                ['zone' => $z(5, 2), 'role' => 'MF'],
                ['zone' => $z(6, 3), 'role' => 'MF'],
                ['zone' => $z(7, 1), 'role' => 'MF'],
                ['zone' => $z(7, 2), 'role' => 'MF'],
                ['zone' => $z(8, 2), 'role' => 'FW'],
                ['zone' => $z(9, 2), 'role' => 'FW'],
            ],
            '5-3-2' => [
                ['zone' => PitchZoneHelper::GK_ZONE, 'role' => 'GK'],
                ['zone' => $z(2, 1), 'role' => 'DF'],
                ['zone' => $z(2, 2), 'role' => 'DF'],
                ['zone' => $z(2, 3), 'role' => 'DF'],
                ['zone' => $z(3, 1), 'role' => 'DF'],
                ['zone' => $z(3, 3), 'role' => 'DF'],
                ['zone' => $z(6, 1), 'role' => 'MF'],
                ['zone' => $z(6, 2), 'role' => 'MF'],
                ['zone' => $z(6, 3), 'role' => 'MF'],
                ['zone' => $z(8, 2), 'role' => 'FW'],
                ['zone' => $z(9, 2), 'role' => 'FW'],
            ],
            '4-2-3-1' => [
                ['zone' => PitchZoneHelper::GK_ZONE, 'role' => 'GK'],
                ['zone' => $z(3, 1), 'role' => 'DF'],
                ['zone' => $z(2, 2), 'role' => 'DF'],
                ['zone' => $z(3, 2), 'role' => 'DF'],
                ['zone' => $z(3, 3), 'role' => 'DF'],
                ['zone' => $z(6, 1), 'role' => 'MF'],
                ['zone' => $z(6, 3), 'role' => 'MF'],
                ['zone' => $z(7, 1), 'role' => 'MF'],
                ['zone' => $z(7, 2), 'role' => 'MF'],
                ['zone' => $z(7, 3), 'role' => 'MF'],
                ['zone' => $z(9, 2), 'role' => 'FW'],
            ],
        ];

        return self::$moduleTemplateCache;
    }

    /** @return string[] */
    public static function moduleOptions(): array
    {
        return array_keys(self::moduleTemplate());
    }

    /** @return string[] */
    public static function tacticOptions(): array
    {
        return ['balanced', 'ultra_defensive', 'all_out_attack'];
    }

    public static function normalizeModule(string $module): string
    {
        $module = trim($module);
        return isset(self::moduleTemplate()[$module]) ? $module : '4-4-2';
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
        $allowed = ['pressing', 'contropiede', 'possesso', 'palla_bassa', 'lancio_lungo', 'catenaccio', 'fuorigioco'];
        $trainedTactic = trim($trainedTactic);
        return in_array($trainedTactic, $allowed, true) ? $trainedTactic : null;
    }

    /**
     * Auto-assign 11 starters based on module + tactic + player readiness.
     * Role-constrained: each slot accepts only players with matching base position.
     *
     * @return array{
     *   assigned:int,
     *   module:string,
     *   tactic:string,
     *   total_players:int,
     *   missing:int,
     *   missing_by_role:array<string,int>
     * }
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
        $slotsTemplate = self::moduleTemplate()[$module];

        $players = Player::find()
            ->where(['team_id' => $team->id])
            ->orderBy(['general_skill' => SORT_DESC, 'position' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
        $totalPlayers = count($players);

        if ($totalPlayers === 0) {
            FormationSlot::deleteAll(['formation_id' => $formation->id]);
            $formation->name = 'Auto ' . $module;
            $formation->tactic = $tactic;
            $formation->marking = $marking;
            $formation->offside_trap = $offsideTrap;
            $formation->trained_tactic = $trainedTactic;
            $formation->save(false, ['name', 'tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);
            return [
                'assigned' => 0,
                'module' => $module,
                'tactic' => $tactic,
                'total_players' => 0,
                'missing' => 11,
            ];
        }

        $tacticTraining = $this->loadTacticTraining($team->id);

        $availablePlayers = array_values(array_filter(
            $players,
            static fn(Player $p): bool => (int) ($p->injury_weeks ?? 0) <= 0 && (int) ($p->suspended_matches ?? 0) <= 0
        ));
        $talentsByPlayer = $this->loadTalentsByPlayer($availablePlayers);
        $playersByRole = [
            'GK' => [],
            'DF' => [],
            'MF' => [],
            'FW' => [],
        ];
        foreach ($availablePlayers as $player) {
            $pos = strtoupper((string) $player->position);
            if (isset($playersByRole[$pos])) {
                $playersByRole[$pos][] = $player;
            }
        }

        $slotsByRole = [
            'GK' => [],
            'DF' => [],
            'MF' => [],
            'FW' => [],
        ];
        foreach ($slotsTemplate as $tpl) {
            $role = strtoupper((string) ($tpl['role'] ?? ''));
            if (isset($slotsByRole[$role])) {
                $slotsByRole[$role][] = (int) $tpl['zone'];
            }
        }

        $assignments = [];
        $assignedPlayers = [];
        foreach (['GK', 'DF', 'MF', 'FW'] as $role) {
            $zones = $slotsByRole[$role] ?? [];
            $rolePlayers = $playersByRole[$role] ?? [];
            if (empty($zones) || empty($rolePlayers)) {
                continue;
            }

            $pairs = [];
            foreach ($zones as $zone) {
                foreach ($rolePlayers as $player) {
                    $score = $player->getOverallForPosition($zone);
                    $score += $this->roleFitScore($player, $role);
                    $score += $this->readinessScore($player);
                    $score += $this->tacticScore($player, $role, $tactic, $tacticTraining);
                    $score += $this->focusScore(
                        $player,
                        $role,
                        $trainedTactic,
                        $tacticTraining,
                        $talentsByPlayer[(int) $player->id] ?? []
                    );
                    $pairs[] = [
                        'zone' => $zone,
                        'player_id' => (int) $player->id,
                        'score' => round($score, 3),
                    ];
                }
            }

            usort($pairs, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

            $assignedZonesForRole = [];
            foreach ($pairs as $pair) {
                $playerId = (int) $pair['player_id'];
                $zone = (int) $pair['zone'];
                if (isset($assignedPlayers[$playerId]) || isset($assignedZonesForRole[$zone])) {
                    continue;
                }
                $assignedPlayers[$playerId] = true;
                $assignedZonesForRole[$zone] = true;
                $assignments[] = ['zone' => $zone, 'player_id' => $playerId];
                if (count($assignedZonesForRole) >= count($zones)) {
                    break;
                }
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

        $assigned = count($assignments);
        $assignedByRole = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        foreach ($assignments as $row) {
            $zoneRole = 'MF';
            foreach ($slotsTemplate as $tpl) {
                if ((int) $tpl['zone'] === (int) $row['zone']) {
                    $zoneRole = strtoupper((string) $tpl['role']);
                    break;
                }
            }
            if (isset($assignedByRole[$zoneRole])) {
                $assignedByRole[$zoneRole]++;
            }
        }
        $missingByRole = ['GK' => 0, 'DF' => 0, 'MF' => 0, 'FW' => 0];
        foreach (['GK', 'DF', 'MF', 'FW'] as $role) {
            $required = count($slotsByRole[$role] ?? []);
            $missingByRole[$role] = max(0, $required - ($assignedByRole[$role] ?? 0));
        }

        return [
            'assigned' => $assigned,
            'module' => $module,
            'tactic' => $tactic,
            'total_players' => $totalPlayers,
            'missing' => max(0, 11 - $assigned),
            'missing_by_role' => $missingByRole,
        ];
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
     * Apply additional ranking for the selected trained tactic focus.
     * Keeps impact bounded to avoid overriding base positional quality.
     *
     * @param array<string,int> $training
     * @param array<string,int> $talents
     */
    private function focusScore(
        Player $player,
        string $role,
        ?string $trainedTactic,
        array $training,
        array $talents
    ): float {
        if ($trainedTactic === null || $trainedTactic === '') {
            return 0.0;
        }

        $focusLevel = max(0, min(100, (int) ($training[$trainedTactic] ?? 0)));
        if ($focusLevel <= 0) {
            return 0.0;
        }

        $focusFactor = $focusLevel / 100.0; // 0..1
        $freshnessFactor = ((int) $player->freshness) / 100.0;
        $conditionFactor = ((int) $player->condition) / 100.0;
        $energy = ($freshnessFactor + $conditionFactor) / 2.0; // 0..1

        $talentLv = static fn(string $code): int => max(0, min(3, (int) ($talents[$code] ?? 0)));

        $profile = 0.0;
        $roleBonus = 0.0;
        switch ($trainedTactic) {
            case 'lancio_lungo':
                $profile = ($player->skill_tr * 0.05)
                    + ($player->skill_tc * 0.04)
                    + ($player->skill_pa * 0.03)
                    + ($player->skill_cr * 0.02)
                    + ($energy * 2.0)
                    + ($talentLv('velocita') * 0.9)
                    + ($talentLv('finalizzazione') * 0.6)
                    + ($talentLv('visione') * 0.5);
                $roleBonus = $role === 'FW' ? 2.0 : ($role === 'MF' ? 1.0 : 0.0);
                break;

            case 'catenaccio':
                $profile = ($player->skill_df * 0.05)
                    + ($player->skill_cn * 0.05)
                    + ($player->skill_po * 0.03)
                    + ($energy * 3.0)
                    + ($talentLv('marcatura') * 0.9)
                    + ($talentLv('disciplina') * 0.8)
                    + ($talentLv('tenacia') * 0.6);
                $roleBonus = ($role === 'DF' || $role === 'GK') ? 2.0 : 0.0;
                break;

            case 'pressing':
                $profile = ($player->skill_cn * 0.05)
                    + ($player->skill_df * 0.03)
                    + ($player->skill_rg * 0.02)
                    + ($freshnessFactor * 2.3)
                    + ($conditionFactor * 2.3)
                    + ($talentLv('resistenza') * 0.9)
                    + ($talentLv('tenacia') * 0.7);
                $roleBonus = $role === 'MF' ? 1.5 : ($role === 'FW' ? 1.0 : 0.5);
                break;

            case 'contropiede':
                $profile = ($player->skill_tr * 0.05)
                    + ($player->skill_tc * 0.04)
                    + ($player->skill_pa * 0.03)
                    + ($player->skill_cr * 0.02)
                    + ($energy * 1.8)
                    + ($talentLv('velocita') * 0.9)
                    + ($talentLv('dribbling') * 0.6)
                    + ($talentLv('finalizzazione') * 0.6);
                $roleBonus = ($role === 'FW' || $role === 'MF') ? 1.8 : 0.0;
                break;

            case 'possesso':
                $profile = ($player->skill_pa * 0.05)
                    + ($player->skill_rg * 0.05)
                    + ($player->skill_tc * 0.03)
                    + ($energy * 1.6)
                    + ($talentLv('visione') * 0.8)
                    + ($talentLv('creativita') * 0.8);
                $roleBonus = $role === 'MF' ? 2.0 : 0.5;
                break;

            case 'palla_bassa':
                $profile = ($player->skill_pa * 0.05)
                    + ($player->skill_tc * 0.05)
                    + ($player->skill_rg * 0.03)
                    + ($freshnessFactor * 1.6)
                    + ($talentLv('visione') * 0.6)
                    + ($talentLv('dribbling') * 0.6)
                    + ($talentLv('freddezza') * 0.5);
                $roleBonus = $role === 'MF' ? 2.0 : ($role === 'DF' ? 0.8 : 0.5);
                break;

            case 'fuorigioco':
                $profile = ($player->skill_df * 0.05)
                    + ($player->skill_cn * 0.04)
                    + ($player->skill_po * 0.03)
                    + ($conditionFactor * 2.0)
                    + ($talentLv('disciplina') * 0.9)
                    + ($talentLv('marcatura') * 0.6);
                $roleBonus = ($role === 'DF' || $role === 'GK') ? 1.8 : 0.0;
                break;
        }

        $raw = $profile + $roleBonus;
        $scaled = $raw * (0.15 + (0.45 * $focusFactor));

        return round(max(-2.0, min(7.0, $scaled)), 3);
    }

    /**
     * @param Player[] $players
     * @return array<int,array<string,int>>
     */
    private function loadTalentsByPlayer(array $players): array
    {
        $ids = [];
        foreach ($players as $player) {
            $pid = (int) $player->id;
            if ($pid > 0) {
                $ids[] = $pid;
            }
        }
        $ids = array_values(array_unique($ids));
        if (empty($ids)) {
            return [];
        }

        $rows = PlayerTalent::find()
            ->where(['player_id' => $ids])
            ->all();

        $map = [];
        foreach ($rows as $row) {
            $pid = (int) $row->player_id;
            $code = (string) $row->code;
            $level = max(1, min(3, (int) $row->level));
            $map[$pid][$code] = $level;
        }

        return $map;
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
