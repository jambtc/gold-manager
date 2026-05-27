<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\FriendlyChallengeService;
use app\components\MultiplayerSyncService;
use app\components\NewsService;
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
            Yii::$app->session->setFlash('info', 'Richiesta già elaborata.');
            return $this->redirect(['/friendly/index']);
        }

        $myTeam = Team::findOne(['user_id' => Yii::$app->user->id]);
        if (!$myTeam) {
            return $this->redirect(['/site/index']);
        }

        FriendlyChallengeService::expirePendingChallenges();
        $targetId = (int) Yii::$app->request->post('away_team_id', Yii::$app->request->post('challenged_id', 0));
        if ($targetId <= 0 || $targetId === (int) $myTeam->id) {
            Yii::$app->session->setFlash('error', 'Seleziona un avversario valido.');
            return $this->redirect(['/friendly/index']);
        }

        $target = Team::find()->with('players')->where(['id' => $targetId])->one();
        if (!$target) {
            Yii::$app->session->setFlash('error', 'Avversario non trovato.');
            return $this->redirect(['/friendly/index']);
        }

        $lockScope = 'friendly.challenge.' . min((int) $myTeam->id, (int) $target->id) . '.' . max((int) $myTeam->id, (int) $target->id);
        $lockToken = MultiplayerSyncService::acquireLock($lockScope, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', 'Operazione concorrente in corso. Riprova.');
            return $this->redirect(['/friendly/index']);
        }
        try {

        $proposedAt = FriendlyChallengeService::nextFriendlySlot();
        if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $myTeam->id, $proposedAt)) {
            Yii::$app->session->setFlash('error', 'Hai già un’amichevole o una sfida attiva in questa settimana.');
            return $this->redirect(['/friendly/index']);
        }
        if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $target->id, $proposedAt)) {
            Yii::$app->session->setFlash('error', "{$target->name} non è disponibile in questa settimana.");
            return $this->redirect(['/friendly/index']);
        }

        $challenge = new FriendlyChallenge();
        $challenge->challenger_id = (int) $myTeam->id;
        $challenge->challenged_id = (int) $target->id;
        $challenge->status = FriendlyChallenge::STATUS_PENDING;
        $challenge->proposed_at = $proposedAt;
        $challenge->created_at = time();
        $challenge->save(false);

        // Human manager target: wait for response.
        if (!$target->is_cpu && $target->user_id) {
            NewsService::create(
                (int) $target->user_id,
                NewsItem::CAT_FRIENDLY,
                'I',
                'Invito amichevole ricevuto',
                sprintf(
                    '%s ti sfida per %s.',
                    $myTeam->name,
                    date('d/m H:i', $proposedAt)
                ),
                Yii::$app->urlManager->createUrl(['/friendly/index']),
                1
            );

            Yii::$app->session->setFlash('success', "Sfida inviata a {$target->name}. In attesa di risposta.");
            return $this->redirect(['/friendly/index']);
        }

        // CPU decision immediate.
        $avgFreshness = FriendlyChallengeService::averageFreshness($target);
        $accepted = false;
        $reason = null;
        if ($avgFreshness < 70) {
            $reason = "I giocatori di {$target->name} sono troppo affaticati";
        } else {
            $accepted = random_int(1, 100) <= 80;
            if (!$accepted) {
                $reason = "{$target->name} ha preferito riposarsi questa settimana";
            }
        }

        if ($accepted) {
            $fixture = FriendlyChallengeService::createFixtureForChallenge($challenge);
            $challenge->status = FriendlyChallenge::STATUS_ACCEPTED;
            $challenge->fixture_id = (int) $fixture->id;
            $challenge->responded_at = time();
            $challenge->save(false, ['status', 'fixture_id', 'responded_at']);

            if ($myTeam->user_id) {
                NewsService::create(
                    (int) $myTeam->user_id,
                    NewsItem::CAT_FRIENDLY,
                    '+',
                    "{$target->name} ha accettato l'amichevole",
                    sprintf('Partita programmata per %s.', date('d/m H:i', $proposedAt)),
                    Yii::$app->urlManager->createUrl(['/fixture/live', 'id' => $fixture->id]),
                    1
                );
            }

            Yii::$app->session->setFlash('success', "{$target->name} ha accettato. Amichevole programmata.");
        } else {
            $challenge->status = FriendlyChallenge::STATUS_DECLINED;
            $challenge->decline_reason = $reason;
            $challenge->responded_at = time();
            $challenge->save(false, ['status', 'decline_reason', 'responded_at']);

            if ($myTeam->user_id) {
                NewsService::create(
                    (int) $myTeam->user_id,
                    NewsItem::CAT_FRIENDLY,
                    '-',
                    "{$target->name} ha declinato l'amichevole",
                    $reason ?: 'Sfida rifiutata.',
                    Yii::$app->urlManager->createUrl(['/friendly/index'])
                );
            }

            Yii::$app->session->setFlash('warning', "{$target->name} ha rifiutato la sfida.");
        }

        return $this->redirect(['/friendly/index']);
        } finally {
            MultiplayerSyncService::releaseLock($lockScope, $lockToken);
        }
    }

    public function actionRespond(int $id, string $decision): Response
    {
        if (!MultiplayerSyncService::ensureOnce('friendly.respond.' . $id . '.' . strtolower($decision), 20)) {
            Yii::$app->session->setFlash('info', 'Risposta già elaborata.');
            return $this->redirect(['/friendly/index']);
        }

        $lockToken = MultiplayerSyncService::acquireLock('friendly.challenge.' . $id, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', 'Operazione concorrente in corso. Riprova.');
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
            Yii::$app->session->setFlash('error', 'Sfida non disponibile.');
            return $this->redirect(['/friendly/index']);
        }

        $decision = strtolower($decision);
        if (!in_array($decision, ['accept', 'decline'], true)) {
            Yii::$app->session->setFlash('error', 'Decisione non valida.');
            return $this->redirect(['/friendly/index']);
        }

        if ($decision === 'accept') {
            if (FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $myTeam->id, (int) $challenge->proposed_at, (int) $challenge->id)
                || FriendlyChallengeService::hasWeeklyFriendlyCommitment((int) $challenge->challenger_id, (int) $challenge->proposed_at, (int) $challenge->id)) {
                $challenge->status = FriendlyChallenge::STATUS_DECLINED;
                $challenge->decline_reason = 'Slot non più disponibile per una delle due squadre';
                $challenge->responded_at = time();
                $challenge->save(false, ['status', 'decline_reason', 'responded_at']);

                if ($challenge->challenger && $challenge->challenger->user_id) {
                    NewsService::create(
                        (int) $challenge->challenger->user_id,
                        NewsItem::CAT_FRIENDLY,
                        '-',
                        'Sfida amichevole annullata',
                        $challenge->decline_reason,
                        Yii::$app->urlManager->createUrl(['/friendly/index'])
                    );
                }

                Yii::$app->session->setFlash('warning', 'Impossibile accettare: slot settimana non più disponibile.');
                return $this->redirect(['/friendly/index']);
            }

            $fixture = FriendlyChallengeService::createFixtureForChallenge($challenge);
            $challenge->status = FriendlyChallenge::STATUS_ACCEPTED;
            $challenge->fixture_id = (int) $fixture->id;
            $challenge->responded_at = time();
            $challenge->save(false, ['status', 'fixture_id', 'responded_at']);

            if ($challenge->challenger && $challenge->challenger->user_id) {
                NewsService::create(
                    (int) $challenge->challenger->user_id,
                    NewsItem::CAT_FRIENDLY,
                    '+',
                    "{$myTeam->name} ha accettato l'amichevole",
                    sprintf('Partita programmata per %s.', date('d/m H:i', (int) $challenge->proposed_at)),
                    Yii::$app->urlManager->createUrl(['/fixture/live', 'id' => $fixture->id]),
                    1
                );
            }

            if ($myTeam->user_id) {
                NewsService::create(
                    (int) $myTeam->user_id,
                    NewsItem::CAT_FRIENDLY,
                    '+',
                    'Hai accettato la sfida amichevole',
                    sprintf('Partita programmata per %s.', date('d/m H:i', (int) $challenge->proposed_at)),
                    Yii::$app->urlManager->createUrl(['/fixture/live', 'id' => $fixture->id])
                );
            }

            Yii::$app->session->setFlash('success', 'Sfida accettata.');
            return $this->redirect(['/friendly/index']);
        }

        $challenge->status = FriendlyChallenge::STATUS_DECLINED;
        $challenge->decline_reason = 'Invito rifiutato';
        $challenge->responded_at = time();
        $challenge->save(false, ['status', 'decline_reason', 'responded_at']);

        if ($challenge->challenger && $challenge->challenger->user_id) {
            NewsService::create(
                (int) $challenge->challenger->user_id,
                NewsItem::CAT_FRIENDLY,
                '-',
                "{$myTeam->name} ha declinato la tua sfida",
                'Invito rifiutato.',
                Yii::$app->urlManager->createUrl(['/friendly/index'])
            );
        }

        Yii::$app->session->setFlash('info', 'Sfida rifiutata.');
        return $this->redirect(['/friendly/index']);
        } finally {
            MultiplayerSyncService::releaseLock('friendly.challenge.' . $id, $lockToken);
        }
    }

    public function actionStartNow(int $id): Response
    {
        if (!MultiplayerSyncService::ensureOnce('friendly.start-now.' . $id, 20)) {
            Yii::$app->session->setFlash('info', 'Richiesta già elaborata.');
            return $this->redirect(['/friendly/index']);
        }

        $lockToken = MultiplayerSyncService::acquireLock('friendly.start-now.' . $id, 15);
        if ($lockToken === null) {
            Yii::$app->session->setFlash('warning', 'Operazione concorrente in corso. Riprova.');
            return $this->redirect(['/friendly/index']);
        }
        try {

        if (!$this->canStartFriendlyNow()) {
            Yii::$app->session->setFlash('error', 'Funzione disponibile solo in ambiente test.');
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
            Yii::$app->session->setFlash('error', 'Amichevole non trovata.');
            return $this->redirect(['/friendly/index']);
        }

        $fixture = $challenge->fixture;
        if ($fixture->competition?->type !== 'friendly') {
            Yii::$app->session->setFlash('error', 'Operazione consentita solo per amichevoli.');
            return $this->redirect(['/friendly/index']);
        }

        if ((int) $fixture->status === Fixture::STATUS_FINISHED) {
            Yii::$app->session->setFlash('warning', 'Partita già terminata.');
            return $this->redirect(['/fixture/replay', 'id' => $fixture->id]);
        }

        if ((int) $fixture->status === Fixture::STATUS_SCHEDULED) {
            $fixture->match_date = time() - 5;
            $fixture->status = Fixture::STATUS_PLAYING;
            $fixture->save(false, ['match_date', 'status']);
            Yii::$app->session->setFlash('success', 'Kickoff forzato: partita avviata.');
        } else {
            Yii::$app->session->setFlash('info', 'Partita già in corso.');
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
}
