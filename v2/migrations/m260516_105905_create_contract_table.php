<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%contract}}`.
 */
class m260516_105905_create_contract_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%contract}}', [
            'id'              => $this->primaryKey(),
            'player_id'       => $this->integer()->notNull(),
            'team_id'         => $this->integer()->notNull(),
            'salary'          => $this->bigInteger()->notNull()->defaultValue(0),  // € per season
            'release_clause'  => $this->bigInteger()->null(),
            'season_start'    => $this->integer()->notNull()->defaultValue(1),
            'season_end'      => $this->integer()->notNull()->defaultValue(3),
            'status'          => $this->string(20)->notNull()->defaultValue('active'),
            // status: active | expired | terminated | transferred
            'created_at'      => $this->integer()->notNull(),
            'updated_at'      => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-contract-player_id', '{{%contract}}', 'player_id');
        $this->createIndex('idx-contract-team_id',   '{{%contract}}', 'team_id');

        $this->addForeignKey('fk-contract-player_id', '{{%contract}}', 'player_id', '{{%player}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-contract-team_id',   '{{%contract}}', 'team_id',   '{{%team}}',   'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-contract-player_id', '{{%contract}}');
        $this->dropForeignKey('fk-contract-team_id',   '{{%contract}}');
        $this->dropTable('{{%contract}}');
    }
}
