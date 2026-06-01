<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * Ensures emoji-safe charset/collation on notification/news tables.
 */
class m260601_095000_harden_utf8mb4_for_notifications extends Migration
{
    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%notification_delivery}}', true) !== null) {
            $this->execute('ALTER TABLE {{%notification_delivery}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }

        if ($this->db->getTableSchema('{{%news_item}}', true) !== null) {
            $this->execute('ALTER TABLE {{%news_item}} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        }
    }

    public function safeDown(): void
    {
        // no-op: downgrade would risk data loss for 4-byte chars
    }
}

