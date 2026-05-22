<?php

use yii\db\Migration;

/**
 * SIP-0044: add captain/penalty/freekick role references on formation.
 */
class m260519_220500_add_special_roles_to_formation extends Migration
{
    public function safeUp()
    {
        $this->addColumn('{{%formation}}', 'captain_player_id', $this->integer()->null()->after('effort'));
        $this->addColumn('{{%formation}}', 'penalty_player_id', $this->integer()->null()->after('captain_player_id'));
        $this->addColumn('{{%formation}}', 'freekick_player_id', $this->integer()->null()->after('penalty_player_id'));

        $this->createIndex('idx-formation-captain_player_id', '{{%formation}}', 'captain_player_id');
        $this->createIndex('idx-formation-penalty_player_id', '{{%formation}}', 'penalty_player_id');
        $this->createIndex('idx-formation-freekick_player_id', '{{%formation}}', 'freekick_player_id');

        $this->addForeignKey(
            'fk-formation-captain_player_id',
            '{{%formation}}',
            'captain_player_id',
            '{{%player}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-formation-penalty_player_id',
            '{{%formation}}',
            'penalty_player_id',
            '{{%player}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-formation-freekick_player_id',
            '{{%formation}}',
            'freekick_player_id',
            '{{%player}}',
            'id',
            'SET NULL',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk-formation-freekick_player_id', '{{%formation}}');
        $this->dropForeignKey('fk-formation-penalty_player_id', '{{%formation}}');
        $this->dropForeignKey('fk-formation-captain_player_id', '{{%formation}}');

        $this->dropIndex('idx-formation-freekick_player_id', '{{%formation}}');
        $this->dropIndex('idx-formation-penalty_player_id', '{{%formation}}');
        $this->dropIndex('idx-formation-captain_player_id', '{{%formation}}');

        $this->dropColumn('{{%formation}}', 'freekick_player_id');
        $this->dropColumn('{{%formation}}', 'penalty_player_id');
        $this->dropColumn('{{%formation}}', 'captain_player_id');
    }
}

