<?php

use yii\db\Migration;

/**
 * Tracks goal scorer and assist per fixture per player.
 * Populated by EconomyController or MatchEngine at full_time.
 */
class m260518_090000_create_player_stat extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%player_stat}}', [
            'id'          => $this->primaryKey(),
            'fixture_id'  => $this->integer()->notNull(),
            'player_id'   => $this->integer()->notNull(),
            'team_id'     => $this->integer()->notNull(),
            'goals'       => $this->tinyInteger()->notNull()->defaultValue(0),
            'assists'     => $this->tinyInteger()->notNull()->defaultValue(0),
            'yellow_cards'=> $this->tinyInteger()->notNull()->defaultValue(0),
            'red_cards'   => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at'  => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx_player_stat_fixture', '{{%player_stat}}', ['fixture_id', 'player_id'], true);
        $this->createIndex('idx_player_stat_player',  '{{%player_stat}}', 'player_id');
        $this->addForeignKey('fk_player_stat_fixture', '{{%player_stat}}', 'fixture_id', '{{%fixture}}', 'id', 'CASCADE');
        $this->addForeignKey('fk_player_stat_player',  '{{%player_stat}}', 'player_id',  '{{%player}}',  'id', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_player_stat_player',  '{{%player_stat}}');
        $this->dropForeignKey('fk_player_stat_fixture', '{{%player_stat}}');
        $this->dropTable('{{%player_stat}}');
    }
}
