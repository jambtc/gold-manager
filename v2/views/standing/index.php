<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var app\models\Competition $competition */
/** @var app\models\Competition[] $allComps */
/** @var app\models\Standing[] $standings */
/** @var int|null $myTeamId */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Standings') . ': ' . $competition->getLabel();
$this->params['breadcrumbs'][] = $this->title;
$crest = function (?\app\models\Team $club, int $width = 22, int $height = 25): string {
    if ($club === null) {
        return '';
    }

    static $id = 0;
    $id++;
    $clipLeft = 'stdShieldL' . $id;
    $clipRight = 'stdShieldR' . $id;
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

<div class="standing-index">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="mb-0 fw-black"><?= Html::encode($competition->getLabel()) ?></h1>
            <p class="text-muted-gm mb-0"><?= Yii::t('app', 'Season') ?> <?= $competition->season ?> · <?= count($standings) ?> <?= Yii::t('app', 'teams') ?></p>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <!-- Competition switcher -->
            <select onchange="window.location.href='<?= Url::to(['/standing/index']) ?>?competitionId='+this.value"
                    style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem;padding:.4rem .75rem;font-size:.82rem">
                <?php foreach ($allComps as $c): ?>
                <option value="<?= $c->id ?>" <?= $c->id === $competition->id ? 'selected' : '' ?> style="background:#1e293b">
                    <?= Html::encode($c->getLabel()) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?= Html::a('<i class="bi bi-calendar3"></i>', ['/fixture/index'], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false, 'title' => Yii::t('app', 'Calendar')]) ?>
        </div>
    </div>

    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm w-100 mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 60px;"><?= Yii::t('app', 'Pos') ?></th>
                        <th><?= Yii::t('app', 'Team') ?></th>
                        <th class="text-center">P</th>
                        <th class="text-center">V</th>
                        <th class="text-center">N</th>
                        <th class="text-center">P</th>
                        <th class="text-center d-none d-md-table-cell">GF</th>
                        <th class="text-center d-none d-md-table-cell">GS</th>
                        <th class="text-center">DR</th>
                        <th class="text-center fw-bold text-gold">PT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($standings as $index => $row): ?>
                        <?php 
                            $pos = $index + 1;
                            $isUserTeam = ($row->team_id === $myTeamId);
                            $rowClass = $isUserTeam ? 'style="background: rgba(245, 158, 11, 0.1);"' : '';
                            
                            // Visual zones (example: 1st place promotion, last 3 relegation)
                            $posClass = '';
                            if ($pos === 1) $posClass = 'border-start border-4 border-success';
                            if ($pos > count($standings) - 3) $posClass = 'border-start border-4 border-danger';
                        ?>
                        <tr <?= $rowClass ?>>
                            <td class="text-center <?= $posClass ?>">
                                <span class="fw-bold <?= $pos <= 3 ? 'text-gold' : 'text-white' ?>">
                                    <?= $pos ?>
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="me-2" style="line-height:1"><?= $crest($row->team, 22, 25) ?></span>
                                    <span class="<?= $isUserTeam ? 'fw-bold text-gold' : 'text-white' ?>">
                                        <?= Html::encode($row->team->name) ?>
                                    </span>
                                    <?php if ($isUserTeam): ?>
                                        <span class="badge bg-gold text-dark ms-2 small" style="font-size: 0.6rem;">TU</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center"><?= $row->played ?></td>
                            <td class="text-center"><?= $row->won ?></td>
                            <td class="text-center"><?= $row->drawn ?></td>
                            <td class="text-center"><?= $row->lost ?></td>
                            <td class="text-center d-none d-md-table-cell text-muted-gm"><?= $row->goals_for ?></td>
                            <td class="text-center d-none d-md-table-cell text-muted-gm"><?= $row->goals_against ?></td>
                            <?php $gd = $row->goals_for - $row->goals_against; ?>
                            <td class="text-center <?= $gd >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= ($gd > 0 ? '+' : '') . $gd ?>
                            </td>
                            <td class="text-center fw-bold text-gold fs-5">
                                <?= $row->points ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex gap-4 small text-muted-gm">
        <div><span class="badge bg-success me-1" style="width: 10px; height: 10px; padding: 0;">&nbsp;</span> <?= Yii::t('app', 'Promotion / Playoff') ?></div>
        <div><span class="badge bg-danger me-1" style="width: 10px; height: 10px; padding: 0;">&nbsp;</span> <?= Yii::t('app', 'Relegation') ?></div>
    </div>
</div>
