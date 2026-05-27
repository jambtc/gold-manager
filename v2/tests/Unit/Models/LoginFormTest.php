<?php

declare(strict_types=1);

namespace app\tests\Unit\Models;

use app\models\LoginForm;
use app\tests\Support\TestUserSeeder;
use Yii;
use yii\base\Security;

final class LoginFormTest extends \Codeception\Test\Unit
{
    private $_model;

    protected function _before(): void
    {
        TestUserSeeder::seed();
    }

    protected function _after()
    {
        Yii::$app->user->logout();
        putenv('GM_LOGIN_MAX_ATTEMPTS');
        putenv('GM_LOGIN_WINDOW_SECONDS');
        putenv('GM_LOGIN_BLOCK_SECONDS');
    }

    public function testLoginNoUser()
    {
        $this->_model = new LoginForm(
            new Security(),
            [
                'username' => 'not_existing_username',
                'password' => 'not_existing_password',
            ],
        );

        verify($this->_model->login())->false();
        verify(Yii::$app->user->isGuest)->true();
    }

    public function testLoginWrongPassword()
    {
        $this->_model = new LoginForm(
            new Security(),
            [
                'username' => 'demo',
                'password' => 'wrong_password',
            ],
        );

        verify($this->_model->login())->false();
        verify(Yii::$app->user->isGuest)->true();
        verify($this->_model->errors)->arrayHasKey('password');
    }

    public function testLoginCorrect()
    {
        $this->_model = new LoginForm(
            new Security(),
            [
                'username' => 'demo',
                'password' => 'demo',
            ],
        );

        verify($this->_model->login())->true();
        verify(Yii::$app->user->isGuest)->false();
        verify($this->_model->errors)->arrayHasNotKey('password');
    }

    public function testLoginBruteForceThrottle(): void
    {
        putenv('GM_LOGIN_MAX_ATTEMPTS=2');
        putenv('GM_LOGIN_WINDOW_SECONDS=600');
        putenv('GM_LOGIN_BLOCK_SECONDS=600');

        $attempt1 = new LoginForm(
            new Security(),
            [
                'username' => 'throttle_probe_user',
                'password' => 'x',
            ],
        );
        verify($attempt1->login())->false();

        $attempt2 = new LoginForm(
            new Security(),
            [
                'username' => 'throttle_probe_user',
                'password' => 'x',
            ],
        );
        verify($attempt2->login())->false();

        $attempt3 = new LoginForm(
            new Security(),
            [
                'username' => 'throttle_probe_user',
                'password' => 'x',
            ],
        );
        verify($attempt3->login())->false();
        verify($attempt3->errors)->arrayHasKey('password');
        verify(implode(' ', $attempt3->errors['password']))->contains('Too many attempts');
    }
}
