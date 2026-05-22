<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\components\FormationAutoHelper;
use app\components\FormationRoleHelper;
use app\models\Team;
use app\models\Formation;
use app\models\FormationSlot;
use app\models\Player;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\AccessControl;

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

        // Remove whoever is currently in this zone
        FormationSlot::deleteAll(['formation_id' => $formationId, 'zone' => $zoneId]);

        if ($playerId) {
            // Block injured and suspended players
            $player = \app\models\Player::findOne((int)$playerId);
            if ($player && $player->injury_weeks > 0) {
                return $this->asJson(['success' => false, 'message' => "Giocatore infortunato ({$player->injury_type}, {$player->injury_weeks} sett. rimanenti)."]);
            }
            if ($player && $player->suspended_matches > 0) {
                return $this->asJson(['success' => false, 'message' => "Giocatore squalificato ({$player->suspended_matches} gara/e)."]);
            }

            // Remove this player from any other zone (one player, one zone)
            FormationSlot::deleteAll(['formation_id' => $formationId, 'player_id' => $playerId]);

            // Max 11 on pitch
            $current = (int) FormationSlot::find()
                ->where(['formation_id' => $formationId])
                ->count();
            if ($current >= 11) {
                return $this->asJson(['success' => false, 'message' => 'Massimo 11 giocatori in campo.']);
            }

            $slot = new FormationSlot();
            $slot->formation_id = (int)$formationId;
            $slot->zone         = (int)$zoneId;
            $slot->player_id    = (int)$playerId;
            $slot->save();
        }

        return $this->asJson(['success' => true, 'count' => (int) FormationSlot::find()->where(['formation_id' => $formationId])->count()]);
    }

    /**
     * Auto-assigns formation slots based on selected module and tactic.
     */
    public function actionAutoAssign(): Response
    {
        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'message' => 'Metodo non consentito.']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => 'Squadra non trovata.']);
        }

        $formationId = (int) Yii::$app->request->post('formation_id', 0);
        $module = (string) Yii::$app->request->post('module', '4-4-2');
        $tactic = (string) Yii::$app->request->post('tactic', 'balanced');
        $marking = (string) Yii::$app->request->post('marking', 'zone');
        $offsideTrap = (int) Yii::$app->request->post('offside_trap', 1);
        $trainedTactic = (string) Yii::$app->request->post('trained_tactic', '');

        $formation = Formation::findOne(['id' => $formationId, 'team_id' => $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => 'Formazione non valida.']);
        }

        try {
            $helper = new FormationAutoHelper();
            $result = $helper->autoAssign($formation, $team, $module, $tactic, $marking, $offsideTrap, $trainedTactic);
            return $this->asJson([
                'success' => true,
                'assigned' => $result['assigned'],
                'module' => $result['module'],
                'tactic' => $result['tactic'],
            ]);
        } catch (\Throwable $e) {
            Yii::error('Formation auto-assign failed: ' . $e->getMessage(), __METHOD__);
            return $this->asJson(['success' => false, 'message' => 'Errore durante auto-formazione.']);
        }
    }

    public function actionSaveSettings(): Response
    {
        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'message' => 'Metodo non consentito.']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => 'Squadra non trovata.']);
        }

        $formationId = (int) Yii::$app->request->post('formation_id', 0);
        $formation = Formation::findOne(['id' => $formationId, 'team_id' => $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => 'Formazione non valida.']);
        }

        $helper = new FormationAutoHelper();
        $formation->tactic = FormationAutoHelper::normalizeTactic((string) Yii::$app->request->post('tactic', 'balanced'));
        $formation->marking = $helper->normalizeMarking((string) Yii::$app->request->post('marking', 'zone'));
        $formation->offside_trap = (int) ((int) Yii::$app->request->post('offside_trap', 1) > 0 ? 1 : 0);
        $formation->trained_tactic = $helper->normalizeTrainedTactic((string) Yii::$app->request->post('trained_tactic', ''));
        $formation->updated_at = time();
        $formation->save(false, ['tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);

        return $this->asJson([
            'success' => true,
            'message' => 'Impostazioni tattiche salvate.',
        ]);
    }

    /**
     * Assign/clear special role on active formation.
     */
    public function actionSetRole(): Response
    {
        if (!Yii::$app->request->isPost) {
            return $this->asJson(['success' => false, 'message' => 'Metodo non consentito.']);
        }

        $team = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$team) {
            return $this->asJson(['success' => false, 'message' => 'Squadra non trovata.']);
        }

        $formationId = (int) Yii::$app->request->post('formation_id', 0);
        $role = (string) Yii::$app->request->post('role', '');
        $playerIdRaw = Yii::$app->request->post('player_id', null);
        $playerId = ($playerIdRaw === '' || $playerIdRaw === null) ? null : (int) $playerIdRaw;

        $formation = Formation::findOne(['id' => $formationId, 'team_id' => $team->id]);
        if (!$formation) {
            return $this->asJson(['success' => false, 'message' => 'Formazione non valida.']);
        }

        $fieldMap = [
            'captain'  => 'captain_player_id',
            'penalty'  => 'penalty_player_id',
            'freekick' => 'freekick_player_id',
            'corner'   => 'corner_player_id',
        ];
        $field = $fieldMap[$role] ?? null;
        if ($field === null) {
            return $this->asJson(['success' => false, 'message' => 'Ruolo non valido.']);
        }

        $slots = FormationSlot::find()
            ->where(['formation_id' => $formation->id])
            ->andWhere(['<=', 'zone', 63])
            ->andWhere(['not', ['player_id' => null]])
            ->all();
        $starterIds = [];
        foreach ($slots as $slot) {
            $starterIds[] = (int) $slot->player_id;
        }
        $starterIds = array_values(array_unique($starterIds));

        if ($playerId !== null) {
            if (!in_array($playerId, $starterIds, true)) {
                return $this->asJson(['success' => false, 'message' => 'Puoi assegnare il ruolo solo a un titolare in campo.']);
            }
            $player = Player::findOne(['id' => $playerId, 'team_id' => $team->id]);
            if (!$player) {
                return $this->asJson(['success' => false, 'message' => 'Giocatore non valido.']);
            }
            $formation->$field = $playerId;
        } else {
            $formation->$field = null;
            $player = null;
        }

        if (!$formation->save(false, [$field, 'updated_at'])) {
            return $this->asJson(['success' => false, 'message' => 'Errore salvataggio ruolo.']);
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
            'SELECT pressing, contropiede, possesso, palla_bassa, lancio_lungo, catenaccio, fuorigioco, calci_piazzati
             FROM {{%training_tactic}}
             WHERE team_id = :teamId
             ORDER BY season DESC
             LIMIT 1',
            [':teamId' => $teamId]
        )->queryOne();

        $labels = [
            'pressing' => 'Pressing',
            'contropiede' => 'Contropiede',
            'possesso' => 'Possesso palla',
            'palla_bassa' => 'Palla bassa',
            'lancio_lungo' => 'Lancio lungo',
            'catenaccio' => 'Catenaccio',
            'fuorigioco' => 'Fuorigioco',
            'calci_piazzati' => 'Calci piazzati',
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
