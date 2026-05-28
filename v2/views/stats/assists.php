<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var array<int,array<string,mixed>> $rows */
/** @var int $season */
/** @var int|null $tier */
/** @var array<int,string> $tierOptions */
/** @var int[] $allSeasons */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Assist Rankings');
$this->params['breadcrumbs'][] = $this->title;

$baseUrl = Url::to(['/stats/assists']);
?>

<div class="stats-assists">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-0 fw-black"><?= Yii::t('app', 'Assist Rankings') ?></h1>
            <p class="text-muted-gm mb-0"><?= Yii::t('app', 'Season') ?> <?= $season ?></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select onchange="window.location.href='<?= $baseUrl ?>?season='+this.value+'&tier=<?= $tier === null ? '' : (int)$tier ?>'"
                    style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem;padding:.4rem .75rem;font-size:.82rem">
                <?php foreach ($allSeasons as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $season ? 'selected' : '' ?> style="background:#1e293b"><?= Yii::t('app', 'Season') ?> <?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <select onchange="window.location.href='<?= $baseUrl ?>?season=<?= $season ?>&tier='+this.value"
                    style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem;padding:.4rem .75rem;font-size:.82rem">
                <option value="" <?= $tier === null ? 'selected' : '' ?> style="background:#1e293b"><?= Yii::t('app', 'All divisions') ?></option>
                <?php foreach ($tierOptions as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $tier === (int) $k ? 'selected' : '' ?> style="background:#1e293b"><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= Html::a('<i class="bi bi-trophy"></i> ' . Yii::t('app', 'Scorers'), ['/stats/scorers', 'season' => $season, 'tier' => $tier], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false]) ?>
            <?= Html::a('<i class="bi bi-shield-check"></i> ' . Yii::t('app', 'Goalkeepers'), ['/stats/keepers', 'season' => $season, 'tier' => $tier], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false]) ?>
        </div>
    </div>

    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm w-100 mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:56px">#</th>
                        <th><?= Yii::t('app', 'Player') ?></th>
                        <th><?= Yii::t('app', 'Team') ?></th>
                        <th class="text-center"><?= Yii::t('app', 'Assists') ?></th>
                        <th class="text-center"><?= Yii::t('app', 'Goals') ?></th>
                        <th class="text-center"><?= Yii::t('app', 'Matches') ?></th>
                        <th class="text-center">🟨</th>
                        <th class="text-center">🟥</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center text-muted-gm py-4"><?= Yii::t('app', 'No data available.') ?></td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $i => $r): ?>
                            <tr>
                                <td class="text-center fw-bold <?= $i < 3 ? 'text-gold' : 'text-white' ?>"><?= $i + 1 ?></td>
                                <td>
                                    <?= Html::a(Html::encode((string) $r['player_name']), ['/player/view', 'id' => (int) $r['player_id']], ['class' => 'text-white fw-semibold']) ?>
                                    <span class="text-muted-gm small"> · <?= Html::encode((string) $r['position']) ?></span>
                                </td>
                                <td class="text-muted-gm"><?= Html::encode((string) $r['team_name']) ?></td>
                                <td class="text-center fw-bold text-gold"><?= (int) $r['assists'] ?></td>
                                <td class="text-center"><?= (int) $r['goals'] ?></td>
                                <td class="text-center"><?= (int) $r['matches'] ?></td>
                                <td class="text-center"><?= (int) $r['yellow_cards'] ?></td>
                                <td class="text-center"><?= (int) $r['red_cards'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
