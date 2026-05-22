<?php

declare(strict_types=1);

use yii\db\Migration;

class m260519_240000_create_news_item extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%news_item}}', [
            'id'         => $this->primaryKey(),
            'user_id'    => $this->integer()->notNull(),
            'category'   => $this->string(20)->notNull(),
            'icon'       => $this->string(10)->notNull(),
            'title'      => $this->string(120)->notNull(),
            'body'       => $this->text()->null(),
            'link_url'   => $this->string(255)->null(),
            'is_read'    => $this->tinyInteger(1)->notNull()->defaultValue(0),
            'priority'   => $this->tinyInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-news_item-user', '{{%news_item}}', ['user_id', 'is_read', 'created_at']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%news_item}}');
    }
}
