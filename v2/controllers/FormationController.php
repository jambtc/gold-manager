<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\components\FormationAutoHelper;
use app\components\FormationRoleHelper;
use app\components\PitchZoneHelper;
use app\models\Team;
use app\models\Formation;
use app\models\FormationSlot;
use app\models\Player;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;
use yii\db\Expression;

class FormationController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Displays the tactical grid.
     */
    public function actionView(): string|Response
    {
        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->redirect(['/site/index']);
        }

        // Get active formation or create a default one
        $formation = Formation::findOne(['team_id' => $team->id, 'is_active' => 1]);
        if (!$formation) {
            $formation = new Formation();
            $formation->team_id = $team->id;
            $formation->name = 'Default 4-4-2';
            $formation->is_active = true;
            $formation->tactic = 'balanced';
            $formation->marking = 'zone';
            $formation->offside_trap = 1;
            $formation->save();
        }

        $slots   = FormationSlot::find()->with('player')->where(['formation_id' => $formation->id])->all();
        $players = Player::find()->where(['team_id' => $team->id])->orderBy(['position' => SORT_ASC, 'general_skill' => SORT_DESC])->all();
        $roleSummary = (new FormationRoleHelper())->resolveRolePlayers($formation);
        $trainedTactics = $this->loadTrainedTactics($team->id);

        return $this->render('view', [
            'team' => $team,
            'formation' => $formation,
            'slots' => $slots,
            'players' => $players,
            'roleSummary' => $roleSummary,
            'trainedTactics' => $trainedTactics,
        ]);
    }

    /**
     * API endpoint to save a player to a zone.
     */
    public function actionSaveSlot(): Response
    {
        $data        = Yii::$app->request->post();
        $formationId = $data['formation_id'] ?? null;
        $zoneId      = $data['zone'] ?? null;
        $playerId    = $data['player_id'] ?? null;

        if (!$formationId || !$zoneId) {
            return $this->asJson(['success' => false, 'message' => 'Missing data']);
        }
        $formationId = (int) $formationId;

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Team not found.')]);
        }
        $formation = Formation::findOne(['id' => $formationId, 'team_id' => (int) $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid lineup.')]);
        }

        $zoneId = (int) $zoneId;
        if (!PitchZoneHelper::isOnPitch($zoneId)) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid zone.')]);
        }

        // Persist outfield display zones with unambiguous storage (1001..1063),
        // so drag&drop keeps exact 7-column cell and does not collapse to 2/4/6.
        if (PitchZoneHelper::isGoalkeeperZone($zoneId)) {
            $zoneId = PitchZoneHelper::DISPLAY_GK_ZONE; // keep UI GK code (64)
        } elseif ($zoneId >= 1 && $zoneId <= 63) {
            $zoneId = PitchZoneHelper::encodeStoredDisplayZone($zoneId);
        }
        // Zones > 64 arriving here can be historical/current callers.

        $displayZone = PitchZoneHelper::toLegacyDisplayZone($zoneId);
        $targetZoneCandidates = PitchZoneHelper::displayZoneCandidates($displayZone);

        if ($playerId) {
            $playerId = (int) $playerId;
            // Block injured and suspended players
            $player = Player::findOne(['id' => $playerId, 'team_id' => (int) $team->id]);
            if (!$player) {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid player for this team.')]);
            }
            if ($player && $player->injury_weeks > 0) {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Player injured ({type}, {weeks} week(s) remaining).', ['{type}' => $player->injury_type, '{weeks}' => $player->injury_weeks])]);
            }
            if ($player && $player->suspended_matches > 0) {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Player suspended ({matches} match(es)).', ['{matches}' => $player->suspended_matches])]);
            }
            $isGkZone = PitchZoneHelper::isGoalkeeperZone($zoneId);
            if ($player && $isGkZone && strtoupper((string) $player->position) !== 'GK') {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'The goalkeeper slot only accepts a goalkeeper (GK).')]);
            }
            if ($player && strtoupper((string) $player->position) === 'GK' && !$isGkZone) {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'The starting goalkeeper must be in the goalkeeper slot.')]);
            }
            $tx = Yii::$app->db->beginTransaction();
            try {
                // Remove whoever is currently in target zone and ensure one-slot-per-player.
                if (!empty($targetZoneCandidates)) {
                    FormationSlot::deleteAll(['formation_id' => $formationId, 'zone' => $targetZoneCandidates]);
                } else {
                    FormationSlot::deleteAll(['formation_id' => $formationId, 'zone' => $zoneId]);
                }
                FormationSlot::deleteAll(['formation_id' => $formationId, 'player_id' => $playerId]);

                // Max 11 starters on pitch (ignore bench/off-pitch zones).
                $current = (int) FormationSlot::find()
                    ->where(['formation_id' => $formationId])
                    ->andWhere(new Expression(PitchZoneHelper::onPitchSql('zone')))
                    ->count();
                if ($current >= 11) {
                    $tx->rollBack();
                    return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Maximum 11 players on the pitch.')]);
                }

                $slot = new FormationSlot();
                $slot->formation_id = $formationId;
                $slot->zone = (int) $zoneId;
                $slot->player_id = $playerId;
                $slot->save(false);
                $tx->commit();
            } catch (\Throwable $e) {
                $tx->rollBack();
                Yii::error('save-slot failed: ' . $e->getMessage(), __METHOD__);
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Error saving lineup.')]);
            }
        } else {
            // Clear zone only.
            if (!empty($targetZoneCandidates)) {
                FormationSlot::deleteAll(['formation_id' => $formationId, 'zone' => $targetZoneCandidates]);
            } else {
                FormationSlot::deleteAll(['formation_id' => $formationId, 'zone' => $zoneId]);
            }
        }

        $count = (int) FormationSlot::find()
            ->where(['formation_id' => $formationId])
            ->andWhere(new Expression(PitchZoneHelper::onPitchSql('zone')))
            ->count();
        return $this->asJson(['success' => true, 'count' => $count]);
    }

    /**
     * Auto-assigns formation slots based on selected module and tactic.
     */
    public function actionAutoAssign(): Response
    {
        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Method not allowed.')]);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Team not found.')]);
        }

        $formationId = (int) Yii::$app->request->post('formation_id', 0);
        $module = (string) Yii::$app->request->post('module', '4-4-2');
        $tactic = (string) Yii::$app->request->post('tactic', 'balanced');
        $marking = (string) Yii::$app->request->post('marking', 'zone');
        $offsideTrap = (int) Yii::$app->request->post('offside_trap', 1);
        $trainedTactic = (string) Yii::$app->request->post('trained_tactic', '');

        $formation = Formation::findOne(['id' => $formationId, 'team_id' => $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid lineup.')]);
        }

        try {
            $helper = new FormationAutoHelper();
            $result = $helper->autoAssign($formation, $team, $module, $tactic, $marking, $offsideTrap, $trainedTactic);

            if (($result['total_players'] ?? 0) === 0) {
                return $this->asJson([
                    'success' => false,
                    'message' => Yii::t('app', 'Auto-lineup unavailable: empty squad.'),
                ]);
            }

            $warning = null;
            $missing = (int) ($result['missing'] ?? 0);
            if ($missing > 0) {
                $missingByRole = is_array($result['missing_by_role'] ?? null) ? $result['missing_by_role'] : [];
                $parts = [];
                foreach (['GK' => 'GK', 'DF' => 'DF', 'MF' => 'MF', 'FW' => 'FW'] as $key => $label) {
                    $value = (int) ($missingByRole[$key] ?? 0);
                    if ($value > 0) {
                        $parts[] = $label . ' x' . $value;
                    }
                }
                $roleHint = empty($parts) ? '' : (' ' . Yii::t('app', 'Missing roles: {roles}.', ['{roles}' => implode(', ', $parts)]));
                $warning = Yii::t('app', 'Auto-lineup completed but only {assigned}/11 slots were filled (current squad: {total}). Missing {missing} players: complete manually or sign new ones.{hint}', [
                    '{assigned}' => (int) $result['assigned'],
                    '{total}'    => (int) ($result['total_players'] ?? 0),
                    '{missing}'  => $missing,
                    '{hint}'     => $roleHint,
                ]);
            }

            return $this->asJson([
                'success' => true,
                'assigned' => $result['assigned'],
                'module' => $result['module'],
                'tactic' => $result['tactic'],
                'warning' => $warning,
                'total_players' => $result['total_players'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Yii::error('Formation auto-assign failed: ' . $e->getMessage(), __METHOD__);
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Error during auto-lineup.')]);
        }
    }

    public function actionSaveSettings(): Response
    {
        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Method not allowed.')]);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Team not found.')]);
        }

        $formationId = (int) Yii::$app->request->post('formation_id', 0);
        $formation = Formation::findOne(['id' => $formationId, 'team_id' => $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid lineup.')]);
        }

        $helper = new FormationAutoHelper();
        $module = FormationAutoHelper::normalizeModule((string) Yii::$app->request->post('module', '4-4-2'));
        $formation->tactic = FormationAutoHelper::normalizeTactic((string) Yii::$app->request->post('tactic', 'balanced'));
        $formation->marking = $helper->normalizeMarking((string) Yii::$app->request->post('marking', 'zone'));
        $formation->offside_trap = (int) ((int) Yii::$app->request->post('offside_trap', 1) > 0 ? 1 : 0);
        $formation->trained_tactic = $helper->normalizeTrainedTactic((string) Yii::$app->request->post('trained_tactic', ''));
        $formation->name = 'Auto ' . $module;
        $formation->updated_at = time();
        $formation->save(false, ['name', 'tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);

        return $this->asJson([
            'success' => true,
            'message' => Yii::t('app', 'Tactical settings saved.'),
        ]);
    }

    /**
     * Assign/clear special role on active formation.
     */
    public function actionSetRole(): Response
    {
        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Method not allowed.')]);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Team not found.')]);
        }

        $formationId = (int) Yii::$app->request->post('formation_id', 0);
        $role = (string) Yii::$app->request->post('role', '');
        $playerIdRaw = Yii::$app->request->post('player_id', null);
        $playerId = ($playerIdRaw === '' || $playerIdRaw === null) ? null : (int) $playerIdRaw;

        $formation = Formation::findOne(['id' => $formationId, 'team_id' => $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid lineup.')]);
        }

        $fieldMap = [
            'captain'  => 'captain_player_id',
            'penalty'  => 'penalty_player_id',
            'freekick' => 'freekick_player_id',
            'corner'   => 'corner_player_id',
        ];
        $field = $fieldMap[$role] ?? null;
        if ($field === null) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid role.')]);
        }

        $slots = FormationSlot::find()
            ->where(['formation_id' => $formation->id])
            ->andWhere(new Expression(PitchZoneHelper::onPitchSql('zone')))
            ->andWhere(['not', ['player_id' => null]])
            ->all();
        $starterIds = [];
        foreach ($slots as $slot) {
            $starterIds[] = (int) $slot->player_id;
        }
        $starterIds = array_values(array_unique($starterIds));

        if ($playerId !== null) {
            if (!in_array($playerId, $starterIds, true)) {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'You can only assign the role to a starting player.')]);
            }
            $player = Player::findOne(['id' => $playerId, 'team_id' => $team->id]);
            if (!$player) {
                return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Invalid player.')]);
            }
            $formation->$field = $playerId;
        } else {
            $formation->$field = null;
            $player = null;
        }

        if (!$formation->save(false, [$field, 'updated_at'])) {
            return $this->asJson(['success' => false, 'message' => Yii::t('app', 'Error saving role.')]);
        }

        $summary = (new FormationRoleHelper())->resolveRolePlayers($formation);
        $resolved = $summary[$role] ?? null;

        return $this->asJson([
            'success' => true,
            'role' => $role,
            'player_id' => $resolved['effective_id'] ?? null,
            'player_name' => $resolved['effective_player']->name ?? null,
            'is_auto' => (bool) ($resolved['is_auto'] ?? false),
        ]);
    }

    /**
     * @return array<string, array{label:string,level:int}>
     */
    private function loadTrainedTactics(int $teamId): array
    {
        $row = Yii::$app->db->createCommand(
            'SELECT pressing, contropiede, possesso, palla_bassa, lancio_lungo, catenaccio, fuorigioco
             FROM {{%training_tactic}}
             WHERE team_id = :teamId
             ORDER BY season DESC
             LIMIT 1',
            [':teamId' => $teamId]
        )->queryOne();

        $labels = [
            'pressing'    => Yii::t('app', 'Pressing'),
            'contropiede' => Yii::t('app', 'Counter-attack'),
            'possesso'    => Yii::t('app', 'Ball Possession'),
            'palla_bassa' => Yii::t('app', 'Low Ball'),
            'lancio_lungo'=> Yii::t('app', 'Long Ball'),
            'catenaccio'  => Yii::t('app', 'Catenaccio'),
            'fuorigioco'  => Yii::t('app', 'Offside Trap'),
        ];

        $result = [];
        foreach ($labels as $key => $label) {
            $result[$key] = [
                'label' => $label,
                'level' => (int) ($row[$key] ?? 0),
            ];
        }

        uasort($result, static fn(array $a, array $b): int => $b['level'] <=> $a['level']);
        return $result;
    }
}
