<?php

declare(strict_types=1);

use yii\db\Migration;

class m260527_120100_create_player_cell_exp extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%player_cell_exp}}')) {
            $this->dropTable('{{%player_cell_exp}}');
        }
        $this->createTable('{{%player_cell_exp}}', [
            'id'                => $this->primaryKey()->unsigned(),
            'player_id'         => $this->integer()->notNull(),
            'zone'              => $this->tinyInteger()->unsigned()->notNull()->comment('1..64 (64=GK display zone)'),
            'official_credits'  => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'friendly_credits'  => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'updated_at'        => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->createIndex('uq_player_zone', '{{%player_cell_exp}}', ['player_id', 'zone'], true);
        $this->createIndex('idx_pce_zone', '{{%player_cell_exp}}', 'zone');
        $this->addForeignKey(
            'fk_pce_player',
            '{{%player_cell_exp}}',
            'player_id',
            '{{%player}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%player_cell_exp}}');
    }
}
