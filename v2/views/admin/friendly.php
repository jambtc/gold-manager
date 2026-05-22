<?php

/** @var yii\web\View $this */
/** @var app\models\Team[] $teams */
/** @var array $teamLeague  team_id => label */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Amichevole Rapida';
?>

<div class="py-4" style="max-width:640px;margin:0 auto">

    <div class="d-flex align-items-center gap-3 mb-4">
        <?= Html::a('<i class="bi bi-arrow-left"></i>', ['/admin/index'], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false]) ?>
        <h1 class="h3 fw-black mb-0">Amichevole Rapida</h1>
        <span class="badge-gm px-2 py-1" style="background:rgba(245,158,11,.15);color:var(--gold);border:1px solid rgba(245,158,11,.3);font-size:.7rem">TEST</span>
    </div>

    <div class="gm-card mb-4 p-4">
        <p class="text-muted-gm small mb-4">
            Crea e simula istantaneamente una partita amichevole tra due squadre.<br>
            Il risultato <strong class="text-white">non incide sulla classifica</strong>. Utile per testare il motore di gioco.
        </p>

        <?php if (Yii::$app->session->hasFlash('error')): ?>
        <div class="gm-card mb-3 p-3" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08)">
            <i class="bi bi-exclamation-triangle text-danger me-2"></i>
            <?= Html::encode(Yii::$app->session->getFlash('error')) ?>
        </div>
        <?php endif; ?>

        <form method="post" action="<?= Url::to(['/admin/play-friendly']) ?>">
            <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">

            <div class="row g-3 align-items-end mb-4">
                <div class="col">
                    <label class="form-label text-muted-gm" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em">Squadra Casa</label>
                    <select name="home_team_id" class="form-select" style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem" required>
                        <option value="">— Seleziona —</option>
                        <?php foreach ($teams as $team): ?>
                        <option value="<?= $team->id ?>" style="background:#1e293b">
                            <?= Html::encode($team->name) ?>
                            [<?= Html::encode($teamLeague[$team->id] ?? '—') ?>]<?= $team->is_cpu ? '' : ' ★' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-auto text-center pb-1">
                    <span class="fw-black text-muted-gm" style="font-size:1.2rem">vs</span>
                </div>

                <div class="col">
                    <label class="form-label text-muted-gm" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em">Squadra Trasferta</label>
                    <select name="away_team_id" class="form-select" style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem" required>
                        <option value="">— Seleziona —</option>
                        <?php foreach ($teams as $team): ?>
                        <option value="<?= $team->id ?>" style="background:#1e293b">
                            <?= Html::encode($team->name) ?>
                            [<?= Html::encode($teamLeague[$team->id] ?? '—') ?>]<?= $team->is_cpu ? '' : ' ★' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn btn-gold w-100 fw-bold" id="sim-btn">
                <i class="bi bi-play-circle me-2"></i>Simula Amichevole
            </button>
        </form>
    </div>

    <div class="gm-card p-3" style="border-color:rgba(245,158,11,.2)">
        <p class="text-muted-gm small mb-0">
            <i class="bi bi-terminal text-gold me-2"></i>
            Puoi anche simulare da console:<br>
            <code style="color:var(--accent-blue)">docker exec gold-manager-php php /var/www/html/v2/yii game/friendly &lt;homeId&gt; &lt;awayId&gt;</code>
        </p>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function() {
    var btn = document.getElementById('sim-btn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Simulazione in corso…';
});
</script>
