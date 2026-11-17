<?php

/**
 * Router simples com suporte a rotas literais e parâmetros no caminho.
 *
 * Exemplos:
 *   $router->get('/produto', $callback);            // rota literal
 *   $router->get('/admin/produtos/{id}/editar', ...) // parâmetro {id}
 *
 * Formulários HTML só enviam GET e POST. Para usar PUT/DELETE, envie um POST
 * com o campo oculto `_method`.
 */
class Router
{
    /** @var array<string, array<string, array{regex: string, params: array<int, string>, callback: callable}>> */
    private array $routes = [];

    public function get(string $route, callable $callback): void
    {
        $this->add('GET', $route, $callback);
    }

    public function post(string $route, callable $callback): void
    {
        $this->add('POST', $route, $callback);
    }

    public function put(string $route, callable $callback): void
    {
        $this->add('PUT', $route, $callback);
    }

    public function delete(string $route, callable $callback): void
    {
        $this->add('DELETE', $route, $callback);
    }

    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $path = $this->normalize(parse_url($uri, PHP_URL_PATH) ?: '/');

        if ($method === 'POST') {
            $override = $_POST['_method'] ?? '';
            $override = is_string($override) ? strtoupper(trim($override)) : '';

            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        $match = $this->find($method, $path);

        if ($match !== null) {
            [$callback, $params] = $match;
            $callback(...$params);
            return;
        }

        $allowed = [];

        foreach (array_keys($this->routes) as $allowedMethod) {
            if ($this->find($allowedMethod, $path) !== null) {
                $allowed[] = $allowedMethod;
            }
        }

        if ($allowed !== []) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            require __DIR__ . '/../views/405.php';
            return;
        }

        http_response_code(404);
        require __DIR__ . '/../views/404.php';
    }

    private function add(string $method, string $route, callable $callback): void
    {
        $route = $this->normalize($route);
        $params = [];
        $regex = '';
        $offset = 0;

        if (preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $route, $matches, PREG_OFFSET_CAPTURE) !== false) {
            foreach ($matches[0] as $index => $match) {
                $literal = substr($route, $offset, $match[1] - $offset);
                $regex .= preg_quote($literal, '#') . '([^/]+)';
                $params[] = $matches[1][$index][0];
                $offset = $match[1] + strlen($match[0]);
            }
        }

        $regex .= preg_quote(substr($route, $offset), '#');

        $this->routes[$method][$route] = [
            'regex' => '#^' . $regex . '$#u',
            'params' => $params,
            'callback' => $callback,
        ];
    }

    /**
     * @return array{0: callable, 1: array<int, string>}|null
     */
    private function find(string $method, string $path): ?array
    {
        $routes = $this->routes[$method] ?? [];

        // Rotas literais têm prioridade sobre rotas com parâmetros.
        foreach ([true, false] as $literalPass) {
            foreach ($routes as $definition) {
                $isLiteral = $definition['params'] === [];

                if ($isLiteral !== $literalPass) {
                    continue;
                }

                if (preg_match($definition['regex'], $path, $matches) !== 1) {
                    continue;
                }

                array_shift($matches);

                return [
                    $definition['callback'],
                    array_map('urldecode', $matches),
                ];
            }
        }

        return null;
    }

    private function normalize(string $route): string
    {
        $route = '/' . trim($route, '/');

        return $route === '/' ? '/' : rtrim($route, '/');
    }
}
