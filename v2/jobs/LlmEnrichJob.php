<?php

declare(strict_types=1);

namespace app\jobs;

use app\components\LlmCommentary;
use app\models\Fixture;
use app\models\MatchEvent;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Enriches a MatchEvent's detail.description with LLM commentary.
 * Runs asynchronously via the queue worker so PHP simulation is never blocked.
 */
class LlmEnrichJob extends BaseObject implements JobInterface
{
    public int    $eventId;
    public int    $fixtureId;
    public string $type;
    public string $side;
    public int    $minute;
    public int    $homeScore = 0;
    public int    $awayScore = 0;
    public string $fallback  = '';

    public function execute($queue): void
    {
        $isPreMatchType = in_array($this->type, ['kickoff', 'pre_match'], true);
        $llmAllowed = $isPreMatchType
            ? \app\components\CommentaryTemplateService::llmPreMatchEnabled()
            : \app\components\CommentaryTemplateService::llmMatchEnrichEnabled();
        if (!$llmAllowed) {
            Yii::info("LlmEnrichJob: LLM disabled for type {$this->type}, skipping.", 'queue');
            return;
        }

        try { Yii::$app->db->createCommand('SELECT 1')->execute(); }
        catch (\Exception $e) { Yii::$app->db->close(); Yii::$app->db->open(); }

        $event = MatchEvent::findOne($this->eventId);
        if (!$event) {
            return;
        }

        $fixture = Fixture::findOne($this->fixtureId);
        if (!$fixture) {
            return;
        }

        // Resolve player name from event details or database
        $detail = $event->detail ? (json_decode($event->detail, true) ?? []) : [];
        $playerName = null;
        if (isset($detail['scorer_name'])) {
            $playerName = $detail['scorer_name'];
        } elseif (isset($detail['shooter_name'])) {
            $playerName = $detail['shooter_name'];
        } elseif (isset($detail['attacker_name'])) {
            $playerName = $detail['attacker_name'];
        }

        if (!$playerName && $event->player_id) {
            $player = \app\models\Player::findOne($event->player_id);
            if ($player) {
                $playerName = $player->name;
            }
        }

        // Resolve team relationship (propria squadra vs avversario)
        $userTeamId = \app\models\Team::find()->select('id')->where('user_id IS NOT NULL')->scalar() ?: null;
        if ($userTeamId && $playerName) {
            $eventTeamId = ($this->side === 'home') ? $fixture->home_team_id : $fixture->away_team_id;
            if ($eventTeamId === $userTeamId) {
                $playerName .= ' (propria squadra)';
            } else {
                $playerName .= ' (avversario)';
            }
        }

        $desc = $this->generateDescription($fixture, $playerName);

        if (!$desc || $desc === $this->fallback) {
            return;
        }

        // Merge description into existing detail JSON
        $detail['description'] = $desc;
        $event->detail = json_encode($detail, JSON_UNESCAPED_UNICODE);
        $event->save(false);

        Yii::info("LlmEnrichJob: enriched event #{$this->eventId} ({$this->type})", 'queue');
    }

    private function generateDescription(Fixture $fixture, ?string $playerName): string
    {
        return match($this->type) {
            'kickoff' => (function () use ($fixture): string {
                $weather    = ['soleggiato', 'piovoso', 'nuvoloso', 'ventoso'][mt_rand(0, 3)];
                $capacity   = $fixture->homeTeam->stadium?->capacity ?? 20000;
                $spectators = mt_rand((int)($capacity * 0.6), $capacity);
                return LlmCommentary::getPreMatch($fixture, $weather, $spectators);
            })(),
            'half_time' => LlmCommentary::getHalfTimeReview($fixture, $this->homeScore, $this->awayScore),
            'full_time'  => LlmCommentary::getFullTimeReview($fixture, $this->homeScore, $this->awayScore),
            default => LlmCommentary::getTickComment($fixture, $this->minute, $this->type, $this->side, $playerName, [
                'home_score' => $this->homeScore,
                'away_score' => $this->awayScore,
            ]),
        };
    }
}
