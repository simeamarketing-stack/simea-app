<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $user = (new UserModel())->findActiveByUsername($username);
        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        // A session can outlive a role that no longer exists (e.g. the role
        // model changes while someone is logged in) — treat that as logged
        // out instead of leaving them stuck on a 403 with no way back to
        // the login page.
        if (!in_array($_SESSION['role'] ?? null, ALL_ROLES, true)) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role'],
        ];
    }

    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /** @param string[] $roles Pass ['*'] for a public route (no login required). */
    public static function requireRole(array $roles): void
    {
        if (in_array('*', $roles, true)) {
            return;
        }
        if (!self::check()) {
            redirectTo('/login');
        }
        if (!in_array(self::role(), $roles, true)) {
            http_response_code(403);
            require APP_PATH . '/views/errors/403.php';
            exit;
        }
    }
}
