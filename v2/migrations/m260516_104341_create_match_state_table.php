<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%match_state}}`.
 */
class m260516_104341_create_match_state_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%match_state}}', [
            'id' => $this->primaryKey(),
            'fixture_id' => $this->integer()->notNull()->unique(),
            'current_minute' => $this->integer()->notNull()->defaultValue(0),
            'phase' => $this->string(20)->notNull()->defaultValue('not_started'),
            // Phases: not_started, first_half, half_time, second_half, finished
            'home_score' => $this->integer()->notNull()->defaultValue(0),
            'away_score' => $this->integer()->notNull()->defaultValue(0),
            'home_formation_id' => $this->integer()->null(),
            'away_formation_id' => $this->integer()->null(),
            'pending_home_actions' => $this->text()->null(), // JSON: tactical changes / subs queued
            'pending_away_actions' => $this->text()->null(),
            'home_subs_used' => $this->integer()->notNull()->defaultValue(0),
            'away_subs_used' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->addForeignKey('fk-match_state-fixture_id', '{{%match_state}}', 'fixture_id', '{{%fixture}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-match_state-home_formation_id', '{{%match_state}}', 'home_formation_id', '{{%formation}}', 'id', 'SET NULL', 'CASCADE');
        $this->addForeignKey('fk-match_state-away_formation_id', '{{%match_state}}', 'away_formation_id', '{{%formation}}', 'id', 'SET NULL', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-match_state-home_formation_id', '{{%match_state}}');
        $this->dropForeignKey('fk-match_state-away_formation_id', '{{%match_state}}');
        $this->dropForeignKey('fk-match_state-fixture_id', '{{%match_state}}');
        $this->dropTable('{{%match_state}}');
    }
}
