<?php

use yii\db\Migration;

class m260527_233000_create_scouting_need_alert_tables extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%scouting_need}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'position' => $this->string(3)->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('uq-scouting_need-team-pos', '{{%scouting_need}}', ['team_id', 'position'], true);
        $this->createIndex('idx-scouting_need-team', '{{%scouting_need}}', 'team_id');
        $this->addForeignKey(
            'fk-scouting_need-team',
            '{{%scouting_need}}',
            'team_id',
            '{{%team}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $this->createTable('{{%scouting_alert}}', [
            'id' => $this->primaryKey(),
            'team_id' => $this->integer()->notNull(),
            'player_id' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('uq-scouting_alert-team-player', '{{%scouting_alert}}', ['team_id', 'player_id'], true);
        $this->createIndex('idx-scouting_alert-team', '{{%scouting_alert}}', 'team_id');
        $this->createIndex('idx-scouting_alert-player', '{{%scouting_alert}}', 'player_id');
        $this->addForeignKey(
            'fk-scouting_alert-team',
            '{{%scouting_alert}}',
            'team_id',
            '{{%team}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-scouting_alert-player',
            '{{%scouting_alert}}',
            'player_id',
            '{{%player}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-scouting_alert-player', '{{%scouting_alert}}');
        $this->dropForeignKey('fk-scouting_alert-team', '{{%scouting_alert}}');
        $this->dropTable('{{%scouting_alert}}');

        $this->dropForeignKey('fk-scouting_need-team', '{{%scouting_need}}');
        $this->dropTable('{{%scouting_need}}');
    }
}
