<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SIP-0091: Add termination_fee to contract.
 * Back-fills existing rows with salary × remaining_seasons × 0.35, min 5000.
 */
class m260602_200100_add_termination_fee_to_contract extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%contract}}', 'termination_fee',
            $this->integer()->unsigned()->notNull()->defaultValue(0)->after('release_clause')
        );

        // Back-fill: fee = salary × max(1, season_end - season_start) × 0.35, min 5000
        $this->execute(
            'UPDATE {{%contract}}
             SET termination_fee = GREATEST(5000,
                 ROUND(salary * GREATEST(1, season_end - season_start) * 0.35, -3))
             WHERE status = \'active\''
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%contract}}', 'termination_fee');
    }
}
