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
<?php
$statsUrl = \yii\helpers\Url::to(['/site/stats']);
$registerUrl = \yii\helpers\Url::to(['/site/register']);
$loginUrl    = \yii\helpers\Url::to(['/site/login']);
?>
<!-- ── HERO ──────────────────────────────────────────────────────────── -->
<div style="margin:-1.5rem -1.5rem 0;padding:5rem 1.5rem 3rem;background:radial-gradient(ellipse 80% 60% at 50% -10%,rgba(245,158,11,.18) 0%,transparent 70%),linear-gradient(180deg,#0f172a 0%,#0d1526 100%);text-align:center;position:relative;overflow:hidden">
    <!-- Background grid -->
    <div aria-hidden="true" style="position:absolute;inset:0;background-image:linear-gradient(rgba(245,158,11,.04) 1px,transparent 1px),linear-gradient(90deg,rgba(245,158,11,.04) 1px,transparent 1px);background-size:40px 40px;pointer-events:none"></div>

    <!-- Logo mark -->
    <div style="display:inline-flex;align-items:center;justify-content:center;width:80px;height:80px;background:linear-gradient(135deg,rgba(245,158,11,.2),rgba(245,158,11,.05));border:1px solid rgba(245,158,11,.3);border-radius:22px;margin-bottom:1.5rem">
        <img src="<?= Yii::getAlias('@web/img/icon-192.svg') ?>" width="52" height="52" alt="Gold Manager icon">
    </div>

    <h1 style="font-size:clamp(2.4rem,6vw,4rem);font-weight:900;letter-spacing:-.03em;margin-bottom:.75rem;line-height:1.05">
        <span style="color:#f59e0b">GOLD</span> MANAGER
    </h1>
    <p style="font-size:clamp(1rem,2.5vw,1.2rem);color:#94a3b8;max-width:540px;margin:0 auto 2rem">
        <?= Yii::t('app', 'The most thrilling football management game. Build your dynasty from Serie C to the top.') ?>
    </p>

    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-bottom:3rem">
        <?= Html::a('<i class="bi bi-lightning-charge-fill me-2"></i>' . Yii::t('app', 'Start for free'), ['/site/register'], ['class' => 'btn btn-gold btn-lg px-5', 'encode' => false, 'style' => 'font-size:1.05rem;font-weight:700']) ?>
        <?= Html::a(Yii::t('app', 'Login'), ['/site/login'], ['class' => 'btn btn-outline-gold btn-lg px-4']) ?>
    </div>

    <!-- Live stats bar -->
    <div id="landing-stats" style="display:inline-flex;gap:2rem;flex-wrap:wrap;justify-content:center;padding:1rem 2rem;background:rgba(255,255,255,.03);border:1px solid rgba(245,158,11,.2);border-radius:999px">
        <div style="text-align:center">
            <div class="text-gold fw-black" style="font-size:1.5rem" id="stat-managers">—</div>
            <div style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em"><?= Yii::t('app', 'Managers') ?></div>
        </div>
        <div style="width:1px;background:rgba(255,255,255,.1)"></div>
        <div style="text-align:center">
            <div class="text-gold fw-black" style="font-size:1.5rem" id="stat-total-matches">—</div>
            <div style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em"><?= Yii::t('app', 'Matches') ?></div>
        </div>
        <div style="width:1px;background:rgba(255,255,255,.1)"></div>
        <div style="text-align:center">
            <div class="text-gold fw-black" style="font-size:1.5rem" id="stat-total-goals">—</div>
            <div style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em"><?= Yii::t('app', 'Goals') ?></div>
        </div>
        <div style="width:1px;background:rgba(255,255,255,.1)"></div>
        <div style="text-align:center">
            <div style="font-size:1.5rem;font-weight:900" id="stat-live">
                <span class="badge bg-danger pulse" style="font-size:.75rem;vertical-align:middle">LIVE</span>
                <span id="stat-live-num" style="color:#f59e0b">—</span>
            </div>
            <div style="font-size:.72rem;color:#64748b;text-transform:uppercase;letter-spacing:.06em"><?= Yii::t('app', 'Now') ?></div>
        </div>
    </div>
</div>

<!-- ── TODAY TICKER ───────────────────────────────────────────────── -->
<div id="today-ticker" style="background:rgba(245,158,11,.06);border-top:1px solid rgba(245,158,11,.15);border-bottom:1px solid rgba(245,158,11,.15);padding:.55rem 1.5rem;display:none">
    <span style="font-size:.8rem;color:#94a3b8">
        <?= Yii::t('app', 'Today') ?>:
        <span class="text-gold fw-bold" id="stat-today-matches">—</span> <?= Yii::t('app', 'matches played') ?>,
        <span class="text-gold fw-bold" id="stat-today-goals">—</span> <?= Yii::t('app', 'goals scored') ?>
    </span>
</div>

<!-- ── FEATURES GRID ──────────────────────────────────────────────── -->
<div style="padding:4rem 0 3rem">
    <h2 style="text-align:center;font-weight:900;font-size:1.8rem;margin-bottom:.5rem"><?= Yii::t('app', 'Everything a real manager needs') ?></h2>
    <p style="text-align:center;color:#64748b;margin-bottom:3rem;font-size:.95rem"><?= Yii::t('app', 'A complete simulation, updated in real time') ?></p>

    <div class="row g-3">
        <?php
        $features = [
            ['bi-graph-up-arrow',    '#10b981', Yii::t('app', 'Live Matches'),        Yii::t('app', 'Watch your team play minute by minute with AI commentary and live stats.')],
            ['bi-people-fill',       '#3b82f6', Yii::t('app', 'Transfer Market'),     Yii::t('app', 'Buy, sell, and loan players. Negotiate contracts with termination fees.')],
            ['bi-shield-shaded',     '#f59e0b', Yii::t('app', 'Tactics & Formations'),Yii::t('app', 'Full tactical control — change formation, effort, and style live during the match.')],
            ['bi-trophy-fill',       '#f59e0b', Yii::t('app', 'League Progression'),  Yii::t('app', 'Climb from Serie C to Serie A. Automatic promotion, relegation, and new leagues.')],
            ['bi-person-badge-fill', '#a855f7', Yii::t('app', 'Staff Management'),    Yii::t('app', 'Hire coaches, doctors, scouts. Each role amplifies your team\'s performance.')],
            ['bi-cpu',               '#06b6d4', Yii::t('app', 'AI Engine'),            Yii::t('app', 'Go-powered match engine + Ollama LLM commentary. The most realistic simulation.')],
            ['bi-cash-stack',        '#10b981', Yii::t('app', 'Economy'),              Yii::t('app', 'Manage wages, sponsors, stadium revenues. Careful budgeting wins championships.')],
            ['bi-bell-fill',         '#f59e0b', Yii::t('app', 'Notifications'),        Yii::t('app', 'Push and Telegram alerts for goals, transfers, and pre-match reminders.')],
        ];
        foreach ($features as $i => [$icon, $color, $title, $desc]):
        ?>
        <div class="col-sm-6 col-lg-3">
            <div class="gm-card h-100 p-3" style="transition:transform .15s,border-color .15s" onmouseover="this.style.transform='translateY(-3px)';this.style.borderColor='rgba(245,158,11,.35)'" onmouseout="this.style.transform='';this.style.borderColor=''">
                <div style="width:40px;height:40px;background:<?= $color ?>1a;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:.85rem">
                    <i class="bi <?= $icon ?>" style="color:<?= $color ?>;font-size:1.1rem"></i>
                </div>
                <div class="fw-bold small mb-1"><?= $title ?></div>
                <div style="font-size:.78rem;color:#64748b;line-height:1.45"><?= $desc ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── HOW IT WORKS ───────────────────────────────────────────────── -->
<div style="padding:3rem 0;border-top:1px solid var(--border)">
    <h2 style="text-align:center;font-weight:900;font-size:1.6rem;margin-bottom:2.5rem"><?= Yii::t('app', 'Up and running in 30 seconds') ?></h2>
    <div class="row g-4 justify-content-center" style="max-width:800px;margin:0 auto">
        <?php foreach ([
            ['01', '#f59e0b', Yii::t('app', 'Register'),         Yii::t('app', 'Choose your username and email. Free forever.')],
            ['02', '#10b981', Yii::t('app', 'Get your team'),    Yii::t('app', 'You\'re automatically assigned a team in Serie C.')],
            ['03', '#3b82f6', Yii::t('app', 'Build & compete'),  Yii::t('app', 'Sign players, choose tactics, follow live matches.')],
            ['04', '#a855f7', Yii::t('app', 'Rise to the top'),  Yii::t('app', 'Win the league, get promoted, become a legend.')],
        ] as [$num, $col, $step, $detail]): ?>
        <div class="col-sm-6">
            <div style="display:flex;gap:1rem;align-items:flex-start">
                <div style="font-size:1.4rem;font-weight:900;color:<?= $col ?>;opacity:.7;min-width:2rem;flex-shrink:0"><?= $num ?></div>
                <div>
                    <div class="fw-bold small mb-1"><?= $step ?></div>
                    <div style="font-size:.8rem;color:#64748b"><?= $detail ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ── FINAL CTA ──────────────────────────────────────────────────── -->
<div style="text-align:center;padding:3rem 1rem 4rem;background:radial-gradient(ellipse 60% 80% at 50% 100%,rgba(245,158,11,.1) 0%,transparent 70%)">
    <h2 style="font-weight:900;font-size:clamp(1.5rem,4vw,2.4rem);margin-bottom:.75rem"><?= Yii::t('app', 'Your team is waiting for you') ?></h2>
    <p style="color:#64748b;margin-bottom:1.75rem;font-size:.95rem"><?= Yii::t('app', 'Join the managers already competing. Free. No download needed.') ?></p>
    <?= Html::a('<i class="bi bi-trophy-fill me-2"></i>' . Yii::t('app', 'Register now — it\'s free'), ['/site/register'], ['class' => 'btn btn-gold btn-lg px-5', 'encode' => false, 'style' => 'font-size:1.1rem;font-weight:700;padding:.8rem 2.5rem']) ?>
    <div style="margin-top:1rem;font-size:.8rem;color:#475569">
        <?= Yii::t('app', 'Already registered?') ?>
        <?= Html::a(Yii::t('app', 'Login'), ['/site/login'], ['style' => 'color:#f59e0b']) ?>
    </div>
</div>

<!-- ── STATS FETCH ────────────────────────────────────────────────── -->
<script>
(function(){
    function fmt(n){return Number(n).toLocaleString('it-IT');}
    fetch('<?= $statsUrl ?>')
        .then(function(r){return r.json();})
        .then(function(d){
            document.getElementById('stat-managers').textContent      = fmt(d.managers);
            document.getElementById('stat-total-matches').textContent = fmt(d.total_matches);
            document.getElementById('stat-total-goals').textContent   = fmt(d.total_goals);
            document.getElementById('stat-live-num').textContent      = d.live_now;
            document.getElementById('stat-today-matches').textContent = fmt(d.matches_today);
            document.getElementById('stat-today-goals').textContent   = fmt(d.goals_today);
            if(d.matches_today > 0){
                document.getElementById('today-ticker').style.display = 'block';
            }
        })
        .catch(function(){});
})();
</script>

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
