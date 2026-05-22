<?php

use yii\db\Migration;

class m260522_090000_create_training_tactic_plan_table extends Migration
{
    public function safeUp(): void
    {
        $this->createTable('{{%training_tactic_plan}}', [
            'id'             => $this->primaryKey(),
            'team_id'        => $this->integer()->notNull(),
            'season'         => $this->integer()->notNull()->defaultValue(1),
            'pressing'       => $this->tinyInteger()->notNull()->defaultValue(15),
            'contropiede'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'possesso'       => $this->tinyInteger()->notNull()->defaultValue(15),
            'palla_bassa'    => $this->tinyInteger()->notNull()->defaultValue(10),
            'lancio_lungo'   => $this->tinyInteger()->notNull()->defaultValue(10),
            'catenaccio'     => $this->tinyInteger()->notNull()->defaultValue(10),
            'fuorigioco'     => $this->tinyInteger()->notNull()->defaultValue(10),
            'calci_piazzati' => $this->tinyInteger()->notNull()->defaultValue(20),
            'updated_at'     => $this->integer()->notNull(),
        ]);
        $this->createIndex(
            'uidx_training_tactic_plan_team',
            '{{%training_tactic_plan}}',
            ['team_id', 'season'],
            true
        );
        $this->addForeignKey(
            'fk_training_tactic_plan_team',
            '{{%training_tactic_plan}}',
            'team_id',
            '{{%team}}',
            'id',
            'CASCADE'
        );

        $rows = (new \yii\db\Query())
            ->from('{{%training_tactic}}')
            ->all();
        $now = time();
        foreach ($rows as $row) {
            $levels = [
                'pressing' => max(0, min(100, (int)($row['pressing'] ?? 0))),
                'contropiede' => max(0, min(100, (int)($row['contropiede'] ?? 0))),
                'possesso' => max(0, min(100, (int)($row['possesso'] ?? 0))),
                'palla_bassa' => max(0, min(100, (int)($row['palla_bassa'] ?? 0))),
                'lancio_lungo' => max(0, min(100, (int)($row['lancio_lungo'] ?? 0))),
                'catenaccio' => max(0, min(100, (int)($row['catenaccio'] ?? 0))),
                'fuorigioco' => max(0, min(100, (int)($row['fuorigioco'] ?? 0))),
                'calci_piazzati' => max(0, min(100, (int)($row['calci_piazzati'] ?? 0))),
            ];
            $sum = array_sum($levels);
            if ($sum <= 0) {
                $plan = [
                    'pressing' => 15, 'contropiede' => 10, 'possesso' => 15, 'palla_bassa' => 10,
                    'lancio_lungo' => 10, 'catenaccio' => 10, 'fuorigioco' => 10, 'calci_piazzati' => 20,
                ];
            } else {
                $plan = [];
                $acc = 0;
                $keys = array_keys($levels);
                foreach ($keys as $idx => $key) {
                    if ($idx === count($keys) - 1) {
                        $plan[$key] = max(0, 100 - $acc);
                    } else {
                        $v = (int) round(($levels[$key] / $sum) * 100);
                        $v = max(0, min(100 - $acc, $v));
                        $plan[$key] = $v;
                        $acc += $v;
                    }
                }
            }

            $this->insert('{{%training_tactic_plan}}', [
                'team_id' => (int)$row['team_id'],
                'season' => (int)($row['season'] ?? 1),
                'pressing' => $plan['pressing'],
                'contropiede' => $plan['contropiede'],
                'possesso' => $plan['possesso'],
                'palla_bassa' => $plan['palla_bassa'],
                'lancio_lungo' => $plan['lancio_lungo'],
                'catenaccio' => $plan['catenaccio'],
                'fuorigioco' => $plan['fuorigioco'],
                'calci_piazzati' => $plan['calci_piazzati'],
                'updated_at' => $now,
            ]);
        }
    }

    public function safeDown(): void
    {
        $this->dropForeignKey('fk_training_tactic_plan_team', '{{%training_tactic_plan}}');
        $this->dropTable('{{%training_tactic_plan}}');
    }
}

