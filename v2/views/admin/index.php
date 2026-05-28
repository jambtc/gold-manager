<?php

/** @var yii\web\View $this */
/** @var app\models\Competition[] $competitions */
/** @var app\models\User[] $managers */
/** @var app\models\Team[] $teamsByUser */
/** @var app\models\Standing[][] $standingsByComp */
/** @var array $stats */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Admin Panel';
?>

<div class="py-4">

    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <h1 class="h3 fw-black mb-0 me-3">Admin Panel</h1>
        <span class="badge bg-danger px-2">Admin</span>
        <span class="ms-3 text-muted-gm small"><?= Yii::t('app', 'Season') ?> <?= (int)$stats['season'] ?></span>
    </div>

    <!-- Flash messages -->
    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="gm-card mb-3 p-3" style="border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.08)">
            <i class="bi bi-check-circle text-success me-2"></i>
            <span><?= Html::encode(Yii::$app->session->getFlash('success')) ?></span>
        </div>
    <?php endif; ?>
    <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="gm-card mb-3 p-3" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08)">
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            <span><?= Html::encode(Yii::$app->session->getFlash('error')) ?></span>
        </div>
    <?php endif; ?>

    <!-- Stats cards -->
    <div class="row g-3 mb-5">
        <?php foreach ([
            ['bi-people-fill',     Yii::t('app', 'Active managers'),   $stats['managers'],      'var(--accent-blue)'],
            ['bi-controller',      Yii::t('app', 'Free slots (B)'),  $stats['free_slots'],    'var(--accent-green)'],
            ['bi-trophy-fill',     Yii::t('app', 'Leagues'),       $stats['competitions'],  'var(--gold)'],
            ['bi-broadcast',       Yii::t('app', 'In progress'),         $stats['playing'],       'var(--accent-red)'],
            ['bi-calendar2-check', Yii::t('app', 'Scheduled'),      $stats['scheduled'],     'var(--text-secondary)'],
            ['bi-flag-fill',       Yii::t('app', 'Played'),          $stats['finished'],      'var(--text-secondary)'],
            ['bi-person-badge',    Yii::t('app', 'Players'),        $stats['total_players'], 'var(--accent-blue)'],
        ] as [$icon, $label, $value, $color]): ?>
        <div class="col-6 col-md-3 col-xl">
            <div class="gm-card h-100 py-3 px-3">
                <i class="bi <?= $icon ?> d-block mb-2 fs-5" style="color:<?= $color ?>"></i>
                <div class="fw-black fs-4 text-white"><?= $value ?></div>
                <div class="text-muted-gm" style="font-size:.72rem;letter-spacing:.04em"><?= $label ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Leagues -->
    <h2 class="h5 fw-bold mb-3 text-white">
        <i class="bi bi-trophy text-gold me-2"></i><?= Yii::t('app', 'Leagues') ?>
    </h2>

    <?php foreach ($competitions as $comp): ?>
    <div class="gm-card mb-4 p-0" style="overflow:hidden">
        <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid var(--border)">
            <span class="fw-bold text-white"><?= Html::encode($comp->getLabel()) ?></span>
            <span class="badge-gm" style="background:rgba(255,255,255,.08);color:var(--text-secondary);font-size:.72rem">
                <?= Yii::t('app', 'Season') ?> <?= $comp->season ?>
            </span>
        </div>
        <div class="p-0">
            <table class="table-gm w-100 mb-0">
                <thead>
                    <tr>
                        <th style="width:2rem">#</th>
                        <th><?= Yii::t('app', 'Team') ?></th>
                        <th><?= Yii::t('app', 'Manager') ?></th>
                        <th class="text-center">G</th>
                        <th class="text-center">V</th>
                        <th class="text-center">P</th>
                        <th class="text-center">S</th>
                        <th class="text-center">GF</th>
                        <th class="text-center">GS</th>
                        <th class="text-center">Pt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standingsByComp[$comp->id] as $i => $s): ?>
                    <tr>
                        <td class="text-muted-gm"><?= $i + 1 ?></td>
                        <td>
                            <span class="<?= $s->team->is_cpu ? 'text-muted-gm' : 'text-white fw-semibold' ?>">
                                <?= Html::encode($s->team->name) ?>
                            </span>
                            <?php if (!$s->team->is_cpu): ?>
                                <span class="ms-1" style="font-size:.65rem;color:var(--accent-green)">●</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted-gm small">
                            <?= $s->team->user_id ? Html::encode($s->team->user->username ?? '—') : '<span style="opacity:.4">CPU</span>' ?>
                        </td>
                        <td class="text-center text-muted-gm"><?= $s->played ?></td>
                        <td class="text-center text-muted-gm"><?= $s->won ?></td>
                        <td class="text-center text-muted-gm"><?= $s->drawn ?></td>
                        <td class="text-center text-muted-gm"><?= $s->lost ?></td>
                        <td class="text-center text-muted-gm"><?= $s->goals_for ?></td>
                        <td class="text-center text-muted-gm"><?= $s->goals_against ?></td>
                        <td class="text-center fw-bold text-gold"><?= $s->points ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Managers -->
    <h2 class="h5 fw-bold mb-3 text-white mt-2">
        <i class="bi bi-people text-gold me-2"></i><?= Yii::t('app', 'Manager') ?>
    </h2>

    <div class="gm-card p-0" style="overflow:hidden">
        <table class="table-gm w-100 mb-0">
            <thead>
                <tr>
                    <th>Username</th>
                    <th><?= Yii::t('app', 'Team') ?></th>
                    <th><?= Yii::t('app', 'League') ?></th>
                    <th><?= Yii::t('app', 'Registered') ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($managers)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted-gm py-4"><?= Yii::t('app', 'No manager registered.') ?></td>
                </tr>
                <?php endif; ?>
                <?php foreach ($managers as $manager): ?>
                <?php $team = $teamsByUser[$manager->id] ?? null; ?>
                <?php
                $standing = $team
                    ? \app\models\Standing::find()->with('competition')->where(['team_id' => $team->id])->one()
                    : null;
                ?>
                <tr>
                    <td class="fw-semibold text-white"><?= Html::encode($manager->username) ?></td>
                    <td class="text-muted-gm"><?= $team ? Html::encode($team->name) : '—' ?></td>
                    <td class="text-muted-gm small"><?= $standing ? Html::encode($standing->competition->getLabel()) : '—' ?></td>
                    <td class="text-muted-gm small"><?= date('d/m/Y', $manager->created_at) ?></td>
                    <td class="text-end pe-4">
                        <button type="button"
                                class="btn btn-outline-danger btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDelete"
                                data-user-id="<?= $manager->id ?>"
                                data-username="<?= Html::encode($manager->username) ?>"
                                data-team="<?= $team ? Html::encode($team->name) : '' ?>">
                            <i class="bi bi-trash3"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="confirmDelete" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-dark);border:1px solid var(--border)">
            <div class="modal-header" style="border-color:var(--border)">
                <h5 class="modal-title text-white"><i class="bi bi-trash3 text-danger me-2"></i><?= Yii::t('app', 'Delete manager') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color:var(--text-secondary)">
                <p class="mb-2"><?= Yii::t('app', 'You are about to delete the manager') ?> <strong class="text-white" id="modal-username"></strong>.</p>
                <p class="mb-0 small" id="modal-team-line" style="color:var(--text-secondary)"></p>
            </div>
            <div class="modal-footer" style="border-color:var(--border)">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal"><?= Yii::t('app', 'Cancel') ?></button>
                <!-- Single form: hidden id field populated by JS -->
                <form id="delete-user-form" method="post" action="<?= Url::to(['/admin/delete-user']) ?>" style="display:inline">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                    <input type="hidden" name="id" id="delete-user-id" value="">
                    <button type="submit" class="btn btn-danger btn-sm">
                        <i class="bi bi-trash3"></i> <?= Yii::t('app', 'Delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirmDelete').addEventListener('show.bs.modal', function (e) {
    const btn  = e.relatedTarget;
    document.getElementById('modal-username').textContent  = btn.dataset.username;
    document.getElementById('delete-user-id').value        = btn.dataset.userId;
    document.getElementById('modal-team-line').textContent = btn.dataset.team
        ? 'La squadra «' + btn.dataset.team + '» sarà restituita al controllo CPU.'
        : 'Questo manager non ha una squadra assegnata.';
});
</script>
