<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "fixture".
 *
 * @property int $id
 * @property int $competition_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property int $match_date
 * @property int|null $home_score
 * @property int|null $away_score
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Competition $competition
 * @property Team $awayTeam
 * @property Team $homeTeam
 */
class Fixture extends ActiveRecord
{
    const STATUS_SCHEDULED = 0;
    const STATUS_PLAYING = 1;
    const STATUS_FINISHED = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%fixture}}';
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

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['competition_id', 'home_team_id', 'away_team_id', 'match_date'], 'required'],
            [['competition_id', 'home_team_id', 'away_team_id', 'match_date', 'home_score', 'away_score', 'status'], 'integer'],
            [['competition_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competition::class, 'targetAttribute' => ['competition_id' => 'id']],
            [['away_team_id'], 'exist', 'skipOnError' => true, 'targetClass' => Team::class, 'targetAttribute' => ['away_team_id' => 'id']],
            [['home_team_id'], 'exist', 'skipOnError' => true, 'targetClass' => Team::class, 'targetAttribute' => ['home_team_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'competition_id' => 'Competition ID',
            'home_team_id' => 'Home Team ID',
            'away_team_id' => 'Away Team ID',
            'match_date' => 'Match Date',
            'home_score' => 'Home Score',
            'away_score' => 'Away Score',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    /**
     * Gets query for [[Competition]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCompetition()
    {
        return $this->hasOne(Competition::class, ['id' => 'competition_id']);
    }

    /**
     * Gets query for [[AwayTeam]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAwayTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'away_team_id']);
    }

    /**
     * Gets query for [[HomeTeam]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getHomeTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'home_team_id']);
    }
}
