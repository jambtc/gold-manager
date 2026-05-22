<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\TelegramService;
use app\models\Team;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\Response;

class UserController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'telegram-verify'       => ['post'],
                    'telegram-link-start'   => ['post'],
                    'telegram-link-confirm' => ['post'],
                    'telegram-test'         => ['post'],
                    'telegram-revoke'       => ['post'],
                    'telegram-toggle'       => ['post'],
                ],
            ],
        ];
    }

    public function actionProfile(): string|Response
    {
        /** @var User|null $user */
        $user = Yii::$app->user->identity;
        if (!$user) {
            return $this->redirect(['/site/login']);
        }

        $team = Team::findOne(['user_id' => $user->id]);
        if ($team) {
            $team->ensureUiColors();
        }

        if ($team && Yii::$app->request->isPost) {
            $newTeamName = trim((string) Yii::$app->request->post('team_name', $team->name));
            if ($newTeamName === '') {
                $newTeamName = $team->name;
            }

            if (mb_strlen($newTeamName) > 255) {
                Yii::$app->session->setFlash('error', 'Nome squadra troppo lungo (max 255 caratteri).');
                return $this->redirect(['/user/profile']);
            }

            $nameTaken = Team::find()
                ->where(['name' => $newTeamName])
                ->andWhere(['<>', 'id', $team->id])
                ->exists();
            if ($nameTaken) {
                Yii::$app->session->setFlash('error', 'Nome squadra già usato. Scegli un nome diverso.');
                return $this->redirect(['/user/profile']);
            }

            $left = Team::sanitizeHexColor(
                Yii::$app->request->post('color_left'),
                $team->color_left ?: Team::DEFAULT_COLOR_LEFT
            );
            $right = Team::sanitizeHexColor(
                Yii::$app->request->post('color_right'),
                $team->color_right ?: Team::DEFAULT_COLOR_RIGHT
            );

            if ($left === $right) {
                [$fallbackLeft, $fallbackRight] = Team::randomUiColors();
                $left = $left ?: $fallbackLeft;
                $right = $fallbackRight === $left ? Team::DEFAULT_COLOR_RIGHT : $fallbackRight;
            }

            $team->name = $newTeamName;
            $team->color_left = $left;
            $team->color_right = $right;
            $team->save(false, ['name', 'color_left', 'color_right']);

            Yii::$app->session->setFlash('success', 'Dati club aggiornati.');
            return $this->redirect(['/user/profile']);
        }

        return $this->render('profile', [
            'user' => $user,
            'team' => $team,
        ]);
    }

    // ── Telegram AJAX endpoints ────────────────────────────────────────────

    public function actionTelegramVerify(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $token = trim((string) Yii::$app->request->post('token', ''));
        if (!$token) return $this->asJson(['ok' => false, 'error' => 'Token vuoto.']);

        $result = TelegramService::verifyBot($token);
        if ($result['ok'] ?? false) {
            // Save token to user
            $user = Yii::$app->user->identity;
            $user->telegram_bot_token = $token;
            $user->save(false);
        }
        return $this->asJson($result);
    }

    public function actionTelegramLinkStart(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var User $user */
        $user  = Yii::$app->user->identity;
        $token = trim((string) ($user->telegram_bot_token ?? Yii::$app->request->post('token', '')));
        if (!$token) return $this->asJson(['ok' => false, 'error' => 'Verifica prima il bot.']);

        $verify = TelegramService::verifyBot($token);
        if (!($verify['ok'] ?? false)) return $this->asJson(['ok' => false, 'error' => 'Bot non valido.']);

        $data = TelegramService::buildDeepLink((string) $verify['username'], (int) $user->id);
        return $this->asJson(array_merge(['ok' => true], $data));
    }

    public function actionTelegramLinkConfirm(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var User $user */
        $user      = Yii::$app->user->identity;
        $token     = trim((string) ($user->telegram_bot_token ?? ''));
        $tokenHash = trim((string) Yii::$app->request->post('token_hash', ''));
        if (!$token || !$tokenHash) return $this->asJson(['found' => false, 'error' => 'Dati mancanti.']);

        $result = TelegramService::findChatForToken($token, $tokenHash);
        if ($result['found'] ?? false) {
            $user->telegram_chat_id = (string) $result['chat_id'];
            $user->telegram_enabled = 1;
            $user->save(false);
        }
        return $this->asJson($result);
    }

    public function actionTelegramTest(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var User $user */
        $user = Yii::$app->user->identity;
        if (empty($user->telegram_bot_token) || empty($user->telegram_chat_id)) {
            return $this->asJson(['ok' => false, 'error' => 'Telegram non ancora collegato.']);
        }
        $ok = TelegramService::send(
            (string) $user->telegram_bot_token,
            (string) $user->telegram_chat_id,
            "✅ <b>Gold Manager</b>\nTelegram collegato correttamente! Riceverai notifiche su questo chat."
        );
        return $this->asJson(['ok' => $ok]);
    }

    public function actionTelegramRevoke(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $user->telegram_bot_token = null;
        $user->telegram_chat_id   = null;
        $user->telegram_enabled   = 0;
        $user->save(false);
        return $this->asJson(['ok' => true]);
    }

    public function actionTelegramToggle(): Response
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        /** @var User $user */
        $user = Yii::$app->user->identity;
        $user->telegram_enabled = $user->telegram_enabled ? 0 : 1;
        $user->save(false);
        return $this->asJson(['ok' => true, 'enabled' => (bool) $user->telegram_enabled]);
    }
}
