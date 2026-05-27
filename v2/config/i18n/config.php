<?php

/**
 * Configuration for 'yii message/extract' command.
 *
 * Usage:
 *   ./yii message/extract @app/config/i18n/config.php
 *
 * This scans all PHP files under @app for Yii::t('app', '...') calls
 * and populates messages/en-US/app.php and messages/it-IT/app.php.
 */

return [
    'color'                 => null,
    'interactive'           => true,
    'help'                  => false,
    'silentExitOnException' => null,
    'sourcePath'            => '@app',
    'messagePath'           => '@app/messages',
    'languages'             => ['en-US', 'it-IT'],
    'translator'            => 'Yii::t',
    'sort'                  => true,
    'overwrite'             => true,
    'removeUnused'          => false,
    'markUnused'            => true,
    'except'                => [
        '.svn',
        '.git',
        '.gitignore',
        '.gitkeep',
        '.hgignore',
        '.hgkeep',
        '/messages',
        '/vendor',
        '/runtime',
        '/tests',
        '/BaseYii.php',
    ],
    'only'                  => ['*.php'],
    'format'                => 'php',
    'phpFileHeader'         => '',
    'phpDocBlock'           => null,
];
