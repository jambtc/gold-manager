<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%transfer}}`.
 */
class m260516_105906_create_transfer_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%transfer}}', [
            'id'               => $this->primaryKey(),
            'player_id'        => $this->integer()->notNull(),
            'from_team_id'     => $this->integer()->null(),   // null = free agent / generated
            'to_team_id'       => $this->integer()->null(),   // null = offer not yet accepted
            'offered_by_team'  => $this->integer()->null(),   // team that placed the bid
            'fee'              => $this->bigInteger()->notNull()->defaultValue(0),
            'proposed_salary'  => $this->bigInteger()->notNull()->defaultValue(0),
            'status'           => $this->string(20)->notNull()->defaultValue('listed'),
            // status: listed | bid_made | accepted | rejected | completed | cancelled
            'listed_at'        => $this->integer()->notNull(),
            'resolved_at'      => $this->integer()->null(),
            'created_at'       => $this->integer()->notNull(),
            'updated_at'       => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-transfer-player_id',    '{{%transfer}}', 'player_id');
        $this->createIndex('idx-transfer-from_team_id', '{{%transfer}}', 'from_team_id');
        $this->createIndex('idx-transfer-status',       '{{%transfer}}', 'status');

        $this->addForeignKey('fk-transfer-player_id',    '{{%transfer}}', 'player_id',    '{{%player}}', 'id', 'CASCADE',  'CASCADE');
        $this->addForeignKey('fk-transfer-from_team_id', '{{%transfer}}', 'from_team_id', '{{%team}}',   'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-transfer-to_team_id',   '{{%transfer}}', 'to_team_id',   '{{%team}}',   'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-transfer-to_team_id',   '{{%transfer}}');
        $this->dropForeignKey('fk-transfer-from_team_id', '{{%transfer}}');
        $this->dropForeignKey('fk-transfer-player_id',    '{{%transfer}}');
        $this->dropTable('{{%transfer}}');
    }
}
