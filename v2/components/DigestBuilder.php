<?php

declare(strict_types=1);

namespace app\components;

use app\components\SponsorService;
use app\models\Player;
use app\models\Team;
use app\models\TrainingSnapshot;
use app\models\Transfer;
use Yii;

/**
 * SIP-0081: builds the daily digest payload for a single team manager.
 *
 * Reads from DB at call time — no caching. Keep calls infrequent (once/day).
 */
final class DigestBuilder
{
    public readonly string $title;
    public readonly string $body;
    public readonly string $telegramText;

    private function __construct(string $title, string $body, string $telegramText)
    {
        $this->title        = $title;
        $this->body         = $body;
        $this->telegramText = $telegramText;
    }

    public static function build(Team $team): self
    {
        $players   = Player::find()->where(['team_id' => $team->id])->all();
        $count     = count($players);

        // ── Squad stats ────────────────────────────────────────────────────
        $avgForm      = $count > 0 ? (int) round(array_sum(array_map(fn($p) => (int)$p->form,      $players)) / $count) : 0;
        $avgFreshness = $count > 0 ? (int) round(array_sum(array_map(fn($p) => (int)$p->freshness, $players)) / $count) : 0;
        $injured      = count(array_filter($players, fn($p) => (int)$p->injury_weeks > 0));
        $suspended    = count(array_filter($players, fn($p) => (int)$p->suspended_matches > 0));

        // ── Finance ────────────────────────────────────────────────────────
        $budget      = (int) $team->budget;
        $weeklyWages = $team->totalWageBill();

        // Season transfer balance (completed this calendar year)
        $transferIn = (int) (Transfer::find()
            ->where(['from_team_id' => $team->id, 'status' => Transfer::STATUS_COMPLETED])
            ->andWhere(['>=', 'resolved_at', mktime(0, 0, 0, 1, 1, (int)date('Y'))])
            ->sum('fee') ?: 0);

        $transferOut = (int) (Transfer::find()
            ->where(['to_team_id' => $team->id, 'status' => Transfer::STATUS_COMPLETED])
            ->andWhere(['>=', 'resolved_at', mktime(0, 0, 0, 1, 1, (int)date('Y'))])
            ->sum('fee') ?: 0);

        $transferBalance = $transferIn - $transferOut;

        // Active sponsor weekly installment
        $activeSponsor  = SponsorService::getActiveContractForTeam((int)$team->id);
        $weeklySponsors = $activeSponsor ? SponsorService::weeklyInstallment($activeSponsor) : 0;

        // ── Top movers (24h via TrainingSnapshot) ──────────────────────────
        $improvers = TrainingSnapshot::getTopImprovers((int)$team->id, 3);
        $decliners = self::getTopDecliners((int)$team->id, 3);

        // ── Next match ─────────────────────────────────────────────────────
        $nextFixture = Yii::$app->db->createCommand(
            'SELECT f.match_date, th.name AS home, ta.name AS away
             FROM {{%fixture}} f
             JOIN {{%team}} th ON th.id = f.home_team_id
             JOIN {{%team}} ta ON ta.id = f.away_team_id
             WHERE f.status = :s
               AND (f.home_team_id = :tid OR f.away_team_id = :tid2)
               AND f.match_date >= :now
             ORDER BY f.match_date ASC LIMIT 1',
            [':s' => 'scheduled', ':tid' => $team->id, ':tid2' => $team->id, ':now' => time()]
        )->queryOne();

        // ── Build texts ────────────────────────────────────────────────────
        $title = Yii::t('app', 'Daily Report') . ' — ' . $team->name;

        $moverLines = '';
        foreach ($improvers as $r) {
            if ((int)$r['delta'] > 0) {
                $moverLines .= "\n  " . $r['name'] . '  +' . $r['delta'];
            }
        }
        $declinerLines = '';
        foreach ($decliners as $r) {
            if ((int)$r['delta'] < 0) {
                $declinerLines .= "\n  " . $r['name'] . '  ' . $r['delta'];
            }
        }

        $nextLine = '';
        if ($nextFixture) {
            $nextLine = "\n\n⏰ " . Yii::t('app', 'Next match') . ': '
                . $nextFixture['home'] . ' vs ' . $nextFixture['away']
                . ' — ' . date('d/m H:i', (int)$nextFixture['match_date']);
        }

        $body = "📊 " . Yii::t('app', 'Daily Report') . " — {$team->name}\n\n"
            . "⚽ " . Yii::t('app', 'Squad') . "\n"
            . "  " . Yii::t('app', 'Avg form') . ":      {$avgForm}%\n"
            . "  " . Yii::t('app', 'Avg freshness') . ": {$avgFreshness}%\n"
            . "  🏥 " . Yii::t('app', 'Injured') . ":    {$injured}\n"
            . "  🟥 " . Yii::t('app', 'Suspended') . ":  {$suspended}\n\n"
            . "💰 " . Yii::t('app', 'Finance') . "\n"
            . "  " . Yii::t('app', 'Budget') . ": €" . number_format($budget, 0, ',', '.') . "\n"
            . "  " . Yii::t('app', 'Wages/week') . ": €" . number_format($weeklyWages, 0, ',', '.') . "\n"
            . "  " . Yii::t('app', 'Sponsor/week') . ": €" . number_format($weeklySponsors, 0, ',', '.') . "\n"
            . "  " . Yii::t('app', 'Transfer balance (season)') . ": "
            . ($transferBalance >= 0 ? '+' : '') . "€" . number_format($transferBalance, 0, ',', '.')
            . ($moverLines    ? "\n\n📈 " . Yii::t('app', 'Top growth (24h)') . $moverLines    : '')
            . ($declinerLines ? "\n\n📉 " . Yii::t('app', 'Top decline (24h)') . $declinerLines : '')
            . $nextLine;

        // Compact Telegram version
        $tgTop = !empty($improvers) && (int)($improvers[0]['delta'] ?? 0) > 0
            ? '📈 ' . $improvers[0]['name'] . ' +' . $improvers[0]['delta']
            : '';
        $tgBot = !empty($decliners) && (int)($decliners[0]['delta'] ?? 0) < 0
            ? '📉 ' . $decliners[0]['name'] . ' ' . $decliners[0]['delta']
            : '';
        $tgMovers = implode(' | ', array_filter([$tgTop, $tgBot]));

        $telegramText = "📊 <b>" . Yii::t('app', 'Daily Report') . "</b> — {$team->name}\n"
            . "⚽ " . Yii::t('app', 'Form') . " {$avgForm}% · "
            . Yii::t('app', 'Fresh') . " {$avgFreshness}% · 🏥{$injured} 🟥{$suspended}\n"
            . "💰 €" . number_format($budget, 0, ',', '.') . " · "
            . Yii::t('app', 'Wages') . " €" . number_format($weeklyWages, 0, ',', '.') . "/w"
            . ($tgMovers ? "\n" . $tgMovers : '')
            . ($nextFixture ? "\n⏰ " . date('d/m H:i', (int)$nextFixture['match_date']) . ' ' . $nextFixture['home'] . ' vs ' . $nextFixture['away'] : '');

        return new self($title, $body, $telegramText);
    }

    /** Top N players by negative general_skill delta (decliners). */
    private static function getTopDecliners(int $teamId, int $limit): array
    {
        $sql = <<<SQL
SELECT s.player_id,
       p.name,
       p.position,
       s.general_skill                                       AS current_skill,
       s.general_skill - COALESCE(prev.general_skill, s.general_skill) AS delta
FROM {{%training_snapshot}} s
JOIN {{%player}} p ON p.id = s.player_id
LEFT JOIN {{%training_snapshot}} prev
       ON prev.player_id = s.player_id
      AND prev.snapshot_date = (
              SELECT MAX(s2.snapshot_date)
              FROM {{%training_snapshot}} s2
              WHERE s2.player_id = s.player_id
                AND s2.snapshot_date < s.snapshot_date
          )
WHERE s.team_id = :t
  AND s.snapshot_date = (SELECT MAX(s3.snapshot_date) FROM {{%training_snapshot}} s3 WHERE s3.team_id = :t2)
ORDER BY delta ASC
LIMIT :lim
SQL;
        return Yii::$app->db->createCommand($sql, [
            ':t' => $teamId, ':t2' => $teamId, ':lim' => $limit,
        ])->queryAll();
    }
}
