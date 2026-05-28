<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "competition".
 *
 * @property int    $id
 * @property string $name
 * @property int    $season
 * @property string $type
 * @property int    $tier         1=Serie A, 2=Serie B, 3=Serie C
 * @property int    $group_number Girone number within the tier (1, 2, 3…)
 * @property int    $created_at
 * @property int    $updated_at
 *
 * @property Fixture[]  $fixtures
 * @property Standing[] $standings
 */
class Competition extends ActiveRecord
{
    public const TIER_A = 1;
    public const TIER_B = 2;
    public const TIER_C = 3;

    public const TIER_NAMES = [
        self::TIER_A => 'Serie A',
        self::TIER_B => 'Serie B',
        self::TIER_C => 'Serie C',
    ];
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%competition}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['season', 'tier', 'group_number'], 'integer'],
            [['tier'], 'in', 'range' => [self::TIER_A, self::TIER_B, self::TIER_C]],
            [['name'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 50],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id'           => 'ID',
            'name'         => 'Name',
            'season'       => 'Season',
            'type'         => 'Type',
            'tier'         => 'Tier',
            'group_number' => Yii::t('app', 'Group'),
            'created_at'   => 'Created At',
            'updated_at'   => 'Updated At',
        ];
    }

    public function getLabel(): string
    {
        $tierName = self::TIER_NAMES[$this->tier] ?? "Tier {$this->tier}";
        return $this->group_number > 1 ? "$tierName - " . Yii::t('app', 'Group') . " {$this->group_number}" : $tierName;
    }

    /**
     * Gets query for [[Fixtures]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFixtures()
    {
        return $this->hasMany(Fixture::class, ['competition_id' => 'id']);
    }

    /**
     * Gets query for [[Standings]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getStandings()
    {
        return $this->hasMany(Standing::class, ['competition_id' => 'id'])->orderBy(['points' => SORT_DESC, 'goals_for' => SORT_DESC]);
    }
}
