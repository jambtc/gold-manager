<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%standing}}`.
 */
class m260516_103818_create_standing_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%standing}}', [
            'id' => $this->primaryKey(),
            'competition_id' => $this->integer()->notNull(),
            'team_id' => $this->integer()->notNull(),
            'points' => $this->integer()->notNull()->defaultValue(0),
            'played' => $this->integer()->notNull()->defaultValue(0),
            'won' => $this->integer()->notNull()->defaultValue(0),
            'drawn' => $this->integer()->notNull()->defaultValue(0),
            'lost' => $this->integer()->notNull()->defaultValue(0),
            'goals_for' => $this->integer()->notNull()->defaultValue(0),
            'goals_against' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-standing-competition_id', '{{%standing}}', 'competition_id');
        $this->createIndex('idx-standing-team_id', '{{%standing}}', 'team_id');

        $this->addForeignKey('fk-standing-competition_id', '{{%standing}}', 'competition_id', '{{%competition}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-standing-team_id', '{{%standing}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey('fk-standing-competition_id', '{{%standing}}');
        $this->dropForeignKey('fk-standing-team_id', '{{%standing}}');
        $this->dropTable('{{%standing}}');
    }
}
