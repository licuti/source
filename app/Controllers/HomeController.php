<?php

namespace App\Controllers;

use App\Core\Request;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\PostModel;


class HomeController extends Controller {
    /**
     * Xử lý hiển thị trang chủ
     */
    public function index(Request $request) {
        $latestNews = PostModel::where('status', 1)
            ->orderBy('id', 'DESC')
            ->limit(4)
            ->get();
        $pageProduct = CategoryModel::where('id', 100)
            ->where('status', 1)
            ->with('translations')
            ->first('id, parent_id');

        $list_id_product = CategoryModel::getChildrenIds(100);

        $featuredProducts = ProductModel::where('category_id', $list_id_product, 'IN')
            ->where('is_featured', 1)
            ->where('status', 1)
            ->with('category')
            ->with('variants')
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

