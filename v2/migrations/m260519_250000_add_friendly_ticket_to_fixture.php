<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_250000_add_friendly_ticket_to_fixture extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%fixture}}', 'friendly_ticket_price', $this->integer()->null()->defaultValue(null)->after('revenue_credited'));
        $this->addColumn('{{%fixture}}', 'friendly_spectators',   $this->integer()->null()->defaultValue(null)->after('friendly_ticket_price'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%fixture}}', 'friendly_ticket_price');
        $this->dropColumn('{{%fixture}}', 'friendly_spectators');
    }
}
