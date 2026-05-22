<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_220000_add_revenue_credited_to_fixture extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%fixture}}', 'revenue_credited',
            $this->tinyInteger(1)->notNull()->defaultValue(0)->after('status'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%fixture}}', 'revenue_credited');
    }
}
