<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $team_id
 * @property int    $player_id
 * @property string $snapshot_date
 * @property int    $general_skill
 * @property int    $skill_po
 * @property int    $skill_df
 * @property int    $skill_cn
 * @property int    $skill_pa
 * @property int    $skill_rg
 * @property int    $skill_cr
 * @property int    $skill_tc
 * @property int    $skill_tr
 * @property int    $form
 * @property int    $condition_val
 * @property int    $created_at
 */
class TrainingSnapshot extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%training_snapshot}}';
    }

    public static function writeSnapshot(int $teamId, int $playerId, Player $player): void
    {
        $date = date('Y-m-d');

        // INSERT IGNORE to avoid duplicates if called twice
        \Yii::$app->db->createCommand()->upsert('{{%training_snapshot}}', [
            'team_id'       => $teamId,
            'player_id'     => $playerId,
            'snapshot_date' => $date,
            'general_skill' => $player->general_skill,
            'skill_po'      => $player->skill_po,
            'skill_df'      => $player->skill_df,
            'skill_cn'      => $player->skill_cn,
            'skill_pa'      => $player->skill_pa,
            'skill_rg'      => $player->skill_rg,
            'skill_cr'      => $player->skill_cr,
            'skill_tc'      => $player->skill_tc,
            'skill_tr'      => $player->skill_tr,
            'form'          => $player->form,
            'condition_val' => $player->condition,
            'created_at'    => time(),
        ], [
            // on duplicate key: update values
            'general_skill' => $player->general_skill,
            'skill_po'      => $player->skill_po,
            'skill_df'      => $player->skill_df,
            'skill_cn'      => $player->skill_cn,
            'skill_pa'      => $player->skill_pa,
            'skill_rg'      => $player->skill_rg,
            'skill_cr'      => $player->skill_cr,
            'skill_tc'      => $player->skill_tc,
            'skill_tr'      => $player->skill_tr,
            'form'          => $player->form,
            'condition_val' => $player->condition,
        ])->execute();
    }

    /** @return array[] snapshots with deltas vs previous */
    public static function getProgressForPlayer(int $playerId, int $weeks = 8): array
    {
        $rows = self::find()
            ->where(['player_id' => $playerId])
            ->orderBy(['snapshot_date' => SORT_ASC])
            ->limit($weeks)
            ->asArray()
            ->all();

        $stats = ['general_skill','skill_po','skill_df','skill_cn',
                  'skill_pa','skill_rg','skill_cr','skill_tc','skill_tr',
                  'form','condition_val'];

        $result = [];
        foreach ($rows as $i => $row) {
            $prev   = $i > 0 ? $rows[$i - 1] : null;
            $deltas = [];
            foreach ($stats as $s) {
                $deltas[$s] = $prev ? ((int)$row[$s] - (int)$prev[$s]) : null;
            }
            $result[] = array_merge($row, ['deltas' => $deltas]);
        }
        return $result;
    }

    /** Top N players by total general_skill delta since last snapshot */
    public static function getTopImprovers(int $teamId, int $limit = 5): array
    {
        $sql = <<<SQL
SELECT s.player_id,
       p.name,
       p.position,
       s.general_skill                                       AS current_skill,
       s.general_skill - COALESCE(prev.general_skill, s.general_skill) AS delta
FROM {{%training_snapshot}} s
JOIN {{%player}} p ON p.id = s.player_id
LEFT JOIN {{%training_snapshot}} prev
       ON prev.player_id = s.player_id
      AND prev.snapshot_date = (
              SELECT MAX(s2.snapshot_date)
              FROM {{%training_snapshot}} s2
              WHERE s2.player_id = s.player_id
                AND s2.snapshot_date < s.snapshot_date
          )
WHERE s.team_id = :t
  AND s.snapshot_date = (SELECT MAX(s3.snapshot_date) FROM {{%training_snapshot}} s3 WHERE s3.team_id = :t2)
ORDER BY delta DESC
LIMIT :lim
SQL;
        return \Yii::$app->db->createCommand($sql, [
            ':t' => $teamId, ':t2' => $teamId, ':lim' => $limit,
        ])->queryAll();
    }
}
