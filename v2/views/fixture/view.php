<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Fixture $fixture */
/** @var app\models\MatchState|null $state */
/** @var app\models\MatchEvent[] $events */

use app\models\Fixture;
use app\components\FixtureViewHelper;
use yii\helpers\Html;

$this->title = $fixture->homeTeam->name . ' vs ' . $fixture->awayTeam->name;
$this->params['breadcrumbs'][] = ['label' => 'Calendario', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Report';
$this->registerCss(<<<CSS
.fixture-view.fixture-live .scoreboard {
    margin-bottom: 0;
}
.fixture-view.fixture-live .event-item {
    gap: .45rem;
    padding: .62rem .58rem;
}
.fixture-view.fixture-live .event-time {
    min-width: 2.05rem;
}
.fixture-view.fixture-live .report-event-icon {
    width: 18px;
    height: 18px;
    margin-right: 0;
    font-size: 1rem;
    flex: 0 0 18px;
}
.fixture-view.fixture-live .gm-card {
    border-color: rgba(148,163,184,.2);
}
.scoreboard-side-crest {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    opacity: .95;
}
.scoreboard-mobile-crests {
    display: none;
}
@media (max-width: 991.98px) {
    .scoreboard-mobile-crests {
        display: flex;
        justify-content: space-between;
        margin-bottom: .6rem;
    }
}
CSS);

// Compute event-based stats
$goals = ['home' => 0, 'away' => 0];
$saves = ['home' => 0, 'away' => 0];
$nearMisses = 0;
$significantTypes = ['goal', 'gk_save', 'near_miss', 'substitution', 'half_time', 'full_time', 'kickoff', 'second_half_start'];

$sideLabel = ['L' => 'fascia sinistra', 'C' => 'centro', 'R' => 'fascia destra'];
$renderDetail = function (?string $raw, string $type) use ($sideLabel): string {
    if (!$raw) return '';
    $d = json_decode($raw, true);
    if (!is_array($d)) return htmlspecialchars($raw);
    return match($type) {
        'goal'          => "Risultato: {$d['home_score']}–{$d['away_score']}",
        'half_time'     => "Intervallo: {$d['home']}–{$d['away']}",
        'full_time'     => "Finale: {$d['home']}–{$d['away']}",
        'near_miss',
        'attack_attempt',
        'shot_on_goal',
        'gk_save'       => isset($d['side']) ? 'Lato: ' . ($sideLabel[$d['side']] ?? $d['side']) : '',
        'substitution'  => isset($d['in']) ? "Entra #" . $d['in'] . (isset($d['out']) ? ", esce #" . $d['out'] : '') : '',
        'tactic_change' => isset($d['tactic']) ? "Modulo: " . htmlspecialchars($d['tactic']) : '',
        default         => '',
    };
};

foreach ($events as $ev) {
    if ($ev->type === 'goal') {
        $goals[$ev->team_side === 'home' ? 'home' : 'away']++;
    }
    if ($ev->type === 'gk_save') {
        $saves[$ev->team_side === 'home' ? 'home' : 'away']++;
    }
    if ($ev->type === 'near_miss') {
        $nearMisses++;
    }
}

$isFinished  = $fixture->status === Fixture::STATUS_FINISHED;
$isPlaying   = $fixture->status === Fixture::STATUS_PLAYING;
$isScheduled = $fixture->status === Fixture::STATUS_SCHEDULED;

$statusLabel = match($fixture->status) {
    Fixture::STATUS_FINISHED  => 'Conclusa',
    Fixture::STATUS_PLAYING   => 'In corso',
    Fixture::STATUS_SCHEDULED => 'Programmata',
    default => '—',
};
$statusColor = match($fixture->status) {
    Fixture::STATUS_FINISHED  => '#6b7280',
    Fixture::STATUS_PLAYING   => '#ef4444',
    Fixture::STATUS_SCHEDULED => '#f59e0b',
    default => '#6b7280',
};

$eventIcons = [
    'goal'               => '⚽',
    'gk_save'            => '🧤',
    'near_miss'          => '💨',
    'substitution'       => '🔄',
    'half_time'          => '🔔',
    'full_time'          => '🏁',
    'kickoff'            => '🎯',
    'second_half_start'  => '▶️',
    'tactic_change'      => '📋',
];

// Filter to significant events for display
$displayEvents = array_filter($events, fn($e) => in_array($e->type, $significantTypes, true));
$homeColorLeft = \app\models\Team::sanitizeHexColor($fixture->homeTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
$homeColorRight = \app\models\Team::sanitizeHexColor($fixture->homeTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
$awayColorLeft = \app\models\Team::sanitizeHexColor($fixture->awayTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
$awayColorRight = \app\models\Team::sanitizeHexColor($fixture->awayTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
$homeLetter = mb_substr((string)$fixture->homeTeam->name, 0, 1);
$awayLetter = mb_substr((string)$fixture->awayTeam->name, 0, 1);
?>

<div class="fixture-view fixture-live">

    <!-- Score header -->
    <div class="gm-card scoreboard text-center mb-4" style="background:linear-gradient(135deg,rgba(15,23,42,.9),rgba(30,41,59,.7))">
        <div class="text-muted-gm small text-uppercase mb-3" style="letter-spacing:.08em">
            <?= Html::encode($fixture->competition->name) ?> · <?= date('d M Y', $fixture->match_date) ?>
        </div>

        <div class="scoreboard-mobile-crests">
            <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($homeColorLeft, $homeColorRight, $homeLetter, 58, 64, 'viewShield') ?></div>
            <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($awayColorLeft, $awayColorRight, $awayLetter, 58, 64, 'viewShield') ?></div>
        </div>
        <div class="row align-items-center mb-4 g-2">
            <div class="col-md-2 d-none d-md-flex justify-content-start">
                <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($homeColorLeft, $homeColorRight, $homeLetter, 96, 108, 'viewShield') ?></div>
            </div>
            <div class="col-12 col-md-8">
                <div class="row align-items-center">
                    <div class="col-5 text-end">
                        <div class="fw-black" style="font-size:1.3rem;line-height:1.1"><?= Html::encode($fixture->homeTeam->name) ?></div>
                        <div class="text-muted-gm small">Casa</div>
                    </div>
                    <div class="col-2 text-center">
                        <?php if ($isFinished || $isPlaying): ?>
                            <div class="fw-black" style="font-size:3rem;color:var(--gold);line-height:1">
                                <?= $fixture->home_score ?>–<?= $fixture->away_score ?>
                            </div>
                        <?php else: ?>
                            <div class="fw-bold text-muted-gm" style="font-size:1.5rem">vs</div>
                            <div class="text-muted-gm small"><?= date('H:i', $fixture->match_date) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-5 text-start">
                        <div class="fw-black" style="font-size:1.3rem;line-height:1.1"><?= Html::encode($fixture->awayTeam->name) ?></div>
                        <div class="text-muted-gm small">Trasferta</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 d-none d-md-flex justify-content-end">
                <div class="scoreboard-side-crest"><?= FixtureViewHelper::renderShieldSvg($awayColorLeft, $awayColorRight, $awayLetter, 96, 108, 'viewShield') ?></div>
            </div>
        </div>

        <?php if ($isFinished || $isPlaying):
            $sheet = \app\components\ScorerSheetService::buildForFixture($fixture->id);
            $compact = \app\components\ScorerSheetService::compactString($sheet);
            if ($compact): ?>
        <div class="text-muted-gm mb-2" style="font-size:.78rem">⚽ <?= Html::encode($compact) ?></div>
        <?php endif; endif; ?>

        <span class="badge-gm px-3 py-1" style="background:<?= $statusColor ?>22;color:<?= $statusColor ?>;border:1px solid <?= $statusColor ?>44;font-size:.72rem;letter-spacing:.06em;text-transform:uppercase">
            <?php if ($isPlaying): ?><span class="pulse me-1" style="display:inline-block;width:6px;height:6px;border-radius:50%;background:<?= $statusColor ?>"></span><?php endif; ?>
            <?= $statusLabel ?>
            <?php if ($isPlaying && $state): ?> — <?= $state->current_minute ?>'<?php endif; ?>
        </span>

        <?php if ($isPlaying): ?>
        <div class="mt-3">
            <?= Html::a('<i class="bi bi-broadcast"></i> Segui live', ['live', 'id' => $fixture->id], ['class' => 'btn btn-danger btn-sm', 'encode' => false]) ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="row g-4">

        <!-- Events timeline -->
        <div class="col-lg-8">
            <div class="gm-card h-100">
                <h3 class="h5 mb-4 text-white">
                    <i class="bi bi-list-stars text-gold me-2"></i>Momenti Salienti
                </h3>

                <?php if (empty($displayEvents)): ?>
                    <div class="text-center py-5 text-muted-gm">
                        <i class="bi bi-calendar-x d-block fs-1 mb-2 opacity-25"></i>
                        <?= $isScheduled ? 'Partita non ancora disputata.' : 'Nessun evento da segnalare.' ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($displayEvents as $event): ?>
                    <div class="event-item">
                        <div class="event-time"><?= $event->minute ?>'</div>
                        <div class="report-event-icon">
                            <?= $eventIcons[$event->type] ?? '·' ?>
                        </div>
                        <div style="flex:1">
                            <div class="text-white fw-semibold small" style="text-transform:uppercase;letter-spacing:.04em">
                                <?= Html::encode(str_replace('_', ' ', $event->type)) ?>
                                <span class="text-muted-gm fw-normal" style="font-size:.7rem;text-transform:none">
                                    (<?= $event->team_side === 'home' ? Html::encode($fixture->homeTeam->name) : Html::encode($fixture->awayTeam->name) ?>)
                                </span>
                            </div>
                            <?php $detailText = $renderDetail($event->detail, $event->type); ?>
                            <?php if ($detailText): ?>
                            <div class="text-muted-gm" style="font-size:.78rem"><?= $detailText ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stats panel -->
        <div class="col-lg-4 d-flex flex-column gap-4">

            <!-- Match stats -->
            <div class="gm-card">
                <h3 class="h5 mb-3 text-white">
                    <i class="bi bi-pie-chart text-gold me-2"></i>Statistiche
                </h3>
                <?php if ($isScheduled): ?>
                <ul class="attribute-list">
                    <li>
                        <span class="text-muted-gm">Stato</span>
                        <span class="text-white">In attesa del calcio d'inizio</span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Orario</span>
                        <span class="text-white"><?= date('d/m/Y H:i', $fixture->match_date) ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Stadio</span>
                        <span class="text-white"><?= Html::encode((string)($fixture->homeTeam->stadium->name ?? '—')) ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Capienza stadio</span>
                        <span class="text-white">
                            <?= $fixture->homeTeam->stadium ? number_format($fixture->homeTeam->stadium->capacity) : '—' ?>
                        </span>
                    </li>
                </ul>
                <?php else: ?>
                <ul class="attribute-list">
                    <li>
                        <span class="text-muted-gm">Gol casa</span>
                        <span class="text-white fw-bold"><?= $goals['home'] ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Gol trasferta</span>
                        <span class="text-white fw-bold"><?= $goals['away'] ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Parate casa</span>
                        <span class="text-white"><?= $saves['home'] ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Parate trasferta</span>
                        <span class="text-white"><?= $saves['away'] ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Tiri fuori</span>
                        <span class="text-white"><?= $nearMisses ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Capienza stadio</span>
                        <span class="text-white">
                            <?= $fixture->homeTeam->stadium ? number_format($fixture->homeTeam->stadium->capacity) : '—' ?>
                        </span>
                    </li>
                </ul>
                <?php endif; ?>
            </div>

            <!-- State info (if available) -->
            <?php if (($isFinished || $isPlaying) && $state): ?>
            <div class="gm-card">
                <h3 class="h5 mb-3 text-white">
                    <i class="bi bi-info-circle text-gold me-2"></i>Stato Partita
                </h3>
                <ul class="attribute-list">
                    <li><span class="text-muted-gm">Minuto</span> <span class="text-white"><?= $state->current_minute ?>'</span></li>
                    <li><span class="text-muted-gm">Fase</span> <span class="text-white"><?= Html::encode($state->phase) ?></span></li>
                    <li><span class="text-muted-gm">Cambi casa</span> <span class="text-white"><?= $state->home_subs_used ?>/3</span></li>
                    <li><span class="text-muted-gm">Cambi trasferta</span> <span class="text-white"><?= $state->away_subs_used ?>/3</span></li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="mt-auto d-flex flex-column gap-2">
                <?php if ($isFinished): ?>
                <?= Html::a('<i class="bi bi-play-circle"></i> Guarda Replay', ['replay', 'id' => $fixture->id], ['class' => 'btn btn-gold w-100', 'encode' => false]) ?>
                <?php endif; ?>
                <?php if ($isPlaying): ?>
                <?= Html::a('<i class="bi bi-broadcast"></i> Vai al Live', ['live', 'id' => $fixture->id], ['class' => 'btn btn-danger w-100', 'encode' => false]) ?>
                <?php endif; ?>
                <?= Html::a('<i class="bi bi-arrow-left"></i> Calendario', ['index'], ['class' => 'btn btn-outline-gold w-100', 'encode' => false]) ?>
            </div>
        </div>
    </div>
</div>
