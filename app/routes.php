<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

$router = new Router();

$router->get('/login', [AuthController::class, 'showLogin'], ['*']);
$router->post('/login', [AuthController::class, 'login'], ['*']);
$router->post('/logout', [AuthController::class, 'logout'], ALL_ROLES);

$router->get('/', [HomeController::class, 'index'], ALL_ROLES);

return $router;
