<?php

declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/helpers.php';

load_env(BASE_PATH . '/.env');

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

if ($appConfig['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

require_once BASE_PATH . '/app/Database.php';
require_once BASE_PATH . '/app/Auth.php';
require_once BASE_PATH . '/app/MasterDataSync.php';
