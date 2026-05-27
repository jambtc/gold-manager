<?php

use yii\db\Migration;

/**
 * Move "calci piazzati" allocation from tactical plan to physical training.
 */
class m260527_130000_add_alloc_calci_piazzati_to_training_skill extends Migration
{
    public function safeUp(): void
    {
        $this->addColumn(
            '{{%training_skill}}',
            'alloc_calci_piazzati',
            $this->tinyInteger()->notNull()->defaultValue(10)->after('alloc_tr')
        );

        $rows = $this->db->createCommand(
            'SELECT s.id, s.team_id, s.season,
                    s.alloc_forma, s.alloc_cond, s.alloc_po, s.alloc_df, s.alloc_cn, s.alloc_pa, s.alloc_rg, s.alloc_cr, s.alloc_tc, s.alloc_tr,
                    p.calci_piazzati AS old_plan
             FROM {{%training_skill}} s
             LEFT JOIN {{%training_tactic_plan}} p ON p.team_id = s.team_id AND p.season = s.season'
        )->queryAll();

        foreach ($rows as $row) {
            $baseSum = 0;
            foreach (['alloc_forma','alloc_cond','alloc_po','alloc_df','alloc_cn','alloc_pa','alloc_rg','alloc_cr','alloc_tc','alloc_tr'] as $f) {
                $baseSum += max(0, min(100, (int) ($row[$f] ?? 0)));
            }
            $oldPlan = max(0, min(100, (int) ($row['old_plan'] ?? 10)));
            $maxAllowed = max(0, 100 - $baseSum);
            $allocSetPieces = min($oldPlan, $maxAllowed);

            $this->update(
                '{{%training_skill}}',
                ['alloc_calci_piazzati' => $allocSetPieces, 'updated_at' => time()],
                ['id' => (int) $row['id']]
            );

            $this->update(
                '{{%training_tactic_plan}}',
                ['calci_piazzati' => 0, 'updated_at' => time()],
                ['team_id' => (int) $row['team_id'], 'season' => (int) $row['season']]
            );
        }
    }

    public function safeDown(): void
    {
        $this->dropColumn('{{%training_skill}}', 'alloc_calci_piazzati');
    }
}
