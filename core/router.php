<?php

class Router
{
    /** @var array<string, array<string, callable>> */
    private array $arr = [];

    public function get(string $route, callable $callback): void
    {
        $this->arr['GET'][$this->normalizar($route)] = $callback;
    }

    public function post(string $route, callable $callback): void
    {
        $this->arr['POST'][$this->normalizar($route)] = $callback;
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);

        if ($method === 'HEAD') {
            $method = 'GET';
        }

        $rota = $this->normalizar(parse_url($uri, PHP_URL_PATH) ?: '/');

        if (isset($this->arr[$method][$rota])) {
            ($this->arr[$method][$rota])();
            return;
        }

        foreach (array_keys($this->arr) as $metodoRegistrado) {
            if ($metodoRegistrado !== $method && isset($this->arr[$metodoRegistrado][$rota])) {
                http_response_code(405);
                header('Allow: ' . $metodoRegistrado);
                require __DIR__ . '/../views/405.php';
                return;
            }
        }

        http_response_code(404);
        require __DIR__ . '/../views/404.php';
    }

    private function normalizar(string $rota): string
    {
        $rota = '/' . trim($rota, '/');

        return $rota === '/' ? '/' : rtrim($rota, '/');
    }
}
