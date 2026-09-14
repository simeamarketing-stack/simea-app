<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class HomeController extends Controller
{
    public function index(): void
    {
        $this->redirect('/dashboard');
    }
}
