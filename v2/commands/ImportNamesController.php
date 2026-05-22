<?php

declare(strict_types=1);

namespace app\commands;

use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Import Italian names/surnames from legacy GU Classic CSV files.
 * Usage: ./yii import-names/run
 */
class ImportNamesController extends Controller
{
    private const FIRST_NAMES_CSV = '@app/../newGM/appoggio/nomi e cognomi/nomi propri.csv';
    private const LAST_NAMES_CSV  = '@app/../newGM/appoggio/nomi e cognomi/nuovo_cognomi.csv';

    public function actionRun(): int
    {
        $db = Yii::$app->db;

        $this->stdout("Importing first names...\n");
        $first = $this->loadFirstNames();
        $db->createCommand()->truncateTable('{{%name_first}}')->execute();
        $rows = 0;
        foreach ($first as [$gender, $name]) {
            $db->createCommand()->insert('{{%name_first}}', ['name' => $name, 'gender' => $gender])->execute();
            $rows++;
        }
        $this->stdout("  Imported $rows first names.\n");

        $this->stdout("Importing surnames...\n");
        $last = $this->loadLastNames();
        $db->createCommand()->truncateTable('{{%name_last}}')->execute();
        $rows = 0;
        foreach ($last as $name) {
            try {
                $db->createCommand()->insert('{{%name_last}}', ['name' => $name])->execute();
                $rows++;
            } catch (\Exception $e) {
                // skip duplicates
            }
        }
        $this->stdout("  Imported $rows surnames.\n");
        $this->stdout("Done.\n");
        return ExitCode::OK;
    }

    private function loadFirstNames(): array
    {
        $out  = [];
        $seen = [];
        $path = Yii::getAlias(self::FIRST_NAMES_CSV);
        if (!file_exists($path)) {
            $this->stderr("First names file not found: " . $path . "\n");
            return $out;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = str_getcsv($line, ';');
            $rawName = $this->lastTextField($parts);
            if (strtolower($rawName) === 'nome') {
                continue;
            }
            $gender = isset($parts[0]) && is_numeric(trim($parts[0])) ? (int) trim($parts[0]) : 0;
            $name   = $this->clean($rawName);
            if (!$name || isset($seen[$name])) continue;
            $seen[$name] = true;
            $out[] = [$gender, $name];
        }
        return $out;
    }

    private function loadLastNames(): array
    {
        $out  = [];
        $seen = [];
        $path = Yii::getAlias(self::LAST_NAMES_CSV);
        if (!file_exists($path)) {
            $this->stderr("Last names file not found: " . $path . "\n");
            return $out;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = str_getcsv($line, ';');
            $rawName = $this->lastTextField($parts);
            if (strtolower($rawName) === 'cognome') {
                continue;
            }
            $name = $this->clean($rawName);
            // Remove duplicated word pattern e.g. "Adamo Adamo"
            $words = explode(' ', $name);
            if (count($words) >= 2 && $words[0] === $words[1]) {
                $name = $words[0];
            }
            if (!$name || isset($seen[strtolower($name)])) continue;
            $seen[strtolower($name)] = true;
            $out[] = $name;
        }
        return $out;
    }

    /**
     * Legacy exports are either "0;Name" or "\"id\";\"name\"".
     */
    private function lastTextField(array $parts): string
    {
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $part = trim((string) $parts[$i], " \t\n\r\0\x0B\"");
            if ($part !== '' && !is_numeric($part)) {
                return $part;
            }
        }

        return trim((string) end($parts));
    }

    private function clean(string $s): string
    {
        $s = trim($s);
        $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8,ISO-8859-1');
        // Title case
        $s = mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
        // Tables allow 60 chars; keep long but valid legacy names.
        if (mb_strlen($s) < 2 || mb_strlen($s) > 60) return '';
        return $s;
    }
}
