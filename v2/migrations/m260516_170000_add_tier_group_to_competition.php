<?php

use yii\db\Migration;

class m260516_170000_add_tier_group_to_competition extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%competition}}', 'tier', $this->tinyInteger()->notNull()->defaultValue(3)->after('type'));
        $this->addColumn('{{%competition}}', 'group_number', $this->integer()->notNull()->defaultValue(1)->after('tier'));
        $this->createIndex('idx_competition_tier_group', '{{%competition}}', ['tier', 'group_number']);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_competition_tier_group', '{{%competition}}');
        $this->dropColumn('{{%competition}}', 'group_number');
        $this->dropColumn('{{%competition}}', 'tier');
    }
}
