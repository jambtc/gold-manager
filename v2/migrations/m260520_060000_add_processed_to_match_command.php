<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_060000_add_processed_to_match_command extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%match_command}}', 'processed',
            $this->tinyInteger(1)->notNull()->defaultValue(0)->after('minute_submitted'));
        $this->createIndex('idx-match_command-processed', '{{%match_command}}',
            ['fixture_id', 'processed']);
    }

    public function safeDown(): void
    {
        $this->dropIndex('idx-match_command-processed', '{{%match_command}}');
        $this->dropColumn('{{%match_command}}', 'processed');
    }
}
