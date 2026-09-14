<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

define('APP_PATH', __DIR__);
define('BASE_PATH', dirname(__DIR__));

date_default_timezone_set('Asia/Ho_Chi_Minh');

require APP_PATH . '/config/config.php';
require APP_PATH . '/config/constants.php';
require APP_PATH . '/config/roles.php';
require APP_PATH . '/core/Helpers.php';

ini_set('display_errors', DISPLAY_ERRORS ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/app.log');
error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {
    static $map = null;
    if ($map === null) {
        $map = [];
        foreach ([APP_PATH . '/core', APP_PATH . '/controllers', APP_PATH . '/models'] as $dir) {
            foreach (glob($dir . '/*.php') as $file) {
                $map[basename($file, '.php')] = $file;
            }
        }
    }
    if (isset($map[$class])) {
        require $map[$class];
    }
});

session_name('simea_session');
session_start();
