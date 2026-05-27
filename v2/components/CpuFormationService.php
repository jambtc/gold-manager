<?php

declare(strict_types=1);

namespace app\components;

use app\models\Formation;
use app\models\Team;

class CpuFormationService
{
    /**
     * Ensure CPU team has active formation with starters.
     *
     * @return array{updated:bool,formation_id:int,assigned:int,module:string,tactic:string,difficulty:string}
     */
    public function ensureTeamReady(Team $team, ?int $competitionId = null): array
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

        $setup = CpuDifficultyHelper::suggestPreMatchSetup($team, $formation, $competitionId);
        $module = FormationAutoHelper::normalizeModule((string) ($setup['module'] ?? str_replace('Auto ', '', (string) $formation->name)));
        $tactic = FormationAutoHelper::normalizeTactic((string) ($setup['tactic'] ?? $formation->tactic));
        $marking = (new FormationAutoHelper())->normalizeMarking((string) ($setup['marking'] ?? $formation->marking));
        $offsideTrap = (int) ((int) ($setup['offside_trap'] ?? $formation->offside_trap ?? 1) > 0 ? 1 : 0);
        $trainedTactic = (string) ($setup['trained_tactic'] ?? $formation->trained_tactic ?? '');
        $difficulty = (string) ($setup['difficulty'] ?? 'normal');

        $formationChanged =
            FormationAutoHelper::normalizeModule((string) str_replace('Auto ', '', (string) $formation->name)) !== $module
            || FormationAutoHelper::normalizeTactic((string) $formation->tactic) !== $tactic
            || (new FormationAutoHelper())->normalizeMarking((string) $formation->marking) !== $marking
            || (int) ($formation->offside_trap ?? 0) !== $offsideTrap
            || (string) ($formation->trained_tactic ?? '') !== $trainedTactic;

        if ($formationChanged) {
            $formation->name = 'Auto ' . $module;
            $formation->tactic = $tactic;
            $formation->marking = $marking;
            $formation->offside_trap = $offsideTrap;
            $formation->trained_tactic = $trainedTactic !== '' ? $trainedTactic : null;
            $formation->save(false, ['name', 'tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);
        }

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
            'difficulty' => $difficulty,
        ];
    }
}
