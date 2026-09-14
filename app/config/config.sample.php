<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

// Copy this file to config.php on the server (never commit the real one)
// and fill in real values. See docs/DEPLOY.md.

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'simea');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_password');

define('APP_BASE_URL', 'https://your-domain.example');

// Set to false in production so PHP errors never render to end users.
define('DISPLAY_ERRORS', true);
