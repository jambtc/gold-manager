<?php

declare(strict_types=1);

namespace app\controllers;

use app\components\NewsService;
use app\models\NewsItem;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class NewsController extends Controller
{
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [['allow' => true, 'roles' => ['@']]],
            ],
        ];
    }

    public function actionIndex(): string
    {
        $userId = Yii::$app->user->id;
        NewsService::markAllRead($userId);

        $items = NewsItem::find()
            ->where(['user_id' => $userId])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(200)
            ->all();

        // Group by date
        $grouped = [];
        foreach ($items as $item) {
            $day = date('Y-m-d', $item->created_at);
            $grouped[$day][] = $item;
        }

        return $this->render('index', ['grouped' => $grouped]);
    }

    public function actionMarkRead(): Response
    {
        NewsService::markAllRead(Yii::$app->user->id);
        return $this->redirect(['index']);
    }
}
