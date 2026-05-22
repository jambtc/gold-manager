<?php

use yii\db\Migration;

class m260516_180000_add_role_to_user extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'role', $this->string(20)->notNull()->defaultValue('manager')->after('username'));
        $this->update('{{%user}}', ['role' => 'admin'], ['username' => 'admin']);
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'role');
    }
}
