<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property int $base_payment
 * @property int $win_bonus
 * @property int $duration_seasons
 */
class Sponsor extends ActiveRecord
{
    public static function tableName(): string { return '{{%sponsor}}'; }
}
