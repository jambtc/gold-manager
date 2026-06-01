<?php

declare(strict_types=1);

namespace app\components;

use Yii;

class CommentaryTemplateService
{
    private static ?bool $hasLanguageColumn = null;

    /** Returns true when LLM commentary is globally enabled */
    public static function llmEnabled(): bool
    {
        return self::envBool('GM_LLM_ENABLED', true);
    }

    /** LLM enrichment for live match events */
    public static function llmMatchEnrichEnabled(): bool
    {
        if (!self::llmEnabled()) {
            return false;
        }
        return self::envBool('GM_LLM_MATCH_ENRICH_ENABLED', true);
    }

    /** LLM long pre-match text */
    public static function llmPreMatchEnabled(): bool
    {
        if (!self::llmEnabled()) {
            return false;
        }
        return self::envBool('GM_LLM_PREMATCH_ENABLED', true);
    }

    public static function commentaryStreamEnabled(): bool
    {
        return self::envBool('GM_COMMENTARY_STREAM_ENABLED', true);
    }

    public static function suspenseEnabled(): bool
    {
        return self::envBool('GM_SUSPENSE_ENABLED', true);
    }

    public static function suspenseDelayMs(): int
    {
        $raw = trim((string) getenv('GM_SUSPENSE_DELAY_MS'));
        $value = ctype_digit($raw) ? (int) $raw : 2500;
        return max(300, min(10000, $value));
    }

    public static function templateStreamChunkMs(): int
    {
        $raw = trim((string) getenv('GM_TEMPLATE_STREAM_CHUNK_MS'));
        $value = ctype_digit($raw) ? (int) $raw : 120;
        return max(15, min(2000, $value));
    }

    public static function templateStreamMode(): string
    {
        $v = strtolower(trim((string) getenv('GM_TEMPLATE_STREAM_MODE')));
        return in_array($v, ['token', 'clause'], true) ? $v : 'token';
    }

    /**
     * Pick a weighted-random template for the given event type and context,
     * render placeholders, and return the final string.
     * Returns null if no template found.
     *
     * SIP-0079: $lang filters templates by language column (default 'it-IT').
     * Falls back to 'it-IT' templates if no $lang match found.
     */
    public static function pick(
        string $eventType,
        int    $minute = 0,
        array  $tokens = [],
        string $subtype = '',
        int    $fixtureId = 0,
        string $gameState = '',
        string $lang = 'it-IT'
    ): ?string {
        $baseParams = [
            ':et'   => $eventType,
            ':sub'  => $subtype, ':sub2' => $subtype,
            ':gs'   => $gameState, ':gs2' => $gameState,
            ':min1' => $minute,  ':min2' => $minute,
            ':max1' => $minute,  ':max2' => $minute,
        ];

        if (self::hasLanguageColumn()) {
            $rows = Yii::$app->db->createCommand(
                'SELECT id, text, weight
                 FROM {{%commentary_template}}
                 WHERE event_type = :et AND enabled = 1
                   AND language = :lang
                   AND (:sub = "" OR subtype IS NULL OR subtype = :sub2)
                   AND (:gs = "" OR game_state IS NULL OR game_state = :gs2)
                   AND (:min1 = 0 OR min_minute IS NULL OR min_minute <= :min2)
                   AND (:max1 = 0 OR max_minute IS NULL OR max_minute >= :max2)
                 ORDER BY weight DESC',
                array_merge($baseParams, [':lang' => $lang])
            )->queryAll();

            // Fallback to it-IT if no templates exist for requested language
            if (empty($rows) && $lang !== 'it-IT') {
                $rows = Yii::$app->db->createCommand(
                    'SELECT id, text, weight
                     FROM {{%commentary_template}}
                     WHERE event_type = :et AND enabled = 1
                       AND language = "it-IT"
                       AND (:sub = "" OR subtype IS NULL OR subtype = :sub2)
                       AND (:gs = "" OR game_state IS NULL OR game_state = :gs2)
                       AND (:min1 = 0 OR min_minute IS NULL OR min_minute <= :min2)
                       AND (:max1 = 0 OR max_minute IS NULL OR max_minute >= :max2)
                     ORDER BY weight DESC',
                    $baseParams
                )->queryAll();
            }
        } else {
            // Backward-compatibility guard for DBs missing migration m260529_210100.
            $rows = Yii::$app->db->createCommand(
                'SELECT id, text, weight
                 FROM {{%commentary_template}}
                 WHERE event_type = :et AND enabled = 1
                   AND (:sub = "" OR subtype IS NULL OR subtype = :sub2)
                   AND (:gs = "" OR game_state IS NULL OR game_state = :gs2)
                   AND (:min1 = 0 OR min_minute IS NULL OR min_minute <= :min2)
                   AND (:max1 = 0 OR max_minute IS NULL OR max_minute >= :max2)
                 ORDER BY weight DESC',
                $baseParams
            )->queryAll();
        }

        if (empty($rows)) return null;

        // Anti-repeat: exclude last 3 used IDs for this fixture (stored in Yii cache)
        $cacheKey = "commentary_used_{$fixtureId}_{$eventType}";
        $used = $fixtureId > 0 ? (Yii::$app->cache->get($cacheKey) ?: []) : [];
        $candidates = array_filter($rows, fn($r) => !in_array((int)$r['id'], $used, true));
        if (empty($candidates)) $candidates = $rows; // all used → reset

        // Weighted random
        $total = array_sum(array_column($candidates, 'weight'));
        $roll  = mt_rand(1, max(1, $total));
        $cumul = 0;
        $chosen = end($candidates);
        foreach ($candidates as $r) {
            $cumul += (int)$r['weight'];
            if ($roll <= $cumul) { $chosen = $r; break; }
        }

        // Track usage
        if ($fixtureId > 0) {
            $used[] = (int)$chosen['id'];
            if (count($used) > 5) array_shift($used);
            Yii::$app->cache->set($cacheKey, $used, 3600);
        }

        return self::render((string)$chosen['text'], $tokens);
    }

    /** Replace {placeholders} with token values */
    private static function render(string $template, array $tokens): string
    {
        $lang     = $tokens['_lang'] ?? 'it-IT';
        $defaults = $lang === 'en-US' ? [
            'player_attacker' => 'a player',
            'player_defender' => 'the defender',
            'player_gk'       => 'the goalkeeper',
            'player_assist'   => 'a teammate',
            'player_in'       => 'the substitute',
            'player_out'      => 'the outgoing player',
            'home_team'       => 'the home side',
            'away_team'       => 'the away side',
            'score_home'      => '0',
            'score_away'      => '0',
            'minute'          => '?',
            'spectators'      => 'many',
        ] : [
            'player_attacker' => 'un giocatore',
            'player_defender' => 'il difensore',
            'player_gk'       => 'il portiere',
            'player_assist'   => 'un compagno',
            'player_in'       => 'il nuovo entrato',
            'player_out'      => 'il giocatore uscente',
            'home_team'       => 'la squadra di casa',
            'away_team'       => 'la squadra ospite',
            'score_home'      => '0',
            'score_away'      => '0',
            'minute'          => '?',
            'spectators'      => 'numerosi',
        ];
        $merged = array_merge($defaults, $tokens);

        foreach ($merged as $k => $v) {
            $template = str_replace('{' . $k . '}', (string)$v, $template);
        }
        return $template;
    }

    private static function envBool(string $name, bool $default): bool
    {
        $v = getenv($name);
        if ($v === false || $v === '') {
            return $default;
        }
        $v = strtolower(trim((string) $v));
        if (in_array($v, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }
        return $default;
    }

    private static function hasLanguageColumn(): bool
    {
        if (self::$hasLanguageColumn !== null) {
            return self::$hasLanguageColumn;
        }
        try {
            $schema = Yii::$app->db->schema->getTableSchema('{{%commentary_template}}', true);
            self::$hasLanguageColumn = $schema !== null && isset($schema->columns['language']);
        } catch (\Throwable) {
            self::$hasLanguageColumn = false;
        }
        return self::$hasLanguageColumn;
    }
}
