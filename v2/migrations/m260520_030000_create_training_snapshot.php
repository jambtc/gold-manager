<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_030000_create_training_snapshot extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%training_snapshot}}', [
            'id'            => $this->primaryKey(),
            'team_id'       => $this->integer()->notNull(),
            'player_id'     => $this->integer()->notNull(),
            'snapshot_date' => $this->date()->notNull(),
            'general_skill' => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_po'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_df'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_cn'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_pa'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_rg'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_cr'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_tc'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'skill_tr'      => $this->tinyInteger()->notNull()->defaultValue(0),
            'form'          => $this->tinyInteger()->notNull()->defaultValue(0),
            'condition_val' => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at'    => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq-snapshot-player-date', '{{%training_snapshot}}',
            ['player_id', 'snapshot_date'], true);
        $this->createIndex('idx-snapshot-team-date', '{{%training_snapshot}}',
            ['team_id', 'snapshot_date']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%training_snapshot}}');
    }
}
