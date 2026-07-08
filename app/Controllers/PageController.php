<?php

namespace App\Controllers;

use App\Core\Response;
use App\Models\PageModel;

/**
 * PageController
 *
 * ÄÃ³ng vai trÃ² Smart Dispatcher cho toÃ n bá»™ trang CMS.
 *
 * - CÃ¡c trang thÃ´ng thÆ°á»ng (vá» chÃºng tÃ´i, dá»‹ch vá»¥...) â†’ render view tá»« DB.
 * - CÃ¡c trang Ä‘áº·c biá»‡t (giá» hÃ ng, thanh toÃ¡n, tra cá»©u...) â†’ dispatch sang
 *   Ä‘Ãºng Controller báº±ng báº£ng VIEW_DISPATCH.
 *
 * Chá»‰ cáº§n thÃªm 1 dÃ²ng vÃ o VIEW_DISPATCH Ä‘á»ƒ Ä‘Äƒng kÃ½ trang Ä‘áº·c biá»‡t má»›i.
 */
class PageController extends Controller {

    /**
     * Mapping: cá»™t `view` trong db_page â†’ [Controller, method]
     * ThÃªm trang má»›i: chá»‰ cáº§n thÃªm 1 dÃ²ng vÃ o Ä‘Ã¢y.
     */
    private const VIEW_DISPATCH = [
        'pages/cart/index'    => [CartController::class,     'index'],
        'pages/cart/checkout' => [CheckoutController::class, 'index'],
        'pages/order-tracking'=> [\App\Controllers\OrderController::class, 'tracking'],
    ];

    /**
     * POST dispatch map â€” cho cÃ¡c trang cÃ³ xá»­ lÃ½ form POST riÃªng
     */
    private const POST_DISPATCH = [
        'pages/cart/checkout' => [CheckoutController::class, 'store'],
    ];

    /**
     * Smart dispatch: xá»­ lÃ½ má»i /{slug} request
     */
    public function dispatch($request, array $params = []) {
        $slug = $params['slug'] ?? $request->param('slug') ?? '';

        if (!$slug) {
            return new Response(view('pages/404', ['com' => '']), 404);
        }

        // 1. Cá»©u cÃ¡nh cho danh má»¥c (VD: /ao-khoac)
        $category = \CategoryModel::where('alias', $slug)->first();
        if ($category) {
            $GLOBALS['row'] = $category;
            $this->registerLanguageLinks($category, $slug, \CategoryModel::class);
            
            // Dispatch tá»›i Ä‘Ãºng Controller dá»±a vÃ o module
            if ($category->module == config('modules.product')) {
                return $this->forwardTo(ProductController::class, 'index', $request, $params);
            } elseif ($category->module == config('modules.post')) {
                return $this->forwardTo(NewsController::class, 'index', $request, $params);
            }
            // Fallback render template máº·c Ä‘á»‹nh
            return new Response(view($category->view ?: 'pages/products/index', ['row' => $category, 'com' => $slug]));
        }

        // 2. Tra cá»©u db_page
        $page = PageModel::where('alias', $slug)->first();

        if (!$page) {
            return new Response(view('pages/404', ['com' => $slug]), 404);
        }

        // ÄÄƒng kÃ½ URL dá»‹ch cho trang nÃ y
        $this->registerLanguageLinks($page, $slug, PageModel::class);

        $viewKey = $page->view ?? '';

        // Xá»­ lÃ½ POST (form submit)
        if ($request->method === 'POST' && isset(self::POST_DISPATCH[$viewKey])) {
            [$class, $method] = self::POST_DISPATCH[$viewKey];
            return $this->forwardTo($class, $method, $request, $params);
        }

        // Dispatch sang Controller Ä‘áº·c biá»‡t náº¿u cÃ³
        if (isset(self::VIEW_DISPATCH[$viewKey])) {
            [$class, $method] = self::VIEW_DISPATCH[$viewKey];
            return $this->forwardTo($class, $method, $request, $params);
        }

        // Render trang CMS thÃ´ng thÆ°á»ng
        return new Response(view($viewKey ?: 'pages/page', [
            'row' => $page,
            'com' => $slug,
        ]));
    }

    /**
     * Forward request sang Controller khÃ¡c
     */
    private function forwardTo(string $class, string $method, $request, array $params) {
        $controller = new $class();
        $result = $controller->$method($request, $params);
        return ($result instanceof Response) ? $result : new Response($result);
    }

    /**
     * ÄÄƒng kÃ½ language links dá»±a trÃªn id_code cá»§a trang/danh má»¥c
     */
    public function registerLanguageLinks($page, string $fallbackSlug, string $modelClass) {
        if (empty($page->id_code)) return;

        // Láº¥y táº¥t cáº£ ngÃ´n ngá»¯ cá»§a trang nÃ y â€” táº¯t lang constraint táº¡m thá»i
        $prevConstraint = \App\Core\Model::getGlobalConstraint();
        \App\Core\Model::setGlobalConstraint('');
        $allTranslations = $modelClass::where('id_code', $page->id_code)->get();
        \App\Core\Model::setGlobalConstraint($prevConstraint);

        if (empty($allTranslations)) return;

        $links = [];
        $defaultLang = 'vi';
        foreach ($allTranslations as $t) {
            $langCode = $t->lang ?? $defaultLang;
            $slug     = $t->alias ?? $fallbackSlug;
            // Trang CMS dÃ¹ng catch-all route, cáº§n thÃªm prefix náº¿u khÃ´ng pháº£i ngÃ´n ngá»¯ máº·c Ä‘á»‹nh
            $path = ($langCode !== $defaultLang) ? "{$langCode}/{$slug}" : $slug;
            $links[$langCode] = url($path);
        }

        \App\Core\App::getInstance()->setLanguageLinks($links);
    }
}
