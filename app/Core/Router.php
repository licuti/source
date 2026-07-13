<?php

namespace App\Core;

class Router {
    protected $routes = [];
    protected $middleware = [];
    protected $request;
    protected $container;
    protected $currentGroupPrefix = '';
    protected $currentGroupMiddleware = [];

    public function __construct(Request $request, Container $container = null) {
        $this->request = $request;
        $this->container = $container ?: new Container();
    }

    public function pushMiddleware($middleware) {
        $this->middleware[] = $middleware;
    }

    protected $namedRoutes = [];

    public function get($path, $callback)    { return $this->addRoute('GET',    $path, $callback); }
    public function post($path, $callback)   { return $this->addRoute('POST',   $path, $callback); }
    public function put($path, $callback)    { return $this->addRoute('PUT',    $path, $callback); }
    public function delete($path, $callback) { return $this->addRoute('DELETE', $path, $callback); }

    public function any($path, $callback) {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $callback);
        }
    }

    public function group($attributes, $callback) {
        if (is_string($attributes)) {
            $attributes = ['prefix' => $attributes];
        }

        $previousPrefix = $this->currentGroupPrefix;
        $previousMiddleware = $this->currentGroupMiddleware;

        if (isset($attributes['prefix'])) {
            $this->currentGroupPrefix .= $attributes['prefix'];
        }
        
        if (isset($attributes['middleware'])) {
            $newMiddleware = is_array($attributes['middleware']) ? $attributes['middleware'] : [$attributes['middleware']];
            $this->currentGroupMiddleware = array_merge($this->currentGroupMiddleware, $newMiddleware);
        }

        $callback($this);

        $this->currentGroupPrefix = $previousPrefix;
        $this->currentGroupMiddleware = $previousMiddleware;
    }

    protected function addRoute($method, $path, $callback) {
        $fullPath = $this->currentGroupPrefix . $path;
        $this->routes[$method][$fullPath] = [
            'callback' => $callback,
            'middleware' => $this->currentGroupMiddleware
        ];
        
        // Trả về anonymous class hỗ trợ chain ->name()
        return new class($this, $fullPath) {
            private $router, $path;
            public function __construct($router, $path) {
                $this->router = $router;
                $this->path = $path;
            }
            public function name($name) {
                $this->router->nameRoute($name, $this->path);
                return $this; // Hỗ trợ chain
            }
        };
    }

    public function nameRoute($name, $path) {
        $this->namedRoutes[$name] = $path;
    }

    public function getNamedRoute($name) {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Điều hướng request qua middleware pipeline → route matching → execute.
     */
    public function dispatch() {
        $match = $this->matchRoute($this->request->method, $this->request->uri);
        $routeMiddleware = $match ? ($match['middleware'] ?? []) : [];
        
        if ($match && isset($match['name'])) {
            $this->request->setRouteName($match['name']);
        }
        
        // Merge global + route middleware
        $allMiddleware = array_merge($this->middleware, $routeMiddleware);

        return $this->runMiddleware($this->request, $allMiddleware, function($request) use ($match) {
            if ($match) {
                $request->setParams($match['params']);
                return $this->execute($match['callback'], $match['params']);
            }

            // Không khớp bất kỳ route nào → Quăng ngoại lệ 404
            throw new \App\Exceptions\HttpException('Not Found', 404);
        });
    }

    /**
     * Khớp URL với route đã đăng ký.
     */
    protected function matchRoute(string $method, string $path): ?array {
        $routes = $this->routes[$method] ?? [];

        // 1. Fast path — exact match (không có param)
        if (isset($routes[$path])) {
            return [
                'callback' => $routes[$path]['callback'], 
                'params' => [], 
                'middleware' => $routes[$path]['middleware'],
                'name' => array_search($path, $this->namedRoutes) ?: null
            ];
        }

        // 2. Pattern match — route có {param}
        foreach ($routes as $routePath => $routeData) {
            if (strpos($routePath, '{') === false) continue;

            $callback = $routeData['callback'];
            $middleware = $routeData['middleware'];

            // Chuyển "/san-pham/{slug}" → regex "#^/san-pham/([^/]+)$#"
            $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                // Lấy tên các param từ route definition
                preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
                $params = array_combine($paramNames[1], array_slice($matches, 1));
                return [
                    'callback' => $callback, 
                    'params' => $params, 
                    'middleware' => $middleware,
                    'name' => array_search($routePath, $this->namedRoutes) ?: null
                ];
            }
        }

        return null;
    }

    protected function runMiddleware($request, $middlewares, $next) {
        $pipeline = array_reverse($middlewares);

        $runner = function($request) use (&$pipeline, &$runner, $next) {
            if (empty($pipeline)) return $next($request);
            $middlewareClass = array_pop($pipeline);
            $middleware = $this->container->make($middlewareClass);
            return $middleware->handle($request, $runner);
        };

        return $runner($request);
    }

    protected function execute($callback, array $params = []) {
        // Đảm bảo Request được bind vào Container để có thể auto-inject
        $this->container->instance(Request::class, $this->request);

        if (is_array($callback)) {
            [$class, $method] = $callback;
            if (class_exists($class)) {
                $controller = $this->container->make($class);
                if (method_exists($controller, $method)) {
                    // Để Container tự động inject dependencies thay vì gán cứng $this->request
                    $result = $this->container->call([$controller, $method], $params);
                    if ($result instanceof Response) return $result;
                    return new Response($result);
                }
            }
        }

        if (is_callable($callback)) {
            $result = $this->container->call($callback, $params);
            if ($result instanceof Response) return $result;
            return new Response($result);
        }

        return new Response('Invalid Route Callback or Controller not found', 500);
    }
}
