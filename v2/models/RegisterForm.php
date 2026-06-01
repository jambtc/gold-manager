<?php

declare(strict_types=1);

namespace app\models;

use app\components\CountryContext;
use Yii;
use yii\base\Model;

class RegisterForm extends Model
{
    public string $username        = '';
    public string $password        = '';
    public string $passwordConfirm = '';
    public string $countryCode     = CountryContext::DEFAULT_COUNTRY;

    public function rules(): array
    {
        return [
            [['username', 'password', 'passwordConfirm', 'countryCode'], 'required'],
            [['username'], 'string', 'min' => 3, 'max' => 50],
            [['username'], 'match', 'pattern' => '/^[a-zA-Z0-9_]+$/'],
            [['username'], 'unique', 'targetClass' => User::class, 'targetAttribute' => 'username'],
            [['password'], 'string', 'min' => 6],
            [['passwordConfirm'], 'compare', 'compareAttribute' => 'password', 'message' => Yii::t('app', 'Passwords do not match.')],
            [['countryCode'], 'in', 'range' => array_keys(CountryContext::catalog())],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username'        => Yii::t('app', 'Username'),
            'password'        => Yii::t('app', 'Password'),
            'passwordConfirm' => Yii::t('app', 'Confirm Password'),
            'countryCode'     => Yii::t('app', 'Country'),
        ];
    }

    /**
     * @return array<string,string>
     */
    public function countryOptions(): array
    {
        return CountryContext::dropdownOptions();
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
        $user->country_code  = CountryContext::normalize($this->countryCode);
        $user->language      = CountryContext::languageForCountry($user->country_code);
        $user->password_hash = Yii::$app->security->generatePasswordHash($this->password);
        $user->auth_key      = Yii::$app->security->generateRandomString();
        $user->created_at    = time();
        $user->updated_at    = time();

        return $user->save(false) ? $user : null;
    }
}
