<?php

declare(strict_types=1);

namespace app\components;

use Yii;
use yii\base\BootstrapInterface;

/**
 * Applies the logged-in user's language preference on every web request.
 * Register in config/web.php: 'bootstrap' => ['log', 'languageBootstrap']
 */
class LanguageBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        if (!($app instanceof \yii\web\Application)) {
            return;
        }
        if ($app->user->isGuest) {
            return;
        }
        /** @var \app\models\User|null $identity */
        $identity = $app->user->identity;
        $lang = (string) ($identity->language ?? 'it-IT');
        if ($lang !== '') {
            $app->language = $lang;
        }
    }
}
