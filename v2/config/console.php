<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'app\commands',
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
        '@tests' => '@app/tests',
    ],
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'log' => [
            'targets' => [
                [
                    'class'    => \yii\log\FileTarget::class,
                    'levels'   => ['error', 'warning', 'info'],
                    'categories' => ['queue', 'yii\queue\*'],
                    'logFile'  => '@runtime/logs/queue.log',
                    'logVars'  => [],
                ],
                [
                    'class'   => \yii\log\FileTarget::class,
                    'levels'  => ['error', 'warning'],
                    'logFile' => '@runtime/logs/app.log',
                    'logVars' => [],
                ],
            ],
        ],
        'db' => $db,
        'matchEngine' => [
            'class' => \app\components\MatchEngine::class,
        ],
        'playerValuator' => [
            'class' => \app\components\PlayerValuator::class,
        ],
        'redis' => [
            'class'    => \yii\redis\Connection::class,
            'hostname' => getenv('REDIS_HOST') ?: 'redis',
            'port'     => 6379,
            'database' => 0,
        ],
        'queue' => [
            'class'   => \yii\queue\redis\Queue::class,
            'redis'   => 'redis',
            'channel' => 'gold-manager-queue',
            'as log'  => \yii\queue\LogBehavior::class,
        ],
    ],
    'bootstrap' => ['log', 'queue'],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
    ];
    // configuration adjustments for 'dev' environment
    // requires version `2.1.21` of yii2-debug module
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
