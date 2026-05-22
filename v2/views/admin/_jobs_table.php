<?php

/** @var array[] $jobs */
/** @var string $emptyText */

if (empty($jobs)) {
    if ($emptyText) echo '<div class="gm-card text-muted-gm small py-3 px-4">' . htmlspecialchars($emptyText) . '</div>';
    return;
}

$decode = function (array $job): string {
    if (empty($job['serialized'])) return '—';
    $raw = base64_decode($job['serialized'], true);
    if ($raw === false) return '(non decodificabile)';
    // yii2-queue serializes as PHP serialize
    $obj = @unserialize($raw);
    if ($obj === false) return '(formato sconosciuto)';
    return get_class($obj);
};
?>

<div class="gm-card p-0 mb-3" style="overflow:hidden">
    <table class="table-gm w-100 mb-0">
        <thead>
            <tr>
                <th style="width:4rem">ID</th>
                <th>Tipo job</th>
                <th class="text-center">Tentativo</th>
                <th>Aggiunto</th>
                <th>TTR</th>
                <th>Ritardo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jobs as $job): ?>
            <tr>
                <td class="text-muted-gm"><?= (int)($job['id'] ?? 0) ?></td>
                <td>
                    <code style="color:var(--accent-blue);font-size:.8rem">
                        <?= htmlspecialchars($decode($job)) ?>
                    </code>
                </td>
                <td class="text-center">
                    <?php $attempt = (int)($job['attempt'] ?? 0); ?>
                    <span class="<?= $attempt > 0 ? 'text-danger fw-bold' : 'text-muted-gm' ?>">
                        <?= $attempt ?>
                    </span>
                </td>
                <td class="text-muted-gm small">
                    <?= isset($job['pushed_at']) ? date('d/m H:i:s', (int)$job['pushed_at']) : '—' ?>
                </td>
                <td class="text-muted-gm small"><?= isset($job['ttr']) ? (int)$job['ttr'] . 's' : '—' ?></td>
                <td class="text-muted-gm small"><?= isset($job['delay']) ? (int)$job['delay'] . 's' : '—' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
