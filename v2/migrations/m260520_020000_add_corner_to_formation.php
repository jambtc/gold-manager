<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_020000_add_corner_to_formation extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%formation}}', 'corner_player_id',
            $this->integer()->null()->after('freekick_player_id'));
        $this->addForeignKey('fk-formation-corner_player', '{{%formation}}',
            'corner_player_id', '{{%player}}', 'id', 'SET NULL', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk-formation-corner_player', '{{%formation}}');
        $this->dropColumn('{{%formation}}', 'corner_player_id');
    }
}
