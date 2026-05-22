<?php

declare(strict_types=1);

namespace app\components;

use app\models\Formation;
use app\models\FormationSlot;
use app\models\Team;

class CpuFormationService
{
    /**
     * Ensure CPU team has active formation with starters.
     *
     * @return array{updated:bool,formation_id:int,assigned:int,module:string,tactic:string}
     */
    public function ensureTeamReady(Team $team): array
    {
        $formation = Formation::find()
            ->where(['team_id' => $team->id, 'is_active' => 1])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if (!$formation) {
            $formation = new Formation();
            $formation->team_id = (int) $team->id;
            $formation->name = 'Auto 4-4-2';
            $formation->is_active = 1;
            $formation->tactic = 'balanced';
            $formation->marking = 'zone';
            $formation->offside_trap = 1;
            $formation->trained_tactic = null;
            $formation->save(false);
        }

        $assigned = (int) FormationSlot::find()
            ->where(['formation_id' => $formation->id])
            ->andWhere(['<=', 'zone', 63])
            ->andWhere(['not', ['player_id' => null]])
            ->count();

        if ($assigned >= 11) {
            return [
                'updated' => false,
                'formation_id' => (int) $formation->id,
                'assigned' => $assigned,
                'module' => FormationAutoHelper::normalizeModule((string) str_replace('Auto ', '', (string) $formation->name)),
                'tactic' => FormationAutoHelper::normalizeTactic((string) $formation->tactic),
            ];
        }

        $module = FormationAutoHelper::normalizeModule((string) str_replace('Auto ', '', (string) $formation->name));
        $tactic = FormationAutoHelper::normalizeTactic((string) $formation->tactic);
        $marking = (new FormationAutoHelper())->normalizeMarking((string) $formation->marking);
        $offsideTrap = (int) ((int) ($formation->offside_trap ?? 1) > 0 ? 1 : 0);
        $trainedTactic = (string) ($formation->trained_tactic ?? '');

        $result = (new FormationAutoHelper())->autoAssign(
            $formation,
            $team,
            $module,
            $tactic,
            $marking,
            $offsideTrap,
            $trainedTactic
        );

        return [
            'updated' => true,
            'formation_id' => (int) $formation->id,
            'assigned' => (int) $result['assigned'],
            'module' => (string) $result['module'],
            'tactic' => (string) $result['tactic'],
        ];
    }
}

