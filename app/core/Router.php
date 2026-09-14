<?php

if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(403);
    exit;
}

class Router
{
    /** @var array<int,array{method:string,pattern:string,handler:array,roles:array}> */
    private array $routes = [];

    /** @param string[] $roles */
    public function get(string $pattern, array $handler, array $roles): void
    {
        $this->add('GET', $pattern, $handler, $roles);
    }

    /** @param string[] $roles */
    public function post(string $pattern, array $handler, array $roles): void
    {
        $this->add('POST', $pattern, $handler, $roles);
    }

    private function add(string $method, string $pattern, array $handler, array $roles): void
    {
        $this->routes[] = compact('method', 'pattern', 'handler', 'roles');
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = rtrim($route['pattern'], '/');
            $regex = preg_replace('#\{[a-zA-Z_]+\}#', '([^/]+)', $pattern);
            $regex = $regex === '' ? '#^/$#' : '#^' . $regex . '/?$#';

            if (preg_match($regex, $path, $matches)) {
                array_shift($matches);
                Auth::requireRole($route['roles']);

                [$controllerClass, $methodName] = $route['handler'];
                $controller = new $controllerClass();
                call_user_func_array([$controller, $methodName], $matches);
                return;
            }
        }

        http_response_code(404);
        require APP_PATH . '/views/errors/404.php';
    }
}
