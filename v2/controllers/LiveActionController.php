<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\MultiplayerSyncService;
use app\models\Fixture;
use app\models\Formation;
use app\models\FormationSlot;
use app\models\MatchCommand;
use app\models\MatchState;
use app\models\Player;
use app\models\Team;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Json;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class LiveActionController extends Controller
{
    public $enableCsrfValidation = false; // API endpoints called via fetch

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    /**
     * GET /live-action/roster?fixtureId={id}
     * Returns starters and bench for the manager's team in this fixture.
     */
    public function actionRoster(int $fixtureId): Response
    {
        $fixture = Fixture::findOne($fixtureId);
        $team    = Team::findOne(['user_id' => Yii::$app->user->id]);

        if (!$fixture || !$team) throw new BadRequestHttpException();

        $side    = $fixture->home_team_id === $team->id ? 'home' : 'away';
        $state   = MatchState::findOne(['fixture_id' => $fixtureId]);

        // Formation in use
        $formField  = "{$side}_formation_id";
        $formId     = $state?->$formField;
        $formation  = $formId ? Formation::findOne($formId) : Formation::findOne(['team_id' => $team->id, 'is_active' => 1]);

        $starterIds = [];
        if ($formation) {
            $slots = FormationSlot::find()->where(['formation_id' => $formation->id])->all();
            foreach ($slots as $slot) {
                $starterIds[] = $slot->player_id;
            }
        }

        // Already substituted players (cannot be used again)
        $subsUsedField  = "{$side}_subs_used";
        $subsUsed       = $state ? (int)($state->$subsUsedField ?? 0) : 0;

        // Players who were subbed off (in pending or past actions)
        $substitutedOut = [];
        if ($state) {
            $pendingField = "pending_{$side}_actions";
            $pending = json_decode($state->$pendingField ?? '[]', true) ?: [];
            foreach ($pending as $a) {
                if (($a['type'] ?? '') === 'substitution') {
                    $substitutedOut[] = (int)$a['out'];
                }
            }
        }

        $mapPlayer = fn(Player $p, bool $isStarter) => [
            'id'            => $p->id,
            'name'          => $p->name,
            'position'      => $p->position,
            'general_skill' => $p->general_skill,
            'freshness'     => $p->freshness,
            'form'          => $p->form,
            'subbed_off'    => in_array($p->id, $substitutedOut, true),
        ];

        $allPlayers = Player::find()->where(['team_id' => $team->id])->all();
        $starters   = [];
        $bench      = [];

        foreach ($allPlayers as $p) {
            if (in_array($p->id, $starterIds, true)) {
                // Skip already subbed-off starters (they're off the field)
                if (!in_array($p->id, $substitutedOut, true)) {
                    $starters[] = $mapPlayer($p, true);
                }
            } else {
                // Bench: only players NOT already subbed in this match
                if (!in_array($p->id, $substitutedOut, true)) {
                    $bench[] = $mapPlayer($p, false);
                }
            }
        }

        // Sort bench by general_skill desc
        usort($bench, fn($a, $b) => $b['general_skill'] - $a['general_skill']);

        return $this->asJson([
            'starters'  => $starters,
            'bench'     => $bench,
            'subs_used' => $subsUsed,
            'max_subs'  => 5,
            'phase'     => strtolower($state?->phase ?? 'not_started'),
        ]);
    }

    /**
     * POST /live-action/substitution
     */
    public function actionSubstitution(int $fixtureId): Response
    {
        if (!MultiplayerSyncService::ensureOnce('live.sub.' . $fixtureId, 6)) {
            return $this->asJson(['success' => true, 'message' => 'Richiesta duplicata ignorata.']);
        }

        $fixture = Fixture::findOne($fixtureId);
        $team    = Team::findOne(['user_id' => Yii::$app->user->id]);

        if (!$fixture || !$team) throw new BadRequestHttpException();
        if ($fixture->home_team_id !== $team->id && $fixture->away_team_id !== $team->id) {
            throw new BadRequestHttpException('Non hai il controllo di questa partita.');
        }

        $playerOutId = (int) Yii::$app->request->post('player_out_id');
        $playerInId  = (int) Yii::$app->request->post('player_in_id');

        if (!$playerOutId || !$playerInId) {
            return $this->asJson(['success' => false, 'message' => 'Seleziona giocatore uscente e entrante.']);
        }

        $side  = $fixture->home_team_id === $team->id ? 'home' : 'away';
        $state = MatchState::findOne(['fixture_id' => $fixtureId]);
        $lockToken = MultiplayerSyncService::acquireLock('live.fixture.' . $fixtureId . '.team.' . (int) $team->id, 8);
        if ($lockToken === null) {
            return $this->asJson(['success' => false, 'message' => 'Operazione concorrente in corso.']);
        }
        try {

        // Check subs limit
        $subsField = "{$side}_subs_used";
        $subsUsed  = $state ? (int)($state->$subsField ?? 0) : 0;
        if ($subsUsed >= 5) {
            return $this->asJson(['success' => false, 'message' => 'Hai già effettuato 5 sostituzioni.']);
        }

        // ── PHP engine: write to MatchState.pending_{side}_actions ──
        if ($state) {
            $field   = "pending_{$side}_actions";
            $actions = json_decode($state->$field ?? '[]', true) ?: [];
            $actions[] = ['type' => 'substitution', 'out' => $playerOutId, 'in' => $playerInId];
            $state->$field = json_encode($actions);
            $state->save(false);
        }

        // ── Go worker: also save to MatchCommand ──
        $cmd = new MatchCommand();
        $cmd->fixture_id      = $fixtureId;
        $cmd->team_id         = $team->id;
        $cmd->command_type    = MatchCommand::TYPE_SUBSTITUTION;
        $cmd->payload         = Json::encode(['out' => $playerOutId, 'in' => $playerInId]);
        $cmd->minute_submitted = $state ? $state->current_minute : 0;
        $cmd->save();

        $out = Player::findOne($playerOutId);
        $in  = Player::findOne($playerInId);
        $msg = sprintf('✓ %s → %s prenotato per il prossimo tick.',
            $out?->name ?? '?', $in?->name ?? '?');

        return $this->asJson([
            'success'   => true,
            'message'   => $msg,
            'subs_used' => $subsUsed + 1,
        ]);
        } finally {
            MultiplayerSyncService::releaseLock('live.fixture.' . $fixtureId . '.team.' . (int) $team->id, $lockToken);
        }
    }

    /**
     * POST /live-action/tactic
     */
    public function actionTactic(int $fixtureId): Response
    {
        if (!MultiplayerSyncService::ensureOnce('live.tactic.' . $fixtureId, 3)) {
            return $this->asJson(['success' => true, 'message' => 'Richiesta duplicata ignorata.']);
        }

        $fixture = Fixture::findOne($fixtureId);
        $team    = Team::findOne(['user_id' => Yii::$app->user->id]);

        if (!$fixture || !$team) throw new BadRequestHttpException();

        $tactic = (string) Yii::$app->request->post('tactic', 'balanced');
        $state  = MatchState::findOne(['fixture_id' => $fixtureId]);
        $side   = $fixture->home_team_id === $team->id ? 'home' : 'away';
        $marking = 'zone';
        $offsideTrap = (int) ((int) Yii::$app->request->post('offside_trap', 1) > 0 ? 1 : 0);
        $trainedTactic = (string) Yii::$app->request->post('trained_tactic', '');
        $allowedTactics = ['balanced', 'ultra_defensive', 'all_out_attack'];
        $allowedMarkings = ['zone', 'man'];
        $allowedTrained = ['pressing', 'contropiede', 'possesso', 'palla_bassa', 'lancio_lungo', 'catenaccio', 'fuorigioco'];

        if (!in_array($tactic, $allowedTactics, true)) {
            $tactic = 'balanced';
        }
        if (!in_array($trainedTactic, $allowedTrained, true)) {
            $trainedTactic = '';
        }

        $formField = "{$side}_formation_id";
        if ($state && $state->$formField) {
            $formation = Formation::findOne((int) $state->$formField);
            if ($formation && in_array((string) $formation->marking, ['zone', 'man'], true)) {
                $marking = (string) $formation->marking;
            }
            if (in_array((string) Yii::$app->request->post('marking', $marking), $allowedMarkings, true)) {
                $marking = (string) Yii::$app->request->post('marking', $marking);
            }
            if ($formation) {
                $formation->tactic = $tactic;
                $formation->marking = $marking;
                $formation->offside_trap = $offsideTrap;
                $formation->trained_tactic = $trainedTactic !== '' ? $trainedTactic : null;
                $formation->updated_at = time();
                $formation->save(false, ['tactic', 'marking', 'offside_trap', 'trained_tactic', 'updated_at']);
            }
        }

        if ($state) {
            $field   = "pending_{$side}_actions";
            $actions = json_decode($state->$field ?? '[]', true) ?: [];
            $actions = array_filter($actions, fn($a) => ($a['type'] ?? '') !== 'tactic_change');
            $actions[] = [
                'type' => 'tactic_change',
                'tactic' => $tactic,
                'marking' => $marking,
                'offside_trap' => $offsideTrap,
                'trained_tactic' => $trainedTactic,
            ];
            $state->$field = json_encode(array_values($actions));
            $state->save(false);
        }

        $cmd = new MatchCommand();
        $cmd->fixture_id      = $fixtureId;
        $cmd->team_id         = $team->id;
        $cmd->command_type    = MatchCommand::TYPE_TACTIC;
        $presetLevels = $this->presetTacticLevels($tactic);
        $cmd->payload         = Json::encode([
            'tactic' => $tactic,
            'marking' => $marking,
            'offside_trap' => $offsideTrap,
            'trained_tactic' => $trainedTactic,
            'preset_levels' => $presetLevels,
        ]);
        $cmd->minute_submitted = $state ? $state->current_minute : 0;
        $cmd->save();

        $label = match($tactic) {
            'ultra_defensive' => 'Difensivo',
            'all_out_attack'  => 'Offensivo',
            default           => 'Bilanciato',
        };

        // SIP-0038: keep tactical levels aligned with live preset (for next ticks and later matches)
        $this->syncTeamTrainingTacticPreset((int) $team->id, $presetLevels);

        return $this->asJson(['success' => true, 'message' => "Stile gara: $label. Attivo dal prossimo tick."]);
    }

    /**
     * @return array<string,int>
     */
    private function presetTacticLevels(string $tactic): array
    {
        return match ($tactic) {
            'ultra_defensive' => [
                'pressing' => 20, 'possesso' => 30, 'catenaccio' => 70, 'contropiede' => 60,
                'palla_bassa' => 60, 'lancio_lungo' => 55, 'fuorigioco' => 50,
            ],
            'all_out_attack' => [
                'pressing' => 80, 'possesso' => 70, 'catenaccio' => 10, 'contropiede' => 20,
                'palla_bassa' => 25, 'lancio_lungo' => 35, 'fuorigioco' => 40,
            ],
            default => [
                'pressing' => 50, 'possesso' => 50, 'catenaccio' => 30, 'contropiede' => 30,
                'palla_bassa' => 40, 'lancio_lungo' => 30, 'fuorigioco' => 30,
            ],
        };
    }

    /**
     * @param array<string,int> $levels
     */
    private function syncTeamTrainingTacticPreset(int $teamId, array $levels): void
    {
        $season = (int) Yii::$app->db->createCommand('SELECT MIN(season) FROM {{%competition}}')->queryScalar() ?: 1;
        $exists = Yii::$app->db->createCommand(
            'SELECT id FROM {{%training_tactic}} WHERE team_id=:t AND season=:s',
            [':t' => $teamId, ':s' => $season]
        )->queryScalar();

        $data = array_merge($levels, ['updated_at' => time()]);
        if ($exists) {
            Yii::$app->db->createCommand()
                ->update('{{%training_tactic}}', $data, ['id' => (int) $exists])
                ->execute();
            return;
        }

        Yii::$app->db->createCommand()->insert('{{%training_tactic}}', array_merge($data, [
            'team_id' => $teamId,
            'season' => $season,
        ]))->execute();
    }
}
