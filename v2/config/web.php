<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => [
                'class' => \yii\symfonymailer\Mailer::class,
                // send all mails to a file by default.
                'useFileTransport' => true,
                'viewPath' => '@app/mail',
            ],
        ],
    ],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'request' => [
            // Required for cookie tamper protection; override via env in deployment.
            'cookieValidationKey' => getenv('COOKIE_VALIDATION_KEY') ?: '3hCiLu7RxzT026pnS5zmw1fd4BLdbajl',
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['info', 'warning'],
                    'categories' => ['security.auth'],
                    'logFile' => '@runtime/logs/security-auth.log',
                    'maxFileSize' => 1024,
                    'maxLogFiles' => 7,
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
        ],
        
        'i18n' => [
            'translations' => [
                'app' => [
                    'class'          => \yii\i18n\PhpMessageSource::class,
                    'basePath'       => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap'        => ['app' => 'app.php'],
                ],
            ],
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET swagger' => 'swagger/index',
                'GET swagger/openapi' => 'swagger/openapi',
                'GET swagger/generate' => 'swagger/generate',
                'GET api/v1/user/status' => 'api/v1/user/status',
                'GET api/user/status' => 'api/user-status',
            ],
        ],
        
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => \yii\debug\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => \yii\gii\Module::class,
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
