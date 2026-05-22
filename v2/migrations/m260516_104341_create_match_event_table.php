<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%match_event}}`.
 */
class m260516_104341_create_match_event_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%match_event}}', [
            'id' => $this->primaryKey(),
            'fixture_id' => $this->integer()->notNull(),
            'minute' => $this->integer()->notNull(),
            'type' => $this->string(30)->notNull(),
            // Types: midfield_win, midfield_loss, attack_attempt, shot_on_goal,
            //        near_miss, gk_save, goal, half_time, full_time,
            //        substitution, tactic_change
            'team_side' => $this->string(4)->notNull(), // 'home' or 'away'
            'player_id' => $this->integer()->null(),  // the player involved, if any
            'detail' => $this->text()->null(),         // JSON bag for extra details
        ]);

        $this->createIndex('idx-match_event-fixture_id', '{{%match_event}}', 'fixture_id');
        $this->addForeignKey('fk-match_event-fixture_id', '{{%match_event}}', 'fixture_id', '{{%fixture}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-match_event-fixture_id', '{{%match_event}}');
        $this->dropTable('{{%match_event}}');
    }
}
