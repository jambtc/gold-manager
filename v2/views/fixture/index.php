<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Fixture[] $fixtures */
/** @var int $round */
/** @var int $totalRounds */
/** @var string $type */
/** @var int|null $myTeamId */

use app\models\Fixture;
use app\models\Team;
use app\components\FixtureViewHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $type === 'friendly' ? Yii::t('app', 'Friendlies') : Yii::t('app', 'Calendar');
$this->params['breadcrumbs'][] = $this->title;

$urlLeague   = Url::to(['/fixture/index', 'type' => 'league',   'round' => $round]);
$urlFriendly = Url::to(['/fixture/index', 'type' => 'friendly', 'round' => $round]);
$urlPrev     = $round > 1           ? Url::to(['/fixture/index', 'type' => $type, 'round' => $round - 1]) : null;
$urlNext     = $round < $totalRounds ? Url::to(['/fixture/index', 'type' => $type, 'round' => $round + 1]) : null;

// Compute leg: first half of rounds = andata, second = ritorno
$midRound = (int) ceil($totalRounds / 2);
$legLabel = $round <= $midRound ? Yii::t('app', 'Home leg') : Yii::t('app', 'Return leg');
$legRound = $round <= $midRound ? $round : ($round - $midRound);
?>

<div class="fixture-index">

    <!-- Header + tabs -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h1 class="mb-0 fw-black"><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= Url::to(['/fixture/index', 'type' => 'league']) ?>"
               class="btn btn-sm <?= $type === 'league' ? 'btn-gold' : 'btn-outline-secondary' ?>">
                <i class="bi bi-trophy me-1"></i><?= Yii::t('app', 'League') ?>
            </a>
            <a href="<?= Url::to(['/fixture/index', 'type' => 'friendly']) ?>"
               class="btn btn-sm <?= $type === 'friendly' ? 'btn-gold' : 'btn-outline-secondary' ?>">
                <i class="bi bi-play-circle me-1"></i><?= Yii::t('app', 'Friendlies') ?>
            </a>
        </div>
    </div>

    <!-- Round navigation (league only) -->
    <?php if ($type === 'league' && $totalRounds > 0): ?>
    <div class="d-flex align-items-center justify-content-between mb-3 gm-card py-2 px-3">
        <?php if ($urlPrev): ?>
            <a href="<?= $urlPrev ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-left"></i>
            </a>
        <?php else: ?>
            <span class="btn btn-outline-secondary btn-sm disabled opacity-25"><i class="bi bi-chevron-left"></i></span>
        <?php endif; ?>

        <div class="text-center">
            <div class="fw-bold text-white"><?= $legLabel ?> — <?= Yii::t('app', 'Matchday') ?> <?= $legRound ?></div>
            <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Matchday') ?> <?= $round ?> / <?= $totalRounds ?></div>
        </div>

        <?php if ($urlNext): ?>
            <a href="<?= $urlNext ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-chevron-right"></i>
            </a>
        <?php else: ?>
            <span class="btn btn-outline-secondary btn-sm disabled opacity-25"><i class="bi bi-chevron-right"></i></span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($fixtures)): ?>
        <div class="gm-card text-center py-5 text-muted-gm">
            <i class="bi bi-calendar-x d-block fs-1 mb-2 opacity-25"></i>
            <?= Yii::t('app', 'No matches on this matchday.') ?>
        </div>
    <?php else: ?>
    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm table-gm-sm w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:110px"><?= Yii::t('app', 'Time') ?></th>
                        <th class="text-end"><?= Yii::t('app', 'Home') ?></th>
                        <th class="text-center" style="width:90px"><?= Yii::t('app', 'Result') ?></th>
                        <th><?= Yii::t('app', 'Away') ?></th>
                        <th class="text-center" style="width:90px"><?= Yii::t('app', 'Status') ?></th>
                        <th class="text-end" style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fixtures as $f):
                        $isMyHome = $myTeamId && $f->home_team_id === $myTeamId;
                        $isMyAway = $myTeamId && $f->away_team_id === $myTeamId;
                        $isMyMatch = $isMyHome || $isMyAway;
                        $rowStyle = $isMyMatch ? 'background:rgba(245,158,11,.05)' : '';
                        $hcl = Team::sanitizeHexColor($f->homeTeam->color_left  ?? null, Team::DEFAULT_COLOR_LEFT);
                        $hcr = Team::sanitizeHexColor($f->homeTeam->color_right ?? null, Team::DEFAULT_COLOR_RIGHT);
                        $acl = Team::sanitizeHexColor($f->awayTeam->color_left  ?? null, Team::DEFAULT_COLOR_LEFT);
                        $acr = Team::sanitizeHexColor($f->awayTeam->color_right ?? null, Team::DEFAULT_COLOR_RIGHT);
                        $hLetter = mb_substr((string) $f->homeTeam->name, 0, 1);
                        $aLetter = mb_substr((string) $f->awayTeam->name, 0, 1);
                    ?>
                    <tr style="<?= $rowStyle ?>">
                        <td class="text-muted-gm" style="font-size:.8rem">
                            <?= date('d/m H:i', $f->match_date) ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex align-items-center justify-content-end gap-2">
                                <span class="<?= $isMyHome ? 'text-gold fw-bold' : 'text-white' ?>" style="font-size:.88rem">
                                    <?= Html::encode($f->homeTeam->name) ?>
                                </span>
                                <?= FixtureViewHelper::renderShieldSvg($hcl, $hcr, $hLetter, 22, 24, 'idx') ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($f->status === Fixture::STATUS_FINISHED): ?>
                                <span class="fw-black" style="font-size:1.15rem;color:var(--gold);letter-spacing:.02em"><?= $f->home_score ?>–<?= $f->away_score ?></span>
                            <?php elseif ($f->status === Fixture::STATUS_PLAYING): ?>
                                <span class="fw-black" style="font-size:1.15rem;color:var(--accent-red)">LIVE</span>
                            <?php else: ?>
                                <span class="text-muted-gm" style="font-size:.9rem">vs</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?= FixtureViewHelper::renderShieldSvg($acl, $acr, $aLetter, 22, 24, 'idx') ?>
                                <span class="<?= $isMyAway ? 'text-gold fw-bold' : 'text-white' ?>" style="font-size:.88rem">
                                    <?= Html::encode($f->awayTeam->name) ?>
                                </span>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if ($f->status === Fixture::STATUS_PLAYING): ?>
                                <span class="badge bg-danger pulse" style="font-size:.65rem">LIVE</span>
                            <?php elseif ($f->status === Fixture::STATUS_FINISHED): ?>
                                <span class="badge bg-secondary" style="font-size:.65rem"><?= Yii::t('app', 'FINISHED') ?></span>
                            <?php else: ?>
                                <span style="font-size:.65rem;color:var(--text-secondary)">prog.</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($f->status === Fixture::STATUS_PLAYING): ?>
                                <?= Html::a('<i class="bi bi-broadcast"></i> Live', ['live', 'id' => $f->id], ['class' => 'btn btn-danger btn-sm', 'encode' => false]) ?>
                            <?php elseif ($f->status === Fixture::STATUS_FINISHED): ?>
                                <div class="d-flex gap-1 justify-content-end">
                                    <?= Html::a(Yii::t('app', 'Report'), ['view', 'id' => $f->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                                    <?= Html::a('<i class="bi bi-play-circle"></i>', ['replay', 'id' => $f->id], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false, 'title' => Yii::t('app', 'Replay')]) ?>
                                </div>
                            <?php else: ?>
                                <?= Html::a(date('H:i', $f->match_date), ['view', 'id' => $f->id], ['style' => 'font-size:.75rem;color:var(--text-secondary);text-decoration:none', 'title' => Yii::t('app', 'Match details')]) ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>
