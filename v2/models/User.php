<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\filters\RateLimitInterface;

/**
 * User model
 *
 * @property int    $id
 * @property string $username
 * @property string $role        'admin' | 'manager'
 * @property string $password_hash
 * @property string $auth_key
 * @property string|null $access_token
 * @property string $status
 * @property string|null $telegram_bot_token
 * @property string|null $telegram_chat_id
 * @property int    $telegram_enabled
 * @property int    $created_at
 * @property int    $updated_at
 */
class User extends ActiveRecord implements IdentityInterface, RateLimitInterface
{
    public const ROLE_ADMIN   = 'admin';
    public const ROLE_MANAGER = 'manager';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE  = 'active';
    public const STATUS_ERROR   = 'error';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_ACTIVE || $this->role === self::ROLE_ADMIN;
    }
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id): static|null
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null): static|null
    {
        return static::findOne(['access_token' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername(string $username): static|null
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey(): string|null
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey): bool
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return \Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * API rate limit per authenticated user.
     *
     * @return array{0:int,1:int}
     */
    public function getRateLimit($request, $action): array
    {
        $limit = (int) (getenv('GM_API_RATE_LIMIT') ?: 120);
        $window = (int) (getenv('GM_API_RATE_WINDOW_SECONDS') ?: 60);

        return [max(1, $limit), max(1, $window)];
    }

    /**
     * @return array{0:int,1:int}
     */
    public function loadAllowance($request, $action): array
    {
        $cache = Yii::$app->cache;
        $key = ['gm', 'api-rate', 'allowance', (int) $this->id];
        $stored = $cache->get($key);

        if (is_array($stored) && isset($stored[0], $stored[1])) {
            return [(int) $stored[0], (int) $stored[1]];
        }

        [$limit] = $this->getRateLimit($request, $action);
        return [$limit, time()];
    }

    public function saveAllowance($request, $action, $allowance, $timestamp): void
    {
        $cache = Yii::$app->cache;
        $key = ['gm', 'api-rate', 'allowance', (int) $this->id];
        [, $window] = $this->getRateLimit($request, $action);
        $cache->set($key, [(int) $allowance, (int) $timestamp], max(2, $window * 2));
    }
}
