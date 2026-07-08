<?php

namespace App\Core;

class Router {
    protected $routes = [];
    protected $middleware = [];
    protected $request;
    protected $currentGroupPrefix = '';
    protected $currentGroupMiddleware = [];

    public function __construct(Request $request) {
        $this->request = $request;
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
        
        // Merge global + route middleware
        $allMiddleware = array_merge($this->middleware, $routeMiddleware);

        return $this->runMiddleware($this->request, $allMiddleware, function($request) use ($match) {
            if ($match) {
                $request->setParams($match['params']);
                return $this->execute($match['callback'], $match['params']);
            }

            // Không khớp bất kỳ route nào → Kiểm tra Redirect 301
            $checkUrl = '/' . ltrim($request->uri, '/');
            $redirect = \App\Models\RedirectModel::where('old_url', $checkUrl)->where('status', 1)->first();
            if ($redirect) {
                header("Location: " . $redirect->new_url, true, 301);
                exit;
            }

            // Không có redirect → 404
            return new Response(view('pages/404', ['com' => trim($request->uri, '/')]), 404);
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
                'middleware' => $routes[$path]['middleware']
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
                    'middleware' => $middleware
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
            $middleware = new $middlewareClass();
            return $middleware->handle($request, $runner);
        };

        return $runner($request);
    }

    protected function execute($callback, array $params = []) {
        if (is_array($callback)) {
            [$class, $method] = $callback;
            if (class_exists($class)) {
                $controller = new $class();
                if (method_exists($controller, $method)) {
                    // Spread params with call_user_func_array
                    $args = array_values($params);
                    array_unshift($args, $this->request);
                    $result = call_user_func_array([$controller, $method], $args);
                    if ($result instanceof Response) return $result;
                    return new Response($result);
                }
            }
        }

        if (is_callable($callback)) {
            $args = array_values($params);
            array_unshift($args, $this->request);
            $result = call_user_func_array($callback, $args);
            if ($result instanceof Response) return $result;
            return new Response($result);
        }

        return new Response('Invalid Route Callback or Controller not found', 500);
    }
}
