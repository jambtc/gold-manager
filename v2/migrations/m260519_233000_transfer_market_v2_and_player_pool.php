<?php

use yii\db\Migration;

/**
 * SIP-0047: transfer offers, transfer extensions, player pool.
 */
class m260519_233000_transfer_market_v2_and_player_pool extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%transfer}}', 'transfer_type', $this->string(10)->notNull()->defaultValue('sale')->after('status'));
        $this->addColumn('{{%transfer}}', 'loan_return_season', $this->integer()->null()->after('transfer_type'));
        $this->addColumn('{{%transfer}}', 'asking_fee', $this->bigInteger()->null()->after('fee'));
        $this->createIndex('idx-transfer-transfer_type', '{{%transfer}}', 'transfer_type');

        $this->createTable('{{%transfer_offer}}', [
            'id' => $this->primaryKey(),
            'transfer_id' => $this->integer()->notNull(),
            'from_team_id' => $this->integer()->notNull(),
            'to_team_id' => $this->integer()->notNull(),
            'player_id' => $this->integer()->notNull(),
            'offered_fee' => $this->bigInteger()->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'created_at' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx-transfer_offer-transfer', '{{%transfer_offer}}', 'transfer_id');
        $this->createIndex('idx-transfer_offer-to_team', '{{%transfer_offer}}', 'to_team_id');
        $this->createIndex('idx-transfer_offer-status', '{{%transfer_offer}}', 'status');
        $this->addForeignKey('fk-transfer_offer-transfer_id', '{{%transfer_offer}}', 'transfer_id', '{{%transfer}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-transfer_offer-from_team_id', '{{%transfer_offer}}', 'from_team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-transfer_offer-to_team_id', '{{%transfer_offer}}', 'to_team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-transfer_offer-player_id', '{{%transfer_offer}}', 'player_id', '{{%player}}', 'id', 'CASCADE', 'CASCADE');

        $this->createTable('{{%player_pool}}', [
            'id' => $this->primaryKey(),
            'player_id' => $this->integer()->notNull(),
            'asking_fee' => $this->bigInteger()->notNull()->defaultValue(0),
            'salary_ask' => $this->bigInteger()->notNull()->defaultValue(0),
            'available_since' => $this->integer()->notNull(),
            'expires_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('uidx-player_pool-player_id', '{{%player_pool}}', 'player_id', true);
        $this->createIndex('idx-player_pool-expires_at', '{{%player_pool}}', 'expires_at');
        $this->addForeignKey('fk-player_pool-player_id', '{{%player_pool}}', 'player_id', '{{%player}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-player_pool-player_id', '{{%player_pool}}');
        $this->dropTable('{{%player_pool}}');

        $this->dropForeignKey('fk-transfer_offer-player_id', '{{%transfer_offer}}');
        $this->dropForeignKey('fk-transfer_offer-to_team_id', '{{%transfer_offer}}');
        $this->dropForeignKey('fk-transfer_offer-from_team_id', '{{%transfer_offer}}');
        $this->dropForeignKey('fk-transfer_offer-transfer_id', '{{%transfer_offer}}');
        $this->dropTable('{{%transfer_offer}}');

        $this->dropIndex('idx-transfer-transfer_type', '{{%transfer}}');
        $this->dropColumn('{{%transfer}}', 'asking_fee');
        $this->dropColumn('{{%transfer}}', 'loan_return_season');
        $this->dropColumn('{{%transfer}}', 'transfer_type');
    }
}

