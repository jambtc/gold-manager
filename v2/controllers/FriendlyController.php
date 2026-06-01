<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\FriendlyChallengeService;
use app\components\MultiplayerSyncService;
use app\components\NewsService;
use app\components\NotificationService;
use app\components\OrchestratorEventService;
use app\models\Fixture;
use app\models\FriendlyChallenge;
use app\models\NewsItem;
use app\models\Standing;
use app\models\Team;
use Yii;
use yii\db\Expression;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class FriendlyController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'play' => ['post'],
                    'challenge' => ['post'],
                    'respond' => ['post'],
                    'start-now' => ['post'],
                ],
            ],
        ];
    }

    public function actionIndex(): string|Response
    {
        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$myTeam) {
            return $this->redirect(['/site/index']);
        }

        FriendlyChallengeService::expirePendingChallenges();
        FriendlyChallengeService::syncPlayedStatus();

        $teams = Team::find()
            ->where(['<>', 'id', $myTeam->id])
            ->with('players')
            ->orderBy(['is_cpu' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $proposedAt = FriendlyChallengeService::nextFriendlySlot();
        $myBusyThisWeek = FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $myTeam->id, $proposedAt);

        $teamData = [];
        foreach ($teams as $team) {
            $standing = Standing::find()->with('competition')->where(['team_id' => $team->id])->one();
            $players = $team->players;
            usort($players, static fn($a, $b) => $b->general_skill <=> $a->general_skill);
            $best11 = array_slice($players, 0, 11);

            $dept = ['GK' => [], 'DF' => [], 'MF' => [], 'FW' => []];
            foreach ($best11 as $p) {
                $dept[$p->position][] = $p->general_skill;
            }
            $avg = static fn(array $arr) => count($arr) ? (int) round(array_sum($arr) / count($arr)) : 0;
            $overall = count($best11) ? (int) round(array_sum(array_column($best11, 'general_skill')) / count($best11)) : 0;

            $busy = FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $team->id, $proposedAt);
            $avgFreshness = FriendlyChallengeService::averageFreshness($team);

            $availability = 'available';
            $availabilityLabel = 'Disponibile';
            if ($busy) {
                $availability = 'blocked';
                $availabilityLabel = 'Non disponibile';
            } elseif ($team->is_cpu && $avgFreshness < 75) {
                $availability = 'warn';
                $availabilityLabel = 'Potrebbe rifiutare';
            }

            $teamData[$team->id] = [
                'team' => $team,
                'league' => $standing ? $standing->competition->getLabel() : '—',
                'tier' => $standing ? (int) $standing->competition->tier : 3,
                'overall' => $overall,
                'gk' => $avg($dept['GK']),
                'def' => $avg($dept['DF']),
                'mid' => $avg($dept['MF']),
                'att' => $avg($dept['FW']),
                'availability' => $availability,
                'availabilityLabel' => $availabilityLabel,
                'avgFreshness' => round($avgFreshness, 1),
                'canChallenge' => !$myBusyThisWeek && !$busy,
            ];
        }

        uasort($teamData, static fn($a, $b) => $b['overall'] <=> $a['overall']);

        $incoming = FriendlyChallenge::find()
            ->with(['challenger', 'challenged'])
            ->where([
                'challenged_id' => $myTeam->id,
                'status' => FriendlyChallenge::STATUS_PENDING,
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $outgoing = FriendlyChallenge::find()
            ->with(['challenger', 'challenged'])
            ->where([
                'challenger_id' => $myTeam->id,
                'status' => FriendlyChallenge::STATUS_PENDING,
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->all();

        $history = FriendlyChallenge::find()
            ->with(['challenger', 'challenged', 'fixture'])
            ->where([
                'or',
                ['challenger_id' => $myTeam->id],
                ['challenged_id' => $myTeam->id],
            ])
            ->andWhere(['in', 'status', [
                FriendlyChallenge::STATUS_ACCEPTED,
                FriendlyChallenge::STATUS_DECLINED,
                FriendlyChallenge::STATUS_EXPIRED,
                FriendlyChallenge::STATUS_PLAYED,
            ]])
            ->orderBy([new Expression('COALESCE(responded_at, created_at) DESC'), 'id' => SORT_DESC])
            ->limit(40)
            ->all();

        $myPlayers = $myTeam->players;
        usort($myPlayers, static fn($a, $b) => $b->general_skill <=> $a->general_skill);
        $myBest11 = array_slice($myPlayers, 0, 11);
        $myOverall = count($myBest11) ? (int) round(array_sum(array_column($myBest11, 'general_skill')) / count($myBest11)) : 0;
        $myStadium = \app\models\Stadium::findOne(['team_id' => $myTeam->id]);

        return $this->render('index', [
            'myTeam' => $myTeam,
            'myOverall' => $myOverall,
            'teamData' => $teamData,
            'myStadium' => $myStadium,
            'incomingChallenges' => $incoming,
            'outgoingChallenges' => $outgoing,
            'historyChallenges' => $history,
            'proposedAt' => $proposedAt,
            'myBusyThisWeek' => $myBusyThisWeek,
            'canStartFriendlyNow' => $this->canStartFriendlyNow(),
        ]);
    }

    /**
     * Legacy endpoint kept for compatibility: now forwards to challenge flow.
     */
    public function actionPlay(): Response
    {
        return $this->actionChallenge();
    }

    public function actionChallenge(): Response
    {
        if (!MultiplayerSyncService::ensureOnce('friendly.challenge', 20)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Request already processed.'));
            return $this->redirect(['/friendly/index']);
        }

        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$myTeam) {
            return $this->redirect(['/site/index']);
        }

        FriendlyChallengeService::expirePendingChallenges();
        $targetId = (int) Yii::$app->request->post('away_team_id', Yii::$app->request->post('challenged_id', 0));
        if ($targetId <= 0 || $targetId === (int) $myTeam->id) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Select a valid opponent.'));
            return $this->redirect(['/friendly/index']);
        }

        $target = Team::find()->with('players')->where(['id' => $targetId])->one();
        if (!$target) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Opponent not found.'));
            return $this->redirect(['/friendly/index']);
        }

        $lockScope = 'friendly.challenge.' . min((int) $myTeam->id, (int) $target->id) . '.' . max((int) $myTeam->id, (int) $target->id);
        $lockToken = MultiplayerSyncService::acquireLock($lockScope, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Concurrent operation in progress. Please retry.'));
            return $this->redirect(['/friendly/index']);
        }
        try {

        $proposedAt = FriendlyChallengeService::nextFriendlySlot();
        if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $myTeam->id, $proposedAt)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'You already have a challenge/friendly this week.'));
            return $this->redirect(['/friendly/index']);
        }
        if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $target->id, $proposedAt)) {
            Yii::$app->session->setFlash('error', Yii::t('app', '{name} is not available this week.', ['{name}' => $target->name]));
            return $this->redirect(['/friendly/index']);
        }

        $challenge = new FriendlyChallenge();
        $challenge->challenger_id = (int) $myTeam->id;
        $challenge->challenged_id = (int) $target->id;
        $challenge->status = FriendlyChallenge::STATUS_PENDING;
        $challenge->proposed_at = $proposedAt;
        $challenge->created_at = time();
        $challenge->request_id = MultiplayerSyncService::currentRequestId('friendly.challenge');
        $challenge->request_source = MultiplayerSyncService::currentRequestSource();
        $challenge->save(false);

        // Human manager target: wait for response.
        if (!$target->is_cpu && $target->user_id) {
            $challengeBody = sprintf(Yii::t('app', '%s challenges you for %s.'), $myTeam->name, date('d/m H:i', $proposedAt));
            NotificationService::notify(
                (int) $target->user_id, NewsItem::CAT_FRIENDLY, 'I',
                Yii::t('app', 'Friendly challenge received'),
                $challengeBody,
                Yii::$app->urlManager->createUrl(['/friendly/index']), 1,
                "📩 <b>" . Yii::t('app', 'Friendly challenge received') . "</b>\n{$challengeBody}"
            );

            Yii::$app->session->setFlash('success', Yii::t('app', 'Challenge sent to {name}. Awaiting response.', ['{name}' => $target->name]));
            return $this->redirect(['/friendly/index']);
        }

        // CPU decision immediate.
        $avgFreshness = FriendlyChallengeService::averageFreshness($target);
        $accepted = false;
        $reason = null;
        if ($avgFreshness < 70) {
            $reason = Yii::t('app', 'Players of {name} are too fatigued.', ['{name}' => $target->name]);
        } else {
            $accepted = random_int(1, 100) <= 80;
            if (!$accepted) {
                $reason = Yii::t('app', '{name} preferred to rest this week.', ['{name}' => $target->name]);
            }
        }

        if ($accepted) {
            $fixture = FriendlyChallengeService::createFixtureForChallenge($challenge);
            $challenge->status = FriendlyChallenge::STATUS_ACCEPTED;
            $challenge->fixture_id = (int) $fixture->id;
            $challenge->responded_at = time();
            $challenge->request_id = MultiplayerSyncService::currentRequestId('friendly.challenge.accept-cpu');
            $challenge->request_source = MultiplayerSyncService::currentRequestSource();
            $challenge->save(false, ['status', 'fixture_id', 'responded_at', 'request_id', 'request_source']);

            if ($myTeam->user_id) {
                $acceptBody = sprintf(Yii::t('app', 'Match scheduled for %s.'), date('d/m H:i', $proposedAt));
                $acceptTitle = Yii::t('app', '{name} accepted the friendly', ['{name}' => $target->name]);
                NotificationService::notify(
                    (int) $myTeam->user_id, NewsItem::CAT_FRIENDLY, '+',
                    $acceptTitle, $acceptBody,
                    Yii::$app->urlManager->createUrl(['/fixture/live', 'id' => $fixture->id]), 1,
                    "📩 <b>{$acceptTitle}</b>\n{$acceptBody}"
                );
            }

            Yii::$app->session->setFlash('success', Yii::t('app', '{name} accepted. Friendly scheduled.', ['{name}' => $target->name]));
        } else {
            $challenge->status = FriendlyChallenge::STATUS_DECLINED;
            $challenge->decline_reason = $reason;
            $challenge->responded_at = time();
            $challenge->request_id = MultiplayerSyncService::currentRequestId('friendly.challenge.decline-cpu');
            $challenge->request_source = MultiplayerSyncService::currentRequestSource();
            $challenge->save(false, ['status', 'decline_reason', 'responded_at', 'request_id', 'request_source']);

            if ($myTeam->user_id) {
                $declineTitle = Yii::t('app', '{name} declined the friendly', ['{name}' => $target->name]);
                $declineBody  = $reason ?: Yii::t('app', 'Challenge rejected.');
                NotificationService::notify(
                    (int) $myTeam->user_id, NewsItem::CAT_FRIENDLY, '-',
                    $declineTitle, $declineBody,
                    Yii::$app->urlManager->createUrl(['/friendly/index']), 0,
                    "📩 <b>{$declineTitle}</b>\n{$declineBody}"
                );
            }

            Yii::$app->session->setFlash('warning', Yii::t('app', '{name} declined the challenge.', ['{name}' => $target->name]));
        }

        return $this->redirect(['/friendly/index']);
        } finally {
            MultiplayerSyncService::releaseLock($lockScope, $lockToken);
        }
    }

    public function actionRespond(int $id, string $decision): Response
    {
        if (!MultiplayerSyncService::ensureOnce('friendly.respond.' . $id . '.' . strtolower($decision), 20)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Response already processed.'));
            return $this->redirect(['/friendly/index']);
        }

        $lockToken = MultiplayerSyncService::acquireLock('friendly.challenge.' . $id, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Concurrent operation in progress. Please retry.'));
            return $this->redirect(['/friendly/index']);
        }
        try {

        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$myTeam) {
            return $this->redirect(['/site/index']);
        }

        FriendlyChallengeService::expirePendingChallenges();

        $challenge = FriendlyChallenge::find()
            ->with(['challenger', 'challenged'])
            ->where([
                'id' => $id,
                'challenged_id' => $myTeam->id,
                'status' => FriendlyChallenge::STATUS_PENDING,
            ])
            ->one();

        if (!$challenge) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Challenge not available.'));
            return $this->redirect(['/friendly/index']);
        }

        $decision = strtolower($decision);
        if (!in_array($decision, ['accept', 'decline'], true)) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Invalid decision.'));
            return $this->redirect(['/friendly/index']);
        }

        if ($decision === 'accept') {
            if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $myTeam->id, (int) $challenge->proposed_at, (int) $challenge->id)
                || FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $challenge->challenger_id, (int) $challenge->proposed_at, (int) $challenge->id)) {
                $challenge->status = FriendlyChallenge::STATUS_DECLINED;
                $challenge->decline_reason = Yii::t('app', 'Weekly slot no longer available for one of the two teams.');
                $challenge->responded_at = time();
                $challenge->request_id = MultiplayerSyncService::currentRequestId('friendly.respond.' . $id . '.auto-decline');
                $challenge->request_source = MultiplayerSyncService::currentRequestSource();
                $challenge->save(false, ['status', 'decline_reason', 'responded_at', 'request_id', 'request_source']);

                if ($challenge->challenger && $challenge->challenger->user_id) {
                    NotificationService::notify(
                        (int) $challenge->challenger->user_id, NewsItem::CAT_FRIENDLY, '-',
                        Yii::t('app', 'Friendly challenge cancelled'),
                        (string) $challenge->decline_reason,
                        Yii::$app->urlManager->createUrl(['/friendly/index']), 0,
                        "📩 <b>" . Yii::t('app', 'Friendly challenge cancelled') . "</b>\n{$challenge->decline_reason}"
                    );
                }

                Yii::$app->session->setFlash('warning', Yii::t('app', 'Cannot accept: weekly slot no longer available.'));
                return $this->redirect(['/friendly/index']);
            }

            $fixture = FriendlyChallengeService::createFixtureForChallenge($challenge);
            $challenge->status = FriendlyChallenge::STATUS_ACCEPTED;
            $challenge->fixture_id = (int) $fixture->id;
            $challenge->responded_at = time();
            $challenge->request_id = MultiplayerSyncService::currentRequestId('friendly.respond.' . $id . '.accept');
            $challenge->request_source = MultiplayerSyncService::currentRequestSource();
            $challenge->save(false, ['status', 'fixture_id', 'responded_at', 'request_id', 'request_source']);

            if ($challenge->challenger && $challenge->challenger->user_id) {
                $t = Yii::t('app', '{name} accepted the friendly', ['{name}' => $myTeam->name]);
                $b = sprintf(Yii::t('app', 'Match scheduled for %s.'), date('d/m H:i', (int) $challenge->proposed_at));
                NotificationService::notify(
                    (int) $challenge->challenger->user_id, NewsItem::CAT_FRIENDLY, '+',
                    $t, $b, Yii::$app->urlManager->createUrl(['/fixture/live', 'id' => $fixture->id]), 1,
                    "📩 <b>{$t}</b>\n{$b}"
                );
            }

            if ($myTeam->user_id) {
                $t = Yii::t('app', 'You accepted the friendly challenge');
                $b = sprintf(Yii::t('app', 'Match scheduled for %s.'), date('d/m H:i', (int) $challenge->proposed_at));
                NotificationService::notify(
                    (int) $myTeam->user_id, NewsItem::CAT_FRIENDLY, '+',
                    $t, $b, Yii::$app->urlManager->createUrl(['/fixture/live', 'id' => $fixture->id]), 0,
                    "📩 <b>{$t}</b>\n{$b}"
                );
            }

            Yii::$app->session->setFlash('success', Yii::t('app', 'Challenge accepted.'));
            return $this->redirect(['/friendly/index']);
        }

        $challenge->status = FriendlyChallenge::STATUS_DECLINED;
        $challenge->decline_reason = Yii::t('app', 'Invitation rejected');
        $challenge->responded_at = time();
        $challenge->request_id = MultiplayerSyncService::currentRequestId('friendly.respond.' . $id . '.decline');
        $challenge->request_source = MultiplayerSyncService::currentRequestSource();
        $challenge->save(false, ['status', 'decline_reason', 'responded_at', 'request_id', 'request_source']);

        if ($challenge->challenger && $challenge->challenger->user_id) {
            $t = Yii::t('app', '{name} declined your challenge', ['{name}' => $myTeam->name]);
            $b = Yii::t('app', 'Invitation rejected.');
            NotificationService::notify(
                (int) $challenge->challenger->user_id, NewsItem::CAT_FRIENDLY, '-',
                $t, $b, Yii::$app->urlManager->createUrl(['/friendly/index']), 0,
                "📩 <b>{$t}</b>\n{$b}"
            );
        }

        Yii::$app->session->setFlash('info', Yii::t('app', 'Challenge rejected.'));
        return $this->redirect(['/friendly/index']);
        } finally {
            MultiplayerSyncService::releaseLock('friendly.challenge.' . $id, $lockToken);
        }
    }

    public function actionStartNow(int $id): Response
    {
        $lockToken = MultiplayerSyncService::acquireLock('friendly.start-now.' . $id, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Concurrent operation in progress. Please retry.'));
            return $this->redirect(['/friendly/index']);
        }
        try {

        if (!$this->canStartFriendlyNow()) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Feature available in test environment only.'));
            return $this->redirect(['/friendly/index']);
        }

        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$myTeam) {
            return $this->redirect(['/site/index']);
        }

        $challenge = FriendlyChallenge::find()
            ->with(['fixture.competition'])
            ->where([
                'id' => $id,
                'status' => FriendlyChallenge::STATUS_ACCEPTED,
            ])
            ->andWhere([
                'or',
                ['challenger_id' => (int) $myTeam->id],
                ['challenged_id' => (int) $myTeam->id],
            ])
            ->one();

        if (!$challenge || !$challenge->fixture) {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Friendly not found.'));
            return $this->redirect(['/friendly/index']);
        }

        $fixture = $challenge->fixture;
        if ($fixture->competition?->type !== 'friendly') {
            Yii::$app->session->setFlash('error', Yii::t('app', 'Operation allowed for friendlies only.'));
            return $this->redirect(['/friendly/index']);
        }

        if ((int) $fixture->status === Fixture::STATUS_FINISHED) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Match already finished.'));
            return $this->redirect(['/fixture/replay', 'id' => $fixture->id]);
        }

        if (!in_array((int) $fixture->status, [Fixture::STATUS_SCHEDULED, Fixture::STATUS_PLAYING], true)) {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Match cannot be started.'));
            return $this->redirect(['/fixture/live', 'id' => $fixture->id]);
        }

        if (OrchestratorEventService::isEnabled()) {
            OrchestratorEventService::enqueue(
                'friendly.start_now',
                'friendly_challenge',
                (int) $challenge->id,
                [
                    'challenge_id' => (int) $challenge->id,
                    'requested_by_team_id' => (int) $myTeam->id,
                ]
            );
            $prematchSeconds = $this->preMatchSeconds();
            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'Start queued. Kickoff in approximately {seconds} seconds.', ['{seconds}' => $prematchSeconds])
            );
            return $this->redirect(['/fixture/live', 'id' => $fixture->id]);
        }

        $result = FriendlyChallengeService::forceStartNow((int) $challenge->id);
        if ($result['ok']) {
            $prematchSeconds = $this->preMatchSeconds();
            Yii::$app->session->setFlash(
                'success',
                Yii::t('app', 'Forced pre-match started. Kickoff in approximately {seconds} seconds.', ['{seconds}' => $prematchSeconds])
            );
        } else {
            Yii::$app->session->setFlash('info', Yii::t('app', 'Match cannot be started.'));
        }

        return $this->redirect(['/fixture/live', 'id' => $fixture->id]);
        } finally {
            MultiplayerSyncService::releaseLock('friendly.start-now.' . $id, $lockToken);
        }
    }

    private function canStartFriendlyNow(): bool
    {
        $raw = getenv('GM_ENABLE_FRIENDLY_START_NOW');
        if ($raw === false || $raw === '') {
            return YII_ENV_DEV;
        }
        return in_array(strtolower((string) $raw), ['1', 'true', 'yes', 'on'], true);
    }

    private function preMatchSeconds(): int
    {
        $raw = getenv('GM_PREMATCH_SECONDS');
        if ($raw === false || $raw === '') {
            return 15;
        }
        $value = (int) $raw;
        return $value >= 0 ? $value : 15;
    }

}
