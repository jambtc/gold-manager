<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var app\models\Stadium $stadium */

use yii\helpers\Html;

$this->title = Yii::t('app', 'Stadium');
$this->params['breadcrumbs'][] = $this->title;

$stat = function(string $label, string $value, string $color = 'var(--text-primary)'): string {
    return '<div style="text-align:center;padding:.8rem 1rem;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:.6rem">'
         . '<div style="font-size:.65rem;color:var(--text-secondary);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.3rem">' . $label . '</div>'
         . '<div style="font-size:1.3rem;font-weight:900;color:' . $color . '">' . $value . '</div>'
         . '</div>';
};
?>

<div class="py-2">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <h1 class="h3 fw-black mb-0"><i class="bi bi-building text-gold me-2"></i><?= Html::encode($stadium->name) ?></h1>
        <span style="font-size:.75rem;color:var(--text-secondary)"><?= Yii::t('app', 'Level') ?> <span class="text-gold fw-bold"><?= $stadium->level ?></span></span>
    </div>

    <div class="row g-4">

        <!-- ── Left: stats + pricing ── -->
        <div class="col-lg-8 d-flex flex-column gap-4">

            <!-- Key stats -->
            <div class="gm-card">
                <div class="row g-3">
                    <div class="col-4">
                        <?= $stat(Yii::t('app', 'Capacity'), number_format($stadium->capacity, 0, ',', '.'), 'var(--text-primary)') ?>
                    </div>
                    <div class="col-4">
                        <?= $stat(Yii::t('app', 'Ticket (league)'), '€' . $stadium->ticket_price, 'var(--gold)') ?>
                    </div>
                    <div class="col-4">
                        <?= $stat(Yii::t('app', 'Seasonal revenue'), '€' . number_format($stadium->season_revenue, 0, ',', '.'), 'var(--accent-green)') ?>
                    </div>
                </div>

                <!-- Revenue estimates -->
                <div class="mt-4 pt-3" style="border-top:1px solid var(--border)">
                    <div class="text-muted-gm small mb-2"><?= Yii::t('app', 'Estimated revenue per league match') ?></div>
                    <div class="row g-2">
                        <?php foreach ([
                            [Yii::t('app', 'Sold out (100%)'), 1.0],
                            [Yii::t('app', 'Average (70%)'),     0.7],
                            [Yii::t('app', 'Poor (40%)'),    0.4],
                        ] as [$label, $factor]): ?>
                        <div class="col-4">
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:.4rem .6rem;background:rgba(255,255,255,.02);border-radius:.4rem;font-size:.75rem">
                                <span class="text-muted-gm"><?= $label ?></span>
                                <span class="fw-bold text-white">€<?= number_format($stadium->calculateMatchRevenue($factor), 0, ',', '.') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Ticket price editor -->
            <div class="gm-card">
                <h3 class="h5 fw-bold text-white mb-1"><i class="bi bi-ticket-perforated text-gold me-2"></i><?= Yii::t('app', 'Ticket prices') ?></h3>
                <p class="text-muted-gm small mb-4"><?= Yii::t('app', 'League attendance varies by ranking, opponent, and matchday phase (season opening/closing).') ?></p>

                <?= Html::beginForm(['stadium/update-price'], 'post') ?>

                <!-- Campionato -->
                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:.82rem;font-weight:700;color:#fff"><?= Yii::t('app', 'League') ?></span>
                        <span id="comp-display" style="font-size:1.2rem;font-weight:900;color:var(--gold)">€<?= $stadium->ticket_price ?></span>
                    </div>
                    <input type="range" name="ticket_price" id="comp-slider"
                           min="5" max="200" step="1" value="<?= $stadium->ticket_price ?>"
                           style="width:100%;accent-color:var(--gold);margin-bottom:.3rem"
                           oninput="window.updateComp(this.value)">
                    <div style="display:flex;justify-content:space-between;font-size:.63rem;color:var(--text-secondary);margin-bottom:.5rem">
                        <span><?= Yii::t('app', '€5 max attendance') ?></span><span><?= Yii::t('app', '€200 max revenue/ticket') ?></span>
                    </div>
                    <div id="comp-preview" style="font-size:.75rem;color:var(--text-secondary)"></div>
                </div>

                <!-- Amichevoli -->
                <div class="mb-4 pt-3" style="border-top:1px solid var(--border)">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span style="font-size:.82rem;font-weight:700;color:#fff"><?= Yii::t('app', 'Friendlies') ?></span>
                        <span id="friendly-display" style="font-size:1.2rem;font-weight:900;color:var(--accent-blue)">€<?= $stadium->friendly_ticket_price ?></span>
                    </div>
                    <input type="range" name="friendly_ticket_price" id="friendly-slider"
                           min="0" max="100" step="1" value="<?= $stadium->friendly_ticket_price ?>"
                           style="width:100%;accent-color:var(--accent-blue);margin-bottom:.3rem"
                           oninput="window.updateFriendly(this.value)">
                    <div style="display:flex;justify-content:space-between;font-size:.63rem;color:var(--text-secondary);margin-bottom:.5rem">
                        <span><?= Yii::t('app', '€0 free (more spectators)') ?></span><span><?= Yii::t('app', '€100 premium') ?></span>
                    </div>
                    <div id="friendly-preview" style="font-size:.75rem;color:var(--text-secondary)"></div>
                </div>

                <button type="submit" class="btn btn-gold fw-bold px-4"><?= Yii::t('app', 'Save prices') ?></button>
                <?= Html::endForm() ?>
            </div>

        </div>

        <!-- ── Right: upgrade ── -->
        <div class="col-lg-4">
            <div class="gm-card h-100">
                <h3 class="h5 fw-bold text-white mb-4"><i class="bi bi-hammer text-gold me-2"></i><?= Yii::t('app', 'Expansion') ?></h3>

                <div class="text-center mb-4">
                    <div class="text-muted-gm small mb-1"><?= Yii::t('app', 'Next level') ?> (<?= $stadium->level + 1 ?>)</div>
                    <div style="font-size:2rem;font-weight:900;color:#fff;margin:.3rem 0"><?= Yii::t('app', '+5,000 seats') ?></div>
                    <div style="font-size:1.1rem;font-weight:800;color:var(--gold)">€<?= number_format($stadium->upgrade_cost, 0, ',', '.') ?></div>
                </div>

                <?php if ($team->budget >= $stadium->upgrade_cost): ?>
                <?= Html::a('<i class="bi bi-arrow-up-circle me-1"></i> ' . Yii::t('app', 'Upgrade stadium'),
                    ['stadium/upgrade'],
                    [
                        'class'          => 'btn btn-gold w-100 fw-bold py-2',
                        'data-method'    => 'post',
                        'data-confirm'   => Yii::t('app', 'Spend €') . number_format($stadium->upgrade_cost, 0, ',', '.') . Yii::t('app', ' per ampliare lo stadio?'),
                        'encode'         => false,
                    ]
                ) ?>
                <?php else: ?>
                <button class="btn btn-outline-secondary w-100 fw-bold py-2" disabled><?= Yii::t('app', 'Insufficient budget') ?></button>
                <div class="text-center mt-2" style="font-size:.75rem;color:var(--accent-red)">
                    <?= Yii::t('app', 'Missing') ?> €<?= number_format($stadium->upgrade_cost - $team->budget, 0, ',', '.') ?>
                </div>
                <?php endif; ?>

                <div class="mt-4 pt-3" style="border-top:1px solid var(--border)">
                    <ul class="attribute-list small">
                        <li><span class="text-muted-gm"><?= Yii::t('app', 'Capacity after') ?></span><span class="fw-bold"><?= number_format($stadium->capacity + 5000, 0, ',', '.') ?></span></li>
                        <li><span class="text-muted-gm"><?= Yii::t('app', 'Avg. revenue +') ?></span><span class="text-gold fw-bold">+€<?= number_format(5000 * 0.7 * $stadium->ticket_price, 0, ',', '.') ?>/<?= Yii::t('app', 'match') ?></span></li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
var CAP           = <?= (int)$stadium->capacity ?>;
var COMP_PRICE    = <?= (int)$stadium->ticket_price ?>;
var FRIEND_PRICE  = <?= (int)$stadium->friendly_ticket_price ?>;

window.updateComp = function(val) {
    val = parseInt(val) || 5;
    document.getElementById('comp-display').textContent = '€' + val;
    var s70 = Math.round(CAP * 0.70);
    document.getElementById('comp-preview').innerHTML =
        'Media affluenza (70%): <strong style="color:#fff">' + s70.toLocaleString('it-IT') +
        ' spettatori</strong> → <strong style="color:var(--gold)">€' +
        Math.round(s70 * val).toLocaleString('it-IT') + '</strong> incasso';
};

window.updateFriendly = function(val) {
    val = parseInt(val) || 0;
    document.getElementById('friendly-display').textContent = '€' + val;
    var normalRef = Math.max(1, COMP_PRICE);
    var ratio     = normalRef / (normalRef + val);
    var spec      = Math.round(CAP * ratio * 0.85);
    var pct       = Math.round(spec / CAP * 100);
    var rev       = spec * val;
    var effect    = pct >= 60
        ? '<span style="color:var(--accent-green)">+1 forma squadra</span>'
        : pct < 20
            ? '<span style="color:var(--accent-red)">Stadio vuoto — no incasso, −3 freschezza</span>'
            : '';
    document.getElementById('friendly-preview').innerHTML =
        'Stimati <strong style="color:#fff">' + spec.toLocaleString('it-IT') + '</strong> spettatori (' + pct + '%)' +
        ' · <strong style="color:var(--accent-blue)">€' + rev.toLocaleString('it-IT') + '</strong> incasso' +
        (effect ? ' · ' + effect : '');
};

window.updateComp(COMP_PRICE);
window.updateFriendly(FRIEND_PRICE);
</script>
