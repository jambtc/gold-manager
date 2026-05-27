<?php

use yii\db\Migration;

class m260527_230000_create_market_bid_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%market_bid}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'market_type' => $this->string(24)->notNull(),
            'market_ref_id' => $this->integer()->notNull(),
            'bid_amount' => $this->integer()->notNull()->defaultValue(0),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'expires_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
            'resolved_at' => $this->integer()->null(),
            'result_note' => $this->string(255)->null(),
        ]);

        $this->createIndex('idx-market_bid-team', '{{%market_bid}}', 'team_id');
        $this->createIndex('idx-market_bid-status-exp', '{{%market_bid}}', ['status', 'expires_at']);
        $this->createIndex('idx-market_bid-target', '{{%market_bid}}', ['market_type', 'market_ref_id', 'status']);
        $this->createIndex('uq-market_bid-team-target-pending', '{{%market_bid}}', ['team_id', 'market_type', 'market_ref_id', 'status']);

        $this->addForeignKey(
            'fk-market_bid-team',
            '{{%market_bid}}',
            'team_id',
            '{{%team}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-market_bid-team', '{{%market_bid}}');
        $this->dropTable('{{%market_bid}}');
    }
}
