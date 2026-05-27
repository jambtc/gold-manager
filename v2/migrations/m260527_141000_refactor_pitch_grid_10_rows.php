<?php

use yii\db\Migration;

/**
 * SIP-0067: migrate legacy 7x9 pitch zones to logical 10-row grid.
 *
 * - formation_slot.zone old(1..63) -> new(GK=10, rows 2..10 lanes 1..3)
 * - calcolatore Formula 2 coefficients aggregated on new ord zones
 */
class m260527_141000_refactor_pitch_grid_10_rows extends Migration
{
    public function safeUp(): void
    {
        $this->migrateFormationSlots();
        $this->migrateCalculatorFormula2();
    }

    public function safeDown(): bool
    {
        echo "m260527_141000_refactor_pitch_grid_10_rows cannot be reverted safely.\n";
        return false;
    }

    private function migrateFormationSlots(): void
    {
        $rows = $this->db->createCommand(
            'SELECT id, zone FROM {{%formation_slot}}'
        )->queryAll();

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $zone = (int) ($row['zone'] ?? 0);
            if ($id <= 0 || $zone <= 0) {
                continue;
            }
            $newZone = $this->normalizeZone($zone);
            if ($newZone !== $zone) {
                $this->update('{{%formation_slot}}', ['zone' => $newZone], ['id' => $id]);
            }
        }
    }

    private function migrateCalculatorFormula2(): void
    {
        $rows = $this->db->createCommand(
            'SELECT formula, qu, po, df, cn, pa, rg, cr, tc, tr, ord
             FROM {{%calcolatore}}
             WHERE formula = :formula',
            [':formula' => 'Formula 2']
        )->queryAll();

        if (empty($rows)) {
            return;
        }

        $bucket = [];
        foreach ($rows as $row) {
            $ord = (int) ($row['ord'] ?? 0);
            if ($ord <= 0) {
                continue;
            }
            $zone = $this->normalizeZone($ord);
            if (!$this->isCurrentZone($zone)) {
                continue;
            }

            if (!isset($bucket[$zone])) {
                $bucket[$zone] = [
                    'count' => 0,
                    'qu' => 0.0,
                    'po' => 0.0,
                    'df' => 0.0,
                    'cn' => 0.0,
                    'pa' => 0.0,
                    'rg' => 0.0,
                    'cr' => 0.0,
                    'tc' => 0.0,
                    'tr' => 0.0,
                ];
            }

            $bucket[$zone]['count']++;
            $bucket[$zone]['qu'] += (float) ($row['qu'] ?? 0);
            $bucket[$zone]['po'] += (float) ($row['po'] ?? 0);
            $bucket[$zone]['df'] += (float) ($row['df'] ?? 0);
            $bucket[$zone]['cn'] += (float) ($row['cn'] ?? 0);
            $bucket[$zone]['pa'] += (float) ($row['pa'] ?? 0);
            $bucket[$zone]['rg'] += (float) ($row['rg'] ?? 0);
            $bucket[$zone]['cr'] += (float) ($row['cr'] ?? 0);
            $bucket[$zone]['tc'] += (float) ($row['tc'] ?? 0);
            $bucket[$zone]['tr'] += (float) ($row['tr'] ?? 0);
        }

        if (empty($bucket)) {
            return;
        }

        ksort($bucket, SORT_NUMERIC);

        $this->delete('{{%calcolatore}}', ['formula' => 'Formula 2']);
        foreach ($bucket as $zone => $agg) {
            $n = max(1, (int) $agg['count']);
            $this->insert('{{%calcolatore}}', [
                'formula' => 'Formula 2',
                'qu' => (int) round($agg['qu'] / $n),
                'po' => round($agg['po'] / $n, 4),
                'df' => round($agg['df'] / $n, 4),
                'cn' => round($agg['cn'] / $n, 4),
                'pa' => round($agg['pa'] / $n, 4),
                'rg' => round($agg['rg'] / $n, 4),
                'cr' => round($agg['cr'] / $n, 4),
                'tc' => round($agg['tc'] / $n, 4),
                'tr' => round($agg['tr'] / $n, 4),
                'ord' => (int) $zone,
            ]);
        }
    }

    private function normalizeZone(int $zone): int
    {
        if ($this->isCurrentZone($zone)) {
            return $zone;
        }
        if (!$this->isLegacyZone($zone)) {
            return 62;
        }

        $legacyRow = intdiv($zone - 1, 7) + 1;
        $legacyCol = (($zone - 1) % 7) + 1;
        if ($legacyRow === 9 && $legacyCol === 4) {
            return 10;
        }

        $row = 11 - $legacyRow;
        $lane = $legacyCol <= 2 ? 1 : ($legacyCol >= 6 ? 3 : 2);
        if ($row < 2) {
            $row = 2;
        } elseif ($row > 10) {
            $row = 10;
        }
        return ($row * 10) + $lane;
    }

    private function isLegacyZone(int $zone): bool
    {
        return $zone >= 1 && $zone <= 63;
    }

    private function isCurrentZone(int $zone): bool
    {
        if ($zone === 10) {
            return true;
        }
        $row = intdiv($zone, 10);
        $lane = $zone % 10;
        return $row >= 2 && $row <= 10 && $lane >= 1 && $lane <= 3;
    }
}

