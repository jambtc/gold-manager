<?php

use yii\db\Migration;

/**
 * SIP-0018 slice-2: request traceability on critical state-change tables.
 */
class m260527_210000_add_sync_audit_fields extends Migration
{
    public function safeUp()
    {
        $tables = [
            '{{%transfer_offer}}',
            '{{%friendly_challenge}}',
            '{{%match_command}}',
        ];

        foreach ($tables as $table) {
            $this->addColumn($table, 'request_id', $this->string(96)->null());
            $this->addColumn($table, 'request_source', $this->string(24)->null());
            $this->createIndex('idx-' . trim($table, '{}%') . '-request-id', $table, 'request_id');
            $this->createIndex('idx-' . trim($table, '{}%') . '-request-source', $table, 'request_source');
        }
    }

    public function safeDown()
    {
        $tables = [
            '{{%transfer_offer}}',
            '{{%friendly_challenge}}',
            '{{%match_command}}',
        ];

        foreach ($tables as $table) {
            $this->dropIndex('idx-' . trim($table, '{}%') . '-request-source', $table);
            $this->dropIndex('idx-' . trim($table, '{}%') . '-request-id', $table);
            $this->dropColumn($table, 'request_source');
            $this->dropColumn($table, 'request_id');
        }
    }
}
