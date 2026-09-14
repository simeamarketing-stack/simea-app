<?php
// Dev-only shim for `php -S localhost:8000 router.php`.
// PHP's built-in server ignores .htaccess, so this mimics the production
// mod_rewrite behavior: serve real static files as-is, route everything
// else through the front controller.

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

$_GET['route'] = ltrim($uri, '/');
require __DIR__ . '/index.php';
