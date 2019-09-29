<?php

use yii\helpers\ArrayHelper;

defined('YII_ENV') or define('YII_ENV', 'test');
defined('YII_DEBUG') or define('YII_DEBUG', true);

require_once __DIR__ .  '/../../vendor/yiisoft/yii2/Yii.php';

/*require __DIR__ .'/../../../app/vendor/autoload.php';

$dir = dirname(__DIR__);*/

$config = require_once __DIR__ .  '/../console.php';
ArrayHelper::remove($config, 'class');
(new yii\console\Application($config))->init();
