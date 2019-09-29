<?php
/**
 * Created by PhpStorm.
 * User: web
 * Date: 15.04.19
 * Time: 18:57
 */

return [
    'class' => \yii\console\Application::class,
    'id' => 'test-console',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm' => '@vendor/npm-asset',
        'somov/appmodule' => __DIR__ . '/../src',
        'mtest' => __DIR__ . '/unit',
        'ext' => __DIR__
    ],
    'components' => [
        'request' => [
            'enableCsrfValidation' => false,
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'logFile' => '@runtime/logs/test.log',
                    'categories' => ['application'],
                    'levels' => ['error', 'trace', 'warning', 'info'],
                    'logVars' => [],
                ],
            ],
        ],
    ],
];