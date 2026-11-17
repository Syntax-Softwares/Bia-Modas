<?php

class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $route, callable $callback): void
    {
        $this->routes['GET'][$this->normalize($route)] = $callback;
    }

    public function post(string $route, callable $callback): void
    {
        $this->routes['POST'][$this->normalize($route)] = $callback;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = $this->normalize($path);

        if (isset($this->routes[$method][$path])) {
            ($this->routes[$method][$path])();
            return;
        }

        foreach (array_keys($this->routes) as $allowedMethod) {
            if (isset($this->routes[$allowedMethod][$path])) {
                http_response_code(405);
                header('Allow: ' . $allowedMethod);
                require __DIR__ . '/../views/405.php';
                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../views/404.php';
    }

    private function normalize(string $route): string
    {
        $route = '/' . trim($route, '/');

        return $route === '/' ? '/' : rtrim($route, '/');
    }
}
