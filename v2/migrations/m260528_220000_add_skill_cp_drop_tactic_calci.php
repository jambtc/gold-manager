<?php

use yii\db\Migration;

/**
 * SIP-0075: Add skill_cp (set piece skill) to player.
 * Drop calci_piazzati from training_tactic and training_tactic_snapshot
 * (it was a team tactic; it is now an individual player skill).
 */
class m260528_220000_add_skill_cp_drop_tactic_calci extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%player}}',
            'skill_cp',
            $this->tinyInteger()->unsigned()->notNull()->defaultValue(0)->after('skill_tr')
        );

        // Seed existing players with a rough starting value based on technique/shooting
        $this->execute(
            'UPDATE {{%player}} SET skill_cp = GREATEST(5, LEAST(30, ROUND((skill_tc + skill_tr) / 8)))'
        );

        try {
            $this->dropColumn('{{%training_tactic}}', 'calci_piazzati');
        } catch (\Throwable) {
            // column may not exist if migration never ran
        }

        try {
            $this->dropColumn('{{%training_tactic_snapshot}}', 'calci_piazzati');
        } catch (\Throwable) {
            // column may not exist
        }
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%player}}', 'skill_cp');
        try {
            $this->addColumn('{{%training_tactic}}', 'calci_piazzati',
                $this->tinyInteger()->notNull()->defaultValue(0));
        } catch (\Throwable) {}
        try {
            $this->addColumn('{{%training_tactic_snapshot}}', 'calci_piazzati',
                $this->tinyInteger()->notNull()->defaultValue(0));
        } catch (\Throwable) {}
    }
}
