<?php

declare(strict_types=1);

namespace app\jobs;

use app\components\TeamAssigner;
use app\models\User;
use Yii;
use yii\base\BaseObject;
use yii\queue\JobInterface;

class WorldSeedJob extends BaseObject implements JobInterface
{
    public int $userId;

    public function execute($queue): void
    {
        try { Yii::$app->db->createCommand('SELECT 1')->execute(); }
        catch (\Exception $e) { Yii::$app->db->close(); Yii::$app->db->open(); }

        try {
            Yii::info("WorldSeedJob: start for user #{$this->userId}", 'queue');

            (new TeamAssigner())->assign($this->userId);

            User::updateAll(['status' => User::STATUS_ACTIVE], ['id' => $this->userId]);

            Yii::info("WorldSeedJob: done for user #{$this->userId}", 'queue');
        } catch (\Throwable $e) {
            Yii::error("WorldSeedJob: failed for user #{$this->userId} — " . $e->getMessage(), 'queue');
            User::updateAll(['status' => User::STATUS_ERROR], ['id' => $this->userId]);
            throw $e; // re-throw so yii2-queue marks it as failed
        }
    }
}
