<?php

use yii\db\Migration;

class m260517_090000_add_status_to_user extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'status', $this->string(20)->notNull()->defaultValue('active')->after('role'));
        // existing users are already active
        $this->update('{{%user}}', ['status' => 'active']);
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'status');
    }
}
