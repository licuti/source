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

        $viewFile = $page->view ?: 'page';

        return new Response(view($viewFile, [
            'row' => $page,
            'com' => $slug,
        ]));
    }
}
