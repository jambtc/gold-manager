<?php

use yii\db\Migration;

class m260528_200000_add_nationality_to_player extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%player}}', 'nationality', $this->string(3)->notNull()->defaultValue('ITA')->after('position'));
        $this->createIndex('idx_player_nationality', '{{%player}}', 'nationality');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_player_nationality', '{{%player}}');
        $this->dropColumn('{{%player}}', 'nationality');
    }
}
