<?php

namespace App\Controllers;


use NewsModel;
use CategoryModel;

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
        $page  = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) config('posts.paging', 12);

        // Lấy danh mục con (nếu có)
        $categoryIds = [];
        if ($row) {
            $categoryIds = getCategoryTreeIds($row->id_code);
        }

        $query = NewsModel::where('hien_thi', 1);
        
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
            NewsModel::where('id', $row->id)->increment('view');
            $_SESSION['viewed_posts'][$row->id] = true;
        }

        $translations = NewsModel::where('id_code', $row->id_code)->get();
        $urls = [];
        foreach ($translations as $t) {
            $urls[$t->lang] = route('news.show.' . $t->lang, $t->alias);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        // Lấy bài viết liên quan
        $category = CategoryModel::where('id_code', $row->id_loai)->first();
        $related  = NewsModel::where('hien_thi', 1)
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

