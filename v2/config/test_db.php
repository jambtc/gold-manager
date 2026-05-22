<?php

return [
    'class' => \yii\db\Connection::class,
    'dsn' => sprintf(
        'mysql:host=%s;port=%s;dbname=%s',
        getenv('DB_HOST_TEST') ?: (getenv('DB_HOST') ?: 'mariadb'),
        getenv('DB_PORT_TEST') ?: (getenv('DB_PORT') ?: '3306'),
        getenv('DB_NAME_TEST') ?: 'gold_manager_test'
    ),
    'username' => getenv('DB_USER_TEST') ?: (getenv('DB_USER') ?: 'gm_user'),
    'password' => getenv('DB_PASSWORD_TEST') ?: (getenv('DB_PASSWORD') ?: 'secret'),
    'charset' => 'utf8mb4',
];
