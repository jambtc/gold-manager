<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%player_event}}`.
 */
class m260519_160000_create_player_event_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%player_event}}', [
            'id' => $this->primaryKey(),
            'player_id' => $this->integer()->null(),
            'team_id' => $this->integer()->null(),
            'season' => $this->integer()->notNull(),
            'type' => $this->string(40)->notNull(),
            'message' => $this->string(255)->null(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-player_event-season-type', '{{%player_event}}', ['season', 'type']);
        $this->createIndex('idx-player_event-player', '{{%player_event}}', 'player_id');
        $this->createIndex('idx-player_event-team', '{{%player_event}}', 'team_id');

        $this->addForeignKey('fk-player_event-player', '{{%player_event}}', 'player_id', '{{%player}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-player_event-team', '{{%player_event}}', 'team_id', '{{%team}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-player_event-player', '{{%player_event}}');
        $this->dropForeignKey('fk-player_event-team', '{{%player_event}}');
        $this->dropTable('{{%player_event}}');
    }
}
