<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Fixture $fixture */
/** @var app\models\MatchState|null $state */
/** @var app\models\Team|null $userTeam */
/** @var bool $isAdmin */
/** @var app\models\MatchEvent[] $existingEvents */
/** @var int $lastEventId */
/** @var array $homeStrength  po/def/mid/att/ovr */
/** @var array $awayStrength  po/def/mid/att/ovr */

use yii\helpers\Html;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\View;
use app\assets\FixtureLiveAsset;
use app\components\CommentaryTemplateService;
use app\components\FixtureViewHelper;

$this->title = $fixture->homeTeam->name . ' vs ' . $fixture->awayTeam->name;
$this->params['breadcrumbs'][] = ['label' => 'Calendario', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Live';
$isDevLiveUi = YII_ENV_DEV;
FixtureLiveAsset::register($this);

$this->registerJsFile('https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js');
?>

<?php
$homeColorLeft = \app\models\Team::sanitizeHexColor($fixture->homeTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
$homeColorRight = \app\models\Team::sanitizeHexColor($fixture->homeTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
$awayColorLeft = \app\models\Team::sanitizeHexColor($fixture->awayTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
$awayColorRight = \app\models\Team::sanitizeHexColor($fixture->awayTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
$homeLetter = mb_substr($fixture->homeTeam->name, 0, 1);
$awayLetter = mb_substr($fixture->awayTeam->name, 0, 1);
$initialWeather = null;
$initialFieldCondition = null;
$initialSpectators = null;
foreach ($existingEvents as $seedEvent) {
    if ($seedEvent->type !== 'pre_match' || !$seedEvent->detail) {
        continue;
    }
    $seedDetail = json_decode((string) $seedEvent->detail, true) ?: [];
    $initialWeather = isset($seedDetail['weather']) ? (string) $seedDetail['weather'] : null;
    $initialFieldCondition = isset($seedDetail['field_condition']) ? (string) $seedDetail['field_condition'] : null;
    $initialSpectators = isset($seedDetail['spectators']) ? (int) $seedDetail['spectators'] : null;
    break;
}
$weatherLabel = $initialWeather ?: 'In aggiornamento…';
$fieldLabel = $initialFieldCondition ?: 'In aggiornamento…';
$spectatorsLabel = $initialSpectators !== null ? number_format($initialSpectators, 0, ',', '.') : '—';

?>

<div class="fixture-live">

    <!-- ── Row 1: scoreboard + info ───────────────────────── -->
    <div class="row g-3 mb-3 align-items-stretch">

        <!-- Scoreboard col-9 -->
        <div class="col-md-9">
            <div class="scoreboard text-center animate__animated animate__fadeInDown" style="margin-bottom:0">
                <div class="scoreboard-mobile-crests">
                    <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($homeColorLeft, $homeColorRight, $homeLetter, 58, 64, 'liveShield') ?></div>
                    <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($awayColorLeft, $awayColorRight, $awayLetter, 58, 64, 'liveShield') ?></div>
                </div>
                <div class="row align-items-center gx-3">
                    <div class="col-md-2 d-none d-md-flex justify-content-start">
                        <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($homeColorLeft, $homeColorRight, $homeLetter, 96, 108, 'liveShield') ?></div>
                    </div>
                    <div class="col-12 col-md-8">
                        <div class="row align-items-center">
                            <div class="col-5">
                                <div class="text-end">
                                    <div class="team-name-live text-white mb-1"><?= Html::encode($fixture->homeTeam->name) ?></div>
                                    <div class="text-muted-gm" style="font-size:.68rem;letter-spacing:.04em">CASA</div>
                                    <div class="mt-1 team-dept-stats" style="margin-left:auto">
                                        <?= FixtureViewHelper::renderStrengthBar('DIF', (int)$homeStrength['def']) ?>
                                        <?= FixtureViewHelper::renderStrengthBar('CEN', (int)$homeStrength['mid']) ?>
                                        <?= FixtureViewHelper::renderStrengthBar('ATT', (int)$homeStrength['att']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="score-display" id="score-text">
                                    <span id="home-score"><?= $state ? $state->home_score : 0 ?></span>:<span id="away-score"><?= $state ? $state->away_score : 0 ?></span>
                                </div>
                                <div class="match-clock mb-2" id="match-clock">
                                    <span id="match-minute"><?= $state ? $state->current_minute : 0 ?></span>'
                                </div>
                                <div id="match-phase" class="text-gold small fw-bold text-uppercase">
                                    <?= $state ? str_replace('_', ' ', $state->phase) : 'IN ATTESA' ?>
                                </div>
                            </div>
                            <div class="col-5">
                                <div class="text-start">
                                    <div class="team-name-live text-white mb-1" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;"><?= Html::encode($fixture->awayTeam->name) ?></div>
                                    <div class=" text-muted-gm" style="font-size:.68rem;letter-spacing:.04em">TRASFERTA</div>
                                    <div class="mt-1 team-dept-stats">
                                        <?= FixtureViewHelper::renderStrengthBar('DIF', (int)$awayStrength['def']) ?>
                                        <?= FixtureViewHelper::renderStrengthBar('CEN', (int)$awayStrength['mid']) ?>
                                        <?= FixtureViewHelper::renderStrengthBar('ATT', (int)$awayStrength['att']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SIP-0064 Attack Indicator -->
                        <div class="ai-wrap">
                            <div class="ai-track">
                                <div class="ai-fill-home" id="ai-fill-home"></div>
                                <div class="ai-fill-away" id="ai-fill-away"></div>
                                <div class="ai-center"></div>
                                <div class="ai-dot" id="ai-dot"></div>
                            </div>
                        </div>
                        <div id="match-progress-bar" style="display:none"></div>
                        <?php if (!$state || $state->phase === 'not_started'): ?>
                            <div style="text-align:center;font-size:.7rem;color:var(--text-secondary);margin-top:.3rem;letter-spacing:.04em">
                                <i class="bi bi-clock me-1"></i><?= date('H:i', $fixture->match_date) ?>
                                <?php if ($fixture->match_date > time()): ?>
                                    — tra <?= max(0, (int)(($fixture->match_date - time()) / 60)) ?> min
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-2 d-none d-md-flex justify-content-end">
                        <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($awayColorLeft, $awayColorRight, $awayLetter, 96, 108, 'liveShield') ?></div>
                    </div>
                </div>

            </div><!-- /scoreboard -->
        </div><!-- /col-md-9 -->

        <!-- Info + Marcatori col-3 -->
        <div class="col-md-3 d-flex flex-column gap-3">
            <?= $this->render('_match_info_card', [
                'fixture' => $fixture,
                'weatherLabel' => $weatherLabel,
                'spectatorsLabel' => $spectatorsLabel,
                'showLiveDebugControls' => $isDevLiveUi,
            ]) ?>
        </div><!-- /col-md-4 -->
    </div><!-- /row 1 -->

    <!-- ── Row 2: cronaca + formazioni ───────────────────── -->
    <div id="half-time-banner" class="d-none gm-card text-center mb-3" style="background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.35);padding:.8rem;border-radius:.6rem">
    <div class="fw-bold" style="color:#bfdbfe;font-size:.85rem;letter-spacing:.06em;text-transform:uppercase">Intervallo</div>
    <div class="text-white" style="font-size:1.6rem;font-weight:900"><span id="half-time-countdown">15</span>s</div>
    <div class="text-muted-gm" style="font-size:.7rem">Puoi inviare comandi ora, verranno applicati all'inizio del 2° tempo</div>
</div>

<div class="row g-4">
        <!-- Cronaca col-4 -->
        <div class="col-lg-4" id="tab-cronaca-panel">
            <div class="gm-card h-100">
                <h3 class="h5 mb-4 text-white"><i class="bi bi-chat-left-text"></i> Cronaca in Diretta</h3>
                <div class="event-log" id="event-log">
                    <?php if (empty($existingEvents)): ?>
                        <div class="text-center py-5 text-muted-gm" id="no-events-msg">
                            <i class="bi bi-broadcast fs-1 d-block mb-2 pulse"></i>
                            <?= (!$state || $state->current_minute === 0) ? 'In attesa della telecronaca pre-partita...' : 'In attesa di eventi dal campo...' ?>
                        </div>
                    <?php else: ?>
                        <?php
                        $sideLabels = ['L' => 'fascia sx', 'C' => 'centro', 'R' => 'fascia dx'];
                        $iconMap = [
                            'pre_match' => '🏟️',
                            'goal' => '⚽',
                            'gk_save' => '🧤',
                            'near_miss' => '💨',
                            'substitution' => '🔄',
                            'tactic_change' => '📋',
                            'half_time' => '🔔',
                            'full_time' => '🏁',
                            'kickoff' => '🎯',
                            'second_half_start' => '▶️',
                        ];
                        $myTeam   = \app\models\Team::findOne(['user_id' => Yii::$app->user->id]);
                        $homeSide = ($myTeam && $myTeam->id === $fixture->home_team_id) ? 'home' : (($myTeam && $myTeam->id === $fixture->away_team_id) ? 'away' : null);
                        ?>
                        <?php foreach ($existingEvents as $ev): ?>
                            <?= $this->render('_live_event_item', [
                                'ev' => $ev,
                                'fixture' => $fixture,
                                'homeSide' => $homeSide,
                                'iconMap' => $iconMap,
                            ]) ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?= $this->render('_formations_and_scorers', [
            'panelColumnClass' => 'col-lg-5',
            'showScorers' => true,
        ]) ?>

        <div class="col-lg-3">
            <?php if ($isAdmin): ?>
                <!-- Admin panel: simulate button -->
                <div class="gm-card live-bench-panel">
                    <h3 class="h5 mb-3 text-white">
                        <i class="bi bi-sliders text-gold me-2"></i>Controllo Admin
                    </h3>
                    <p class="text-muted-gm small mb-3">
                        Simula questa partita istantaneamente tramite il motore PHP.
                        Il risultato sarà disponibile in <a href="<?= Url::to(['/fixture/view', 'id' => $fixture->id]) ?>" class="text-gold">Report</a>.
                    </p>
                    <form id="admin-simulate-form" method="post" action="<?= Url::to(['/admin/simulate-fixture']) ?>">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                        <input type="hidden" name="fixture_id" value="<?= $fixture->id ?>">
                        <button type="submit" class="btn btn-gold w-100 fw-bold">
                            <i class="bi bi-play-fill me-1"></i>Simula ora
                        </button>
                    </form>
                    <div class="mt-2 text-center" style="font-size:.7rem;color:var(--text-secondary)">
                        oppure via console:
                        <code style="color:var(--accent-blue);display:block;margin-top:.25rem">./yii game/simulate-fixture <?= $fixture->id ?></code>
                    </div>
                </div>
            <?php elseif ($userTeam): ?>
                <!-- Manager panel: tactics -->
                <div class="gm-card">
                    <h3 class="h5 mb-3 text-white">
                        <i class="bi bi-gear text-gold me-2"></i>Panchina
                    </h3>
                    <?php
                    // Show current formation starters count
                    $myFormation = \app\models\Formation::findOne(['team_id' => $userTeam->id, 'is_active' => 1]);
                    $startersCount = $myFormation
                        ? \app\models\FormationSlot::find()->where(['formation_id' => $myFormation->id])->count()
                        : 0;
                    $tacticTraining = Yii::$app->db->createCommand(
                        'SELECT pressing, contropiede, possesso, palla_bassa, lancio_lungo, catenaccio, fuorigioco, calci_piazzati
                     FROM {{%training_tactic}}
                     WHERE team_id = :teamId
                     ORDER BY season DESC
                     LIMIT 1',
                        [':teamId' => $userTeam->id]
                    )->queryOne() ?: [];
                    $trainedTacticMap = [
                        'pressing' => 'Pressing',
                        'contropiede' => 'Contropiede',
                        'possesso' => 'Possesso',
                        'palla_bassa' => 'Palla bassa',
                        'lancio_lungo' => 'Lancio lungo',
                        'catenaccio' => 'Catenaccio',
                        'fuorigioco' => 'Fuorigioco',
                        'calci_piazzati' => 'Piazzati',
                    ];
                    $initialStyle = $myFormation && in_array((string) $myFormation->tactic, ['balanced', 'ultra_defensive', 'all_out_attack'], true)
                        ? (string) $myFormation->tactic : 'balanced';
                    $initialMarking = $myFormation && in_array((string) $myFormation->marking, ['zone', 'man'], true)
                        ? (string) $myFormation->marking : 'zone';
                    $initialOffside = $myFormation ? (((int) ($myFormation->offside_trap ?? 1)) > 0 ? 1 : 0) : 1;
                    $initialFocus = (string) ($myFormation->trained_tactic ?? '');
                    if ($initialFocus === '' || !array_key_exists($initialFocus, $trainedTacticMap)) {
                        $initialFocus = (string) array_key_first($trainedTacticMap);
                    }
                    ?>
                    <div class="live-formation-card p-2 mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted-gm small">Formazione attiva</span>
                            <span class="text-gold fw-bold"><?= $startersCount ?>/11</span>
                        </div>
                        <?php if ($startersCount < 11): ?>
                            <div class="mt-1" style="font-size:.7rem;color:var(--accent-red)">
                                <i class="bi bi-exclamation-triangle"></i>
                                Formazione incompleta — <?= Html::a('completa in tattica', ['/formation/view'], ['class' => 'text-gold']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="live-asset-panel mb-3">
                        <div class="text-muted-gm small fw-bold text-uppercase mb-2" style="letter-spacing:.06em">Assetto live</div>
                        <div class="live-badge-grid">
                            <span id="live-badge-focus" style="font-size:.64rem;border:1px solid rgba(245,158,11,.4);padding:.1rem .38rem;border-radius:.35rem;color:var(--gold)">Tattica: —</span>
                            <span id="live-badge-style" style="font-size:.64rem;border:1px solid rgba(255,255,255,.2);padding:.1rem .38rem;border-radius:.35rem;color:var(--text-secondary)">Stile: —</span>
                            <span id="live-badge-marking" style="font-size:.64rem;border:1px solid rgba(255,255,255,.2);padding:.1rem .38rem;border-radius:.35rem;color:var(--text-secondary)">Marcatura: —</span>
                            <span id="live-badge-offside" style="font-size:.64rem;border:1px solid rgba(255,255,255,.2);padding:.1rem .38rem;border-radius:.35rem;color:var(--text-secondary)">Fuorigioco: —</span>
                        </div>
                        <div class="row mb-2">
                            <label class="live-section-label">Stile gara</label>
                            <div class="btn-group w-100 mt-2">
                                <button id="btn-style-def" class="btn btn-outline-secondary btn-sm live-btn-mini" data-tactic="ultra_defensive">Difensivo</button>
                                <button id="btn-style-bal" class="btn btn-outline-gold btn-sm live-btn-mini" data-tactic="balanced">Bilanciato</button>
                                <button id="btn-style-att" class="btn btn-outline-danger btn-sm live-btn-mini" data-tactic="all_out_attack">Offensivo</button>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="live-section-label">Marcatura</label>
                            <div class="btn-group w-100 mt-2">
                                <button id="btn-mark-zone" class="btn btn-sm live-btn-mini live-btn-mark-zone" data-marking="zone">A zona</button>
                                <button id="btn-mark-man" class="btn btn-sm live-btn-mini live-btn-mark-man" data-marking="man">A uomo</button>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <label class="live-section-label">Trappola fuorigioco</label>
                            <div class="btn-group w-100 mt-2">
                                <button id="btn-offside-yes" class="btn btn-sm live-btn-mini live-btn-off-yes" data-offside="1">Sì</button>
                                <button id="btn-offside-no" class="btn btn-sm live-btn-mini live-btn-off-no" data-offside="0">No</button>
                            </div>
                        </div>

                    </div>

                    <!-- ── Sostituzioni ─────────────────────────────── -->
                    <div class="pt-2" style="border-top:1px solid var(--border)">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted-gm small fw-bold text-uppercase" style="letter-spacing:.06em">Sostituzioni</span>
                            <span id="subs-counter" style="font-size:.75rem;color:var(--gold);font-weight:700">0/5</span>
                        </div>
                        <button id="sub-btn" class="btn btn-outline-gold w-100 btn-sm">
                            <i class="bi bi-arrow-left-right me-1"></i>Effettua sostituzione
                        </button>
                        <div id="sub-msg" class="small mt-2" style="display:none;color:var(--accent-green)"></div>
                    </div>

                    <div id="live-action-status" class="small mt-2 text-gold live-action-status" style="display:none"></div>
                </div>
            <?php else: ?>
                <div class="gm-card text-center py-4">
                    <i class="bi bi-eye text-muted-gm d-block mb-2 fs-2 opacity-25"></i>
                    <p class="text-muted-gm small mb-0">Stai guardando come spettatore.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>


</div>

<?php
$pollUrl     = '/fixture/get-events?fixtureId=' . (int)$fixture->id;
$updatesUrl  = '/fixture/get-event-updates?fixtureId=' . (int)$fixture->id;
$streamUrl   = Json::htmlEncode('/stream/' . (int)$fixture->id);
$tacticUrl   = Url::to(['live-action/tactic',        'fixtureId' => $fixture->id]);
$scorersUrl     = Url::to(['fixture/get-scorers',   'fixtureId' => $fixture->id]);
$formationsUrl  = Url::to(['fixture/formations',    'fixtureId' => $fixture->id]);
$rosterUrl   = Url::to(['live-action/roster',        'fixtureId' => $fixture->id]);
$subUrl      = Url::to(['live-action/substitution',  'fixtureId' => $fixture->id]);
$csrfToken   = Yii::$app->request->getCsrfToken();
$csrfParam   = Yii::$app->request->csrfParam;
$isFinished  = $state && strtolower($state->phase) === \app\models\MatchState::PHASE_FINISHED ? 'true' : 'false';
$initialMinute = $state ? $state->current_minute : 0;
$fixtureStatus = (int)$fixture->status;
$jsIsDevLiveUi = $isDevLiveUi ? 'true' : 'false';
$jsUserSide   = Json::htmlEncode($userSide);
$homeTeamName = Json::htmlEncode($fixture->homeTeam->name);
$awayTeamName = Json::htmlEncode($fixture->awayTeam->name);
$jsInitialStyle = Json::htmlEncode($initialStyle ?? 'balanced');
$jsInitialMarking = Json::htmlEncode($initialMarking ?? 'zone');
$jsInitialOffside = (int) ($initialOffside ?? 1);
$jsInitialFocus = Json::htmlEncode($initialFocus ?? 'pressing');
$jsTrainedLabels = Json::htmlEncode($trainedTacticMap ?? [
    'pressing' => 'Pressing',
    'contropiede' => 'Contropiede',
    'possesso' => 'Possesso',
    'palla_bassa' => 'Palla bassa',
    'lancio_lungo' => 'Lancio lungo',
    'catenaccio' => 'Catenaccio',
    'fuorigioco' => 'Fuorigioco',
    'calci_piazzati' => 'Piazzati',
]);
$jsSuspenseEnabled = CommentaryTemplateService::suspenseEnabled() ? 'true' : 'false';
$jsSuspenseDelayMs = CommentaryTemplateService::suspenseDelayMs();
$jsCommentaryStreamEnabled = CommentaryTemplateService::commentaryStreamEnabled() ? 'true' : 'false';
$quietRows = (new \yii\db\Query())
    ->select(['text'])
    ->from('{{%commentary_template}}')
    ->where(['enabled' => 1])
    ->andWhere(['event_type' => ['midfield_duel', 'attack_attempt']])
    ->orderBy(['weight' => SORT_DESC, 'id' => SORT_DESC])
    ->limit(200)
    ->all();
$quietPhrasesDb = [];
foreach ($quietRows as $row) {
    $text = trim((string)($row['text'] ?? ''));
    if ($text === '') {
        continue;
    }
    $quietPhrasesDb[] = $text;
}
$jsQuietPhrases = Json::htmlEncode(array_values(array_unique($quietPhrasesDb)));
$quietPlayers = (new \yii\db\Query())
    ->select(['name'])
    ->from('{{%player}}')
    ->where(['team_id' => [(int)$fixture->home_team_id, (int)$fixture->away_team_id]])
    ->orderBy(['general_skill' => SORT_DESC, 'id' => SORT_ASC])
    ->limit(22)
    ->column();
$jsQuietPlayers = Json::htmlEncode(array_values(array_unique(array_filter(array_map('strval', $quietPlayers)))));

$this->registerJs('window.GM_LIVE_CONFIG = ' . Json::htmlEncode([
    'pollUrl' => $pollUrl,
    'updatesUrl' => $updatesUrl,
    'fixtureId' => (int)$fixture->id,
    'streamUrl' => '/stream/' . (int)$fixture->id,
    'scorersUrl' => $scorersUrl,
    'formationsUrl' => $formationsUrl,
    'userSide' => $userSide,
    'homeName' => $fixture->homeTeam->name,
    'awayName' => $fixture->awayTeam->name,
    'initialStyle' => $initialStyle ?? 'balanced',
    'initialMarking' => $initialMarking ?? 'zone',
    'initialOffside' => (int)($initialOffside ?? 1),
    'initialFocus' => $initialFocus ?? '',
    'trainedLabels' => $trainedTacticMap ?? [],
    'fixtureStatus' => (int)$fixtureStatus,
    'suspenseEnabled' => CommentaryTemplateService::suspenseEnabled(),
    'commentaryStreamEnabled' => CommentaryTemplateService::commentaryStreamEnabled(),
    'suspenseDelayMs' => CommentaryTemplateService::suspenseDelayMs(),
    'quietPhrases' => array_values(array_unique($quietPhrasesDb)),
    'quietPlayers' => array_values(array_unique(array_filter(array_map('strval', $quietPlayers)))),
    'lastEventId' => (int)$lastEventId,
    'isFinished' => (bool)($state && strtolower($state->phase) === \app\models\MatchState::PHASE_FINISHED),
    'initialMinute' => (int)$initialMinute,
    'isDevLiveUi' => (bool)$isDevLiveUi,
    'tacticUrl' => $tacticUrl,
    'rosterUrl' => $rosterUrl,
    'subUrl' => $subUrl,
    'csrfParam' => $csrfParam,
    'csrfToken' => $csrfToken,
]) . ';', View::POS_HEAD, 'gm-live-config');
?>

<!-- ── Substitution Modal ───────────────────────────────────────── -->
<div class="modal fade" id="subModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-dark);border:1px solid var(--border)">
            <div class="modal-header" style="border-color:var(--border)">
                <h5 class="modal-title text-white">
                    <i class="bi bi-arrow-left-right text-gold me-2"></i>Sostituzione
                    <span id="sub-modal-counter" class="text-muted-gm ms-2" style="font-size:.75rem"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div class="row g-3 sub-lists-wrap" id="sub-lists">
                    <div class="col-6">
                        <div class="text-muted-gm small fw-bold text-uppercase mb-2" style="letter-spacing:.06em">
                            <i class="bi bi-person-dash me-1"></i>Chi esce
                        </div>
                        <div id="starters-list" style="max-height:320px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--gold) transparent"></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted-gm small fw-bold text-uppercase mb-2" style="letter-spacing:.06em">
                            <i class="bi bi-person-plus me-1"></i>Chi entra
                        </div>
                        <div id="bench-list" style="max-height:320px;overflow-y:auto;scrollbar-width:thin;scrollbar-color:var(--gold) transparent"></div>
                    </div>
                </div>
                <div id="sub-selection-info" class="mt-3 text-center text-muted-gm small" style="min-height:1.5rem"></div>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Annulla</button>
                <button type="button" id="sub-confirm-btn" class="btn btn-gold btn-sm fw-bold" disabled>
                    <i class="bi bi-check-lg me-1"></i>Conferma sostituzione
                </button>
            </div>
        </div>
    </div>
</div>
