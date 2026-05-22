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

$this->title = 'Statistiche Portieri';
$this->params['breadcrumbs'][] = $this->title;

$baseUrl = Url::to(['/stats/keepers']);
?>

<div class="stats-keepers">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-0 fw-black">Statistiche Portieri</h1>
            <p class="text-muted-gm mb-0">Stagione <?= $season ?></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <select onchange="window.location.href='<?= $baseUrl ?>?season='+this.value+'&tier=<?= $tier === null ? '' : (int)$tier ?>'"
                    style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem;padding:.4rem .75rem;font-size:.82rem">
                <?php foreach ($allSeasons as $s): ?>
                    <option value="<?= $s ?>" <?= $s === $season ? 'selected' : '' ?> style="background:#1e293b">Stagione <?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <select onchange="window.location.href='<?= $baseUrl ?>?season=<?= $season ?>&tier='+this.value"
                    style="background:rgba(255,255,255,.05);border:1px solid var(--border);color:#fff;border-radius:.6rem;padding:.4rem .75rem;font-size:.82rem">
                <option value="" <?= $tier === null ? 'selected' : '' ?> style="background:#1e293b">Tutte serie</option>
                <?php foreach ($tierOptions as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $tier === (int) $k ? 'selected' : '' ?> style="background:#1e293b"><?= Html::encode($label) ?></option>
                <?php endforeach; ?>
            </select>
            <?= Html::a('<i class="bi bi-trophy"></i> Marcatori', ['/stats/scorers', 'season' => $season, 'tier' => $tier], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false]) ?>
            <?= Html::a('<i class="bi bi-table"></i> Squadre', ['/stats/team', 'season' => $season, 'tier' => $tier], ['class' => 'btn btn-outline-gold btn-sm', 'encode' => false]) ?>
        </div>
    </div>

    <div class="gm-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table-gm w-100 mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width:56px">#</th>
                        <th>Portiere</th>
                        <th>Squadra</th>
                        <th class="text-center">Partite</th>
                        <th class="text-center">Min</th>
                        <th class="text-center">Gol Sub.</th>
                        <th class="text-center">Parate</th>
                        <th class="text-center">CS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center text-muted-gm py-4">Nessun dato disponibile.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $i => $r): ?>
                            <tr>
                                <td class="text-center fw-bold <?= $i < 3 ? 'text-gold' : 'text-white' ?>"><?= $i + 1 ?></td>
                                <td><?= Html::a(Html::encode((string) $r['player_name']), ['/player/view', 'id' => (int) $r['player_id']], ['class' => 'text-white fw-semibold']) ?></td>
                                <td class="text-muted-gm"><?= Html::encode((string) $r['team_name']) ?></td>
                                <td class="text-center"><?= (int) $r['matches'] ?></td>
                                <td class="text-center"><?= (int) $r['minutes_played'] ?></td>
                                <td class="text-center"><?= (int) $r['goals_conceded'] ?></td>
                                <td class="text-center text-gold fw-bold"><?= (int) $r['saves'] ?></td>
                                <td class="text-center text-gold fw-bold"><?= (int) $r['clean_sheets'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
