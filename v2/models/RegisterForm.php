<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;

class RegisterForm extends Model
{
    public string $username        = '';
    public string $password        = '';
    public string $passwordConfirm = '';

    public function rules(): array
    {
        return [
            [['username', 'password', 'passwordConfirm'], 'required'],
            [['username'], 'string', 'min' => 3, 'max' => 50],
            [['username'], 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/'],
            [['username'], 'unique', 'targetClass' => User::class, 'targetAttribute' => 'username'],
            [['password'], 'string', 'min' => 6],
            [['passwordConfirm'], 'compare', 'compareAttribute' => 'password', 'message' => 'Passwords do not match.'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username'        => 'Username',
            'password'        => 'Password',
            'passwordConfirm' => 'Confirm Password',
        ];
    }

    /**
     * Creates and saves the User record. Returns the User on success, null on failure.
     */
    public function register(): ?User
    {
        if (!$this->validate()) {
            return null;
        }

        $user = new User();
        $user->username      = $this->username;
        $user->role          = User::ROLE_MANAGER;
        $user->status        = User::STATUS_PENDING;
        $user->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        $user->auth_key      = Yii::$app->security->generateRandomString();
        $user->created_at    = time();
        $user->updated_at    = time();

        return $user->save(false) ? $user : null;
    }
}
