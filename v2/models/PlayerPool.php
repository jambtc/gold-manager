<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $player_id
 * @property int $asking_fee
 * @property int $salary_ask
 * @property int $available_since
 * @property int $expires_at
 *
 * @property Player $player
 */
class PlayerPool extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%player_pool}}';
    }

    public function rules(): array
    {
        return [
            [['player_id', 'asking_fee', 'salary_ask', 'available_since', 'expires_at'], 'required'],
            [['player_id', 'asking_fee', 'salary_ask', 'available_since', 'expires_at'], 'integer'],
        ];
    }

    public function getPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'player_id']);
    }

    public static function findBestForPosition(string $position): ?self
    {
        return self::find()
            ->alias('pp')
            ->innerJoin(['p' => Player::tableName()], 'p.id = pp.player_id')
            ->where(['p.position' => strtoupper($position), 'p.team_id' => null])
            ->andWhere(['>', 'pp.expires_at', time()])
            ->orderBy(['p.general_skill' => SORT_DESC, 'pp.asking_fee' => SORT_ASC, 'pp.id' => SORT_ASC])
            ->limit(1)
            ->one();
    }
}

