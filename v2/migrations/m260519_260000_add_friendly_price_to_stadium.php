<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_260000_add_friendly_price_to_stadium extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%stadium}}', 'friendly_ticket_price',
            $this->integer()->notNull()->defaultValue(10)->after('ticket_price'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%stadium}}', 'friendly_ticket_price');
    }
}
