<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $token = $_POST['_csrf'] ?? '';
        if ($token === '' || !hash_equals($_SESSION['_csrf'] ?? '', $token)) {
            http_response_code(419);
            die('Phiên làm việc đã hết hạn, vui lòng tải lại trang và thử lại.');
        }
    }
}
