<?php

include_once __DIR__ . '/../vendor/autoload.php';

defined('ROUCHI_ROOT_PATH') or define('ROUCHI_ROOT_PATH', dirname(__DIR__));
defined('ROUCHI_CONF_PATH') or define('ROUCHI_CONF_PATH', ROUCHI_ROOT_PATH.'/Config');
defined('ROUCHI_LOG_PATH') or define('ROUCHI_LOG_PATH', ROUCHI_ROOT_PATH.'/Log');
defined('ROUCHI_APP_NAME') or define('ROUCHI_APP_NAME', "ROUCHI");


Jy\App::run();
