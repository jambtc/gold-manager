<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_010000_add_telegram_to_user extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn('{{%user}}', 'telegram_bot_token', $this->string(100)->null()->after('status'));
        $this->addColumn('{{%user}}', 'telegram_chat_id',   $this->string(50)->null()->after('telegram_bot_token'));
        $this->addColumn('{{%user}}', 'telegram_enabled',   $this->tinyInteger(1)->notNull()->defaultValue(1)->after('telegram_chat_id'));
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%user}}', 'telegram_bot_token');
        $this->dropColumn('{{%user}}', 'telegram_chat_id');
        $this->dropColumn('{{%user}}', 'telegram_enabled');
    }
}
