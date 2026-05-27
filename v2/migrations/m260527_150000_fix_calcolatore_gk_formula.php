<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Fix calcolatore Formula 2 for GK zone (ord=10).
 *
 * Root cause: skill_po (portiere) had coefficient 0.0000 for ALL zones,
 * meaning a goalkeeper's primary skill was never used in position ratings.
 * A GK could score higher in an outfield zone than in goal.
 *
 * Fix: zone 10 gets po-dominant coefficients that sum to ~95.5 (same as
 * other zones) so GK players always score highest in the GK position.
 */
class m260527_150000_fix_calcolatore_gk_formula extends Migration
{
    private const FORMULA = 'Formula 2';
    private const ORD     = 10;

    // Old coefficients (backed up for safeDown)
    private const OLD = ['po' => 0.0, 'df' => 18.1320, 'cn' => 17.3818, 'pa' => 15.6475,
                         'rg' => 5.7085, 'cr' => 7.6465, 'tc' => 14.5100, 'tr' => 16.4965];

    // New coefficients: po dominant, sum ≈ 95.5
    private const NEW = ['po' => 65.0, 'df' => 8.0, 'cn' => 4.0, 'pa' => 2.0,
                         'rg' => 10.0, 'cr' => 0.5, 'tc' => 5.5, 'tr' => 0.5];

    public function safeUp(): void
    {
        $this->update(
            '{{%calcolatore}}',
            self::NEW,
            ['formula' => self::FORMULA, 'ord' => self::ORD]
        );
        echo "    > GK zone (ord=10) po coefficient: 0 → 65.0\n";
    }

    public function safeDown(): void
    {
        $this->update(
            '{{%calcolatore}}',
            self::OLD,
            ['formula' => self::FORMULA, 'ord' => self::ORD]
        );
    }
}
