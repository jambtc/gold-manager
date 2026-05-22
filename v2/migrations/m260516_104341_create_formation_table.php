<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%formation}}`.
 */
class m260516_104341_create_formation_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%formation}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'name' => $this->string(50)->notNull()->defaultValue('Formazione 1'),
            'is_active' => $this->boolean()->defaultValue(false),
            'tactic' => $this->string(50)->null(),
            'marking' => $this->string(50)->null(),
            'effort' => $this->integer()->notNull()->defaultValue(3), // 1=50%, 2=75%, 3=100%, 4=125%, 5=150%
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        // formation_slot: one row per zone per formation
        $this->createTable('{{%formation_slot}}', [
            'id' => $this->primaryKey(),
            'formation_id' => $this->integer()->notNull(),
            'zone' => $this->integer()->notNull(), // 1-64 pitch zones, 65-70 bench
            'player_id' => $this->integer()->null(),
        ]);

        $this->createIndex('idx-formation-team_id', '{{%formation}}', 'team_id');
        $this->createIndex('idx-formation_slot-formation_id', '{{%formation_slot}}', 'formation_id');
        $this->createIndex('idx-formation_slot-player_id', '{{%formation_slot}}', 'player_id');

        $this->addForeignKey('fk-formation-team_id', '{{%formation}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-formation_slot-formation_id', '{{%formation_slot}}', 'formation_id', '{{%formation}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-formation_slot-player_id', '{{%formation_slot}}', 'player_id', '{{%player}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-formation_slot-player_id', '{{%formation_slot}}');
        $this->dropForeignKey('fk-formation_slot-formation_id', '{{%formation_slot}}');
        $this->dropForeignKey('fk-formation-team_id', '{{%formation}}');
        $this->dropTable('{{%formation_slot}}');
        $this->dropTable('{{%formation}}');
    }
}
