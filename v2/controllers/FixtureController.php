<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\FixtureViewHelper;
use app\components\PitchZoneHelper;
use app\models\Fixture;
use app\models\MatchEvent;
use app\models\MatchState;
use app\models\Team;
use Yii;
use yii\filters\AccessControl;
use yii\db\Expression;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class FixtureController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'actions' => ['index', 'view', 'live', 'get-events', 'get-event-updates', 'replay', 'get-scorers', 'formations'],
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * List of all fixtures (schedule).
     */
    public function actionIndex(): string
    {
        /** @var \app\models\User|null $identity */
        $identity = Yii::$app->user->identity;
        $isAdmin  = $identity && $identity->isAdmin();

        // Find user's competition to filter fixtures
        $myTeam = $isAdmin ? null : \app\models\Team::findOne(['user_id' => Yii::$app->user->id]);
        $myStanding = $myTeam
            ? \app\models\Standing::findOne(['team_id' => $myTeam->id])
            : null;
        $myCompetitionId = $myStanding?->competition_id;

        $type   = Yii::$app->request->get('type', 'league'); // 'league' | 'friendly'
        $round  = (int) Yii::$app->request->get('round', 0);

        // Base query — exclude friendly unless explicitly requested
        $query = Fixture::find()
            ->joinWith('competition', false)
            ->orderBy(['match_date' => SORT_ASC]);

        if ($type === 'friendly') {
            $query->andWhere(['competition.type' => 'friendly']);
        } else {
            $query->andWhere(['!=', 'competition.type', 'friendly']);
            if ($myCompetitionId) {
                $query->andWhere(['fixture.competition_id' => $myCompetitionId]);
            }
        }

        $allFixtures = $query->all();

        // Group by matchday (calendar date)
        $rounds = [];
        foreach ($allFixtures as $f) {
            $dayKey = date('Y-m-d', $f->match_date);
            $rounds[$dayKey][] = $f;
        }
        ksort($rounds);
        $roundKeys = array_keys($rounds);

        // Select current round
        if ($round <= 0 || $round > count($roundKeys)) {
            // Default: current or next upcoming round
            $round = 1;
            foreach ($roundKeys as $i => $key) {
                $hasUpcoming = array_filter($rounds[$key], fn($f) => $f->status !== Fixture::STATUS_FINISHED);
                if ($hasUpcoming) { $round = $i + 1; break; }
            }
        }

        $currentKey      = $roundKeys[$round - 1] ?? null;
        $currentFixtures = $currentKey ? ($rounds[$currentKey] ?? []) : [];
        $totalRounds     = count($roundKeys);

        return $this->render('index', [
            'fixtures'        => $currentFixtures,
            'allFixtures'     => $allFixtures,
            'round'           => $round,
            'totalRounds'     => $totalRounds,
            'type'            => $type,
            'myTeamId'        => $myTeam?->id,
        ]);
    }

    /**
     * Match report / details (post-match or scheduled).
     */
    public function actionView(int $id): string
    {
        $fixture = Fixture::findOne($id);
        if (!$fixture) {
            throw new NotFoundHttpException(Yii::t('app', 'Match not found.'));
        }

        $state = MatchState::findOne(['fixture_id' => $id]);
        $events = MatchEvent::find()
            ->where(['fixture_id' => $id])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $this->render('view', [
            'fixture' => $fixture,
            'state' => $state,
            'events' => $events,
        ]);
    }
    /**
     * Live match view.
     */
    public function actionLive(int $id): string
    {
        $fixture = Fixture::findOne($id);
        if (!$fixture) {
            throw new NotFoundHttpException(Yii::t('app', 'Match not found.'));
        }
        FixtureViewHelper::ensureUiColors($fixture);

        $state = MatchState::findOne(['fixture_id' => $id]);
        if ($state) {
            $state->phase = strtolower($state->phase); // Go worker may write uppercase
        }

        /** @var \app\models\User|null $identity */
        $identity = Yii::$app->user->identity;
        $isAdmin  = $identity && $identity->isAdmin();

        $userTeam  = $isAdmin ? null : Team::findOne(['user_id' => Yii::$app->user->id]);
        $isManager = $userTeam && (
            $fixture->home_team_id === $userTeam->id ||
            $fixture->away_team_id === $userTeam->id
        );

        // Go worker non genera pre_match: lo garantiamo lato PHP.
        FixtureViewHelper::ensurePreMatchEvent($fixture);

        // Load existing significant events for server-side rendering (no animation)
        $existingEvents = FixtureViewHelper::loadLiveSeedEvents($id);

        // Start JS polling from the latest event ID so refresh never replays animations
        $lastEventId = $existingEvents ? (int)$existingEvents[0]->id : 0;

        $userSide = null;
        if ($isManager && $userTeam) {
            $userSide = $fixture->home_team_id === $userTeam->id ? 'home' : 'away';
        }

        return $this->render('live', [
            'fixture'        => $fixture,
            'state'          => $state,
            'userTeam'       => $isManager ? $userTeam : null,
            'isAdmin'        => $isAdmin,
            'existingEvents' => $existingEvents,
            'lastEventId'    => $lastEventId,
            'userSide'       => $userSide,
            'homeStrength'   => FixtureViewHelper::computeStrength($fixture->homeTeam),
            'awayStrength'   => FixtureViewHelper::computeStrength($fixture->awayTeam),
        ]);
    }

    /**
     * Match replay: plays back all events with animated clock.
     */
    public function actionReplay(int $id): string
    {
        $fixture = Fixture::findOne($id);
        if (!$fixture) {
            throw new NotFoundHttpException(Yii::t('app', 'Match not found.'));
        }
        FixtureViewHelper::ensureUiColors($fixture);

        $existingEvents = MatchEvent::find()
            ->where(['fixture_id' => $id])
            ->orderBy(['minute' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $state = MatchState::findOne(['fixture_id' => $id]);
        if ($state) {
            $state->phase = strtolower($state->phase); // Go worker may write uppercase
        }

        $userSide = null;
        /** @var \app\models\User|null $identity */
        $identity = Yii::$app->user->identity;
        $isAdmin  = $identity && $identity->isAdmin();
        if (!$isAdmin && !Yii::$app->user->isGuest) {
            $userTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
            if ($userTeam) {
                if ((int)$fixture->home_team_id === (int)$userTeam->id) {
                    $userSide = 'home';
                } elseif ((int)$fixture->away_team_id === (int)$userTeam->id) {
                    $userSide = 'away';
                }
            }
        }

        return $this->render('replay', [
            'fixture' => $fixture,
            'existingEvents'  => $existingEvents,
            'state'          => $state,
            'homeStrength'   => FixtureViewHelper::computeStrength($fixture->homeTeam),
            'awayStrength'   => FixtureViewHelper::computeStrength($fixture->awayTeam),
            'userSide'       => $userSide,
        ]);
    }

    /**
     * API: Get latest events for polling.
     */
    public function actionGetEvents(int $fixtureId, int $lastEventId = 0): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $events = MatchEvent::find()
            ->where(['fixture_id' => $fixtureId])
            ->andWhere(['>', 'id', $lastEventId])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $state = MatchState::findOne(['fixture_id' => $fixtureId]);

        return $this->asJson([
            'success' => true,
            'events'  => array_map([$this, 'serializeEvent'], $events),
            'state' => $state ? [
                'minute' => $state->current_minute,
                'phase' => $state->phase,
                'home_score' => $state->home_score,
                'away_score' => $state->away_score,
            ] : null,
        ]);
    }

    /**
     * API: Get refreshed descriptions for events already visible in the live page.
     */
    public function actionGetEventUpdates(int $fixtureId, string $ids = ''): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $eventIds = array_values(array_filter(array_unique(array_map(
            static fn($id) => (int) trim($id),
            explode(',', $ids)
        ))));

        if (empty($eventIds)) {
            return $this->asJson(['success' => true, 'events' => []]);
        }

        $events = MatchEvent::find()
            ->where(['fixture_id' => $fixtureId, 'id' => $eventIds])
            ->orderBy(['id' => SORT_ASC])
            ->all();

        return $this->asJson([
            'success' => true,
            'events'  => array_map([$this, 'serializeEvent'], $events),
        ]);
    }

    public function actionFormations(int $fixtureId): Response
    {
        $fixture = \app\models\Fixture::findOne($fixtureId);
        if (!$fixture) return $this->asJson(['success' => false]);

        $state = \app\models\MatchState::findOne(['fixture_id' => $fixtureId]);

        // Substituted player IDs from match_event
        $subEvents = \app\models\MatchEvent::find()
            ->where(['fixture_id' => $fixtureId, 'type' => 'substitution'])
            ->all();
        $subbedOff = ['home' => [], 'away' => []];
        foreach ($subEvents as $ev) {
            $d = $ev->detail ? (json_decode($ev->detail, true) ?? []) : [];
            if (!empty($d['out'])) $subbedOff[$ev->team_side][] = (int)$d['out'];
        }

        $buildSide = function(string $side) use ($fixture, $state, $subbedOff) {
            $formId = $side === 'home'
                ? $state?->home_formation_id
                : $state?->away_formation_id;
            $team = $side === 'home' ? $fixture->homeTeam : $fixture->awayTeam;

            $formation = $formId
                ? \app\models\Formation::findOne($formId)
                : \app\models\Formation::findOne(['team_id' => $team->id, 'is_active' => 1]);

            $module = '?';
            if ($formation && preg_match('/(\d-\d-\d(?:-\d)?)/', (string)$formation->name, $m)) {
                $module = $m[1];
            }

            $captainId = $formation ? (int)($formation->captain_player_id ?? 0) : 0;

            $players = [];
            if ($formation) {
                $slots = \app\models\FormationSlot::find()
                    ->with('player')
                    ->where(['formation_id' => $formation->id])
                    ->andWhere(new Expression(PitchZoneHelper::onPitchSql('zone')))
                    ->all();
                foreach ($slots as $slot) {
                    if (!$slot->player) continue;
                    $p = $slot->player;
                    $zone = PitchZoneHelper::normalizeToCurrent((int) $slot->zone);
                    if (!PitchZoneHelper::isCurrentZone($zone)) {
                        continue;
                    }
                    $players[] = [
                        'zone'       => $zone,
                        'player_id'  => (int)$p->id,
                        'name'       => $p->name,
                        'number'     => (int)$p->number,
                        'position'   => $p->position,
                        'subbed_off' => in_array((int)$p->id, $subbedOff[$side], true),
                        'is_captain' => (int)$p->id === $captainId,
                        'injured'    => (int)$p->injury_weeks > 0,
                    ];
                }
            }

            return [
                'team_name'   => $team->name ?? '?',
                'module'      => $module,
                'color_left'  => $team->color_left  ?? '#2563eb',
                'color_right' => $team->color_right ?? '#1e40af',
                'players'     => $players,
            ];
        };

        return $this->asJson([
            'success' => true,
            'home'    => $buildSide('home'),
            'away'    => $buildSide('away'),
        ]);
    }

    public function actionGetScorers(int $fixtureId, int $upToMinute = 999): Response
    {
        return $this->asJson([
            'success' => true,
            'sheet'   => \app\components\ScorerSheetService::buildForFixture($fixtureId, $upToMinute),
        ]);
    }

    private function serializeEvent(MatchEvent $event): array
    {
        $detail = $event->detail ? (json_decode($event->detail, true) ?? []) : [];
        $scorerName = isset($detail['scorer_name']) ? (string) $detail['scorer_name'] : null;
        if (($scorerName === null || $scorerName === '') && (int) $event->player_id > 0) {
            $scorerName = (string) (\app\models\Player::find()
                ->select('name')
                ->where(['id' => (int) $event->player_id])
                ->scalar() ?? '');
        }

        return [
            'id'            => $event->id,
            'minute'        => $event->minute,
            'type'          => $event->type,
            'team_side'     => $event->team_side,
            'detail'        => $event->detail,
            'suspense_text' => $detail['suspense_text'] ?? null,
            'description'   => $detail['description'] ?? null,
            'home_score'    => $detail['home_score'] ?? ($detail['home'] ?? null),
            'away_score'    => $detail['away_score'] ?? ($detail['away'] ?? null),
            'scorer_name'   => $scorerName ?: null,
            'out_name'      => $detail['out_name'] ?? null,
            'in_name'       => $detail['in_name'] ?? null,
            'player_name'   => $detail['player_name'] ?? null,
            'injury_type'   => $detail['injury_type'] ?? null,
            'tactic'        => $detail['tactic'] ?? null,
            'marking'       => $detail['marking'] ?? null,
            'offside_trap'  => $detail['offside_trap'] ?? null,
            'trained_tactic'=> $detail['trained_tactic'] ?? null,
            'weather'       => $detail['weather'] ?? null,
            'field_condition' => $detail['field_condition'] ?? null,
            'spectators'    => $detail['spectators'] ?? null,
        ];
    }
}
