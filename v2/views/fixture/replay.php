<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Fixture $fixture */
/** @var app\models\MatchEvent[] $existingEvents */
/** @var app\models\MatchState|null $state */
/** @var array $homeStrength  po/def/mid/att/ovr */
/** @var array $awayStrength  po/def/mid/att/ovr */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\db\Query;
use yii\web\View;
use app\assets\FixtureReplayAsset;
use app\components\FixtureViewHelper;
use app\components\PitchZoneHelper;

$this->title = $fixture->homeTeam->name . ' vs ' . $fixture->awayTeam->name . ' — Replay';
$this->params['breadcrumbs'][] = ['label' => 'Calendario', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Replay';
FixtureReplayAsset::register($this);

$this->registerJsFile('https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js');

$sideLabel = ['L' => 'fascia sx', 'C' => 'centro', 'R' => 'fascia dx'];

// Pre-process events for JS: only meaningful ones + detail as readable string
$jsEvents = [];
foreach ($existingEvents as $ev) {
    $detail = '';
    $d = $ev->detail ? json_decode($ev->detail, true) : null;
    if (is_array($d)) {
        if (isset($d['description']))      $detail = $d['description'];
        elseif (isset($d['home_score']))   $detail = "{$d['home_score']}–{$d['away_score']}";
        elseif (isset($d['home']))         $detail = "HT {$d['home']}–{$d['away']}";
        elseif (isset($d['side']))         $detail = $sideLabel[$d['side']] ?? $d['side'];
        elseif (isset($d['tactic']))       $detail = $d['tactic'];
    }
    $jsEvents[] = [
        'minute'    => $ev->minute,
        'type'      => $ev->type,
        'team_side' => $ev->team_side,
        'detail'    => $detail,
    ];
}

// Compute final score from goal events
$homeScore = 0;
$awayScore = 0;
foreach ($existingEvents as $ev) {
    if ($ev->type === 'goal') {
        $ev->team_side === 'home' ? $homeScore++ : $awayScore++;
    }
}

$sheet = \app\components\ScorerSheetService::buildForFixture((int)$fixture->id);
$homeScorers = $sheet['home'] ?? [];
$awayScorers = $sheet['away'] ?? [];

$loadLineup = static function (int $teamId): array {
    $rows = (new Query())
        ->select(['p.name'])
        ->from('{{%formation}} f')
        ->innerJoin('{{%formation_slot}} fs', 'fs.formation_id = f.id')
        ->innerJoin('{{%player}} p', 'p.id = fs.player_id')
        ->where(['f.team_id' => $teamId, 'f.is_active' => 1])
        ->andWhere(new \yii\db\Expression(PitchZoneHelper::onPitchSql('fs.zone')))
        ->andWhere(['is not', 'fs.player_id', null])
        ->orderBy(['fs.zone' => SORT_ASC, 'p.id' => SORT_ASC])
        ->limit(11)
        ->column();
    if (!empty($rows)) {
        return array_values($rows);
    }
    return (new Query())
        ->select(['name'])
        ->from('{{%player}}')
        ->where(['team_id' => $teamId])
        ->orderBy(['general_skill' => SORT_DESC, 'id' => SORT_ASC])
        ->limit(11)
        ->column();
};

$homeLineup = $loadLineup((int)$fixture->home_team_id);
$awayLineup = $loadLineup((int)$fixture->away_team_id);
$homeColorLeft = \app\models\Team::sanitizeHexColor($fixture->homeTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
$homeColorRight = \app\models\Team::sanitizeHexColor($fixture->homeTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
$awayColorLeft = \app\models\Team::sanitizeHexColor($fixture->awayTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
$awayColorRight = \app\models\Team::sanitizeHexColor($fixture->awayTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
$homeLetter = mb_substr((string)$fixture->homeTeam->name, 0, 1);
$awayLetter = mb_substr((string)$fixture->awayTeam->name, 0, 1);

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

<div class="fixture-replay fixture-live">

    <!-- Controls -->
    <div class="gm-card py-3 px-4 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <button id="btn-play" class="btn btn-gold fw-bold px-4">
                <i class="bi bi-play-fill me-1"></i>Avvia
            </button>
            <button id="btn-pause" class="btn btn-outline-secondary px-3" disabled>
                <i class="bi bi-pause-fill"></i>
            </button>
            <button id="btn-reset" class="btn btn-outline-secondary px-3">
                <i class="bi bi-skip-start-fill"></i>
            </button>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="text-muted-gm small">Velocità:</span>
            <?php foreach ([['1×', 1000], ['2×', 500], ['5×', 200], ['10×', 100]] as [$lbl, $ms]): ?>
                <button
                    class="btn btn-sm speed-btn <?= $ms === 1000 ? 'btn-gold' : 'btn-outline-secondary' ?>"
                    data-speed-ms="<?= $ms ?>"
                ><?= $lbl ?></button>
            <?php endforeach; ?>
        </div>

        <div class="text-muted-gm small">
            Risultato finale:
            <strong class="text-gold"><?= $homeScore ?>–<?= $awayScore ?></strong>
        </div>
    </div>

    <!-- Row 1: scoreboard + info/marcatori -->
    <div class="row g-3 mb-3 align-items-stretch">
        <!-- Scoreboard col-9 -->
        <div class="col-md-9">
            <div class="scoreboard text-center animate__animated animate__fadeInDown" style="margin-bottom:0">
                <div class="scoreboard-mobile-crests">
                    <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($homeColorLeft, $homeColorRight, $homeLetter, 58, 64, 'replayShield') ?></div>
                    <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($awayColorLeft, $awayColorRight, $awayLetter, 58, 64, 'replayShield') ?></div>
                </div>
                <div class="row align-items-center gx-3">
                    <div class="col-md-2 d-none d-md-flex justify-content-start">
                        <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($homeColorLeft, $homeColorRight, $homeLetter, 96, 108, 'replayShield') ?></div>
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
                        <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($awayColorLeft, $awayColorRight, $awayLetter, 96, 108, 'replayShield') ?></div>
                    </div>
                </div>

            </div><!-- /scoreboard -->
        </div><!-- /col-md-9 -->
        <div class="col-md-3 d-flex flex-column gap-3">
            <?= $this->render('_match_info_card', [
                'fixture' => $fixture,
                'weatherLabel' => $weatherLabel,
                'spectatorsLabel' => $spectatorsLabel,
                'showLiveDebugControls' => false,
            ]) ?>
        </div>
    </div>

    <!-- Row 2: cronaca + squadre + stats -->
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="gm-card h-100">
                <h3 class="h5 mb-3 text-white">
                    <i class="bi bi-chat-left-text text-gold me-2"></i>Cronaca
                </h3>
                <div class="event-log" id="event-log" style="min-height:300px">
                    <div id="no-events-msg" class="text-center py-5 text-muted-gm">
                        <i class="bi bi-play-circle d-block fs-1 mb-2 opacity-25"></i>
                        Premi Avvia per iniziare il replay
                    </div>
                </div>
            </div>
        </div>

        <?= $this->render('_formations_and_scorers', [
            'panelColumnClass' => 'col-lg-5',
            'showScorers' => true,
        ]) ?>

        <div class="col-lg-3 d-flex flex-column gap-3">
            <div class="gm-card">
                <h3 class="h5 mb-3 text-white"><i class="bi bi-pie-chart text-gold me-2"></i>Statistiche</h3>
                <ul class="attribute-list">
                    <li><span class="text-muted-gm">Gol casa</span> <span id="stat-home-goals" class="text-white fw-bold">0</span></li>
                    <li><span class="text-muted-gm">Gol trasferta</span> <span id="stat-away-goals" class="text-white fw-bold">0</span></li>
                    <li><span class="text-muted-gm">Parate</span> <span id="stat-saves" class="text-white">0</span></li>
                    <li><span class="text-muted-gm">Tiri fuori</span> <span id="stat-near" class="text-white">0</span></li>
                    <li><span class="text-muted-gm">Minuto</span> <span id="stat-minute" class="text-muted-gm">—</span></li>
                </ul>
            </div>
            <div>
                <?= Html::a('<i class="bi bi-file-earmark-text me-1"></i>Vai al report', ['/fixture/view', 'id' => $fixture->id], ['class' => 'btn btn-outline-gold w-100', 'encode' => false]) ?>
            </div>
        </div>
    </div>
</div>

<?php
$viewerSide = $userSide ?? null;
$formationsUrl = Url::to(['fixture/formations', 'fixtureId' => $fixture->id]);
$this->registerJs(
    'window.GM_REPLAY_CONFIG = ' . Json::htmlEncode([
        'fixtureId' => (int)$fixture->id,
        'events' => $jsEvents,
        'formationsUrl' => $formationsUrl,
        'viewerSide' => $viewerSide,
    ]) . ';',
    View::POS_HEAD,
    'gm-replay-config'
);
?>
