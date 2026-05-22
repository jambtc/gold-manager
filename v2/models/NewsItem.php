<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int    $id
 * @property int    $user_id
 * @property string $category   transfer|match|staff|injury|discipline|finance|friendly|system
 * @property string $icon
 * @property string $title
 * @property string|null $body
 * @property string|null $link_url
 * @property int    $is_read
 * @property int    $priority   0=normal 1=important 2=urgent
 * @property int    $created_at
 */
class NewsItem extends ActiveRecord
{
    public const CAT_TRANSFER   = 'transfer';
    public const CAT_MATCH      = 'match';
    public const CAT_STAFF      = 'staff';
    public const CAT_INJURY     = 'injury';
    public const CAT_DISCIPLINE = 'discipline';
    public const CAT_FINANCE    = 'finance';
    public const CAT_FRIENDLY   = 'friendly';
    public const CAT_SYSTEM     = 'system';

    public static function tableName(): string
    {
        return '{{%news_item}}';
    }

    public function rules(): array
    {
        return [
            [['user_id', 'category', 'icon', 'title', 'created_at'], 'required'],
            [['user_id', 'is_read', 'priority', 'created_at'], 'integer'],
            [['category'], 'string', 'max' => 20],
            [['icon'], 'string', 'max' => 10],
            [['title'], 'string', 'max' => 120],
            [['body', 'link_url'], 'string'],
        ];
    }
}
