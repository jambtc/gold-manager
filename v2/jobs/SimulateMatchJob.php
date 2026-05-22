<?php

declare(strict_types=1);

namespace app\jobs;

use app\models\Fixture;
use app\models\MatchEvent;
use app\models\MatchState;
use app\models\Team;
use app\components\CommentaryTemplateService;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

/**
 * Simulates a full match in the background queue worker.
 *
 * LLM strategy — ALL async (never blocks the simulation):
 *
 *   pre_match   → instant fallback text → LlmEnrichJob in queue (high priority)
 *   during play → LlmEnrichJob async for goals only
 *   half_time   → instant fallback text → LlmEnrichJob in queue
 *   full_time   → instant fallback text → LlmEnrichJob in queue
 *
 * The browser polls refreshPendingUpdates() which re-fetches descriptions for
 * recent events and updates the displayed text when Ollama finishes.
 * This means Ollama NEVER blocks the clock — the match starts in seconds.
 *
 * CPU-vs-CPU → instant, no LLM at all.
 */
class SimulateMatchJob extends BaseObject implements JobInterface
{
    public int $fixtureId;

    public function execute($queue): void
    {
        try {
            Yii::$app->db->createCommand('SELECT 1')->execute();
        } catch (\Exception $e) {
            Yii::$app->db->close();
            Yii::$app->db->open();
        }

        $fixture = Fixture::findOne($this->fixtureId);
        if (!$fixture || $fixture->status === Fixture::STATUS_FINISHED) {
            return;
        }

        $homeTeam     = Team::findOne($fixture->home_team_id);
        $awayTeam     = Team::findOne($fixture->away_team_id);
        $isHumanMatch = ($homeTeam && !$homeTeam->is_cpu) || ($awayTeam && !$awayTeam->is_cpu);

        MatchState::deleteAll(['fixture_id' => $this->fixtureId]);
        MatchEvent::deleteAll(['fixture_id' => $this->fixtureId]);
        $fixture->home_score = 0;
        $fixture->away_score = 0;
        $fixture->save(false);

        /** @var \app\components\MatchEngine $engine */
        $engine = Yii::$app->matchEngine;

        if (!$isHumanMatch) {
            // ── CPU vs CPU: instant, no LLM ────────────────────────────────
            for ($tick = 0; $tick < 115; $tick++) {
                $engine->advanceTick($fixture);
                $state = MatchState::findOne(['fixture_id' => $this->fixtureId]);
                if ($state && strtolower($state->phase) === MatchState::PHASE_FINISHED) break;
            }
            Yii::info("SimulateMatchJob: cpu #{$this->fixtureId} done.", 'queue');
            return;
        }

        // ── HUMAN MATCH ─────────────────────────────────────────────────────

        // ① PRE-MATCH: save instantly with fallback, enrich async
        $weather    = ['soleggiato','piovoso','nuvoloso','ventoso'][random_int(0, 3)];
        $capacity   = $fixture->homeTeam->stadium?->capacity ?? 20000;
        $spectators = random_int((int)($capacity * 0.6), $capacity);
        $homeName   = $fixture->homeTeam->name ?? 'Casa';
        $awayName   = $fixture->awayTeam->name ?? 'Trasferta';
        $fallbackPre = "Le squadre di {$homeName} e {$awayName} entrano in campo. "
                     . "{$spectators} spettatori pronti a seguire il match. Meteo: {$weather}.";

        $preEvent             = new MatchEvent();
        $preEvent->fixture_id = $this->fixtureId;
        $preEvent->minute     = 0;
        $preEvent->type       = 'pre_match';
        $preEvent->team_side  = 'home';
        $preEvent->detail     = json_encode(
            ['description' => $fallbackPre, 'weather' => $weather, 'spectators' => $spectators],
            JSON_UNESCAPED_UNICODE
        );
        $preEvent->save(false);

        // Async LLM — will update pre_match description when Ollama is free
        if ($preEvent->id && Yii::$app->has('queue') && CommentaryTemplateService::llmPreMatchEnabled()) {
            Yii::$app->queue->push(new LlmEnrichJob([
                'eventId'   => $preEvent->id,
                'fixtureId' => $this->fixtureId,
                'type'      => 'kickoff',   // LlmEnrichJob maps 'kickoff' → getPreMatch()
                'side'      => 'home',
                'minute'    => 0,
                'homeScore' => 0,
                'awayScore' => 0,
                'fallback'  => $fallbackPre,
            ]));
        }

        // Give browser 3s to open the live page before ticks start
        sleep(3);

        // ── SIMULATION LOOP ─────────────────────────────────────────────────
        $htEnriched = false; // enqueue HT LLM only once

        for ($tick = 0; $tick < 115; $tick++) {
            $engine->advanceTick($fixture);

            $state = MatchState::findOne(['fixture_id' => $this->fixtureId]);
            if (!$state) continue;

            $phase = strtolower($state->phase);

            if ($phase === MatchState::PHASE_FINISHED) {
                break;
            }

            // ③ HALF-TIME: enqueue LLM enrichment once (async)
            if ($phase === 'half_time' && !$htEnriched) {
                $htEnriched = true;
                $htEvent = MatchEvent::find()
                    ->where(['fixture_id' => $this->fixtureId, 'type' => 'half_time'])
                    ->orderBy(['id' => SORT_DESC])
                    ->one();

                if ($htEvent && Yii::$app->has('queue') && CommentaryTemplateService::llmMatchEnrichEnabled()) {
                    Yii::$app->queue->push(new LlmEnrichJob([
                        'eventId'   => $htEvent->id,
                        'fixtureId' => $this->fixtureId,
                        'type'      => 'half_time',
                        'side'      => 'home',
                        'minute'    => 45,
                        'homeScore' => $state->home_score,
                        'awayScore' => $state->away_score,
                        'fallback'  => '',
                    ]));
                }
            }

            // ② DURING PLAY & half-time: pace at 2s
            sleep(2);
        }

        // ④ FULL-TIME: enqueue LLM enrichment (async)
        $ftState = MatchState::findOne(['fixture_id' => $this->fixtureId]);
        $ftEvent = MatchEvent::find()
            ->where(['fixture_id' => $this->fixtureId, 'type' => 'full_time'])
            ->orderBy(['id' => SORT_DESC])
            ->one();

        if ($ftEvent && Yii::$app->has('queue') && CommentaryTemplateService::llmMatchEnrichEnabled()) {
            Yii::$app->queue->push(new LlmEnrichJob([
                'eventId'   => $ftEvent->id,
                'fixtureId' => $this->fixtureId,
                'type'      => 'full_time',
                'side'      => 'home',
                'minute'    => 90,
                'homeScore' => $ftState?->home_score ?? 0,
                'awayScore' => $ftState?->away_score ?? 0,
                'fallback'  => '',
            ]));
        }

        Yii::info("SimulateMatchJob: human #{$this->fixtureId} completed.", 'queue');
    }
}
