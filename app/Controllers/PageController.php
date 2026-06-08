<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\PageModel;

/**
 * PageController
 *
 * Đóng vai trò Smart Dispatcher cho toàn bộ trang CMS.
 *
 * - Các trang thông thường (về chúng tôi, dịch vụ...) → render view từ DB.
 * - Các trang đặc biệt (giỏ hàng, thanh toán, tra cứu...) → dispatch sang
 *   đúng Controller bằng bảng VIEW_DISPATCH.
 *
 * Chỉ cần thêm 1 dòng vào VIEW_DISPATCH để đăng ký trang đặc biệt mới.
 */
class PageController extends Controller {

    /**
     * Mapping: cột `view` trong db_page → [Controller, method]
     * Thêm trang mới: chỉ cần thêm 1 dòng vào đây.
     */
    private const VIEW_DISPATCH = [
        'pages/cart/index'    => [CartController::class,     'index'],
        'pages/cart/checkout' => [CheckoutController::class, 'index'],
        'pages/order-tracking'=> [\App\Controllers\OrderController::class, 'tracking'],
    ];

    /**
     * POST dispatch map — cho các trang có xử lý form POST riêng
     */
    private const POST_DISPATCH = [
        'pages/cart/checkout' => [CheckoutController::class, 'store'],
    ];

    /**
     * Smart dispatch: xử lý mọi /{slug} request
     */
    public function dispatch($request, array $params = []) {
        $slug = $params['slug'] ?? $request->param('slug') ?? '';

        if (!$slug) {
            return new Response(view('pages/404', ['com' => '']), 404);
        }

        // 1. Cứu cánh cho danh mục (VD: /ao-khoac)
        $category = \CategoryModel::where('alias', $slug)->first();
        if ($category) {
            $GLOBALS['row'] = $category;
            $this->registerLanguageLinks($category, $slug, \CategoryModel::class);
            
            // Dispatch tới đúng Controller dựa vào module
            if ($category->module == config('modules.product')) {
                return $this->forwardTo(ProductController::class, 'index', $request, $params);
            } elseif ($category->module == config('modules.post')) {
                return $this->forwardTo(NewsController::class, 'index', $request, $params);
            }
            // Fallback render template mặc định
            return new Response(view($category->view ?: 'pages/products/index', ['row' => $category, 'com' => $slug]));
        }

        // 2. Tra cứu db_page
        $page = PageModel::where('alias', $slug)->first();

        if (!$page) {
            return new Response(view('pages/404', ['com' => $slug]), 404);
        }

        // Đăng ký URL dịch cho trang này
        $this->registerLanguageLinks($page, $slug, PageModel::class);

        $viewKey = $page->view ?? '';

        // Xử lý POST (form submit)
        if ($request->method === 'POST' && isset(self::POST_DISPATCH[$viewKey])) {
            [$class, $method] = self::POST_DISPATCH[$viewKey];
            return $this->forwardTo($class, $method, $request, $params);
        }

        // Dispatch sang Controller đặc biệt nếu có
        if (isset(self::VIEW_DISPATCH[$viewKey])) {
            [$class, $method] = self::VIEW_DISPATCH[$viewKey];
            return $this->forwardTo($class, $method, $request, $params);
        }

        // Render trang CMS thông thường
        return new Response(view($viewKey ?: 'pages/page', [
            'row' => $page,
            'com' => $slug,
        ]));
    }

    /**
     * Forward request sang Controller khác
     */
    private function forwardTo(string $class, string $method, $request, array $params) {
        $controller = new $class();
        $result = $controller->$method($request, $params);
        return ($result instanceof Response) ? $result : new Response($result);
    }

    /**
     * Đăng ký language links dựa trên id_code của trang/danh mục
     */
    public function registerLanguageLinks($page, string $fallbackSlug, string $modelClass) {
        if (empty($page->id_code)) return;

        // Lấy tất cả ngôn ngữ của trang này — tắt lang constraint tạm thời
        $prevConstraint = \Model::getGlobalConstraint();
        \Model::setGlobalConstraint('');
        $allTranslations = $modelClass::where('id_code', $page->id_code)->get();
        \Model::setGlobalConstraint($prevConstraint);

        if (empty($allTranslations)) return;

        $links = [];
        $defaultLang = 'vi';
        foreach ($allTranslations as $t) {
            $langCode = $t->lang ?? $defaultLang;
            $slug     = $t->alias ?? $fallbackSlug;
            // Trang CMS dùng catch-all route, cần thêm prefix nếu không phải ngôn ngữ mặc định
            $path = ($langCode !== $defaultLang) ? "{$langCode}/{$slug}" : $slug;
            $links[$langCode] = url($path);
        }

        \App\Core\App::getInstance()->setLanguageLinks($links);
    }
}
