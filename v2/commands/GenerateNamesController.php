<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Generates football player names for each nationality via Ollama (SIP-0070).
 *
 * Usage:
 *   ./yii generate-names/run               # all nationalities
 *   ./yii generate-names/run --nat=ESP     # single nationality
 *   ./yii generate-names/run --count=80    # names per type per nationality
 */
class GenerateNamesController extends Controller
{
    private const ENDPOINT    = 'http://ollama:11434/api/generate';
    private const MODEL       = 'qwen2:1.5b';
    private const TEMPERATURE = 0.85;
    private const NUM_PREDICT = 800;
    private const TIMEOUT     = 120;

    private const NATIONALITIES = [
        'ITA' => 'Italian',
        'ESP' => 'Spanish',
        'BRA' => 'Brazilian',
        'ARG' => 'Argentine',
        'FRA' => 'French',
        'DEU' => 'German',
        'ENG' => 'English',
        'PRT' => 'Portuguese',
        'NLD' => 'Dutch',
        'HRV' => 'Croatian',
        'SEN' => 'Senegalese',
        'NGA' => 'Nigerian',
        'GHA' => 'Ghanaian',
        'CMR' => 'Cameroonian',
        'BEL' => 'Belgian',
        'URY' => 'Uruguayan',
        'COL' => 'Colombian',
        'CHI' => 'Chilean',
        'MEX' => 'Mexican',
        'USA' => 'American',
        'JPN' => 'Japanese',
        'KOR' => 'South Korean',
        'MAR' => 'Moroccan',
        'DZA' => 'Algerian',
        'SRB' => 'Serbian',
    ];

    /** @var string nationality code to process (empty = all) */
    public string $nat = '';

    /** @var int names to generate per type (first / last) per nationality */
    public int $count = 60;

    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), ['nat', 'count']);
    }

    public function actionRun(): int
    {
        $targets = $this->nat !== ''
            ? [$this->nat => self::NATIONALITIES[$this->nat] ?? $this->nat]
            : self::NATIONALITIES;

        foreach ($targets as $code => $label) {
            $this->stdout("[$code] Generating $label names (count={$this->count})...\n");

            $firstNames = $this->generate($label, 'first', $this->count);
            $lastNames  = $this->generate($label, 'last', $this->count);

            $insertedFirst = $this->insertNames('{{%name_first}}', $code, $firstNames, true);
            $insertedLast  = $this->insertNames('{{%name_last}}', $code, $lastNames, false);

            $this->stdout("  first: " . count($firstNames) . " generated, $insertedFirst inserted\n");
            $this->stdout("  last:  " . count($lastNames) . " generated, $insertedLast inserted\n");
        }

        $this->stdout("Done.\n");
        return ExitCode::OK;
    }

    /**
     * Asks the LLM for a JSON array of football player names.
     * Returns a deduplicated, cleaned array of strings.
     */
    private function generate(string $nationality, string $type, int $count): array
    {
        $typeLabel = $type === 'first' ? 'first names (given names)' : 'surnames (last names)';

        $prompt = <<<PROMPT
You are a data generator. Respond ONLY with a valid JSON array of strings, nothing else.
No prose, no explanation, no markdown, no code block — just the raw JSON array starting with [ and ending with ].

Generate {$count} realistic {$nationality} football player {$typeLabel}.
Use authentic names that real {$nationality} footballers actually have.
Vary the names — avoid repetition.

Example output format:
["Marco","Luca","Andrea"]

Generate {$count} names now:
PROMPT;

        $raw = $this->callOllama($prompt);
        return $this->parseNames($raw);
    }

    private function callOllama(string $prompt): string
    {
        $payload = json_encode([
            'model'  => self::MODEL,
            'prompt' => $prompt,
            'stream' => true,
            'options' => [
                'temperature' => self::TEMPERATURE,
                'num_predict' => self::NUM_PREDICT,
            ],
        ]);

        $accumulated = '';
        $handle      = curl_init(self::ENDPOINT);

        curl_setopt_array($handle, [
            CURLOPT_POST            => true,
            CURLOPT_POSTFIELDS      => $payload,
            CURLOPT_HTTPHEADER      => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT         => self::TIMEOUT,
            CURLOPT_RETURNTRANSFER  => false,
            CURLOPT_WRITEFUNCTION   => function ($_curl, string $data) use (&$accumulated): int {
                foreach (explode("\n", $data) as $line) {
                    $line  = trim($line);
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
            $this->stderr("  [curl error] $err\n");
        }

        return $accumulated;
    }

    /**
     * Parses LLM output into a clean name array.
     * Handles: pure JSON, JSON embedded in prose, markdown code blocks.
     */
    private function parseNames(string $raw): array
    {
        $raw = trim($raw);

        // Strip markdown code block if present
        $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```$/', '', $raw);
        $raw = trim($raw ?? '');

        // Try direct JSON decode
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $this->cleanNames($decoded);
        }

        // Extract first [...] block from prose
        if (preg_match('/(\[[\s\S]*?\])/m', $raw, $m)) {
            $decoded = json_decode($m[1], true);
            if (is_array($decoded)) {
                return $this->cleanNames($decoded);
            }
        }

        // Last resort: extract quoted strings
        preg_match_all('/"([^"]{2,60})"/', $raw, $matches);
        return $this->cleanNames($matches[1] ?? []);
    }

    private function cleanNames(array $raw): array
    {
        $out  = [];
        $seen = [];
        foreach ($raw as $item) {
            if (!is_string($item)) continue;
            $name = trim($item);
            // Remove numbering prefixes like "1. " or "1) "
            $name = preg_replace('/^\d+[.)]\s*/', '', $name) ?? $name;
            $name = mb_convert_case(trim($name), MB_CASE_TITLE, 'UTF-8');
            if (mb_strlen($name) < 2 || mb_strlen($name) > 60) continue;
            $key = mb_strtolower($name);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = $name;
        }
        return $out;
    }

    private function insertNames(string $table, string $nationality, array $names, bool $hasGender): int
    {
        $db      = Yii::$app->db;
        $inserted = 0;
        foreach ($names as $name) {
            $exists = (int) $db->createCommand(
                "SELECT COUNT(*) FROM $table WHERE name=:n AND nationality=:nat",
                [':n' => $name, ':nat' => $nationality]
            )->queryScalar();
            if ($exists > 0) continue;

            $row = ['name' => $name, 'nationality' => $nationality];
            if ($hasGender) $row['gender'] = 0;

            try {
                $db->createCommand()->insert($table, $row)->execute();
                $inserted++;
            } catch (\Throwable) {
                // skip
            }
        }
        return $inserted;
    }
}
