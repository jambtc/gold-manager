<?php

use yii\db\Migration;

class m260529_100100_add_pre_match_notified_to_fixture extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%fixture}}',
            'pre_match_notified',
            $this->tinyInteger(1)->unsigned()->notNull()->defaultValue(0)
        );
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%fixture}}', 'pre_match_notified');
    }
}
