<?php

declare(strict_types=1);

use yii\db\Migration;

/**
 * SIP-0077 slice-1: orchestrator core tables (schedule/event/run/outbox).
 */
class m260601_120000_create_orchestrator_core_tables extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%orchestrator_schedule}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'job_name' => $this->string(80)->notNull(),
            'enabled' => $this->boolean()->notNull()->defaultValue(1),
            'cron_expr' => $this->string(80)->null(),
            'interval_seconds' => $this->integer()->notNull()->defaultValue(300),
            'timezone' => $this->string(40)->notNull()->defaultValue('Europe/Rome'),
            'next_run_at' => $this->integer()->notNull(),
            'payload_json' => $this->text()->null(),
            'last_run_at' => $this->integer()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);
        $this->createIndex('uq_orch_schedule_job_name', '{{%orchestrator_schedule}}', 'job_name', true);
        $this->createIndex('idx_orch_schedule_due', '{{%orchestrator_schedule}}', ['enabled', 'next_run_at']);

        $this->createTable('{{%orchestrator_event}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'event_type' => $this->string(80)->notNull(),
            'aggregate_type' => $this->string(80)->null(),
            'aggregate_id' => $this->bigInteger()->null(),
            'payload_json' => $this->text()->null(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'available_at' => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
            'processed_at' => $this->integer()->null(),
            'last_error' => $this->string(255)->null(),
        ]);
        $this->createIndex('idx_orch_event_status_due', '{{%orchestrator_event}}', ['status', 'available_at', 'id']);
        $this->createIndex('idx_orch_event_type', '{{%orchestrator_event}}', ['event_type', 'status']);

        $this->createTable('{{%orchestrator_run}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'source_type' => $this->string(20)->notNull(),
            'source_id' => $this->bigInteger()->null(),
            'job_name' => $this->string(80)->notNull(),
            'idempotency_key' => $this->string(140)->notNull(),
            'status' => $this->string(20)->notNull()->defaultValue('running'),
            'started_at' => $this->integer()->notNull(),
            'finished_at' => $this->integer()->null(),
            'error_text' => $this->string(255)->null(),
            'metrics_json' => $this->text()->null(),
        ]);
        $this->createIndex('uq_orch_run_idempotency', '{{%orchestrator_run}}', 'idempotency_key', true);
        $this->createIndex('idx_orch_run_status', '{{%orchestrator_run}}', ['status', 'started_at']);

        $this->createTable('{{%orchestrator_outbox}}', [
            'id' => $this->bigPrimaryKey()->unsigned(),
            'channel' => $this->string(20)->notNull(),
            'topic' => $this->string(80)->null(),
            'payload_json' => $this->text()->null(),
            'status' => $this->string(20)->notNull()->defaultValue('pending'),
            'attempts' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'sent_at' => $this->integer()->null(),
            'last_error' => $this->string(255)->null(),
        ]);
        $this->createIndex('idx_orch_outbox_status', '{{%orchestrator_outbox}}', ['status', 'channel', 'id']);

        $now = time();
        $this->insert('{{%orchestrator_schedule}}', [
            'job_name' => 'market.resolve_auctions',
            'enabled' => 1,
            'cron_expr' => '*/5 * * * *',
            'interval_seconds' => 300,
            'timezone' => 'Europe/Rome',
            'next_run_at' => $now + 30,
            'payload_json' => null,
            'last_run_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%orchestrator_outbox}}');
        $this->dropTable('{{%orchestrator_run}}');
        $this->dropTable('{{%orchestrator_event}}');
        $this->dropTable('{{%orchestrator_schedule}}');
    }
}

