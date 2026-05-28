<?php

declare(strict_types=1);

namespace app\components;

use app\models\Fixture;

/**
 * Calls the local "telecronista" Ollama model.
 * The model has the Italian football commentator persona baked in via Modelfile,
 * so prompts only need to supply event facts — no role instructions needed.
 */
class LlmCommentary
{
    private const MODEL       = 'telecronista';
    private const NUM_PREDICT = 46;
    private const TEMPERATURE = 0.30;
    private const TOP_P       = 0.75;
    private const REPEAT_PENALTY = 1.12;
    private const TIMEOUT     = 30;
    private const ENDPOINT    = 'http://ollama:11434/api/generate';

    /**
     * Sends a prompt to Ollama using streaming mode.
     * Streaming keeps the HTTP connection alive (token per token) so curl
     * never hits an idle timeout even when generation takes 15-20s on CPU.
     */
    public static function generate(string $prompt, array $requiredTerms = []): string
    {
        $prompt = self::strictPrompt($prompt);
        $payload = json_encode([
            'model'  => self::MODEL,
            'prompt' => $prompt,
            'stream' => true,
            'options' => [
                'temperature'    => self::TEMPERATURE,
                'top_p'          => self::TOP_P,
                'repeat_penalty' => self::REPEAT_PENALTY,
                'num_predict'    => self::NUM_PREDICT,
            ],
        ]);

        $accumulated = '';
        $handle      = curl_init(self::ENDPOINT);

        curl_setopt_array($handle, [
            CURLOPT_POST          => true,
            CURLOPT_POSTFIELDS    => $payload,
            CURLOPT_HTTPHEADER    => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT       => self::TIMEOUT,
            CURLOPT_RETURNTRANSFER => false,
            // $_curl = curl handle (required by signature but unused here)
            CURLOPT_WRITEFUNCTION => function ($_curl, string $data) use (&$accumulated): int {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if ($line === '') continue;
                    $chunk = json_decode($line, true);
                    if (is_array($chunk) && isset($chunk['response'])) {
                        $accumulated .= $chunk['response'];
                    }
                }
                return strlen($data);
            },
        ]);

        curl_exec($handle);
        $err = curl_error($handle);
        curl_close($handle);

        if ($err) {
            return '';
        }

        $text = self::clean(trim(strip_tags($accumulated)));
        return self::isValid($text, $requiredTerms) ? $text : '';
    }

    private static function strictPrompt(string $facts): string
    {
        return <<<PROMPT
Scrivi telecronaca calcio italiana.
Regole obbligatorie:
- una sola frase;
- massimo 22 parole;
- usa solo dati forniti;
- non inventare nomi, azioni, risultato o contesto;
- niente preamboli, niente virgolette.
Dati: {$facts}
PROMPT;
    }

    private static function clean(string $text): string
    {
        $text = preg_replace('/\s+/u', ' ', $text) ?? '';
        $text = trim($text, " \t\n\r\0\x0B\"'");
        return mb_substr($text, 0, 220);
    }

    private static function isValid(string $text, array $requiredTerms): bool
    {
        if ($text === '' || mb_strlen($text) > 220 || preg_match('/[\r\n]/', $text)) {
            return false;
        }

        if (preg_match('/\b(non posso|mi dispiace|come ai|assistente|invent)/iu', $text)) {
            return false;
        }

        $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (count($words ?: []) > 28) {
            return false;
        }

        $haystack = mb_strtolower($text);
        foreach ($requiredTerms as $term) {
            $term = trim((string) $term);
            if ($term !== '' && !str_contains($haystack, mb_strtolower($term))) {
                return false;
            }
        }

        return true;
    }

    // ── Short, fact-only prompts — persona already baked in the model ──────

    public static function getPreMatch(Fixture $fixture, string $weather, int $spectators): string
    {
        $home    = $fixture->homeTeam->name;
        $away    = $fixture->awayTeam->name;
        $stadium = $fixture->homeTeam->stadium?->name ?? 'Stadio Comunale';

        $prompt  = "Partita: {$home} vs {$away}. Stadio: {$stadium}, {$spectators} spettatori, meteo: {$weather}.";
        $fallback = "Benvenuti allo {$stadium}! {$home} affronta {$away} davanti a {$spectators} spettatori, {$weather}.";

        return self::generate($prompt, [$home, $away]) ?: $fallback;
    }

    public static function getTickComment(
        Fixture $fixture,
        int     $minute,
        string  $type,
        string  $side,
        ?string $playerName,
        array   $detail
    ): string {
        $home       = $fixture->homeTeam->name;
        $away       = $fixture->awayTeam->name;
        $attTeam    = $side === 'home' ? $home : $away;
        $defTeam    = $side === 'home' ? $away : $home;
        $player     = $playerName ? " ({$playerName})" : '';

        $hs = $detail['home_score'] ?? $detail['home'] ?? 0;
        $as = $detail['away_score'] ?? $detail['away'] ?? 0;

        [$prompt, $fallback] = match ($type) {
            'goal' => [
                "Min {$minute}: GOL {$attTeam}{$player} contro {$defTeam}. {$hs}-{$as}.",
                "GOOOOOL! {$attTeam} segna al minuto {$minute}! Punteggio: {$hs}-{$as}!",
            ],
            'gk_save' => [
                "Min {$minute}: parata {$defTeam}, tiro {$attTeam}{$player}.",
                "Parata strepitosa! Il portiere del {$defTeam} respinge il tiro del {$attTeam}!",
            ],
            'near_miss' => [
                "Min {$minute}: palo/fuori {$attTeam}{$player} contro {$defTeam}.",
                "Clamoroso! {$attTeam} sfiora il gol, palla fuori di un soffio!",
            ],
            'attack_attempt' => [
                "Min {$minute}: azione {$attTeam} su {$defTeam}.",
                "{$attTeam} avanza verso l'area di {$defTeam}.",
            ],
            default => [
                "Min {$minute}: centrocampo {$home} vs {$away}.",
                "Il gioco staziona a centrocampo, ritmi blandi in questa fase.",
            ],
        };

        return self::generate($prompt, [$attTeam]) ?: $fallback;
    }

    public static function getHalfTimeReview(Fixture $fixture, int $homeScore, int $awayScore): string
    {
        $home    = $fixture->homeTeam->name;
        $away    = $fixture->awayTeam->name;
        $prompt  = "Intervallo: {$home} {$homeScore} - {$away} {$awayScore}.";
        $fallback = "Duplice fischio! Si va negli spogliatoi sul {$homeScore}-{$awayScore}.";

        return self::generate($prompt, [$home, $away]) ?: $fallback;
    }

    public static function getFullTimeReview(Fixture $fixture, int $homeScore, int $awayScore): string
    {
        $home    = $fixture->homeTeam->name;
        $away    = $fixture->awayTeam->name;
        $prompt  = "Finale: {$home} {$homeScore} - {$away} {$awayScore}.";
        $fallback = "Triplice fischio! Finisce {$home} {$homeScore} - {$awayScore} {$away}!";

        return self::generate($prompt, [$home, $away]) ?: $fallback;
    }
}
