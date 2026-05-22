<?php

use yii\db\Migration;

class m260518_100000_create_name_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%name_first}}', [
            'id'     => $this->primaryKey(),
            'name'   => $this->string(60)->notNull(),
            'gender' => $this->tinyInteger()->notNull()->defaultValue(0),
        ]);
        $this->createTable('{{%name_last}}', [
            'id'   => $this->primaryKey(),
            'name' => $this->string(60)->notNull()->unique(),
        ]);
        $this->createIndex('idx_name_first_gender', '{{%name_first}}', 'gender');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%name_last}}');
        $this->dropTable('{{%name_first}}');
    }
}
