<?php

namespace App\Controllers;


use App\Models\ProductModel;

/**
 * SearchController
 * Xử lý tìm kiếm sản phẩm và bài viết.
 */
class SearchController extends Controller {
    /**
     * Hiển thị kết quả tìm kiếm
     */
    public function index($request) {
        $keyword = trim($request->get('keyword', ''));
        $page    = max(1, (int) $request->get('page', 1));
        $limit   = 12;

        $results = [];
        $total   = 0;

        if (!empty($keyword)) {
            $query = ProductModel::where('hien_thi', 1)
                ->whereRaw("(sp.ten LIKE '%$keyword%' OR sp.alias LIKE '%$keyword%' OR sp.ma_sp LIKE '%$keyword%')");

            $total   = $query->count();
            $results = $query->orderBy('id', 'DESC')
                ->limit($limit, ($page - 1) * $limit)
                ->with('variants')
                ->get();
        }

        return view('pages/search', [
            'title'         => 'Tìm kiếm: ' . e($keyword),
            'keyword'       => $keyword,
            'results'       => $results,
            'total_records' => $total,
            'page'          => $page,
            'limit'         => $limit,
        ]);
    }
}

