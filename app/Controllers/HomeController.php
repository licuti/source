<?php

namespace App\Controllers;

use CategoryModel;
use ProductModel;
use App\Models\PostModel;


class HomeController extends Controller {
    /**
     * Xử lý hiển thị trang chủ
     */
    public function index($request) {
        $latestNews = PostModel::where('status', 'publish')
            ->orderBy('id', 'DESC')
            ->limit(4)
            ->get();
        $pageProduct = CategoryModel::query()
            ->where('id_code', 100)
            ->where('hien_thi', 1)
            ->first('ten, id_code');

        $list_id_product = CategoryModel::getChildrenIds(100);

        $featuredProducts = ProductModel::where('id_loai', $list_id_product, 'IN')
            ->where('tieu_bieu', 1)
            ->where('hien_thi', 1)
            ->withCategory()
            ->with('variants')
            ->orderBy('so_thu_tu')
            ->orderBy('id', 'DESC')
            ->limit(24)
            ->get();

        return view('pages/home/index', [
            'title'            => 'Trang chủ - ' . config('app.name'),
            'featuredProducts' => $featuredProducts,
            'latestNews'       => $latestNews
        ]);
    }
}

