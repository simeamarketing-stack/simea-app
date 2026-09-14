<?php

define('APP_BOOTSTRAPPED', true);
require __DIR__ . '/app/bootstrap.php';

$router = require APP_PATH . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], '/' . ($_GET['route'] ?? ''));
