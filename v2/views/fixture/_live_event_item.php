<?php

declare(strict_types=1);

/**
 * @var \app\models\MatchEvent $ev
 * @var \app\models\Fixture $fixture
 * @var string|null $homeSide
 * @var array<string,string> $iconMap
 */

$detail = $ev->detail ? json_decode((string) $ev->detail, true) : [];
$hs = $detail['home_score'] ?? ($detail['home'] ?? null);
$as = $detail['away_score'] ?? ($detail['away'] ?? null);
$desc = $detail['description'] ?? '';
$icon = $iconMap[$ev->type] ?? '·';
$isOurs = $homeSide !== null && $ev->team_side === $homeSide;
$teamName = $ev->team_side === 'home' ? (string) $fixture->homeTeam->name : (string) $fixture->awayTeam->name;
$badgeColor = $isOurs ? 'var(--gold)' : '#f87171';
$badgeBg = $isOurs ? 'rgba(245,158,11,.15)' : 'rgba(248,113,113,.12)';
$badgePfx = $isOurs ? '🛡️ ' : '⚔️ ';
$labelColor = $ev->type === 'goal'
    ? 'var(--gold)'
    : ($ev->type === 'gk_save' ? 'var(--accent-blue)' : 'var(--text-primary)');
$isKickoff = in_array($ev->type, ['kickoff', 'pre_match'], true);
$minuteStr = $ev->type === 'pre_match' ? 'PRE' : ($isKickoff ? '0' : (string) $ev->minute);
?>

<div class="event-item" data-event-id="<?= (int) $ev->id ?>">
    <div class="event-time"><?= $minuteStr ?>'</div>
    <div class="report-event-icon"><?= $icon ?></div>
    <div style="flex:1;min-width:0" data-event-body="1">
        <div style="display:flex;align-items:center;gap:.2rem;flex-wrap:wrap;margin-bottom:.05rem">
            <?php if (!$isKickoff): ?>
                <span style="font-size:.62rem;font-weight:700;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;padding:.08rem .35rem;border-radius:.25rem;white-space:nowrap"><?= $badgePfx . \yii\helpers\Html::encode($teamName) ?></span>
            <?php endif; ?>
            <span style="font-weight:700;font-size:.75rem;letter-spacing:.04em;color:<?= $labelColor ?>"><?= \yii\helpers\Html::encode(strtoupper(str_replace('_', ' ', (string) $ev->type))) ?></span>
            <?php if ($hs !== null): ?>
                <span style="font-weight:700;color:var(--gold);font-size:.75rem"><?= \yii\helpers\Html::encode((string) $hs) ?>–<?= \yii\helpers\Html::encode((string) $as) ?></span>
            <?php endif; ?>
            <?php if ($ev->type === 'goal' && !empty($detail['scorer_name'])): ?>
                <span style="font-size:.7rem;color:var(--gold);font-weight:600">⚽ <?= \yii\helpers\Html::encode((string) $detail['scorer_name']) ?></span>
            <?php endif; ?>
            <?php if ($ev->type === 'substitution' && (!empty($detail['out_name']) || !empty($detail['in_name']))): ?>
                <span style="font-size:.7rem;color:var(--text-secondary)"><?= \yii\helpers\Html::encode((string) ($detail['out_name'] ?? '?')) ?> ↗ <?= \yii\helpers\Html::encode((string) ($detail['in_name'] ?? '?')) ?></span>
            <?php endif; ?>
            <?php if (in_array($ev->type, ['yellow_card', 'red_card', 'injury'], true) && !empty($detail['player_name'])): ?>
                <?php $pColor = $ev->type === 'yellow_card' ? '#fbbf24' : ($ev->type === 'red_card' ? '#f87171' : '#fca5a5'); ?>
                <span style="font-size:.7rem;color:<?= $pColor ?>;font-weight:600"><?= \yii\helpers\Html::encode((string) $detail['player_name']) ?></span>
            <?php endif; ?>
        </div>
        <?php if ($desc): ?>
            <div class="event-description" style="font-size:.8rem;font-style:italic;color:var(--text-secondary);margin-top:.08rem">"<?= \yii\helpers\Html::encode((string) $desc) ?>"</div>
        <?php endif; ?>
    </div>
</div>

