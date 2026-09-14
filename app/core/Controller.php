<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $flashes = getFlashes();
        $currentUser = Auth::user();
        require APP_PATH . '/views/layout/header.php';
        require APP_PATH . "/views/{$view}.php";
        require APP_PATH . '/views/layout/footer.php';
    }

    protected function redirect(string $path): void
    {
        redirectTo($path);
    }

    protected function input(string $key, $default = null)
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function requireCsrf(): void
    {
        Csrf::verify();
    }

    protected function abort404(): void
    {
        http_response_code(404);
        require APP_PATH . '/views/errors/404.php';
        exit;
    }
}
