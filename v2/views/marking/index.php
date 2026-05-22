<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Fixture $fixture */
/** @var app\models\Team $myTeam */
/** @var app\models\Team $oppTeam */
/** @var app\models\FormationSlot[] $myDefenders */
/** @var array[] $oppRoster */
/** @var app\models\ManMarking[] $markings */
/** @var bool $canAdd */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Marcature';
$this->params['breadcrumbs'][] = ['label' => 'Partite', 'url' => ['/fixture/index']];
$this->params['breadcrumbs'][] = $this->title;

$posColors = ['GK' => 'pos-po', 'DF' => 'pos-d', 'MF' => 'pos-c', 'FW' => 'pos-a'];
$markedIds  = array_map(fn($m) => (int)$m->marked_id,  $markings);
$markerIds  = array_map(fn($m) => (int)$m->marker_id, $markings);
?>

<div class="py-2">
    <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
        <div>
            <h1 class="h3 fw-black mb-0">Marcature a Uomo</h1>
            <p class="text-muted-gm mb-0">
                <?= Html::encode($fixture->homeTeam->name) ?> vs <?= Html::encode($fixture->awayTeam->name) ?>
                · <?= date('d/m H:i', $fixture->match_date) ?>
            </p>
        </div>
        <div class="ms-auto">
            <span style="font-size:.78rem;color:var(--text-secondary)">
                <?= count($markings) ?>/3 marcature assegnate
            </span>
        </div>
    </div>

    <?php foreach (['success','error','info'] as $t): ?>
    <?php if (Yii::$app->session->hasFlash($t)): ?>
    <div class="gm-card mb-3 p-3" style="border-color:rgba(<?= $t==='success'?'16,185,129':($t==='error'?'239,68,68':'245,158,11') ?>,.4)">
        <?= Html::encode(Yii::$app->session->getFlash($t)) ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <div class="row g-4">

        <!-- Marcature attive -->
        <div class="col-lg-5">
            <div class="gm-card">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-shield-fill text-gold me-2"></i>Le mie marcature</h5>

                <?php if (empty($markings)): ?>
                <p class="text-muted-gm small">Nessuna marcatura. Usa il modulo a destra.</p>
                <?php else: ?>
                <?php foreach ($markings as $m): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid var(--border)">
                    <div style="font-size:.82rem">
                        <span class="fw-bold text-white"><?= Html::encode($m->marker?->name ?? '#'.$m->marker_id) ?></span>
                        <span class="text-muted-gm mx-2">→</span>
                        <span style="color:var(--accent-red)"><?= Html::encode($m->marked?->name ?? '#'.$m->marked_id) ?></span>
                    </div>
                    <?php if ($fixture->status === \app\models\Fixture::STATUS_SCHEDULED): ?>
                    <?= Html::beginForm(['/marking/remove', 'id' => $m->id], 'post', ['class' => 'd-inline']) ?>
                    <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2">✕</button>
                    <?= Html::endForm() ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($canAdd): ?>
                <div class="mt-3 pt-2" style="border-top:1px solid var(--border)">
                    <h6 class="text-white mb-2" style="font-size:.78rem">Aggiungi marcatura</h6>
                    <?= Html::beginForm(['/marking/assign'], 'post') ?>
                    <input type="hidden" name="fixture_id" value="<?= $fixture->id ?>">
                    <div class="mb-2">
                        <label class="text-muted-gm" style="font-size:.7rem">Mio difensore</label>
                        <select name="marker_id" class="form-select form-select-sm mt-1"
                                style="background:#0f172a;border:1px solid var(--border);color:#fff">
                            <?php foreach ($myDefenders as $s): ?>
                            <?php $p = $s->player; if (!$p || in_array($p->id, $markerIds, true)) continue; ?>
                            <option value="<?= $p->id ?>"><?= Html::encode($p->name) ?> (<?= $p->position ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted-gm" style="font-size:.7rem">Avversario da marcare</label>
                        <select name="marked_id" class="form-select form-select-sm mt-1"
                                style="background:#0f172a;border:1px solid var(--border);color:#fff">
                            <?php foreach ($oppRoster as $opp): ?>
                            <?php if (in_array((int)($opp['player_id'] ?? 0), $markedIds, true)) continue; ?>
                            <option value="<?= (int)($opp['player_id'] ?? 0) ?>">
                                <?= Html::encode($opp['name']) ?> (<?= $opp['position'] ?>) ~<?= $opp['skill'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-gold btn-sm w-100">Assegna marcatura</button>
                    <?= Html::endForm() ?>
                </div>
                <?php elseif (!$canAdd && $fixture->status === \app\models\Fixture::STATUS_SCHEDULED): ?>
                <p class="text-muted-gm small mt-3">Massimo 3 marcature raggiunto.</p>
                <?php elseif ($fixture->status !== \app\models\Fixture::STATUS_SCHEDULED): ?>
                <p class="text-muted-gm small mt-3">Partita già iniziata — marcature bloccate.</p>
                <?php endif; ?>
            </div>

            <!-- Effetti -->
            <div class="gm-card mt-3">
                <h6 class="text-white fw-bold mb-2"><i class="bi bi-info-circle text-gold me-2"></i>Effetti in partita</h6>
                <ul class="attribute-list small">
                    <li><span class="text-muted-gm">Marcatore attivo</span><span style="color:var(--accent-green)">+8% Difesa</span></li>
                    <li><span class="text-muted-gm">Avversario marcato</span><span style="color:var(--accent-red)">−12% Attacco</span></li>
                    <li><span class="text-muted-gm">Sostituzione marcatore</span><span class="text-muted-gm">marcatura annullata</span></li>
                </ul>
            </div>
        </div>

        <!-- Rosa avversaria -->
        <div class="col-lg-7">
            <div class="gm-card">
                <h5 class="text-white fw-bold mb-3">
                    <i class="bi bi-people text-gold me-2"></i>
                    Rosa <?= Html::encode($oppTeam?->name ?? 'Avversario') ?>
                    <span class="text-muted-gm" style="font-size:.68rem;font-weight:400">(dati <?= empty($oppRoster) || ($oppRoster[0]['approx'] ?? false) ? 'approssimativi' : 'precisi' ?>)</span>
                </h5>

                <?php if (empty($oppRoster)): ?>
                <p class="text-muted-gm small">Rosa non disponibile.</p>
                <?php else: ?>
                <div class="row g-2">
                    <?php foreach ($oppRoster as $opp):
                        $pid     = (int)($opp['player_id'] ?? 0);
                        $isMarked = in_array($pid, $markedIds, true);
                        $pos     = $opp['position'] ?? '?';
                    ?>
                    <div class="col-md-6">
                        <div style="display:flex;align-items:center;gap:.5rem;padding:.4rem .5rem;background:<?= $isMarked ? 'rgba(239,68,68,.08)' : 'rgba(255,255,255,.02)' ?>;border:1px solid <?= $isMarked ? 'rgba(239,68,68,.3)' : 'var(--border)' ?>;border-radius:.5rem">
                            <span class="badge-gm <?= $posColors[$pos] ?? 'pos-c' ?>" style="font-size:.55rem;padding:.05rem .3rem"><?= $pos ?></span>
                            <span style="flex:1;font-size:.78rem;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= Html::encode($opp['name']) ?></span>
                            <span style="font-size:.7rem;color:var(--text-secondary)"><?= Html::encode((string)$opp['skill']) ?></span>
                            <?php if ($isMarked): ?>
                            <span style="font-size:.65rem;color:var(--accent-red)">🎯</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
