<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Players cannot be re-listed for 48h after being pulled from market without a sale.
 */
class m260527_170000_create_transfer_cooldown extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%transfer_cooldown}}', [
            'player_id'  => $this->integer()->notNull(),
            'team_id'    => $this->integer()->notNull(),
            'unlocks_at' => $this->dateTime()->notNull(),
            'PRIMARY KEY ([[player_id]])',
        ]);
        $this->addForeignKey('fk_tc_player', '{{%transfer_cooldown}}', 'player_id', '{{%player}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_tc_team',   '{{%transfer_cooldown}}', 'team_id',   '{{%team}}',   'id', 'CASCADE', 'CASCADE');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%transfer_cooldown}}');
    }
}
