<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "standing".
 *
 * @property int $id
 * @property int $competition_id
 * @property int $team_id
 * @property int $points
 * @property int $played
 * @property int $won
 * @property int $drawn
 * @property int $lost
 * @property int $goals_for
 * @property int $goals_against
 * @property int $penalties_for
 * @property int $penalties_against
 * @property int $clean_sheets
 * @property int $biggest_win_home
 * @property int $biggest_win_away
 * @property int $created_at
 * @property int $updated_at
 *
 * @property Competition $competition
 * @property Team $team
 */
class Standing extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%standing}}';
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
            [['competition_id', 'team_id'], 'required'],
            [['competition_id', 'team_id', 'points', 'played', 'won', 'drawn', 'lost', 'goals_for', 'goals_against', 'penalties_for', 'penalties_against', 'clean_sheets', 'biggest_win_home', 'biggest_win_away'], 'integer'],
            [['competition_id'], 'exist', 'skipOnError' => true, 'targetClass' => Competition::class, 'targetAttribute' => ['competition_id' => 'id']],
            [['team_id'], 'exist', 'skipOnError' => true, 'targetClass' => Team::class, 'targetAttribute' => ['team_id' => 'id']],
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
            'team_id' => 'Team ID',
            'points' => 'Points',
            'played' => 'Played',
            'won' => 'Won',
            'drawn' => 'Drawn',
            'lost' => 'Lost',
            'goals_for' => 'Goals For',
            'goals_against' => 'Goals Against',
            'penalties_for' => 'Penalties For',
            'penalties_against' => 'Penalties Against',
            'clean_sheets' => 'Clean Sheets',
            'biggest_win_home' => 'Biggest Home Win',
            'biggest_win_away' => 'Biggest Away Win',
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
     * Gets query for [[Team]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'team_id']);
    }
}
