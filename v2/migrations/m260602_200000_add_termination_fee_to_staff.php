<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SIP-0091: Add termination_fee to staff.
 * Back-fills existing rows with salary × 0.35 (1-season implicit contract).
 */
class m260602_200000_add_termination_fee_to_staff extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%staff}}', 'termination_fee',
            $this->integer()->unsigned()->notNull()->defaultValue(0)->after('salary')
        );

        // Back-fill: fee ≈ salary × 0.35, min 5000
        $this->execute('UPDATE {{%staff}} SET termination_fee = GREATEST(5000, ROUND(salary * 0.35, -3))');
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%staff}}', 'termination_fee');
    }
}
