<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $transfer_id
 * @property int $from_team_id
 * @property int $to_team_id
 * @property int $player_id
 * @property int $offered_fee
 * @property string $status pending|accepted|rejected|withdrawn
 * @property int $created_at
 * @property int $expires_at
 * @property int $updated_at
 *
 * @property Transfer $transfer
 * @property Team $fromTeam
 * @property Team $toTeam
 * @property Player $player
 */
class TransferOffer extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_WITHDRAWN = 'withdrawn';

    public static function tableName(): string
    {
        return '{{%transfer_offer}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['transfer_id', 'from_team_id', 'to_team_id', 'player_id', 'offered_fee', 'expires_at'], 'required'],
            [['transfer_id', 'from_team_id', 'to_team_id', 'player_id', 'offered_fee', 'created_at', 'expires_at', 'updated_at'], 'integer'],
            [['status'], 'string', 'max' => 20],
            [['status'], 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_ACCEPTED,
                self::STATUS_REJECTED,
                self::STATUS_WITHDRAWN,
            ]],
        ];
    }

    public function getTransfer()
    {
        return $this->hasOne(Transfer::class, ['id' => 'transfer_id']);
    }

    public function getFromTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'from_team_id']);
    }

    public function getToTeam()
    {
        return $this->hasOne(Team::class, ['id' => 'to_team_id']);
    }

    public function getPlayer()
    {
        return $this->hasOne(Player::class, ['id' => 'player_id']);
    }
}

