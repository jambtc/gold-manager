<?php

use yii\db\Migration;

class m260528_200300_add_nationality_to_staff extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%staff}}', 'nationality', $this->string(3)->notNull()->defaultValue('ITA')->after('name'));
        $this->addColumn('{{%staff_market}}', 'nationality', $this->string(3)->notNull()->defaultValue('ITA')->after('name'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%staff}}', 'nationality');
        $this->dropColumn('{{%staff_market}}', 'nationality');
    }
}
