<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;
use yii\base\Security;

/**
 * LoginForm is the model behind the login form.
 *
 * @property-read User|null $user
 *
 */
class LoginForm extends Model
{
    public string $username = '';
    public string $password = '';
    public bool $rememberMe = true;
    private User|null $_user = null;
    private bool $_userLoaded = false;
    public function __construct(private readonly Security $security, $config = [])
    {
        parent::__construct($config);
    }

    /**
     * @return array the validation rules.
     */
    public function rules(): array
    {
        return [
            // username and password are both required
            [['username', 'password'], 'required'],
            // rememberMe must be a boolean value
            ['rememberMe', 'boolean'],
            // password is validated by validatePassword()
            ['password', 'validatePassword'],
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword(string $attribute, array|null $params): void
    {
        if ($this->isTemporarilyBlocked()) {
            $this->addError($attribute, Yii::t('app', 'Too many attempts. Try again later.'));
            return;
        }

        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$this->security->validatePassword($this->password, $user->password_hash)) {
                $this->registerFailedAttempt();
                $this->addError($attribute, Yii::t('app', 'Incorrect username or password.'));
                Yii::warning(
                    sprintf(
                        'Login failed for username="%s" ip="%s"',
                        $this->username,
                        $this->clientIp()
                    ),
                    'security.auth'
                );
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login(): bool
    {
        if ($this->validate()) {
            $ok = Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
            if ($ok) {
                $this->clearAttemptState();
                Yii::info(
                    sprintf(
                        'Login success for user_id=%d username="%s" ip="%s"',
                        (int) $this->getUser()?->id,
                        $this->username,
                        $this->clientIp()
                    ),
                    'security.auth'
                );
            }
            return $ok;
        }

        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser(): User|null
    {
        if (!$this->_userLoaded) {
            $this->_user = User::findByUsername($this->username);
            $this->_userLoaded = true;
        }

        return $this->_user;
    }

    private function attemptWindowSeconds(): int
    {
        return max(60, (int) (getenv('GM_LOGIN_WINDOW_SECONDS') ?: 900));
    }

    private function maxAttempts(): int
    {
        return max(2, (int) (getenv('GM_LOGIN_MAX_ATTEMPTS') ?: 8));
    }

    private function blockSeconds(): int
    {
        return max(60, (int) (getenv('GM_LOGIN_BLOCK_SECONDS') ?: 900));
    }

    private function attemptKey(): string
    {
        return sprintf('gm:login:attempt:%s:%s', $this->clientIp(), strtolower(trim($this->username)));
    }

    private function blockKey(): string
    {
        return sprintf('gm:login:block:%s:%s', $this->clientIp(), strtolower(trim($this->username)));
    }

    private function clientIp(): string
    {
        $ip = Yii::$app?->request?->userIP;
        return is_string($ip) && $ip !== '' ? $ip : 'cli';
    }

    private function isTemporarilyBlocked(): bool
    {
        $blockedUntil = (int) Yii::$app->cache->get($this->blockKey());
        return $blockedUntil > time();
    }

    private function registerFailedAttempt(): void
    {
        $cache = Yii::$app->cache;
        $count = (int) $cache->get($this->attemptKey());
        $count++;
        $cache->set($this->attemptKey(), $count, $this->attemptWindowSeconds());

        if ($count >= $this->maxAttempts()) {
            $blockedUntil = time() + $this->blockSeconds();
            $cache->set($this->blockKey(), $blockedUntil, $this->blockSeconds());
            Yii::warning(
                sprintf(
                    'Login throttled for username="%s" ip="%s" until=%d',
                    $this->username,
                    $this->clientIp(),
                    $blockedUntil
                ),
                'security.auth'
            );
        }
    }

    private function clearAttemptState(): void
    {
        Yii::$app->cache->delete($this->attemptKey());
        Yii::$app->cache->delete($this->blockKey());
    }
}
