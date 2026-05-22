<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%fixture}}`.
 */
class m260516_103510_create_fixture_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%fixture}}', [
            'id' => $this->primaryKey(),
            'competition_id' => $this->integer()->notNull(),
            'home_team_id' => $this->integer()->notNull(),
            'away_team_id' => $this->integer()->notNull(),
            'match_date' => $this->integer()->notNull(),
            'home_score' => $this->integer()->null(),
            'away_score' => $this->integer()->null(),
            'status' => $this->integer()->notNull()->defaultValue(0), // 0: scheduled, 1: playing, 2: finished
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-fixture-competition_id', '{{%fixture}}', 'competition_id');
        $this->createIndex('idx-fixture-home_team_id', '{{%fixture}}', 'home_team_id');
        $this->createIndex('idx-fixture-away_team_id', '{{%fixture}}', 'away_team_id');

        $this->addForeignKey('fk-fixture-competition_id', '{{%fixture}}', 'competition_id', '{{%competition}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-fixture-home_team_id', '{{%fixture}}', 'home_team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-fixture-away_team_id', '{{%fixture}}', 'away_team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-fixture-competition_id', '{{%fixture}}');
        $this->dropForeignKey('fk-fixture-home_team_id', '{{%fixture}}');
        $this->dropForeignKey('fk-fixture-away_team_id', '{{%fixture}}');
        $this->dropTable('{{%fixture}}');
    }
}
