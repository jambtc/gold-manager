<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $formation_id
 * @property int $zone
 * @property int|null $player_id
 *
 * @property Formation $formation
 * @property Player|null $player
 */
class FormationSlot extends ActiveRecord
{
    public static function tableName(): string { return '{{%formation_slot}}'; }

    public function rules(): array
    {
        return [
            [['formation_id', 'zone'], 'required'],
            [['formation_id', 'zone', 'player_id'], 'integer'],
        ];
    }

    public function getFormation() { return $this->hasOne(Formation::class, ['id' => 'formation_id']); }
    public function getPlayer()    { return $this->hasOne(Player::class, ['id' => 'player_id']); }
}
