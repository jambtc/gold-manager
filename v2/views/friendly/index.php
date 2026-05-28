<?php

/** @var yii\web\View $this */
/** @var app\models\Team $myTeam */
/** @var int $myOverall */
/** @var array[] $teamData */
/** @var app\models\Stadium|null $myStadium */
/** @var app\models\FriendlyChallenge[] $incomingChallenges */
/** @var app\models\FriendlyChallenge[] $outgoingChallenges */
/** @var app\models\FriendlyChallenge[] $historyChallenges */
/** @var int $proposedAt */
/** @var bool $myBusyThisWeek */
/** @var bool $canStartFriendlyNow */

use app\models\FriendlyChallenge;
use app\models\Fixture;
use app\components\FixtureViewHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Friendlies');
$this->params['breadcrumbs'][] = $this->title;

$bar = static function (int $val, string $color, int $max = 99): string {
    $pct = min(100, round($val / $max * 100));
    return '<div style="background:rgba(255,255,255,.07);border-radius:3px;height:5px;overflow:hidden;margin-top:2px">'
        . '<div style="height:100%;width:' . $pct . '%;background:' . $color . ';border-radius:3px"></div>'
        . '</div>';
};

$diffColor = static function (int $mySkill, int $theirSkill): string {
    $d = $theirSkill - $mySkill;
    if ($d <= -10) return 'var(--accent-green)';
    if ($d >= 10)  return 'var(--accent-red)';
    return 'var(--gold)';
};
?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="gm-card p-4" style="position:sticky;top:80px">
            <div class="text-muted-gm small text-uppercase mb-3" style="letter-spacing:.06em"><?= Yii::t('app', 'Your team') ?></div>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div style="width:48px;height:48px;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.3);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                    <i class="bi bi-shield-shaded text-gold fs-4"></i>
                </div>
                <div>
                    <div class="fw-black text-white" style="font-size:1rem"><?= Html::encode($myTeam->name) ?></div>
                    <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Home · Weekly challenge') ?></div>
                </div>
            </div>

            <div class="text-center mb-3 p-3 rounded-3" style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2)">
                <div class="text-muted-gm" style="font-size:.65rem;text-transform:uppercase;letter-spacing:.06em"><?= Yii::t('app', 'Average strength') ?></div>
                <div class="fw-black text-gold" style="font-size:2rem"><?= $myOverall ?: '—' ?></div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:.75rem;color:var(--text-secondary)"><i class="bi bi-calendar-week me-1"></i><?= Yii::t('app', 'Next slot') ?></span>
                <span style="font-weight:900;color:var(--gold)"><?= date('D d/m H:i', $proposedAt) ?></span>
            </div>

            <?php $friendlyPrice = $myStadium?->friendly_ticket_price ?? 10; ?>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span style="font-size:.75rem;color:var(--text-secondary)"><i class="bi bi-ticket-perforated me-1"></i><?= Yii::t('app', 'Friendly ticket') ?></span>
                <span style="font-weight:900;color:var(--gold)">€<?= (int) $friendlyPrice ?></span>
            </div>

            <div class="mt-3 pt-3" style="border-top:1px solid var(--border)">
                <?php if ($myBusyThisWeek): ?>
                    <div class="text-warning small"><i class="bi bi-exclamation-triangle me-1"></i><?= Yii::t('app', 'You already have a challenge/friendly this week.') ?></div>
                <?php else: ?>
                    <div class="text-success small"><i class="bi bi-check-circle me-1"></i><?= Yii::t('app', 'Weekly slot available.') ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php foreach (['error' => 'danger', 'warning' => 'warning', 'success' => 'success', 'info' => 'info'] as $flash => $bs): ?>
            <?php if (Yii::$app->session->hasFlash($flash)): ?>
                <div class="gm-card mb-3 p-3 border border-<?= $bs ?>">
                    <?= Html::encode((string) Yii::$app->session->getFlash($flash)) ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-challenge" type="button"><?= Yii::t('app', 'Challenge') ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-incoming" type="button"><?= Yii::t('app', 'Received invites') ?><?= count($incomingChallenges) ? ' (' . count($incomingChallenges) . ')' : '' ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-outgoing" type="button"><?= Yii::t('app', 'My challenges') ?><?= count($outgoingChallenges) ? ' (' . count($outgoingChallenges) . ')' : '' ?></button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button"><?= Yii::t('app', 'History') ?></button></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-challenge">
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($teamData as $data): ?>
                        <?php
                        $team = $data['team'];
                        $dc = $diffColor($myOverall, $data['overall']);
                        $diff = $data['overall'] - $myOverall;
                        $diffStr = ($diff > 0 ? '+' : '') . $diff;
                        $availabilityColor = $data['availability'] === 'blocked'
                            ? 'var(--accent-red)'
                            : ($data['availability'] === 'warn' ? 'var(--gold)' : 'var(--accent-green)');
                        ?>
                        <div class="gm-card p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div style="flex-shrink:0;width:44px;height:44px;background:<?= $dc ?>22;border:1px solid <?= $dc ?>55;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;color:<?= $dc ?>">
                                    <?= (int) $data['overall'] ?>
                                </div>
                                <div style="flex:1;min-width:0">
                                    <div class="fw-bold text-white"><?= Html::encode($team->name) ?> <?= $team->is_cpu ? '<span class="badge bg-secondary ms-1">CPU</span>' : '<span class="badge bg-primary ms-1">MANAGER</span>' ?></div>
                                    <div class="text-muted-gm" style="font-size:.72rem"><?= Html::encode($data['league']) ?> · <?= Yii::t('app', 'Average freshness') ?> <?= Html::encode((string) $data['avgFreshness']) ?>%</div>
                                    <div class="row g-2 mt-2" style="font-size:.65rem">
                                        <?php foreach ([['PO', $data['gk'], 'var(--accent-red)'], ['DF', $data['def'], 'var(--accent-blue)'], ['MF', $data['mid'], 'var(--accent-green)'], ['AT', $data['att'], '#f97316']] as [$lbl, $val, $col]): ?>
                                            <div class="col-3">
                                                <div class="d-flex justify-content-between">
                                                    <span style="color:var(--text-secondary)"><?= $lbl ?></span>
                                                    <span style="color:<?= $col ?>;font-weight:700"><?= (int) $val ?></span>
                                                </div>
                                                <?= $bar((int) $val, $col) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="text-end" style="flex-shrink:0">
                                    <div style="font-size:.72rem;font-weight:800;color:<?= $availabilityColor ?>"><?= Html::encode($data['availabilityLabel']) ?></div>
                                    <div style="font-size:.7rem;color:<?= $dc ?>;margin-bottom:.5rem"><?= Html::encode((string) $diffStr) ?></div>
                                    <?php if ($data['canChallenge']): ?>
                                        <form method="post" action="<?= Url::to(['/friendly/challenge']) ?>">
                                            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                                            <input type="hidden" name="challenged_id" value="<?= (int) $team->id ?>">
                                            <button class="btn btn-sm btn-gold"><?= Yii::t('app', 'Challenge') ?></button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled><?= Yii::t('app', 'Locked') ?></button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-incoming">
                <div class="gm-card">
                    <?php if (empty($incomingChallenges)): ?>
                        <div class="text-muted-gm"><?= Yii::t('app', 'No pending invites.') ?></div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($incomingChallenges as $row): ?>
                                <?php
                                $challengerTeam = $row->challenger;
                                $challengerStrength = $challengerTeam ? FixtureViewHelper::computeStrength($challengerTeam) : ['ovr' => 0];
                                $cl = \app\models\Team::sanitizeHexColor($challengerTeam->color_left ?? null, \app\models\Team::DEFAULT_COLOR_LEFT);
                                $cr = \app\models\Team::sanitizeHexColor($challengerTeam->color_right ?? null, \app\models\Team::DEFAULT_COLOR_RIGHT);
                                $letter = $challengerTeam ? mb_substr((string) $challengerTeam->name, 0, 1) : '?';
                                ?>
                                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="border:1px solid var(--border);background:rgba(255,255,255,.03)">
                                    <div class="d-flex align-items-center gap-2">
                                        <span style="line-height:0"><?= FixtureViewHelper::renderShieldSvg($cl, $cr, $letter, 28, 32, 'friendlyInvite') ?></span>
                                        <div>
                                            <div class="text-white fw-bold"><?= Html::encode($row->challenger?->name ?? 'N/D') ?></div>
                                            <div class="text-muted-gm" style="font-size:.68rem"><?= Yii::t('app', 'Avg OVR') ?>: <span class="text-gold fw-bold"><?= (int) ($challengerStrength['ovr'] ?? 0) ?></span></div>
                                            <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Proposal') ?>: <?= date('d/m H:i', (int) $row->proposed_at) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <form method="post" action="<?= Url::to(['/friendly/respond', 'id' => $row->id, 'decision' => 'accept']) ?>">
                                            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                                            <button class="btn btn-sm btn-success"><?= Yii::t('app', 'Accept') ?></button>
                                        </form>
                                        <form method="post" action="<?= Url::to(['/friendly/respond', 'id' => $row->id, 'decision' => 'decline']) ?>">
                                            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                                            <button class="btn btn-sm btn-outline-danger"><?= Yii::t('app', 'Reject') ?></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-outgoing">
                <div class="gm-card">
                    <?php if (empty($outgoingChallenges)): ?>
                        <div class="text-muted-gm"><?= Yii::t('app', 'No pending sent challenges.') ?></div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($outgoingChallenges as $row): ?>
                                <div class="d-flex align-items-center justify-content-between p-2 rounded" style="border:1px solid var(--border);background:rgba(255,255,255,.03)">
                                    <div>
                                        <div class="text-white fw-bold"><?= Html::encode($row->challenged?->name ?? 'N/D') ?></div>
                                        <div class="text-muted-gm" style="font-size:.72rem"><?= Yii::t('app', 'Proposal') ?>: <?= date('d/m H:i', (int) $row->proposed_at) ?></div>
                                    </div>
                                    <div class="text-warning" style="font-size:.8rem"><i class="bi bi-hourglass-split me-1"></i><?= Yii::t('app', 'Pending') ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-history">
                <div class="gm-card">
                    <?php if (empty($historyChallenges)): ?>
                        <div class="text-muted-gm"><?= Yii::t('app', 'History empty.') ?></div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-dark table-hover align-middle mb-0">
                                <thead>
                                <tr>
                                    <th><?= Yii::t('app', 'vs') ?></th>
                                    <th><?= Yii::t('app', 'Date') ?></th>
                                    <th><?= Yii::t('app', 'Status') ?></th>
                                    <th>Fixture</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($historyChallenges as $row): ?>
                                    <?php
                                    $isChallenger = (int) $row->challenger_id === (int) $myTeam->id;
                                    $opponent = $isChallenger ? $row->challenged : $row->challenger;
                                    $statusBadge = match ($row->status) {
                                        FriendlyChallenge::STATUS_ACCEPTED => 'info',
                                        FriendlyChallenge::STATUS_PLAYED => 'success',
                                        FriendlyChallenge::STATUS_DECLINED => 'warning',
                                        FriendlyChallenge::STATUS_EXPIRED => 'secondary',
                                        default => 'secondary',
                                    };
                                    ?>
                                    <tr>
                                        <td><?= Html::encode($opponent?->name ?? 'N/D') ?></td>
                                        <td><?= date('d/m/Y H:i', (int) $row->proposed_at) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $statusBadge ?>"><?= strtoupper(Html::encode($row->status)) ?></span>
                                            <?php if ($row->decline_reason): ?>
                                                <div class="text-muted-gm" style="font-size:.7rem"><?= Html::encode($row->decline_reason) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($row->fixture_id): ?>
                                                <?php if ($row->fixture && (int) $row->fixture->status === Fixture::STATUS_FINISHED): ?>
                                                    <?= Html::a(Yii::t('app', 'Replay'), ['/fixture/replay', 'id' => $row->fixture_id], ['class' => 'btn btn-sm btn-outline-gold']) ?>
                                                <?php else: ?>
                                                    <div class="d-flex gap-2 justify-content-end">
                                                        <?= Html::a('Live', ['/fixture/live', 'id' => $row->fixture_id], ['class' => 'btn btn-sm btn-outline-secondary']) ?>
                                                        <?php if (
                                                            $canStartFriendlyNow
                                                            && $row->status === FriendlyChallenge::STATUS_ACCEPTED
                                                            && $row->fixture
                                                            && (int) $row->fixture->status === Fixture::STATUS_SCHEDULED
                                                        ): ?>
                                                            <form method="post" action="<?= Url::to(['/friendly/start-now', 'id' => $row->id]) ?>">
                                                                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                                                                <button class="btn btn-sm btn-warning"><?= Yii::t('app', 'Start now') ?></button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted-gm">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
