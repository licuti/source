<?php

namespace App\Controllers;


use App\Models\PostModel;
use App\Models\CategoryModel;

/**
 * NewsController
 * Xử lý danh sách tin tức và chi tiết bài viết.
 */
class NewsController extends Controller {
    /**
     * Danh sách tin tức (trang chuyên mục)
     */
    public function index($request) {
        $row   = $GLOBALS['row'] ?? null;
        
        // Cứu cánh: Nếu gọi qua route tĩnh (VD: /tin-tuc) mà chưa có $row, thử tự tìm category
        if (!$row) {
            $slug = explode('/', ltrim($request->uri, '/'))[0];
            $row = \CategoryModel::where('alias', $slug)->first();
            if ($row) {
                $GLOBALS['row'] = $row;
                // Gọi một instance của PageController để dùng lại hàm registerLanguageLinks
                (new \App\Controllers\PageController())->registerLanguageLinks($row, $slug, \CategoryModel::class);
            }
        }

        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) config('posts.paging', 12);

        // Lấy danh mục con (nếu có)
        $categoryIds = [];
        if ($row) {
            $categoryIds = getCategoryTreeIds($row->id_code);
        }

        $query = PostModel::where('status', \App\Models\PostModel::STATUS_PUBLISH);
        
        if (!empty($categoryIds)) {
            $query->where('id_loai', $categoryIds, 'IN');
        }

        $total  = $query->count();
        $posts  = $query->orderBy('id', 'DESC')
            ->limit($limit, ($page - 1) * $limit)
            ->get();

        if ($row) {
            $translations = \CategoryModel::where('id_code', $row->id_code)->get();
            $urls = [];
            foreach ($translations as $t) {
                $urls[$t->lang] = route('news.show.' . $t->lang, $t->alias);
            }
            \App\Core\App::getInstance()->setLanguageLinks($urls);
        } else {
            $urls = [];
            foreach (config('lang', []) as $l) {
                $urls[$l['code']] = route('news.index.' . $l['code']);
            }
            \App\Core\App::getInstance()->setLanguageLinks($urls);
        }

        return view('pages/news/index', [
            'posts'         => $posts,
            'total_records' => $total,
            'limit'         => $limit,
            'page'          => $page,
            'row'           => $row,
            'com'           => $GLOBALS['com'] ?? '',
            'paging_url'    => getCurrentUrlWithoutPage(),
        ]);
    }

    /**
     * Chi tiết bài viết
     */
    public function show($request) {
        $row = $GLOBALS['row'] ?? null;
        if (!$row) return '404';

        // Tăng lượt xem (chỉ 1 lần / session)
        if (!isset($_SESSION['viewed_posts'][$row->id])) {
            PostModel::where('id', $row->id)->increment('view');
            $_SESSION['viewed_posts'][$row->id] = true;
        }

        $translations = PostModel::where('id_code', $row->id_code)->get();
        $urls = [];
        foreach ($translations as $t) {
            $urls[$t->lang] = route('news.show.' . $t->lang, $t->alias);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        // Lấy bài viết liên quan
        $category = CategoryModel::where('id_code', $row->id_loai)->first();
        $related  = PostModel::where('status', \App\Models\PostModel::STATUS_PUBLISH)
            ->where('id_loai', $row->id_loai)
            ->whereRaw("tt.id != {$row->id}")
            ->orderBy('id', 'DESC')
            ->limit(4)
            ->get();

        return view('pages/news/detail', [
            'row'      => $row,
            'category' => $category,
            'related'  => $related,
            'com'      => $GLOBALS['com'] ?? '',
        ]);
    }
}

