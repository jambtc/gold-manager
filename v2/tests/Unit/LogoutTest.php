<?php

declare(strict_types=1);

namespace app\tests\Unit;

use app\controllers\SiteController;
use app\models\User;
use app\tests\Support\TestUserSeeder;
use Yii;
use yii\base\Security;
use yii\web\IdentityInterface;
use yii\web\View;

final class LogoutTest extends \Codeception\Test\Unit
{
    protected function _before(): void
    {
        TestUserSeeder::seed();
    }

    public function testRenderLogoutLinkWhenUserIsLoggedIn(): void
    {
        $user = User::findIdentity('100');

        $controller = new SiteController(
            'site',
            Yii::$app,
            Yii::$app->mailer,
            new Security(),
        );

        $view = new View(['context' => $controller]);

        self::assertNotNull(
            $user,
            "Failed asserting that the user identity with ID '100' exists.",
        );
        self::assertInstanceOf(
            IdentityInterface::class,
            $user,
            "Failed asserting that the identity is an instance of 'Identity' class.",
        );

        Yii::$app->user->login($user);

        $html = $view->render('//layouts/main.php', ['content' => 'Hello World°']);

        self::assertStringContainsString(
            'Esci',
            $html,
            'Failed asserting that the logout link is rendered for a logged-in user.',
        );
        self::assertStringContainsString(
            'method="post"',
            $html,
            'Failed asserting that the logout link uses POST method.',
        );

        $controller->actionLogout();

        $html = $view->render('//layouts/main.php', ['content' => 'Hello World°']);

        self::assertStringNotContainsString(
            'Esci',
            $html,
            'Failed asserting that the logout link is not rendered after logout.',
        );
    }
}
