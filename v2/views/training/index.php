<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var array $skill   training_skill row */
/** @var array $tactic  training_tactic row */
/** @var array $tacticPlan training_tactic_plan row */
/** @var array $tacticDelta */
/** @var array $staffBonus */
/** @var int   $season */
/** @var int   $tacticalAvg */
/** @var bool  $matchSoon */

use yii\helpers\Html;
use yii\helpers\Url;
use app\components\PlayerAttributeHelper;
use app\components\UiIconHelper;

$this->title = 'Allenamento';
$this->params['breadcrumbs'][] = $this->title;

$tab = Yii::$app->request->get('tab', 'fisico');
$talentDefs = PlayerAttributeHelper::talentDefinitions();
$talentAllocMap = PlayerAttributeHelper::talentAllocMap();
$talentsByAlloc = [];
foreach ($talentAllocMap as $code => $allocKeys) {
    $label = (string)($talentDefs[$code]['label'] ?? ucfirst($code));
    foreach ($allocKeys as $k) {
        $talentsByAlloc[$k][] = $label;
    }
}

$slider = function(string $name, int $val, string $label, string $desc, string $color = 'var(--gold)', bool $projected = false, bool $showProgress = false, ?int $currentValue = null, ?int $deltaValue = null): string {
    $proj = '';
    if ($projected) {
        $baseChance = min(100, round($val * 0.08, 1));
        $proj = '<div style="font-size:.64rem;color:var(--text-secondary);margin-top:.15rem">Chance crescita base: <span id="proj_' . $name . '" style="color:var(--accent-green);font-weight:700">' . $baseChance . '%</span> / giorno</div>';
    }
    $progress = '';
    if ($showProgress) {
        $displayVal = max(0, min(100, (int) ($currentValue ?? $val)));
        $delta = (int)($deltaValue ?? 0);
        $deltaColor = $delta > 0 ? 'var(--accent-green)' : ($delta < 0 ? 'var(--accent-red)' : 'var(--text-secondary)');
        $deltaText = $delta > 0 ? ('+' . $delta) : (string)$delta;
        $progress = '<div style="margin-top:.3rem">'
                  . '<div style="height:7px;background:rgba(148,163,184,.22);border-radius:999px;overflow:hidden">'
                  . '<div id="bar_' . $name . '" style="height:100%;width:' . $displayVal . '%;background:' . $color . ';border-radius:999px;transition:width .2s ease"></div>'
                  . '</div>'
                  . '<div style="font-size:.64rem;color:var(--text-secondary);margin-top:.15rem;display:flex;align-items:center;gap:.4rem">'
                  . 'Attuale: <span id="pct_' . $name . '" style="color:#fff;font-weight:700">' . $displayVal . '</span>/100'
                  . '<span style="color:var(--text-secondary)">·</span>'
                  . '<span style="color:' . $deltaColor . '">Δ ' . $deltaText . '</span>'
                  . '</div>'
                  . '</div>';
    }
    return '<div class="mb-3">'
         . '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">'
         . '<div><div style="font-weight:600;font-size:.82rem;color:#fff">' . $label . '</div>'
         . '<div style="font-size:.68rem;color:var(--text-secondary)">' . $desc . '</div>' . $proj . '</div>'
         . '<span id="lbl_' . $name . '" style="font-weight:800;color:' . $color . ';min-width:3rem;text-align:right">' . $val . ' pt</span>'
         . '</div>'
         . '<input type="range" name="' . $name . '" id="sl_' . $name . '" value="' . $val . '" min="0" max="100" step="5"'
         . ' style="width:100%;accent-color:' . $color . '"'
         . ' oninput="updateSlider(\'' . $name . '\',this.value)">'
         . $progress
         . '</div>';
};
?>

<div class="py-2">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <h1 class="h3 fw-black mb-0">Allenamento</h1>
        <?php if (!empty($staffBonus['tactical']) || !empty($staffBonus['physical'])): ?>
        <div style="font-size:.72rem;color:var(--text-secondary)">
            Staff — Fisico: <span style="color:var(--gold)">+<?= $staffBonus['physical'] ?>%</span>
            · Tattico: <span style="color:var(--accent-green)">+<?= $staffBonus['tactical'] ?>%</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
    <div class="gm-card mb-3 p-3" style="border-color:rgba(16,185,129,.4);background:rgba(16,185,129,.08)">
        <i class="bi bi-check-circle text-success me-2"></i><?= Yii::$app->session->getFlash('success') ?>
    </div>
    <?php endif; ?>
    <?php if (Yii::$app->session->hasFlash('error')): ?>
    <div class="gm-card mb-3 p-3" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08)">
        <i class="bi bi-exclamation-triangle text-danger me-2"></i><?= Yii::$app->session->getFlash('error') ?>
    </div>
    <?php endif; ?>

    <!-- Tab switcher -->
    <div class="d-flex gap-2 mb-4">
        <a href="<?= Url::to(['/training/index', 'tab' => 'fisico']) ?>"      class="btn btn-sm <?= $tab === 'fisico'      ? 'btn-gold' : 'btn-outline-secondary' ?>"><i class="bi bi-person-arms-up me-1"></i>Fisico</a>
        <a href="<?= Url::to(['/training/index', 'tab' => 'tattico']) ?>"     class="btn btn-sm <?= $tab === 'tattico'     ? 'btn-gold' : 'btn-outline-secondary' ?>"><i class="bi bi-grid-3x3 me-1"></i>Tattico</a>
        <a href="<?= Url::to(['/training/index', 'tab' => 'statistiche']) ?>" class="btn btn-sm <?= $tab === 'statistiche' ? 'btn-gold' : 'btn-outline-secondary' ?>"><i class="bi bi-graph-up me-1"></i>Statistiche</a>
        <a href="<?= Url::to(['/training/index', 'tab' => 'progressione']) ?>" class="btn btn-sm <?= $tab === 'progressione' ? 'btn-gold' : 'btn-outline-secondary' ?>"><i class="bi bi-activity me-1"></i>Progressione</a>
    </div>

    <?php if ($tab === 'fisico'): ?>
    <!-- ── FISICO TAB ─────────────────────────────────── -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="gm-card">
                <h3 class="h5 fw-bold text-white mb-4"><i class="bi bi-person-arms-up text-gold me-2"></i>Allenamento Fisico</h3>
                <p class="text-muted-gm small mb-4">
                    Distribuisci <strong class="text-white">100 punti</strong> tra le skill.
                    Il risultato viene applicato giornalmente da <code>EconomyController</code>.
                    Staff migliore = crescita più rapida.
                </p>
                <form method="post" action="<?= Url::to(['/training/save-skill']) ?>">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                    <?= $slider('alloc_forma', (int)($skill['alloc_forma'] ?? 10), 'Forma',    'Recupero psico-fisico', 'var(--accent-green)', true) ?>
                    <?= $slider('alloc_cond',  (int)($skill['alloc_cond']  ?? 10), 'Condizione','Efficienza atletica',  '#f97316', true) ?>
                    <?= $slider('alloc_po',    (int)($skill['alloc_po']    ?? 5),  'Parate (PO)', 'Abilità del portiere', '#ea580c', true) ?>
                    <?= $slider('alloc_df',    (int)($skill['alloc_df']    ?? 10), 'Difesa (DF)',   'Abilità difensiva', 'var(--accent-blue)', true) ?>
                    <?= $slider('alloc_cn',    (int)($skill['alloc_cn']    ?? 10), 'Contrasti (CN)','Duelli e aggressività', 'var(--accent-blue)', true) ?>
                    <?= $slider('alloc_pa',    (int)($skill['alloc_pa']    ?? 10), 'Passaggi (PA)', 'Precisione nei passaggi', 'var(--accent-green)', true) ?>
                    <?= $slider('alloc_rg',    (int)($skill['alloc_rg']    ?? 10), 'Regia (RG)',    'Visione e costruzione', 'var(--accent-green)', true) ?>
                    <?= $slider('alloc_cr',    (int)($skill['alloc_cr']    ?? 10), 'Cross (CR)',    'Qualità dei cross', 'var(--gold)', true) ?>
                    <?= $slider('alloc_tc',    (int)($skill['alloc_tc']    ?? 10), 'Tecnica (TC)',  'Controllo palla', 'var(--gold)', true) ?>
                    <?= $slider('alloc_tr',    (int)($skill['alloc_tr']    ?? 5),  'Tiro (TR)',     'Potenza e precisione', 'var(--accent-red)', true) ?>
                    <?= $slider('alloc_calci_piazzati', (int)($skill['alloc_calci_piazzati'] ?? 10), 'Calci piazzati', 'Schemi su corner e punizioni', 'var(--gold)', true) ?>
                    <div class="d-flex align-items-center justify-content-between mt-4 pt-3" style="border-top:1px solid var(--border)">
                        <div>
                            <span style="color:var(--text-secondary)">Totale: </span>
                            <span id="total-pts" style="font-weight:900;font-size:1.1rem;color:var(--gold)">0</span>
                            <span style="color:var(--text-secondary)"> / 100</span>
                            <div style="font-size:.72rem;color:var(--text-secondary);margin-top:.2rem">
                                Carico: <span id="load-label" style="font-weight:700;color:var(--gold)">—</span>
                                · Freschezza giorno: <span id="freshness-impact" style="font-weight:700;color:var(--gold)">—</span>
                            </div>
                            <div id="match-week-warning" style="display:none;font-size:.72rem;color:#f87171;margin-top:.2rem">
                                ⚠️ Partita entro 3 giorni: carico alto riduce la forma pre-gara.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gold fw-bold px-4">Salva</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="gm-card sticky-top" style="top:80px">
                <h5 class="text-white mb-3"><i class="bi bi-info-circle text-gold me-2"></i>Come funziona</h5>
                <p class="text-muted-gm small">Ogni giorno <code>EconomyController</code> applica l'XP guadagnato a tutti i giocatori in rosa.</p>
                <p class="text-muted-gm small mb-2">Talenti: progresso giornaliero solo se allocazione correlata <strong class="text-white">&gt; 80</strong> e comunque con probabilità.</p>
                <ul class="attribute-list small">
                    <li><span class="text-muted-gm">Staff bonus</span><span style="color:var(--gold)">×<?= round(1 + $staffBonus['physical']/100, 2) ?></span></li>
                    <li><span class="text-muted-gm">Età > 25</span><span class="text-muted-gm">−2%/anno</span></li>
                    <li><span class="text-muted-gm">Diligente</span><span style="color:var(--accent-green)">+15% XP</span></li>
                    <li><span class="text-muted-gm">Inflessibile</span><span style="color:var(--accent-red)">−20% skill non primaria</span></li>
                    <li><span class="text-muted-gm">Capitano carismatico</span><span style="color:var(--accent-green)">+5% XP team</span></li>
                    <li><span class="text-muted-gm">Popolare</span><span style="color:var(--accent-green)">+5% se forma team alta</span></li>
                    <li><span class="text-muted-gm">Carico pesante</span><span style="color:var(--accent-red)">−3 freschezza/week</span></li>
                </ul>
                <div class="mt-3 pt-2" style="border-top:1px solid var(--border)">
                    <div class="text-white fw-semibold mb-2" style="font-size:.78rem">Mappa allenamento → talenti</div>
                    <div style="display:grid;gap:.35rem">
                        <?php foreach ([
                            'alloc_forma' => 'Forma',
                            'alloc_cond'  => 'Condizione',
                            'alloc_po'    => 'Parate',
                            'alloc_df'    => 'Difesa',
                            'alloc_cn'    => 'Contrasti',
                            'alloc_pa'    => 'Passaggi',
                            'alloc_rg'    => 'Regia',
                            'alloc_cr'    => 'Cross',
                            'alloc_tc'    => 'Tecnica',
                            'alloc_tr'    => 'Tiro',
                            'alloc_calci_piazzati' => 'Calci piazzati',
                        ] as $allocKey => $allocLabel): ?>
                        <div style="font-size:.68rem;display:flex;align-items:center;justify-content:space-between;gap:.5rem">
                            <span style="color:var(--text-secondary)"><?= Html::encode($allocLabel) ?></span>
                            <span style="color:var(--gold);text-align:right">
                                <?= Html::encode(implode(', ', $talentsByAlloc[$allocKey] ?? ['—'])) ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php elseif ($tab === 'tattico'): ?>
    <!-- ── TATTICO TAB ─────────────────────────────────── -->
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="gm-card">
                <h3 class="h5 fw-bold text-white mb-4"><i class="bi bi-grid-3x3 text-gold me-2"></i>Allenamento Tattico</h3>
                <p class="text-muted-gm small mb-4">
                    Distribuisci <strong class="text-white">100 punti</strong> tra le tattiche.
                    Ogni tattica mostra anche il suo valore attuale (0–100).
                </p>
                <form method="post" action="<?= Url::to(['/training/save-tactic']) ?>">
                    <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                    <?= $slider('pressing',      (int)($tacticPlan['pressing']      ?? 15), 'Pressing',           '+% duello centrocampo offensivo', 'var(--accent-red)', false, true, (int)($tactic['pressing'] ?? 0), (int)($tacticDelta['pressing'] ?? 0)) ?>
                    <?= $slider('contropiede',   (int)($tacticPlan['contropiede']   ?? 15), 'Contropiede',        '+% forza attacco in ripartenza',  '#f97316', false, true, (int)($tactic['contropiede'] ?? 0), (int)($tacticDelta['contropiede'] ?? 0)) ?>
                    <?= $slider('possesso',      (int)($tacticPlan['possesso']      ?? 15), 'Possesso palla',     '+% dominio centrocampo',          'var(--accent-green)', false, true, (int)($tactic['possesso'] ?? 0), (int)($tacticDelta['possesso'] ?? 0)) ?>
                    <?= $slider('palla_bassa',   (int)($tacticPlan['palla_bassa']   ?? 15), 'Gioco palla bassa',  '−% forza attacco avversario',     'var(--accent-blue)', false, true, (int)($tactic['palla_bassa'] ?? 0), (int)($tacticDelta['palla_bassa'] ?? 0)) ?>
                    <?= $slider('lancio_lungo',  (int)($tacticPlan['lancio_lungo']  ?? 10), 'Lancio lungo',       '20% chance bypass centrocampo',   'var(--gold)', false, true, (int)($tactic['lancio_lungo'] ?? 0), (int)($tacticDelta['lancio_lungo'] ?? 0)) ?>
                    <?= $slider('catenaccio',    (int)($tacticPlan['catenaccio']    ?? 15), 'Catenaccio',         '+% muro difensivo',               'var(--accent-blue)', false, true, (int)($tactic['catenaccio'] ?? 0), (int)($tacticDelta['catenaccio'] ?? 0)) ?>
                    <?= $slider('fuorigioco',    (int)($tacticPlan['fuorigioco']    ?? 15), 'Trappola fuorigioco','15% chance annullare attacco',    'var(--text-secondary)', false, true, (int)($tactic['fuorigioco'] ?? 0), (int)($tacticDelta['fuorigioco'] ?? 0)) ?>
                    <?php
                    // Conflict warnings (SIP-0038)
                    $press = (int)($tactic['pressing'] ?? 30);
                    $caten = (int)($tactic['catenaccio'] ?? 20);
                    $lanc  = (int)($tactic['lancio_lungo'] ?? 20);
                    $poss  = (int)($tactic['possesso'] ?? 40);
                    if ($press > 50 && $caten > 50): ?>
                    <div class="gm-card p-2 mb-2" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);font-size:.75rem">
                        ⚠️ <strong>Conflitto:</strong> Pressing + Catenaccio entrambi >50% → pressing −30% efficacia
                    </div>
                    <?php endif;
                    if ($lanc > 50 && $poss > 50): ?>
                    <div class="gm-card p-2 mb-2" style="border-color:rgba(239,68,68,.4);background:rgba(239,68,68,.08);font-size:.75rem">
                        ⚠️ <strong>Conflitto:</strong> Lancio lungo + Possesso entrambi >50% → possesso −40% efficacia
                    </div>
                    <?php endif; ?>
                    <div class="d-flex align-items-center justify-content-between mt-4 pt-3" style="border-top:1px solid var(--border)">
                        <div>
                            <span style="color:var(--text-secondary)">Totale: </span>
                            <span id="total-pts" style="font-weight:900;font-size:1.1rem;color:var(--gold)">0</span>
                            <span style="color:var(--text-secondary)"> / 100</span>
                        </div>
                        <button type="submit" class="btn btn-gold fw-bold px-4">Salva tattica</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="gm-card sticky-top" style="top:80px">
                <h5 class="text-white mb-3"><i class="bi bi-info-circle text-gold me-2"></i>Effetti in partita</h5>
                <ul class="attribute-list small">
                    <li><span class="text-muted-gm">Pressing max</span><span style="color:var(--accent-red)">+8% mid duel</span></li>
                    <li><span class="text-muted-gm">Possesso max</span><span style="color:var(--accent-green)">+10% mid duel</span></li>
                    <li><span class="text-muted-gm">Lancio max</span><span style="color:var(--gold)">20% bypass</span></li>
                    <li><span class="text-muted-gm">Catenaccio max</span><span style="color:var(--accent-blue)">+12% difesa</span></li>
                    <li><span class="text-muted-gm">Fuorigioco max</span><span style="color:var(--text-secondary)">15% annulla att.</span></li>
                    <li><span class="text-muted-gm">Staff bonus</span><span style="color:var(--gold)">×<?= round(1 + $staffBonus['tactical']/100, 2) ?></span></li>
                </ul>
                <p class="text-muted-gm small mt-3">I preset live (Difensivo/Bilanciato/Offensivo) in partita sovrascrivono temporaneamente questi valori.</p>
            </div>
        </div>
    </div>
    <?php elseif ($tab === 'statistiche'): ?>
    <!-- ── STATISTICHE TAB ───────────────────────────────────── -->
    <?php
    $statsUrl = Url::to(['/training/stats']);
    ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="gm-card">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-activity text-gold me-2"></i>Trend Forma Squadra (14 giorni)</h5>
                <canvas id="teamTrendChart" height="170"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="gm-card h-100">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-speedometer2 text-gold me-2"></i>KPI Oggi</h5>
                <div id="kpi-today" class="d-grid gap-2">
                    <div class="text-muted-gm small">Caricamento...</div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gm-card h-100">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-graph-up-arrow text-gold me-2"></i>Top Crescita (7g)</h5>
                <div id="top-growth"><p class="text-muted-gm small">Caricamento...</p></div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="gm-card h-100">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-graph-down-arrow text-gold me-2"></i>Top Calo (7g)</h5>
                <div id="top-drop"><p class="text-muted-gm small">Caricamento...</p></div>
            </div>
        </div>
        <div class="col-12">
            <div class="gm-card">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-grid-3x3 text-gold me-2"></i>Tattiche Squadra (livello + trend 7g)</h5>
                <div id="tactic-trend-table" class="table-responsive">
                    <p class="text-muted-gm small">Caricamento...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php
    $jsStats = <<<JS
(function(){
    var STATS_URL = '$statsUrl';
    var teamTrendChart = null;

    function renderPlayerRows(targetId, rows, invert) {
        var out = '';
        (rows || []).forEach(function(p, i){
            var d = parseInt(p.delta || 0, 10);
            var sign = d > 0 ? '+' : '';
            var col = d > 0 ? 'var(--accent-green)' : (d < 0 ? 'var(--accent-red)' : 'var(--text-secondary)');
            if (invert && d > 0) col = 'var(--text-secondary)';
            out += '<div style="display:flex;justify-content:space-between;padding:.4rem 0;'+(i>0?'border-top:1px solid var(--border)':'')+'">'
                + '<div><span class="fw-bold text-white" style="font-size:.82rem">'+p.name+'</span> '
                + '<span class="text-muted-gm" style="font-size:.72rem">'+p.position+'</span></div>'
                + '<span style="color:'+col+';font-weight:800;font-size:.85rem">'+sign+d+'</span></div>';
        });
        document.getElementById(targetId).innerHTML = out || '<p class="text-muted-gm small">Dati non disponibili.</p>';
    }

    function metricChip(label, value) {
        return '<div style="display:flex;justify-content:space-between;align-items:center;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:.55rem;padding:.45rem .55rem">'
            + '<span class="text-muted-gm" style="font-size:.74rem">'+label+'</span>'
            + '<span class="text-white fw-bold" style="font-size:.86rem">'+value+'</span></div>';
    }

    // ── Dashboard stats ───────────────────────────────────────
    fetch(STATS_URL, {credentials:'same-origin'})
    .then(function(r){return r.json();})
    .then(function(data){
        var trend = data.trend || {};
        var dates = trend.dates || [];
        var formData = trend.form || [];
        var condData = trend.condition || [];
        var ctx = document.getElementById('teamTrendChart').getContext('2d');
        if (teamTrendChart) teamTrendChart.destroy();
        teamTrendChart = new Chart(ctx, {
            type:'line',
            data:{
                labels: dates.map(function(d){ return String(d).slice(5); }),
                datasets: [
                    { label:'Forma', data: formData, borderColor:'#22c55e', backgroundColor:'transparent', tension:.35, pointRadius:2.8, borderWidth:2 },
                    { label:'Condizione', data: condData, borderColor:'#f59e0b', backgroundColor:'transparent', tension:.35, pointRadius:2.8, borderWidth:2 }
                ]
            },
            options:{
                responsive:true,
                plugins:{legend:{labels:{color:'#94a3b8'}}},
                scales:{
                    x:{ticks:{color:'#64748b'}},
                    y:{ticks:{color:'#64748b'}, suggestedMin:0, suggestedMax:99}
                }
            }
        });

        var kpi = data.kpi || {};
        document.getElementById('kpi-today').innerHTML =
            metricChip('Forma media', (kpi.avg_form || 0).toFixed ? (kpi.avg_form).toFixed(1) : kpi.avg_form) +
            metricChip('Condizione media', (kpi.avg_condition || 0).toFixed ? (kpi.avg_condition).toFixed(1) : kpi.avg_condition) +
            metricChip('Freschezza media', (kpi.avg_freshness || 0).toFixed ? (kpi.avg_freshness).toFixed(1) : kpi.avg_freshness);

        renderPlayerRows('top-growth', data.topGrowth || [], false);
        renderPlayerRows('top-drop', data.topDrop || [], true);

        var labels = {
            pressing:'Pressing', contropiede:'Contropiede', possesso:'Possesso',
            palla_bassa:'Palla bassa', lancio_lungo:'Lancio lungo', catenaccio:'Catenaccio',
            fuorigioco:'Fuorigioco'
        };
        var t = data.tacticTrend || {};
        var rows = '';
        Object.keys(labels).forEach(function(k){
            var r = t[k] || {current:0, delta_7d:0, alloc:0};
            var d = parseInt(r.delta_7d || 0, 10);
            var sign = d > 0 ? '+' : '';
            var col = d > 0 ? 'var(--accent-green)' : (d < 0 ? 'var(--accent-red)' : 'var(--text-secondary)');
            rows += '<tr>'
                + '<td class="text-white">'+labels[k]+'</td>'
                + '<td class="text-gold fw-bold">'+(r.current || 0)+'/100</td>'
                + '<td style="color:'+col+';font-weight:700">'+sign+d+'</td>'
                + '<td class="text-muted-gm">'+(r.alloc || 0)+'/100</td>'
                + '</tr>';
        });
        var tableHtml = '<table class="table table-dark table-sm align-middle mb-0" style="--bs-table-bg:transparent">'
            + '<thead><tr><th>Tattica</th><th>Livello</th><th>Δ 7g</th><th>Alloc Oggi</th></tr></thead>'
            + '<tbody>'+rows+'</tbody></table>';
        document.getElementById('tactic-trend-table').innerHTML = rows ? tableHtml : '<p class="text-muted-gm small">Nessun dato tattico disponibile.</p>';
    })
    .catch(function(){});

}());
JS;
    $this->registerJs($jsStats);
    ?>
    <?php elseif ($tab === 'progressione'): ?>
    <!-- ── PROGRESSIONE GIOCATORE TAB ────────────────────────── -->
    <?php
    $progressUrl = Url::to(['/training/progress']);
    $players = \app\models\Player::find()
        ->where(['team_id' => $team->id])
        ->orderBy(['position' => SORT_ASC, 'general_skill' => SORT_DESC, 'name' => SORT_ASC])
        ->all();
    ?>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="gm-card" style="max-height:640px;overflow:auto">
                <h5 class="text-white fw-bold mb-3"><i class="bi bi-people text-gold me-2"></i>Giocatori</h5>
                <div class="d-grid gap-2">
                    <?php foreach ($players as $idx => $p): ?>
                    <button type="button"
                            class="btn btn-outline-secondary text-start player-progress-item <?= $idx === 0 ? 'active' : '' ?>"
                            data-player-id="<?= (int)$p->id ?>"
                            style="padding:.55rem .65rem;border-color:var(--border)">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-semibold text-white" style="font-size:.84rem"><?= Html::encode($p->name) ?></span>
                            <?= UiIconHelper::renderPositionBadge((string) $p->position, true, 10, 'font-size:.6rem;padding:.12rem .34rem') ?>
                        </div>
                        <div class="text-muted-gm" style="font-size:.7rem">GEN <?= (int)$p->general_skill ?></div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="gm-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <h5 class="text-white fw-bold mb-0"><i class="bi bi-activity text-gold me-2"></i>Progressione giocatore</h5>
                    <span class="text-muted-gm small">Ultimi 14 giorni</span>
                </div>
                <div id="progress-player-name" class="text-white fw-semibold mb-2" style="font-size:.9rem">—</div>
                <canvas id="playerChart" height="170"></canvas>
                <div id="delta-badges" class="d-flex flex-wrap gap-2 mt-3"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php
    $firstPlayerId = !empty($players) ? (int)$players[0]->id : 0;
    $jsProgress = <<<JS
(function(){
    var PROGRESS_URL = '$progressUrl';
    var FIRST_PLAYER_ID = $firstPlayerId;
    var STAT_LABELS = {
        skill_po:'PO', skill_df:'DF', skill_cn:'CN', skill_pa:'PA',
        skill_rg:'RG', skill_cr:'CR', skill_tc:'TC', skill_tr:'TR',
        form:'Forma', condition_val:'Cond.'
    };
    var COLORS = ['#f59e0b','#3b82f6','#22c55e','#ef4444','#a855f7','#06b6d4','#f97316','#84cc16','#ec4899','#94a3b8'];
    var playerChart = null;

    function markActive(playerId) {
        document.querySelectorAll('.player-progress-item').forEach(function(btn){
            var isActive = parseInt(btn.getAttribute('data-player-id') || '0', 10) === playerId;
            btn.classList.toggle('active', isActive);
            btn.style.borderColor = isActive ? 'rgba(245,158,11,.55)' : 'var(--border)';
            btn.style.background = isActive ? 'rgba(245,158,11,.12)' : '';
        });
    }

    function loadPlayer(playerId) {
        if (!playerId) return;
        fetch(PROGRESS_URL + '?playerId=' + playerId + '&weeks=14', {credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(data){
            var nameEl = document.getElementById('progress-player-name');
            if (nameEl) {
                var pname = data.player && data.player.name ? data.player.name : '—';
                var ppos = data.player && data.player.position ? (' (' + data.player.position + ')') : '';
                nameEl.textContent = pname + ppos;
            }

            var snaps = data.snapshots || [];
            if (snaps.length < 2) {
                document.getElementById('delta-badges').innerHTML = '<span class="text-muted-gm small">Non abbastanza dati (servono almeno 2 snapshot giornalieri).</span>';
                if (playerChart) { playerChart.destroy(); playerChart = null; }
                return;
            }

            var labels = snaps.map(function(s){ return s.snapshot_date.slice(5); });
            var statKeys = ['skill_po','skill_df','skill_cn','skill_pa','skill_rg','skill_cr','skill_tc','skill_tr','form','condition_val'];
            var datasets = statKeys.map(function(k, i){
                return {
                    label: STAT_LABELS[k],
                    data: snaps.map(function(s){ return s[k]; }),
                    borderColor: COLORS[i % COLORS.length],
                    backgroundColor: 'transparent',
                    tension: .35,
                    pointRadius: 2.8,
                    borderWidth: 2
                };
            });

            var ctx = document.getElementById('playerChart').getContext('2d');
            if (playerChart) playerChart.destroy();
            playerChart = new Chart(ctx, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
                options: {
                    responsive: true,
                    plugins: { legend: { labels: { color: '#94a3b8', boxWidth: 11 } } },
                    scales: {
                        x: { ticks: { color: '#64748b' } },
                        y: { ticks: { color: '#64748b' }, suggestedMin: 0, suggestedMax: 99 }
                    }
                }
            });

            var last = snaps[snaps.length - 1];
            var deltas = last.deltas || {};
            var html = '';
            Object.keys(STAT_LABELS).forEach(function(k){
                var d = deltas[k];
                if (d === null || d === undefined) {
                    html += '<span style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.4rem;padding:.2rem .5rem;font-size:.72rem;color:var(--text-secondary)"><strong>'+STAT_LABELS[k]+'</strong> —</span>';
                    return;
                }
                d = parseInt(d, 10);
                var col = d > 0 ? 'var(--accent-green)' : (d < 0 ? 'var(--accent-red)' : 'var(--text-secondary)');
                var sign = d > 0 ? '+' : '';
                html += '<span style="background:rgba(255,255,255,.04);border:1px solid var(--border);border-radius:.4rem;padding:.2rem .5rem;font-size:.72rem;color:'+col+'"><strong>'+STAT_LABELS[k]+'</strong> '+sign+d+'</span>';
            });
            document.getElementById('delta-badges').innerHTML = html;
            markActive(playerId);
        })
        .catch(function(){});
    }

    document.querySelectorAll('.player-progress-item').forEach(function(btn){
        btn.addEventListener('click', function(){
            var id = parseInt(this.getAttribute('data-player-id') || '0', 10);
            loadPlayer(id);
        });
    });

    if (FIRST_PLAYER_ID > 0) loadPlayer(FIRST_PLAYER_ID);
}());
JS;
    $this->registerJs($jsProgress);
    ?>
    <?php endif; ?>
</div>

<?php
$js = <<<JS
const TACTICAL_AVG = parseInt("{$tacticalAvg}", 10) || 0;
const MATCH_SOON = ("{$matchSoon}" === "1");
const CURRENT_TAB = "{$tab}";
window.updateSlider = function(name, val) {
    val = parseInt(val) || 0;
    val = Math.max(0, Math.min(100, val));
    if (CURRENT_TAB === 'fisico' || CURRENT_TAB === 'tattico') {
        var others = 0;
        document.querySelectorAll('input[type=range]').forEach(function(s){
            if (s.id !== 'sl_' + name) others += parseInt(s.value) || 0;
        });
        val = Math.min(val, Math.max(0, 100 - others));
    }
    var slider = document.getElementById('sl_' + name);
    if (slider) slider.value = val;
    document.getElementById('lbl_' + name).textContent = val + ' pt';
    var proj = document.getElementById('proj_' + name);
    if (proj) {
        var chance = Math.min(100, (val * 0.08));
        proj.textContent = chance.toFixed(1) + '%';
    }
    window.recalc();
};
window.recalc = function() {
    var t = 0;
    document.querySelectorAll('input[type=range]').forEach(function(s){ t += parseInt(s.value)||0; });
    if (CURRENT_TAB === 'fisico') {
        var el = document.getElementById('total-pts');
        if (el) { el.textContent = t; el.style.color = t > 100 ? 'var(--accent-red)' : 'var(--gold)'; }

        var load = Math.round((t + TACTICAL_AVG) / 2);
        var loadLabel = document.getElementById('load-label');
        var frLabel = document.getElementById('freshness-impact');
        var warn = document.getElementById('match-week-warning');
        if (loadLabel) {
            var txt = '';
            var col = 'var(--text-secondary)';
            if (load >= 80) { txt = 'Heavy'; col = 'var(--accent-red)'; }
            else if (load >= 50) { txt = 'Moderato'; col = 'var(--gold)'; }
            else if (load >= 20) { txt = 'Light'; col = 'var(--accent-green)'; }
            else { txt = 'Rest'; col = 'var(--accent-blue)'; }
            loadLabel.textContent = txt + ' (' + load + ')';
            loadLabel.style.color = col;
        }
        if (frLabel) {
            var delta = 5;
            if (load >= 80) delta = -3;
            else if (load >= 50) delta = -1;
            else if (load >= 20) delta = +2;
            var sign = delta > 0 ? '+' : '';
            frLabel.textContent = sign + delta;
            frLabel.style.color = delta < 0 ? 'var(--accent-red)' : 'var(--accent-green)';
        }
        if (warn) {
            warn.style.display = (MATCH_SOON && load > 60) ? 'block' : 'none';
        }
        return;
    }
    if (CURRENT_TAB === 'tattico') {
        var tacticalTotal = document.getElementById('total-pts');
        if (tacticalTotal) {
            tacticalTotal.textContent = t;
            tacticalTotal.style.color = t > 100 ? 'var(--accent-red)' : 'var(--gold)';
        }
    }
};
window.recalc();
JS;
$this->registerJs($js);
?>
