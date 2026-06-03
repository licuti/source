<?php

namespace App\Controllers;

use App\Core\Response;

/**
 * PageController
 * Xử lý các trang tĩnh lưu trong DB (bảng db_page).
 * Được gọi bởi catch-all route: GET /{slug}
 */
class PageController extends Controller {

    public function show($request, array $params = []) {
        $slug = $params['slug'] ?? $request->param('slug');

        if (!$slug) {
            return new Response(view('pages/404', ['com' => '']), 404);
        }

        $page = \App\Models\PageModel::where('alias', $slug)->first();

        if (!$page) {
            return new Response(view('pages/404', ['com' => $slug]), 404);
        }

        // Đăng ký URL dịch cho trang tĩnh
        $translations = \App\Models\PageModel::where('id_code', $page->id_code)->get();
        $urls = [];
        foreach ($translations as $t) {
            // Trang tĩnh sử dụng catch-all route (/{slug})
            $urls[$t->lang] = url($t->alias);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        $viewFile = $page->view ?: 'page';

        return new Response(view($viewFile, [
            'row' => $page,
            'com' => $slug,
        ]));
    }
}
