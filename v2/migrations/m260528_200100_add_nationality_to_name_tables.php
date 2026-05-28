<?php

use yii\db\Migration;

class m260528_200100_add_nationality_to_name_tables extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%name_first}}', 'nationality', $this->string(3)->notNull()->defaultValue('ITA'));
        $this->addColumn('{{%name_last}}', 'nationality', $this->string(3)->notNull()->defaultValue('ITA'));

        // Drop unique on name_last.name — same surname can exist in multiple nationalities
        try {
            $this->dropIndex('name', '{{%name_last}}');
        } catch (\Throwable $e) {
            // index may have a different name
            try {
                $this->dropIndex('name_last_ibfk_name', '{{%name_last}}');
            } catch (\Throwable) {}
        }

        $this->createIndex('idx_nf_nationality', '{{%name_first}}', 'nationality');
        $this->createIndex('idx_nl_nationality', '{{%name_last}}', 'nationality');
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx_nf_nationality', '{{%name_first}}');
        $this->dropIndex('idx_nl_nationality', '{{%name_last}}');
        $this->dropColumn('{{%name_first}}', 'nationality');
        $this->dropColumn('{{%name_last}}', 'nationality');
    }
}
