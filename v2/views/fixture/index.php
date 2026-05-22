<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Fixture[] $fixtures */
/** @var int $round */
/** @var int $totalRounds */
/** @var string $type */
/** @var int|null $myTeamId */

use app\models\Fixture;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $type === 'friendly' ? 'Amichevoli' : 'Calendario';
$this->params['breadcrumbs'][] = $this->title;

$urlLeague   = Url::to(['/fixture/index', 'type' => 'league',   'round' => $round]);
$urlFriendly = Url::to(['/fixture/index', 'type' => 'friendly', 'round' => $round]);
$urlPrev     = $round > 1           ? Url::to(['/fixture/index', 'type' => $type, 'round' => $round - 1]) : null;
$urlNext     = $round < $totalRounds ? Url::to(['/fixture/index', 'type' => $type, 'round' => $round + 1]) : null;

// Compute leg: first half of rounds = andata, second = ritorno
$midRound = (int) ceil($totalRounds / 2);
$legLabel = $round <= $midRound ? 'Andata' : 'Ritorno';
$legRound = $round <= $midRound ? $round : ($round - $midRound);
?>

<div class="fixture-index">

    <!-- Header + tabs -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h1 class="mb-0 fw-black"><?= Html::encode($this->title) ?></h1>
        <div class="d-flex gap-2">
            <a href="<?= Url::to(['/fixture/index', 'type' => 'league']) ?>"
               class="btn btn-sm <?= $type === 'league' ? 'btn-gold' : 'btn-outline-secondary' ?>">
                <i class="bi bi-trophy me-1"></i>Campionato
            </a>
            <a href="<?= Url::to(['/fixture/index', 'type' => 'friendly']) ?>"
               class="btn btn-sm <?= $type === 'friendly' ? 'btn-gold' : 'btn-outline-secondary' ?>">
                <i class="bi bi-play-circle me-1"></i>Amichevoli
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
            <div class="fw-bold text-white"><?= $legLabel ?> — Giornata <?= $legRound ?></div>
            <div class="text-muted-gm" style="font-size:.72rem">Giornata <?= $round ?> / <?= $totalRounds ?></div>
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
            Nessuna partita in questa giornata.
        </div>
    <?php else: ?>
    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm table-gm-sm w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:110px">Orario</th>
                        <th class="text-end">Casa</th>
                        <th class="text-center" style="width:90px">Risultato</th>
                        <th>Trasferta</th>
                        <th class="text-center" style="width:90px">Stato</th>
                        <th class="text-end" style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fixtures as $f):
                        $isMyHome = $myTeamId && $f->home_team_id === $myTeamId;
                        $isMyAway = $myTeamId && $f->away_team_id === $myTeamId;
                        $isMyMatch = $isMyHome || $isMyAway;
                        $rowStyle = $isMyMatch ? 'background:rgba(245,158,11,.05)' : '';
                    ?>
                    <tr style="<?= $rowStyle ?>">
                        <td class="text-muted-gm" style="font-size:.8rem">
                            <?= date('d/m H:i', $f->match_date) ?>
                        </td>
                        <td class="text-end">
                            <span class="<?= $isMyHome ? 'text-gold fw-bold' : 'text-white' ?>">
                                <?= Html::encode($f->homeTeam->name) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($f->status === Fixture::STATUS_FINISHED): ?>
                                <span class="fw-black text-gold"><?= $f->home_score ?>–<?= $f->away_score ?></span>
                                <?php
                                $s = \app\components\ScorerSheetService::buildForFixture($f->id);
                                $c = \app\components\ScorerSheetService::compactString($s);
                                if ($c): ?>
                                <div style="font-size:.62rem;color:var(--text-secondary);margin-top:1px"><?= Html::encode($c) ?></div>
                                <?php endif; ?>
                            <?php elseif ($f->status === Fixture::STATUS_PLAYING): ?>
                                <span class="fw-black text-danger">LIVE</span>
                            <?php else: ?>
                                <span class="text-muted-gm">vs</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="<?= $isMyAway ? 'text-gold fw-bold' : 'text-white' ?>">
                                <?= Html::encode($f->awayTeam->name) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <?php if ($f->status === Fixture::STATUS_PLAYING): ?>
                                <span class="badge bg-danger pulse" style="font-size:.65rem">LIVE</span>
                            <?php elseif ($f->status === Fixture::STATUS_FINISHED): ?>
                                <span class="badge bg-secondary" style="font-size:.65rem">FINITA</span>
                            <?php else: ?>
                                <span style="font-size:.65rem;color:var(--text-secondary)">prog.</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($f->status === Fixture::STATUS_PLAYING): ?>
                                <?= Html::a('<i class="bi bi-broadcast"></i> Live', ['live', 'id' => $f->id], ['class' => 'btn btn-danger btn-sm', 'encode' => false]) ?>
                            <?php elseif ($f->status === Fixture::STATUS_FINISHED): ?>
                                <div class="d-flex gap-1 justify-content-end">
                                    <?= Html::a('Report', ['view', 'id' => $f->id], ['class' => 'btn btn-outline-secondary btn-sm']) ?>
                                    <?= Html::a('<i class="bi bi-play-circle"></i>', ['replay', 'id' => $f->id], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false, 'title' => 'Replay']) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted-gm" style="font-size:.75rem"><?= date('H:i', $f->match_date) ?></span>
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
