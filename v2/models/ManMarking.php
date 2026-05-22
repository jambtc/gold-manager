<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $fixture_id
 * @property int $team_id
 * @property int $marker_id
 * @property int $marked_id
 * @property int $created_at
 *
 * @property Player $marker
 * @property Player $marked
 */
class ManMarking extends ActiveRecord
{
    public static function tableName(): string { return '{{%man_marking}}'; }

    public function getMarker() { return $this->hasOne(Player::class, ['id' => 'marker_id']); }
    public function getMarked() { return $this->hasOne(Player::class, ['id' => 'marked_id']); }

    /** Load all markings for a fixture+team, indexed by marker_id */
    public static function forFixtureTeam(int $fixtureId, int $teamId): array
    {
        return self::find()
            ->where(['fixture_id' => $fixtureId, 'team_id' => $teamId])
            ->with(['marker', 'marked'])
            ->all();
    }

    /** Clear markings when a match finishes */
    public static function clearForFixture(int $fixtureId): void
    {
        self::deleteAll(['fixture_id' => $fixtureId]);
    }
}
