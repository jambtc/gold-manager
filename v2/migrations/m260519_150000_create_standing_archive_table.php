<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Creates table `{{%standing_archive}}` for SIP-0027 season rollover snapshots.
 */
class m260519_150000_create_standing_archive_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%standing_archive}}', [
            'id' => $this->primaryKey(),
            'standing_id' => $this->integer()->notNull(),
            'competition_id' => $this->integer()->notNull(),
            'team_id' => $this->integer()->notNull(),
            'season' => $this->integer()->notNull(),
            'final_position' => $this->integer()->notNull(),
            'points' => $this->integer()->notNull()->defaultValue(0),
            'played' => $this->integer()->notNull()->defaultValue(0),
            'won' => $this->integer()->notNull()->defaultValue(0),
            'drawn' => $this->integer()->notNull()->defaultValue(0),
            'lost' => $this->integer()->notNull()->defaultValue(0),
            'goals_for' => $this->integer()->notNull()->defaultValue(0),
            'goals_against' => $this->integer()->notNull()->defaultValue(0),
            'archived_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-standing_archive-competition-season', '{{%standing_archive}}', ['competition_id', 'season']);
        $this->createIndex('idx-standing_archive-team-season', '{{%standing_archive}}', ['team_id', 'season']);
        $this->createIndex('idx-standing_archive-standing_id', '{{%standing_archive}}', 'standing_id');
        $this->createIndex('uidx-standing_archive-competition-team-season', '{{%standing_archive}}', ['competition_id', 'team_id', 'season'], true);

        $this->addForeignKey('fk-standing_archive-competition_id', '{{%standing_archive}}', 'competition_id', '{{%competition}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-standing_archive-team_id', '{{%standing_archive}}', 'team_id', '{{%team}}', 'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-standing_archive-team_id', '{{%standing_archive}}');
        $this->dropForeignKey('fk-standing_archive-competition_id', '{{%standing_archive}}');
        $this->dropTable('{{%standing_archive}}');
    }
}

