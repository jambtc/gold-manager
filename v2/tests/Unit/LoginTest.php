<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\controllers\SiteController;
use app\tests\Support\TestUserSeeder;
use Yii;
use yii\base\Security;
use yii\web\View;

final class LoginTest extends \Codeception\Test\Unit
{
    protected function _before(): void
    {
        TestUserSeeder::seed();
    }

    public function testRenderLoginWrongUsername(): void
    {
        $_SERVER['REQUEST_URI'] = '/index-test.php?r=site/login';

        $controller = new SiteController(
            'site',
            Yii::$app,
            Yii::$app->mailer,
            new Security(),
        );

        $view = new View(['context' => $controller]);

        $controller->actionLogin();

        self::assertStringNotContainsString(
            'Logout (admin)',
            $view->render('//layouts/main.php', ['content' => 'Hello World°']),
            'Failed asserting that the logout link is not rendered for a wrong username.',
        );
    }
}
