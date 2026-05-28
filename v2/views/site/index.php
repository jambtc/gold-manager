<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team|null $team */
/** @var app\models\Fixture|null $nextFixture */
/** @var app\models\Fixture|null $nextFriendlyFixture */
/** @var app\models\Fixture[] $recentFixtures */
/** @var app\models\Standing|null $standing */
/** @var app\models\Competition|null $competition */
/** @var app\models\Standing[] $leagueTable */
/** @var app\models\NewsItem[] $latestNews */

use app\models\Fixture;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $team ? Html::encode($team->name) . ' — Dashboard' : 'Gold Manager';

// Helpers
$pos = function (int $n): string {
    return match($n) { 1 => '1°', 2 => '2°', 3 => '3°', default => $n . '°' };
};
$eur = fn(int $n) => '€' . number_format($n, 0, ',', '.');
$crest = function (?\app\models\Team $club, int $width = 30, int $height = 34): string {
    if ($club === null) {
        return '';
    }

    static $id = 0;
    $id++;
    $clipLeft = 'dashShieldL' . $id;
    $clipRight = 'dashShieldR' . $id;
    $left = \app\models\Team::sanitizeHexColor($club->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
    $right = \app\models\Team::sanitizeHexColor($club->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
    $letter = mb_substr($club->name, 0, 1);

    return '<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 36 40" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
        . '<defs>'
        . '<clipPath id="' . $clipLeft . '"><rect x="0" y="0" width="18" height="40"/></clipPath>'
        . '<clipPath id="' . $clipRight . '"><rect x="18" y="0" width="18" height="40"/></clipPath>'
        . '</defs>'
        . '<path d="M18 2 L34 8 L34 22 Q34 34 18 39 Q2 34 2 22 L2 8 Z" fill="' . $left . '" clip-path="url(#' . $clipLeft . ')" opacity=".95"/>'
        . '<path d="M18 2 L34 8 L34 22 Q34 34 18 39 Q2 34 2 22 L2 8 Z" fill="' . $right . '" clip-path="url(#' . $clipRight . ')" opacity=".95"/>'
        . '<path d="M18 2 L34 8 L34 22 Q34 34 18 39 Q2 34 2 22 L2 8 Z" fill="none" stroke="rgba(255,255,255,.18)" stroke-width="1.2"/>'
        . '<path d="M18 5 L31 10 L31 22 Q31 32 18 37 Q5 32 5 22 L5 10 Z" fill="rgba(255,255,255,.08)"/>'
        . '<text x="18" y="24" text-anchor="middle" dominant-baseline="middle" fill="#fff" font-weight="900" font-size="13" font-family="system-ui">' . Html::encode($letter) . '</text>'
        . '</svg>';
};
?>

<?php if (!Yii::$app->user->isGuest && $team === null): ?>
<!-- ── Waiting for team assignment ─────────────────────────────────── -->
<div class="d-flex align-items-center justify-content-center" style="min-height:60vh">
    <div class="text-center">
        <div class="fs-1 mb-3">⏳</div>
        <h2 class="text-gold"><?= Yii::t('app', 'Team not yet assigned') ?></h2>
        <p class="text-muted-gm"><?= Yii::t('app', 'Contact administrator or try again in a moment.') ?></p>
    </div>
</div>

<?php elseif (Yii::$app->user->isGuest): ?>
<!-- ── Landing hero ────────────────────────────────────────────────── -->
<div class="row align-items-center g-5 py-5">
    <div class="col-lg-6">
        <h1 class="display-4 fw-black mb-3">
            <span class="text-gold">GOLD</span> MANAGER
        </h1>
        <p class="lead text-muted-gm mb-4">
            <?= Yii::t('app', 'The most thrilling football management game. Build your team, climb from the lower leagues to the top, and become a legend.') ?>
        </p>
        <div class="d-flex gap-3 flex-wrap">
            <?= Html::a(Yii::t('app', 'Register — it\'s free'), ['/site/register'], ['class' => 'btn btn-gold btn-lg px-5']) ?>
            <?= Html::a(Yii::t('app', 'Login'), ['/site/login'], ['class' => 'btn btn-outline-gold btn-lg px-4']) ?>
        </div>
        <div class="mt-4 d-flex gap-4">
            <div><span class="text-gold fw-bold fs-5">3</span> <span class="text-muted-gm small"><?= Yii::t('app', 'Division') ?></span></div>
            <div><span class="text-gold fw-bold fs-5">16</span> <span class="text-muted-gm small"><?= Yii::t('app', 'Teams per group') ?></span></div>
            <div><span class="text-gold fw-bold fs-5">30</span> <span class="text-muted-gm small"><?= Yii::t('app', 'Matchdays') ?></span></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="gm-card p-4" style="background: linear-gradient(135deg, rgba(245,158,11,0.08) 0%, rgba(15,23,42,0.9) 100%)">
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width:48px;height:48px;background:var(--gold-glow);border-radius:12px;display:flex;align-items:center;justify-content:center">
                    <i class="bi bi-trophy-fill text-gold fs-5"></i>
                </div>
                <div>
                    <div class="fw-bold"><?= Yii::t('app', 'How it works') ?></div>
                    <div class="text-muted-gm small"><?= Yii::t('app', 'Register and start now') ?></div>
                </div>
            </div>
            <?php foreach ([
                ['bi-person-plus', Yii::t('app', 'Register'), Yii::t('app', 'Create your account in 30 seconds')],
                ['bi-shield-shaded', Yii::t('app', 'Take the team'), Yii::t('app', 'You are assigned a team in Serie C')],
                ['bi-graph-up-arrow', Yii::t('app', 'Climb the leagues'), Yii::t('app', 'Win the league and get promoted to B, then A')],
                ['bi-stars', Yii::t('app', 'Become a legend'), Yii::t('app', 'Build a winning dynasty')],
            ] as $i => [$icon, $title, $desc]): ?>
            <div class="d-flex gap-3 mb-3 <?= $i < 3 ? 'pb-3 border-bottom' : '' ?>" style="border-color: var(--border) !important">
                <div class="text-gold mt-1"><i class="bi <?= $icon ?>"></i></div>
                <div>
                    <div class="fw-semibold small"><?= $title ?></div>
                    <div class="text-muted-gm" style="font-size:.8rem"><?= $desc ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php else: ?>
<!-- ── Manager Dashboard ───────────────────────────────────────────── -->

<?php
$players   = $team->players;
$sumOvr = 0;
foreach ($players as $_p) {
    $sumOvr += (int) $_p->getNaturalOverall();
}
$avgSkill  = count($players) ? round($sumOvr / count($players), 1) : 0;
$wageBill  = $team->totalWageBill();
$stadium   = $team->stadium;
$timeTravelEnabled = YII_ENV_DEV && getenv('GM_TEST_TIME_TRAVEL') === '1';
$myRank    = 0;
foreach ($leagueTable as $i => $s) {
    if ($s->team_id === $team->id) { $myRank = $i + 1; break; }
}
?>

<!-- Live match alert -->
<?php if ($nextFixture && $nextFixture->status === Fixture::STATUS_PLAYING): ?>
<div class="gm-card mb-4 p-3" style="background:rgba(220,38,38,.12);border-color:rgba(220,38,38,.4)">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-danger pulse">LIVE</span>
            <span class="fw-bold"><?= Yii::t('app', 'Match in progress — your team is on the pitch!') ?></span>
        </div>
        <?= Html::a(Yii::t('app', 'Follow live') . ' &rarr;', ['/fixture/live', 'id' => $nextFixture->id], ['class' => 'btn btn-danger btn-sm px-3', 'encode' => false]) ?>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">

    <!-- ── Col sx: team card + stat bar ── -->
    <div class="col-xl-8">

        <!-- Team header -->
        <div class="gm-card gm-card-gold mb-4">
            <div class="d-flex align-items-start gap-3 mb-4">
                <div class="flex-shrink-0" style="width:56px;height:56px;background:rgba(255,255,255,.05);border:1px solid var(--border);border-radius:14px;display:flex;align-items:center;justify-content:center">
                    <?= $crest($team, 44, 49) ?>
                </div>
                <div class="flex-grow-1">
                    <h2 class="mb-0 fw-black"><?= Html::encode($team->name) ?></h2>
                    <div class="text-muted-gm small">
                        <?= $competition ? Html::encode($competition->getLabel()) : '—' ?>
                        <?php if ($myRank): ?>
                            &nbsp;·&nbsp; <span class="text-gold fw-bold"><?= $pos($myRank) ?> <?= Yii::t('app', 'seat') ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fs-4 fw-black text-gold"><?= $eur($team->budget) ?></div>
                    <div class="text-muted-gm" style="font-size:.75rem"><?= Yii::t('app', 'available budget') ?></div>
                </div>
            </div>

            <!-- Stat pills -->
            <div class="row g-3 mb-4">
                <?php foreach ([
                    [Yii::t('app', 'Players'),    count($players),                   'bi-people'],
                    [Yii::t('app', 'Avg OVR'),    $avgSkill,                         'bi-bar-chart'],
                    [Yii::t('app', 'Wages/week'), $eur($wageBill),                   'bi-cash-stack'],
                    [Yii::t('app', 'Stadium cap.'), $stadium ? number_format($stadium->capacity) : '—', 'bi-building'],
                ] as [$label, $val, $icon]): ?>
                <div class="col-6 col-md-3">
                    <div class="p-3 rounded-3 text-center" style="background:rgba(255,255,255,.03);border:1px solid var(--border)">
                        <i class="bi <?= $icon ?> text-gold d-block mb-1"></i>
                        <div class="fw-bold"><?= $val ?></div>
                        <div class="text-muted-gm" style="font-size:.7rem"><?= $label ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Top 3 players -->
            <?php
            $sorted = $players;
            usort($sorted, fn($a, $b) => $b->getNaturalOverall() <=> $a->getNaturalOverall());
            $top3 = array_slice($sorted, 0, 3);
            ?>
            <div class="small text-muted-gm text-uppercase fw-bold mb-2" style="letter-spacing:.06em"><?= Yii::t('app', 'Top player') ?></div>
            <div class="row g-2">
                <?php foreach ($top3 as $rank => $p): ?>
                <div class="col-md-4">
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3" style="background:rgba(255,255,255,.03);border:1px solid var(--border)">
                        <span class="badge-gm pos-<?= strtolower($p->position) ?>"><?= $p->position ?></span>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="text-truncate fw-semibold small"><?= Html::encode($p->name) ?></div>
                            <div class="text-gold" style="font-size:.7rem">OVR <?= $p->getNaturalOverall() ?> · <?= $p->age ?> <?= Yii::t('app', 'years') ?></div>
                        </div>
                        <?php if ($rank === 0): ?><i class="bi bi-star-fill text-gold" style="font-size:.75rem"></i><?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($timeTravelEnabled): ?>
            <div class="d-flex gap-2 mt-4 flex-wrap">
                <?= Html::beginForm(['/test-time/advance-day'], 'post', ['class' => 'd-inline']) ?>
                    <button type="submit"
                            class="btn btn-outline-warning btn-sm"
                            data-confirm="<?= Yii::t('app', 'Test operation: advance by 1 day?') ?>">
                        <i class="bi bi-fast-forward"></i> <?= Yii::t('app', '+1 day (test)') ?>
                    </button>
                <?= Html::endForm() ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent results -->
        <div class="gm-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-gold me-2"></i><?= Yii::t('app', 'Recent results') ?></h5>
                <?= Html::a(Yii::t('app', 'All'), ['/fixture/index'], ['class' => 'text-gold small text-decoration-none']) ?>
            </div>
            <?php if (empty($recentFixtures)): ?>
                <p class="text-muted-gm small mb-0"><?= Yii::t('app', 'No matches played yet. The season has not started.') ?></p>
            <?php else: ?>
                <?php foreach ($recentFixtures as $f): ?>
                <?php
                $isHome  = $f->home_team_id === $team->id;
                $myScore = $isHome ? $f->home_score : $f->away_score;
                $opScore = $isHome ? $f->away_score : $f->home_score;
                $result  = $myScore > $opScore ? 'W' : ($myScore < $opScore ? 'L' : 'D');
                $colors  = ['W' => '#10b981', 'D' => '#f59e0b', 'L' => '#ef4444'];
                $opponent = $isHome ? $f->awayTeam->name : $f->homeTeam->name;
                ?>
                <div class="d-flex align-items-center gap-3 py-2 border-bottom" style="border-color:var(--border)!important">
                    <span class="fw-black" style="color:<?= $colors[$result] ?>;min-width:1.2rem"><?= $result ?></span>
                    <span class="text-muted-gm small" style="min-width:3rem"><?= date('d/m', $f->match_date) ?></span>
                    <span class="flex-grow-1 small text-truncate"><?= $isHome ? Yii::t('app', 'vs') . ' ' : '@ ' ?><?= Html::encode($opponent) ?></span>
                    <span class="fw-bold" style="color:<?= $colors[$result] ?>"><?= $myScore ?>–<?= $opScore ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── Col dx: prossima partita + classifica ── -->
    <div class="col-xl-4">

        <!-- Next fixture -->
        <div class="gm-card mb-4">
            <h5 class="mb-3 fw-bold"><i class="bi bi-calendar-event text-gold me-2"></i><?= Yii::t('app', 'Next match') ?></h5>
            <?php if ($nextFixture && $nextFixture->status !== Fixture::STATUS_PLAYING): ?>
            <?php
            $isHome   = $nextFixture->home_team_id === $team->id;
            $opponent = $isHome ? $nextFixture->awayTeam : $nextFixture->homeTeam;
            $daysLeft = max(0, (int)(($nextFixture->match_date - time()) / 86400));
            ?>
            <div class="text-center py-2">
                <div class="text-muted-gm small mb-3"><?= $isHome ? Yii::t('app', 'HOME') : Yii::t('app', 'AWAY') ?></div>
                <div class="d-flex align-items-center justify-content-center gap-3 mb-3">
                    <div class="text-center">
                        <div class="mb-1"><?= $crest($team, 42, 47) ?></div>
                        <div class="small fw-bold text-truncate" style="max-width:100px"><?= Html::encode($team->name) ?></div>
                    </div>
                    <div class="text-muted-gm fw-black fs-5">VS</div>
                    <div class="text-center">
                        <div class="mb-1"><?= $crest($opponent, 42, 47) ?></div>
                        <div class="small fw-bold text-truncate" style="max-width:100px"><?= Html::encode($opponent->name) ?></div>
                    </div>
                </div>
                <div class="text-gold fw-bold mb-1"><?= date('d M Y — H:i', $nextFixture->match_date) ?></div>
                <div class="text-muted-gm small">
                    <?= $daysLeft === 0 ? Yii::t('app', 'Today!') : Yii::t('app', 'in') . " $daysLeft " . ($daysLeft === 1 ? Yii::t('app', 'day') : Yii::t('app', 'days')) ?>
                </div>
            </div>
            <div class="mt-3 d-grid gap-2">
                <?= Html::a(Yii::t('app', 'Match details'), ['/fixture/view', 'id' => $nextFixture->id], ['class' => 'btn btn-outline-gold btn-sm']) ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-calendar-x text-muted-gm fs-1 d-block mb-2"></i>
                <span class="text-muted-gm small"><?= Yii::t('app', 'No matches scheduled.') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Next friendly -->
        <div class="gm-card mb-4">
            <h5 class="mb-3 fw-bold"><i class="bi bi-play-circle text-gold me-2"></i><?= Yii::t('app', 'Next friendly') ?></h5>
            <?php if ($nextFriendlyFixture): ?>
            <?php
            $isHomeFriendly = $nextFriendlyFixture->home_team_id === $team->id;
            $friendlyOpp = $isHomeFriendly ? $nextFriendlyFixture->awayTeam : $nextFriendlyFixture->homeTeam;
            $friendlyDays = max(0, (int)(($nextFriendlyFixture->match_date - time()) / 86400));
            ?>
            <div class="text-center py-2">
                <div class="text-muted-gm small mb-2"><?= $isHomeFriendly ? Yii::t('app', 'HOME') : Yii::t('app', 'AWAY') ?></div>
                <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                    <span><?= $crest($team, 24, 27) ?></span>
                    <span class="small fw-bold text-white"><?= Html::encode($team->name) ?></span>
                    <span class="text-muted-gm small">vs</span>
                    <span><?= $crest($friendlyOpp, 24, 27) ?></span>
                    <span class="small fw-bold text-white"><?= Html::encode($friendlyOpp->name) ?></span>
                </div>
                <div class="text-gold fw-bold mb-1"><?= date('d M Y — H:i', $nextFriendlyFixture->match_date) ?></div>
                <div class="text-muted-gm small">
                    <?= $friendlyDays === 0 ? Yii::t('app', 'Today!') : Yii::t('app', 'in') . " $friendlyDays " . ($friendlyDays === 1 ? Yii::t('app', 'day') : Yii::t('app', 'days')) ?>
                </div>
            </div>
            <div class="mt-3 d-grid gap-2">
                <?= Html::a(Yii::t('app', 'Friendly details'), ['/fixture/view', 'id' => $nextFriendlyFixture->id], ['class' => 'btn btn-outline-gold btn-sm']) ?>
            </div>
            <?php else: ?>
            <div class="text-center py-4">
                <i class="bi bi-calendar-plus text-muted-gm fs-1 d-block mb-2"></i>
                <span class="text-muted-gm small"><?= Yii::t('app', 'No friendly scheduled.') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- News widget -->
        <?php if (!empty($latestNews)): ?>
        <div class="gm-card mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-bell text-gold me-2"></i><?= Yii::t('app', 'News') ?></h5>
                <?= Html::a(Yii::t('app', 'All') . ' →', ['/news/index'], ['style' => 'font-size:.75rem;color:var(--gold)']) ?>
            </div>
            <?php foreach ($latestNews as $i => $item): ?>
            <div style="display:flex;align-items:flex-start;gap:.6rem;padding:.5rem 0;<?= $i > 0 ? 'border-top:1px solid var(--border)' : '' ?>">
                <span style="font-size:1rem;flex-shrink:0"><?= Html::encode($item->icon) ?></span>
                <div style="flex:1;min-width:0">
                    <div style="font-size:.78rem;font-weight:700;color:<?= $item->priority > 0 ? 'var(--gold)' : 'var(--text-primary)' ?>;line-height:1.3"><?= Html::encode($item->title) ?></div>
                    <?php if ($item->body): ?><div style="font-size:.68rem;color:var(--text-secondary)"><?= Html::encode($item->body) ?></div><?php endif; ?>
                </div>
                <div style="font-size:.6rem;color:var(--text-secondary);flex-shrink:0;white-space:nowrap"><?= date('H:i', $item->created_at) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>
