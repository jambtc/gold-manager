<?php

use yii\db\Migration;

class m260529_100000_add_notification_delivery extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%notification_delivery}}', [
            'id'             => $this->bigPrimaryKey()->unsigned(),
            'user_id'        => $this->integer()->unsigned()->notNull(),
            'news_item_id'   => $this->integer()->unsigned()->null(),
            'channel'        => $this->string(20)->notNull()->defaultValue('telegram'),
            'status'         => $this->string(20)->notNull()->defaultValue('pending'),
            'telegram_text'  => $this->text()->null(),
            'attempts'       => $this->tinyInteger()->unsigned()->notNull()->defaultValue(0),
            'last_error'     => $this->text()->null(),
            'sent_at'        => $this->integer()->unsigned()->null(),
            'created_at'     => $this->integer()->unsigned()->notNull(),
            'updated_at'     => $this->integer()->unsigned()->notNull(),
        ]);

        $this->createIndex('idx_nd_status_channel', '{{%notification_delivery}}', ['status', 'channel']);
        $this->createIndex('idx_nd_news_item',      '{{%notification_delivery}}', 'news_item_id');
        $this->createIndex('idx_nd_user',            '{{%notification_delivery}}', 'user_id');
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%notification_delivery}}');
    }
}
