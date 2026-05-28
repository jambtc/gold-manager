<?php

use yii\db\Migration;

/**
 * SIP-0069: add language preference to user.
 */
class m260528_230000_add_language_to_user extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%user}}',
            'language',
            $this->string(10)->notNull()->defaultValue('it-IT')->after('status')
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'language');
    }
}
