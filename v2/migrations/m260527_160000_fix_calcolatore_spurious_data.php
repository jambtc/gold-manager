<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Fix spurious data in calcolatore Formula 2.
 *
 * Issues found:
 *  1. Zone 62 (row 6 center): po=34.0 — goalkeeper coefficient on a midfield zone.
 *     Fix: po=0, redistribute weight using average of zones 61 and 63.
 *
 *  2. Zone 71 (row 7 left): missing entirely.
 *     Fix: INSERT interpolated from zones 61 and 81 (column neighbours).
 *
 *  3. Zones 101, 102, 103 (row 10): totals ≈91, all others ≈95-100.
 *     Fix: proportional scale to reach target total 95.5.
 */
class m260527_160000_fix_calcolatore_spurious_data extends Migration
{
    private const F = 'Formula 2';

    // -----------------------------------------------------------------------
    // Zone 62 — old vs new
    // -----------------------------------------------------------------------
    private const Z62_OLD = ['po' => 34.0000, 'df' => 24.3340, 'cn' => 15.3108,
                              'pa' =>  8.2675, 'rg' =>  1.3950, 'cr' =>  4.3965,
                              'tc' =>  8.4290, 'tr' =>  0.7650];

    // Average of zones 61 and 63, tr adjusted so sum = 95.50
    private const Z62_NEW = ['po' =>  0.0000, 'df' => 13.0385, 'cn' => 14.1652,
                              'pa' => 18.8875, 'rg' =>  7.9922, 'cr' => 28.3562,
                              'tc' =>  9.4038, 'tr' =>  3.6600];

    // -----------------------------------------------------------------------
    // Zone 71 — new row to insert (interpolated from zones 61 and 81)
    // qu=55 is between zone 61 (qu=36) and zone 81 (qu=75)
    // -----------------------------------------------------------------------
    private const Z71_NEW = ['formula' => self::F, 'ord' => 71, 'qu' => 55,
                              'po' =>  0.0000, 'df' =>  7.3502, 'cn' => 10.5202,
                              'pa' => 23.0150, 'rg' =>  7.9039, 'cr' => 32.1454,
                              'tc' => 10.2672, 'tr' =>  6.2425];

    // -----------------------------------------------------------------------
    // Zones 101, 102, 103 — old totals ≈91, scale to 95.5
    // -----------------------------------------------------------------------
    private const Z101_OLD = ['po' => 0.0, 'df' => 0.9000, 'cn' =>  5.7870,
                               'pa' => 29.4750, 'rg' => 5.3415, 'cr' => 30.1576,
                               'tc' => 10.5498, 'tr' =>  8.8350];

    private const Z101_NEW = ['po' => 0.0, 'df' => 0.9440, 'cn' =>  6.0703,
                               'pa' => 30.9191, 'rg' => 5.6030, 'cr' => 31.6334,
                               'tc' => 11.0665, 'tr' =>  9.2674];

    private const Z102_OLD = ['po' => 0.0, 'df' => 0.9000, 'cn' =>  8.6220,
                               'pa' => 15.5280, 'rg' => 4.6080, 'cr' => 10.1629,
                               'tc' => 20.0197, 'tr' => 31.8377];

    private const Z102_NEW = ['po' => 0.0, 'df' => 0.9375, 'cn' =>  8.9813,
                               'pa' => 16.1753, 'rg' => 4.8000, 'cr' => 10.5866,
                               'tc' => 20.8536, 'tr' => 33.1644];

    // Zone 103 has same coefficients as 101
    private const Z103_OLD = self::Z101_OLD;
    private const Z103_NEW = self::Z101_NEW;

    // -----------------------------------------------------------------------

    public function safeUp(): void
    {
        // 1. Fix zone 62
        $this->update('{{%calcolatore}}', self::Z62_NEW,
            ['formula' => self::F, 'ord' => 62]);
        echo "    > Zone 62: po 34.0 → 0, coefficients redistributed\n";

        // 2. Add missing zone 71
        $this->insert('{{%calcolatore}}', self::Z71_NEW);
        echo "    > Zone 71: inserted (interpolated from zones 61 and 81)\n";

        // 3. Normalise zones 101-103
        foreach ([101 => self::Z101_NEW, 102 => self::Z102_NEW, 103 => self::Z103_NEW] as $ord => $vals) {
            $this->update('{{%calcolatore}}', $vals, ['formula' => self::F, 'ord' => $ord]);
        }
        echo "    > Zones 101-103: totals normalised from ~91 to ~95.5\n";
    }

    public function safeDown(): void
    {
        $this->update('{{%calcolatore}}', self::Z62_OLD,
            ['formula' => self::F, 'ord' => 62]);

        $this->delete('{{%calcolatore}}', ['formula' => self::F, 'ord' => 71]);

        foreach ([101 => self::Z101_OLD, 102 => self::Z102_OLD, 103 => self::Z103_OLD] as $ord => $vals) {
            $this->update('{{%calcolatore}}', $vals, ['formula' => self::F, 'ord' => $ord]);
        }
    }
}
