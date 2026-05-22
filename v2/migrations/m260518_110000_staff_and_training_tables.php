<?php

use yii\db\Migration;

/**
 * SIP-0035: staff.efficiency + staff.specialisation
 * SIP-0036: training_skill, training_tactic tables
 * SIP-0039: training_log table
 */
class m260518_110000_staff_and_training_tables extends Migration
{
    public function safeUp(): void
    {
        // ── SIP-0035: staff enhancements ──────────────────────────────────
        $this->addColumn('{{%staff}}', 'efficiency',
            $this->tinyInteger()->notNull()->defaultValue(50)->after('salary'));
        $this->addColumn('{{%staff}}', 'specialisation',
            $this->string(20)->notNull()->defaultValue('equilibrato')->after('efficiency'));

        // ── SIP-0036: weekly skill training allocation per team ──────────
        $this->createTable('{{%training_skill}}', [
            'id'          => $this->primaryKey(),
            'team_id'     => $this->integer()->notNull(),
            'season'      => $this->integer()->notNull()->defaultValue(1),
            'alloc_forma' => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_cond'  => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_po'    => $this->tinyInteger()->notNull()->defaultValue(5),
            'alloc_df'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_cn'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_pa'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_rg'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_cr'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_tc'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'alloc_tr'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'updated_at'  => $this->integer()->notNull(),
        ]);
        $this->createIndex('uidx_training_skill_team', '{{%training_skill}}', ['team_id', 'season'], true);
        $this->addForeignKey('fk_training_skill_team', '{{%training_skill}}', 'team_id', '{{%team}}', 'id', 'CASCADE');

        // ── SIP-0038: tactical training levels per team ──────────────────
        $this->createTable('{{%training_tactic}}', [
            'id'              => $this->primaryKey(),
            'team_id'         => $this->integer()->notNull(),
            'season'          => $this->integer()->notNull()->defaultValue(1),
            'pressing'        => $this->tinyInteger()->notNull()->defaultValue(30),
            'contropiede'     => $this->tinyInteger()->notNull()->defaultValue(20),
            'possesso'        => $this->tinyInteger()->notNull()->defaultValue(40),
            'palla_bassa'     => $this->tinyInteger()->notNull()->defaultValue(30),
            'lancio_lungo'    => $this->tinyInteger()->notNull()->defaultValue(20),
            'catenaccio'      => $this->tinyInteger()->notNull()->defaultValue(20),
            'fuorigioco'      => $this->tinyInteger()->notNull()->defaultValue(10),
            'calci_piazzati'  => $this->tinyInteger()->notNull()->defaultValue(30),
            'updated_at'      => $this->integer()->notNull(),
        ]);
        $this->createIndex('uidx_training_tactic_team', '{{%training_tactic}}', ['team_id', 'season'], true);
        $this->addForeignKey('fk_training_tactic_team', '{{%training_tactic}}', 'team_id', '{{%team}}', 'id', 'CASCADE');

        // ── SIP-0039: weekly training log ────────────────────────────────
        $this->createTable('{{%training_log}}', [
            'id'         => $this->primaryKey(),
            'team_id'    => $this->integer()->notNull(),
            'player_id'  => $this->integer()->notNull(),
            'week'       => $this->tinyInteger()->notNull()->defaultValue(1),
            'season'     => $this->integer()->notNull()->defaultValue(1),
            'stat'       => $this->string(20)->notNull(),
            'xp_gained'  => $this->float()->notNull()->defaultValue(0),
            'new_value'  => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('idx_training_log_player', '{{%training_log}}', ['player_id', 'season']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%training_log}}');
        $this->dropForeignKey('fk_training_tactic_team', '{{%training_tactic}}');
        $this->dropTable('{{%training_tactic}}');
        $this->dropForeignKey('fk_training_skill_team', '{{%training_skill}}');
        $this->dropTable('{{%training_skill}}');
        $this->dropColumn('{{%staff}}', 'specialisation');
        $this->dropColumn('{{%staff}}', 'efficiency');
    }
}
