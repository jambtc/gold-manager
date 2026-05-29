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
        $this->addColumn(
            '{{%commentary_template}}',
            'language',
            $this->string(10)->notNull()->defaultValue('it-IT')->after('enabled')
        );

        $this->createIndex('idx_ct_lang_type', '{{%commentary_template}}', ['language', 'event_type']);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_ct_lang_type', '{{%commentary_template}}');
        $this->dropColumn('{{%commentary_template}}', 'language');
    }
}
