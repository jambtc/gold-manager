<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_040000_create_man_marking extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%man_marking}}', [
            'id'         => $this->primaryKey(),
            'fixture_id' => $this->integer()->notNull(),
            'team_id'    => $this->integer()->notNull(),
            'marker_id'  => $this->integer()->notNull(),
            'marked_id'  => $this->integer()->notNull(),
            'created_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('uq-marking-marker', '{{%man_marking}}',
            ['fixture_id', 'team_id', 'marker_id'], true);
        $this->createIndex('uq-marking-marked', '{{%man_marking}}',
            ['fixture_id', 'team_id', 'marked_id'], true);
        $this->createIndex('idx-marking-fixture-team', '{{%man_marking}}',
            ['fixture_id', 'team_id']);
    }

    public function safeDown(): void
    {
        $this->dropTable('{{%man_marking}}');
    }
}
