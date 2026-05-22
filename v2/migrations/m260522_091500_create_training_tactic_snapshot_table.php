<?php

use yii\db\Migration;

class m260522_091500_create_training_tactic_snapshot_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%training_tactic_snapshot}}', [
            'id'             => $this->primaryKey(),
            'team_id'        => $this->integer()->notNull(),
            'season'         => $this->integer()->notNull()->defaultValue(1),
            'snapshot_date'  => $this->date()->notNull(),
            'pressing'       => $this->tinyInteger()->notNull()->defaultValue(0),
            'contropiede'    => $this->tinyInteger()->notNull()->defaultValue(0),
            'possesso'       => $this->tinyInteger()->notNull()->defaultValue(0),
            'palla_bassa'    => $this->tinyInteger()->notNull()->defaultValue(0),
            'lancio_lungo'   => $this->tinyInteger()->notNull()->defaultValue(0),
            'catenaccio'     => $this->tinyInteger()->notNull()->defaultValue(0),
            'fuorigioco'     => $this->tinyInteger()->notNull()->defaultValue(0),
            'calci_piazzati' => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at'     => $this->integer()->notNull(),
        ]);

        $this->createIndex(
            'uq_training_tactic_snapshot_team_date',
            '{{%training_tactic_snapshot}}',
            ['team_id', 'season', 'snapshot_date'],
            true
        );
        $this->createIndex(
            'idx_training_tactic_snapshot_team',
            '{{%training_tactic_snapshot}}',
            ['team_id', 'snapshot_date']
        );
        $this->addForeignKey(
            'fk_training_tactic_snapshot_team',
            '{{%training_tactic_snapshot}}',
            'team_id',
            '{{%team}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_training_tactic_snapshot_team', '{{%training_tactic_snapshot}}');
        $this->dropTable('{{%training_tactic_snapshot}}');
    }
}

