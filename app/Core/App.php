<?php

namespace App\Core;

/**
 * App Kernel (Modern Version)
 * Chịu trách nhiệm khởi động toàn bộ hệ thống mà không phụ thuộc vào code cũ.
 */
class App {
    protected static $instance;
    public $container;
    public $request;
    public $router;
    public $view;
    protected $languageLinks = [];

    protected function __construct() {
        $this->container = new Container();
        $this->request = new Request();
        $this->router  = new Router($this->request, $this->container);
        $this->view    = new View();
        
        // Bind core instances
        $this->container->singleton(Container::class, clone $this->container);
        $this->container->singleton(Request::class, clone $this->request);
        $this->container->singleton(Router::class, clone $this->router);
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setLanguageLinks(array $links) {
        $this->languageLinks = $links;
    }

    public function getLanguageLinks() {
        return $this->languageLinks;
    }

    /**
     * Khởi động các dịch vụ cốt lõi
     */
    public function boot() {
        // 1. Error & Exception Handlers
        set_exception_handler([ExceptionHandler::class, 'handle']);
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
             throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });

        // 2. Output Buffering (Nên giữ lại ở đây để bảo vệ Header toàn cục)
        ob_start();

        // 2.5 Config Loader
        $basePath = dirname(dirname(dirname(__FILE__)));
        $configItems = array_merge(
            [
                'database'           => include $basePath . '/config/database.php',
                'lang'               => include $basePath . '/config/languages.php',
                'route_translations' => include $basePath . '/config/route_translations.php',
                'modules'            => include $basePath . '/config/modules.php',
            ],
            include $basePath . '/config/app.php'
        );
        $this->container->singleton('config', function() use ($configItems) {
            return new \App\Core\Config\Repository($configItems);
        });

        // 3. Database & Model Booting
        $dbConfig = config('database');
        $this->container->singleton(\App\Core\Database\Connection::class, function($c) use ($dbConfig) {
            return new \App\Core\Database\Connection($dbConfig);
        });
        
        \App\Core\Database\Model::boot($dbConfig);
        \App\Core\Database\Model::setContainer($this->container);

        // 4. (Đã gỡ bỏ: bootSiteConstants không còn nằm trong Core để đảm bảo chuẩn Laravel)

        // 5. Tự động xác định com từ Request URI và đặt vào $GLOBALS['com'] làm fallback
        if (empty($GLOBALS['com'])) {
            $uri = trim($this->request->uri, '/');
            $segments = explode('/', $uri);
            $comSegment = $segments[0] ?? '';
            $comSegment = preg_replace('/\.html$/', '', $comSegment);
            $GLOBALS['com'] = $comSegment;
        }

        // 5. Đăng ký các Middleware toàn cục (Global Middlewares)
        // Lưu ý: StartSession nên chạy đầu tiên để các middleware sau có Session dùng
        $this->router->pushMiddleware(\App\Middleware\StartSession::class);
        $this->router->pushMiddleware(\App\Middleware\CsrfMiddleware::class);
        $this->router->pushMiddleware(\App\Middleware\LanguageMiddleware::class);
        $this->router->pushMiddleware(\App\Middleware\MaintenanceMiddleware::class);
        // Middleware bảo vệ mật khẩu toàn trang (nếu được bật trong config)
        if (config('protection', false)) {
            $this->router->pushMiddleware(\App\Middleware\SitePasswordMiddleware::class);
        }

        // Middleware bảo vệ khu vực Admin
        $this->router->pushMiddleware(\App\Middleware\AdminAuthMiddleware::class);

        // 6. Load Routing System
        $this->loadRoutes();

        (new Logger())->info("App kernel booted in modern mode.");
    }

    /**
     * Nạp các file định nghĩa Route
     */
    protected function loadRoutes() {
        $router = $this->router;
        require_once dirname(dirname(__DIR__)) . '/routes/web.php';
        require_once dirname(dirname(__DIR__)) . '/routes/api.php';
        require_once dirname(dirname(__DIR__)) . '/routes/admin.php';
    }

    /**
     * Thực thi Request và trả về Response
     */
    public function run() {
        try {
            $response = $this->router->dispatch();
            if (!$response instanceof \App\Core\Response) {
                $response = new \App\Core\Response($response);
            }
            $response->send();
        } catch (\Exception $e) {
            ExceptionHandler::handle($e);
        }
    }
}
