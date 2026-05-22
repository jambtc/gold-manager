<?php

use yii\db\Migration;

/**
 * SIP-0046: staff market, history, and contract metadata.
 */
class m260519_231500_staff_market_and_contracts extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%staff}}', 'contract_ends', $this->integer()->notNull()->defaultValue(1)->after('motivation'));
        $this->addColumn('{{%staff}}', 'age', $this->tinyInteger()->notNull()->defaultValue(35)->after('experience'));

        $this->createTable('{{%staff_market}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'name' => $this->string(80)->notNull(),
            'role' => $this->string(30)->notNull(),
            'ability' => $this->tinyInteger()->notNull()->defaultValue(50),
            'experience' => $this->tinyInteger()->notNull()->defaultValue(10),
            'motivation' => $this->tinyInteger()->notNull()->defaultValue(80),
            'salary' => $this->integer()->notNull()->defaultValue(50000),
            'contract_length' => $this->tinyInteger()->notNull()->defaultValue(2),
            'negotiations' => $this->tinyInteger()->notNull()->defaultValue(4),
            'raise_used' => $this->tinyInteger()->notNull()->defaultValue(0),
            'generated_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-staff_market-team_id', '{{%staff_market}}', 'team_id');
        $this->createIndex('idx-staff_market-role', '{{%staff_market}}', 'role');
        $this->addForeignKey('fk-staff_market-team_id', '{{%staff_market}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%staff_history}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'name' => $this->string(80)->notNull(),
            'role' => $this->string(30)->notNull(),
            'ability' => $this->tinyInteger()->notNull()->defaultValue(50),
            'experience' => $this->tinyInteger()->notNull()->defaultValue(10),
            'motivation' => $this->tinyInteger()->notNull()->defaultValue(80),
            'salary' => $this->integer()->notNull()->defaultValue(0),
            'efficiency' => $this->tinyInteger()->notNull()->defaultValue(0),
            'joined_season' => $this->integer()->null(),
            'left_season' => $this->integer()->notNull()->defaultValue(1),
            'left_reason' => $this->string(20)->notNull()->defaultValue('fired'),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-staff_history-team_id', '{{%staff_history}}', 'team_id');
        $this->addForeignKey('fk-staff_history-team_id', '{{%staff_history}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-staff_history-team_id', '{{%staff_history}}');
        $this->dropTable('{{%staff_history}}');

        $this->dropForeignKey('fk-staff_market-team_id', '{{%staff_market}}');
        $this->dropTable('{{%staff_market}}');

        $this->dropColumn('{{%staff}}', 'age');
        $this->dropColumn('{{%staff}}', 'contract_ends');
    }
}

