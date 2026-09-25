<?php

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $groupMiddleware = [];
    private string $groupPrefix = '';

    public function get(string $uri, array|\Closure $action, array $middleware = []): void
    {
        $this->addRoute('GET', $uri, $action, $middleware);
    }

    public function post(string $uri, array|\Closure $action, array $middleware = []): void
    {
        $this->addRoute('POST', $uri, $action, $middleware);
    }

    public function put(string $uri, array|\Closure $action, array $middleware = []): void
    {
        $this->addRoute('PUT', $uri, $action, $middleware);
    }

    public function delete(string $uri, array|\Closure $action, array $middleware = []): void
    {
        $this->addRoute('DELETE', $uri, $action, $middleware);
    }

    public function group(array $options, \Closure $callback): void
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddleware = $this->groupMiddleware;

        $this->groupPrefix .= $options['prefix'] ?? '';
        $this->groupMiddleware = array_merge($this->groupMiddleware, $options['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }

    private function addRoute(string $method, string $uri, array|\Closure $action, array $middleware): void
    {
        $uri = $this->groupPrefix . $uri;
        $uri = $uri === '' ? '/' : rtrim($uri, '/');
        $uri = $uri === '' ? '/' : $uri;

        $this->routes[] = [
            'method' => $method,
            'uri' => $uri,
            'action' => $action,
            'middleware' => array_merge($this->groupMiddleware, $middleware),
            'pattern' => $this->buildPattern($uri),
        ];
    }

    private function buildPattern(string $uri): string
    {
        $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri = $request->uri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                foreach ($route['middleware'] as $middlewareEntry) {
                    if (is_array($middlewareEntry)) {
                        [$middlewareClass, $arg] = $middlewareEntry;
                        $middleware = new $middlewareClass($arg);
                    } else {
                        $middleware = new $middlewareEntry();
                    }

                    if (!$middleware->handle($request)) {
                        return;
                    }
                }

                $action = $route['action'];
                if ($action instanceof \Closure) {
                    $action($request, ...array_values($params));
                    return;
                }

                [$controllerClass, $methodName] = $action;
                $controller = new $controllerClass();
                $controller->$methodName($request, ...array_values($params));
                return;
            }
        }

        $this->notFound($request);
    }

    private function notFound(Request $request): void
    {
        if (str_starts_with($request->uri(), '/api/')) {
            Response::error('Endpoint tidak ditemukan.', 'not_found', 404);
            return;
        }

        Response::notFound();
    }
}
