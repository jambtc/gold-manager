<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Team $team */
/** @var int $playerWages */
/** @var int $staffWages */
/** @var float $weeklyStadiumIncome */
/** @var float $weeklySponsorIncome */

use yii\helpers\Html;

$this->title = 'Bilancio Finanziario';
$this->params['breadcrumbs'][] = $this->title;

$totalExpenses = ($playerWages + $staffWages) / 52; // Weekly estimate
$totalIncome = $weeklyStadiumIncome + $weeklySponsorIncome;
$netBalance = $totalIncome - $totalExpenses;
?>

<div class="economy-index">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mb-0"><?= Html::encode($this->title) ?></h1>
            <p class="text-muted-gm">Analisi dei flussi di cassa e proiezioni settimanali.</p>
        </div>
        <div class="text-end text-gold">
            <small class="d-block text-muted-gm">Saldo Totale</small>
            <span class="h2 fw-bold">€<?= number_format($team->budget, 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="row g-4">
        <!-- Summary Card -->
        <div class="col-12">
            <div class="gm-card bg-gradient-dark">
                <div class="row text-center align-items-center">
                    <div class="col-md-4 border-end border-secondary">
                        <div class="text-muted-gm small mb-1">PROIEZIONE SETTIMANALE</div>
                        <div class="h3 mb-0 <?= $netBalance >= 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $netBalance >= 0 ? '+' : '' ?>€<?= number_format($netBalance, 0, ',', '.') ?>
                        </div>
                    </div>
                    <div class="col-md-8 text-md-start ps-md-5">
                        <p class="text-muted-gm small mb-0">
                            Basato sugli stipendi correnti e sugli incassi stimati (stadio al 70% e sponsor attivo), 
                            il tuo club sta generando un saldo <?= $netBalance >= 0 ? '<span class="text-success fw-bold">Positivo</span>' : '<span class="text-danger fw-bold">Negativo</span>' ?>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Income -->
        <div class="col-md-6">
            <div class="gm-card h-100">
                <h3 class="h5 mb-4 text-white"><i class="bi bi-arrow-down-left text-success"></i> Entrate Stimate (Settimana)</h3>
                <ul class="attribute-list">
                    <li>
                        <span class="text-muted-gm">Incassi Stadio (Stima)</span>
                        <span class="text-white fw-bold">€<?= number_format($weeklyStadiumIncome, 0, ',', '.') ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Sponsor Ufficiale</span>
                        <span class="text-white fw-bold">€<?= number_format($weeklySponsorIncome, 0, ',', '.') ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Merchandising (Fase 8)</span>
                        <span class="text-muted small">COMING SOON</span>
                    </li>
                    <li class="border-top border-secondary mt-3 pt-3">
                        <span class="text-white fw-bold">Totale Entrate</span>
                        <span class="text-success h5 mb-0">€<?= number_format($totalIncome, 0, ',', '.') ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Expenses -->
        <div class="col-md-6">
            <div class="gm-card h-100">
                <h3 class="h5 mb-4 text-white"><i class="bi bi-arrow-up-right text-danger"></i> Uscite Determinate (Settimana)</h3>
                <ul class="attribute-list">
                    <li>
                        <span class="text-muted-gm">Stipendi Giocatori</span>
                        <span class="text-white fw-bold">€<?= number_format($playerWages / 52, 0, ',', '.') ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Stipendi Staff</span>
                        <span class="text-white fw-bold">€<?= number_format($staffWages / 52, 0, ',', '.') ?></span>
                    </li>
                    <li>
                        <span class="text-muted-gm">Manutenzione Stadio</span>
                        <span class="text-white fw-bold">€0</span>
                    </li>
                    <li class="border-top border-secondary mt-3 pt-3">
                        <span class="text-white fw-bold">Totale Uscite</span>
                        <span class="text-danger h5 mb-0">€<?= number_format($totalExpenses, 0, ',', '.') ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Health Check -->
    <div class="gm-card mt-4 border-<?= $netBalance >= 0 ? 'success' : 'warning' ?>">
        <h4 class="h6 text-<?= $netBalance >= 0 ? 'success' : 'warning' ?> mb-3">Health Check Finanziario</h4>
        <div class="progress bg-dark mb-3" style="height: 10px;">
            <div class="progress-bar bg-success" style="width: <?= $totalIncome > 0 ? min(100, ($totalIncome / $totalExpenses) * 50) : 0 ?>%"></div>
            <div class="progress-bar bg-danger" style="width: <?= $totalExpenses > 0 ? 50 : 0 ?>%"></div>
        </div>
        <p class="small text-muted-gm mb-0">
            Il rapporto tra entrate e uscite è di <?= $totalIncome > 0 ? round($totalIncome / $totalExpenses, 2) : 0 ?>. 
            Un valore superiore a 1.0 indica un club finanziariamente sostenibile.
        </p>
    </div>
</div>
