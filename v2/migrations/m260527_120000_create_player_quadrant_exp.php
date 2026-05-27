<?php

declare(strict_types=1);

use yii\db\Migration;

class m260527_120000_create_player_quadrant_exp extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%player_quadrant_exp}}')) {
            $this->dropTable('{{%player_quadrant_exp}}');
        }
        $this->createTable('{{%player_quadrant_exp}}', [
            'id'                => $this->primaryKey()->unsigned(),
            'player_id'         => $this->integer()->notNull(),
            'quadrant'          => "ENUM('gk','def_l','def_c','def_r','mid_l','mid_c','mid_r','att_l','att_c','att_r') NOT NULL",
            'official_credits'  => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'friendly_credits'  => $this->integer()->unsigned()->notNull()->defaultValue(0),
            'updated_at'        => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        ]);

        $this->createIndex('uq_player_quadrant', '{{%player_quadrant_exp}}', ['player_id', 'quadrant'], true);
        $this->addForeignKey(
            'fk_pqe_player',
            '{{%player_quadrant_exp}}',
            'player_id',
            '{{%player}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%player_quadrant_exp}}');
    }
}
