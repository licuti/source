<?php

namespace App\Core;

/**
 * App Kernel (Modern Version)
 * Chịu trách nhiệm khởi động toàn bộ hệ thống mà không phụ thuộc vào code cũ.
 */
class App {
    protected static $instance;
    public $request;
    public $router;
    public $view;

    protected function __construct() {
        $this->request = new Request();
        $this->router  = new Router($this->request);
        $this->view    = new View();
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
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

        // 3. Database & Model Booting
        \Model::boot(config('database'));

        // 3.5. Legacy $d Booting cho frontend views
        require_once dirname(__DIR__, 2) . '/admin/lib/class.php';
        $GLOBALS['d'] = new \func_index(config('database'));


        // 4. Boot SiteInfoService — define các hằng số legacy (_logo, _favicon, v.v.)
        $this->bootSiteConstants();

        // 4b. Tự động xác định com từ Request URI và đặt vào $GLOBALS['com'] làm fallback
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
        $this->router->pushMiddleware(\App\Middleware\LanguageMiddleware::class);
        // Middleware bảo vệ mật khẩu toàn trang (nếu được bật trong config)
        if (config('protection', false)) {
            $this->router->pushMiddleware(\App\Middleware\SitePasswordMiddleware::class);
        }

        // 6. Load Routing System
        $this->loadRoutes();

        Logger::info("App kernel booted in modern mode.");
    }

    /**
     * Define các hằng số legacy (_logo, _favicon ...) từ SiteInfoService.
     * Đảm bảo các file template cũ (seo.php, header, footer...) hoạt động đúng.
     */
    protected function bootSiteConstants() {
        try {
            $info = \App\Services\SiteInfoService::getInstance();
            $baseUrl = rtrim(config('urls.base', '/'), '/') . '/';

            // Xây dựng URL hiện tại (đặt chỗ cho $d->fullAddress())
            $currentUrl = $baseUrl . ltrim($this->request->uri, '/');

            $constants = [
                '_logo'         => $info->get('logo'),
                '_favicon'      => $info->get('favicon'),
                '_coppy_right'  => $info->get('coppy_right'),
                '_website'      => $info->get('website'),
                '_ten_cong_ty'  => $info->get('company'),
                '_dia_chi'      => $info->get('address'),
                '_email'        => $info->get('email'),
                '_dien_thoai'   => $info->get('dien_thoai'),
                '_hotline'      => $info->get('hotline'),
                '_thoi_gian'    => $info->get('thoi_gian'),
                '_bando'        => $info->get('map'),
                '_link_map'     => $info->get('link_map'),
                '_zalo'         => $info->get('zalo'),
                '_messenger'    => $info->get('messenger'),
                '_skype'        => $info->get('skype'),
                '_facebook'     => $info->get('facebook'),
                '_twitter'      => $info->get('twitter'),
                '_linkedin'     => $info->get('linkedin'),
                '_youtube'      => $info->get('youtube'),
                '_pinterest'    => $info->get('pinterest'),
                '_instagram'    => $info->get('instagram'),
                '_telegram'     => $info->get('telegram'),
                '_whatsapp'     => $info->get('whatsapp'),
                '_tiktok'       => $info->get('tiktok'),
                '_shoppe'       => $info->get('shoppe'),
                '_sitekey'      => $info->get('site_key'),
                '_secretkey'    => $info->get('secret_key'),
                '_web_page'     => $baseUrl,
                '_url_page'     => $currentUrl,
                'URLPATH'       => $baseUrl,
                '_URLLANG'      => $baseUrl,
                'LANG'          => 'vi',
            ];

            foreach ($constants as $name => $value) {
                if (!defined($name)) {
                    define($name, (string)$value);
                }
            }
        } catch (\Exception $e) {
            Logger::error('bootSiteConstants failed: ' . $e->getMessage());
        }
    }

    /**
     * Nạp các file định nghĩa Route
     */
    protected function loadRoutes() {
        $router = $this->router;
        require_once dirname(dirname(__DIR__)) . '/routes/web.php';
        require_once dirname(dirname(__DIR__)) . '/routes/api.php';
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
