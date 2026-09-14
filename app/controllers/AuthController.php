<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }
        $this->view('auth/login');
    }

    public function login(): void
    {
        $this->requireCsrf();

        $username = trim((string) $this->input('username', ''));
        $password = (string) $this->input('password', '');

        if ($username === '' || $password === '') {
            flash('error', 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu.');
            $this->redirect('/login');
        }

        if (Auth::attempt($username, $password)) {
            $this->redirect('/');
        }

        flash('error', 'Tên đăng nhập hoặc mật khẩu không đúng.');
        $this->redirect('/login');
    }

    public function logout(): void
    {
        $this->requireCsrf();
        Auth::logout();
        $this->redirect('/login');
    }
}
