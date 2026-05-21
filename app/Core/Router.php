<?php

namespace App\Core;

class Router {
    protected $routes = [];
    protected $middleware = [];
    protected $request;
    protected $currentGroupPrefix = '';

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

    public function group($prefix, $callback) {
        $previousPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix .= $prefix;
        $callback($this);
        $this->currentGroupPrefix = $previousPrefix;
    }

    protected function addRoute($method, $path, $callback) {
        $fullPath = $this->currentGroupPrefix . $path;
        $this->routes[$method][$fullPath] = $callback;
        
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
        return $this->runMiddleware($this->request, function($request) {
            $match = $this->matchRoute($request->method, $request->uri);

            if ($match) {
                $request->setParams($match['params']);
                return $this->execute($match['callback'], $match['params']);
            }

            // Không khớp bất kỳ route nào → 404
            return new Response(view('pages/404', ['com' => trim($request->uri, '/')]), 404);
        });
    }

    /**
     * Khớp URL với route đã đăng ký.
     * Hỗ trợ:
     *   - Exact match:   "/gio-hang"
     *   - Param match:   "/san-pham/{slug}"  →  params = ['slug' => 'ao-thun-nam']
     *
     * @return array|null  ['callback' => ..., 'params' => [...]]  hoặc null nếu không khớp
     */
    protected function matchRoute(string $method, string $path): ?array {
        $routes = $this->routes[$method] ?? [];

        // 1. Fast path — exact match (không có param)
        if (isset($routes[$path])) {
            return ['callback' => $routes[$path], 'params' => []];
        }

        // 2. Pattern match — route có {param}
        foreach ($routes as $routePath => $callback) {
            if (strpos($routePath, '{') === false) continue;

            // Chuyển "/san-pham/{slug}" → regex "#^/san-pham/([^/]+)$#"
            $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            if (preg_match($pattern, $path, $matches)) {
                // Lấy tên các param từ route definition
                preg_match_all('/\{([^}]+)\}/', $routePath, $paramNames);
                $params = array_combine($paramNames[1], array_slice($matches, 1));
                return ['callback' => $callback, 'params' => $params];
            }
        }

        return null;
    }

    protected function runMiddleware($request, $next) {
        $pipeline = array_reverse($this->middleware);

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
                    // Truyền $params như argument thứ 2 để Controller có thể nhận
                    $result = $controller->$method($this->request, $params);
                    if ($result instanceof Response) return $result;
                    return new Response($result);
                }
            }
        }

        if (is_callable($callback)) {
            $result = call_user_func($callback, $this->request, $params);
            if ($result instanceof Response) return $result;
            return new Response($result);
        }

        return new Response('Invalid Route Callback or Controller not found', 500);
    }
}
