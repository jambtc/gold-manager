<?php

/** @var yii\web\View $this */
/** @var bool $ok */
/** @var string $source */
/** @var string $file */
/** @var string $openapiUrl */
/** @var string|null $warning */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Generazione swagger.json';
?>
<div style="max-width:980px;margin:24px auto;padding:0 12px;">
    <h2 style="margin-bottom:14px;"><?= Html::encode($this->title) ?></h2>

    <?php if ($ok): ?>
        <div style="padding:10px 12px;border:1px solid #166534;background:#052e16;color:#bbf7d0;border-radius:8px;margin-bottom:12px;">
            File creato con successo.
        </div>
    <?php endif; ?>

    <?php if (!empty($warning)): ?>
        <div style="padding:10px 12px;border:1px solid #92400e;background:#451a03;color:#fde68a;border-radius:8px;margin-bottom:12px;">
            <?= Html::encode($warning) ?>
        </div>
    <?php endif; ?>

    <div style="padding:12px;border:1px solid #334155;background:#0f172a;color:#e2e8f0;border-radius:8px;">
        <div><strong>Source:</strong> <?= Html::encode($source) ?></div>
        <div><strong>File:</strong> <?= Html::encode($file) ?></div>
        <div><strong>OpenAPI URL:</strong> <a href="<?= Html::encode($openapiUrl) ?>" target="_blank" rel="noopener"><?= Html::encode($openapiUrl) ?></a></div>
    </div>

    <div style="margin-top:16px;">
        <a href="<?= Url::to(['/swagger/index']) ?>" style="display:inline-block;padding:8px 12px;border:1px solid #334155;border-radius:8px;text-decoration:none;color:#e2e8f0;">
            Apri Swagger UI
        </a>
    </div>
</div>

