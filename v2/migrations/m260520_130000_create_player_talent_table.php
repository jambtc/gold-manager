<?php

declare(strict_types=1);

use yii\db\Migration;

class m260520_130000_create_player_talent_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%player_talent}}', [
            'id' => $this->primaryKey(),
            'player_id' => $this->integer()->notNull(),
            'code' => $this->string(32)->notNull(),
            'level' => $this->tinyInteger()->notNull()->defaultValue(1),
            'progress_weeks' => $this->smallInteger()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull()->defaultValue(0),
            'updated_at' => $this->integer()->notNull()->defaultValue(0),
        ]);

        $this->createIndex('idx_player_talent_player', '{{%player_talent}}', ['player_id']);
        $this->createIndex('uidx_player_talent_player_code', '{{%player_talent}}', ['player_id', 'code'], true);
        $this->addForeignKey(
            'fk_player_talent_player',
            '{{%player_talent}}',
            'player_id',
            '{{%player}}',
            'id',
            'CASCADE',
            'CASCADE'
        );

        $codes = [
            'creativita', 'resistenza', 'dribbling', 'velocita',
            'visione', 'leadership', 'marcatura', 'riflessi',
            'finalizzazione', 'disciplina', 'tenacia', 'freddezza',
        ];

        $players = $this->db->createCommand('SELECT id FROM {{%player}}')->queryColumn();
        $now = time();
        foreach ($players as $pid) {
            $roll = random_int(1, 100);
            $count = $roll <= 35 ? 0 : ($roll <= 82 ? 1 : 2); // 0..2 talenti
            if ($count === 0) {
                continue;
            }

            $shuffled = $codes;
            shuffle($shuffled);
            $picked = array_slice($shuffled, 0, $count);
            foreach ($picked as $code) {
                $this->insert('{{%player_talent}}', [
                    'player_id' => (int) $pid,
                    'code' => $code,
                    'level' => 1,
                    'progress_weeks' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_player_talent_player', '{{%player_talent}}');
        $this->dropIndex('uidx_player_talent_player_code', '{{%player_talent}}');
        $this->dropIndex('idx_player_talent_player', '{{%player_talent}}');
        $this->dropTable('{{%player_talent}}');
    }
}

