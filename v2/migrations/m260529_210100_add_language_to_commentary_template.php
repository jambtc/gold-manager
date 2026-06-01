<?php

use yii\db\Migration;

/**
 * SIP-0079: language column on commentary_template.
 * Existing rows keep their default 'it-IT'.
 * New en-US templates can be added via seed or admin.
 */
class m260529_210100_add_language_to_commentary_template extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->getTableSchema('{{%commentary_template}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['language'])) {
            $this->addColumn(
                '{{%commentary_template}}',
                'language',
                $this->string(10)->notNull()->defaultValue('it-IT')->after('enabled')
            );
        }

        $indexes = $this->db->createCommand("SHOW INDEX FROM {{%commentary_template}} WHERE Key_name='idx_ct_lang_type'")->queryAll();
        if (empty($indexes)) {
            $this->createIndex('idx_ct_lang_type', '{{%commentary_template}}', ['language', 'event_type']);
        }
    }

    public function safeDown(): void
    {
        $schema = $this->db->getTableSchema('{{%commentary_template}}', true);
        if ($schema === null) {
            return;
        }

        $indexes = $this->db->createCommand("SHOW INDEX FROM {{%commentary_template}} WHERE Key_name='idx_ct_lang_type'")->queryAll();
        if (!empty($indexes)) {
            $this->dropIndex('idx_ct_lang_type', '{{%commentary_template}}');
        }

        if (isset($schema->columns['language'])) {
            $this->dropColumn('{{%commentary_template}}', 'language');
        }
    }
}
