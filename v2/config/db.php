<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => 'mysql:host=mariadb;dbname=gold_manager',
    'username' => 'gm_user',
    'password' => 'secret',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
