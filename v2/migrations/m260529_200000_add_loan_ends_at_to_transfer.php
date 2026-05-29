<?php

use yii\db\Migration;

class m260529_200000_add_loan_ends_at_to_transfer extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%transfer}}',
            'loan_ends_at',
            $this->integer()->unsigned()->null()->comment('Unix ts when loan expires (SIP-0078)')
        );

        $this->createIndex(
            'idx_transfer_loan_ends_at',
            '{{%transfer}}',
            ['status', 'transfer_type', 'loan_ends_at']
        );
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_transfer_loan_ends_at', '{{%transfer}}');
        $this->dropColumn('{{%transfer}}', 'loan_ends_at');
    }
}
