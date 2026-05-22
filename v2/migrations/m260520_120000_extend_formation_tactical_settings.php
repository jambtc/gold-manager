<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_120000_extend_formation_tactical_settings extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->schema->getTableSchema('{{%formation}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['offside_trap'])) {
            $this->addColumn('{{%formation}}', 'offside_trap', $this->tinyInteger(1)->notNull()->defaultValue(1)->after('marking'));
        }
        if (!isset($schema->columns['trained_tactic'])) {
            $this->addColumn('{{%formation}}', 'trained_tactic', $this->string(50)->null()->after('offside_trap'));
        }

        $this->update('{{%formation}}', ['marking' => 'zone'], ['marking' => null]);
        $this->update('{{%formation}}', ['tactic' => 'balanced'], ['tactic' => null]);
    }

    public function safeDown(): void
    {
        $schema = $this->db->schema->getTableSchema('{{%formation}}', true);
        if ($schema === null) {
            return;
        }
        if (isset($schema->columns['trained_tactic'])) {
            $this->dropColumn('{{%formation}}', 'trained_tactic');
        }
        if (isset($schema->columns['offside_trap'])) {
            $this->dropColumn('{{%formation}}', 'offside_trap');
        }
    }
}

