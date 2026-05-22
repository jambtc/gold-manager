<?php

declare(strict_types=1);

namespace app\tests\Support;

use Yii;

final class TestUserSeeder
{
    public static function seed(): void
    {
        $hash = Yii::$app->security->generatePasswordHash('demo');
        $now = time();

        Yii::$app->db->createCommand()->delete('{{%user}}', [
            'or',
            ['id' => [100, 101]],
            ['username' => ['admin', 'demo']],
        ])->execute();

        Yii::$app->db->createCommand()->batchInsert('{{%user}}', [
            'id',
            'username',
            'role',
            'status',
            'password_hash',
            'auth_key',
            'access_token',
            'created_at',
            'updated_at',
        ], [
            [100, 'admin', 'admin', 'active', $hash, 'test100key', '100-token', $now, $now],
            [101, 'demo', 'manager', 'active', $hash, 'test101key', '101-token', $now, $now],
        ])->execute();
    }
}
