<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int         $id
 * @property int         $user_id
 * @property int|null    $news_item_id
 * @property string      $channel      telegram
 * @property string      $status       pending|sent|failed
 * @property string|null $telegram_text
 * @property int         $attempts
 * @property string|null $last_error
 * @property int|null    $sent_at
 * @property int         $created_at
 * @property int         $updated_at
 */
class NotificationDelivery extends ActiveRecord
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    public const CHANNEL_TELEGRAM = 'telegram';

    public const MAX_ATTEMPTS = 3;

    public static function tableName(): string
    {
        return '{{%notification_delivery}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'channel', 'status', 'created_at', 'updated_at'], 'required'],
            [['user_id', 'news_item_id', 'attempts', 'sent_at', 'created_at', 'updated_at'], 'integer'],
            [['channel', 'status'], 'string', 'max' => 20],
            [['telegram_text', 'last_error'], 'string'],
        ];
    }
}
