<?php

use yii\db\Migration;

/**
 * SIP-0079: language column drives LLM commentary language.
 * Principle: follows the home manager's user.language (default it-IT).
 */
class m260529_210000_add_language_to_fixture extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%fixture}}',
            'language',
            $this->string(10)->notNull()->defaultValue('it-IT')
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%fixture}}', 'language');
    }
}
