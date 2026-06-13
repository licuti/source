<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Core\Response;


class ProductController extends Controller {
    /**
     * Danh sách sản phẩm
     */
    public function index($request) {
        $row = $GLOBALS['row'] ?? null;
        
        // Cứu cánh: Nếu gọi qua route tĩnh (VD: /san-pham) mà chưa có $row, thử tự tìm category
        if (!$row) {
            $slug = explode('/', ltrim($request->uri, '/'))[0];
            $row = \CategoryModel::where('alias', $slug)->first();
            if ($row) {
                $GLOBALS['row'] = $row;
                // Gọi một instance của PageController để dùng lại hàm registerLanguageLinks
                (new \App\Controllers\PageController())->registerLanguageLinks($row, $slug, \CategoryModel::class);
            }
        }

        // 1. Chỉ cập nhật view nếu db có hỗ trợ. Hiện db_category không có cột view/luot_xem.
        if (!$row) {
            // Đăng ký URL dịch cho trang index danh sách sản phẩm
            $urls = [];
            foreach (config('lang', []) as $l) {
                $urls[$l['code']] = route('product.index.' . $l['code']);
            }
            \App\Core\App::getInstance()->setLanguageLinks($urls);
        }

        // 2. Tham số phân trang & lọc
        $limit = (int)config('product.paging', 16) ?: 16;
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $sort  = $_GET['sort'] ?? 'default';

        // 3. Xác định danh mục
        $selectedCats = array_filter(array_map('intval', (array)($_GET['cats'] ?? [])));
        if (!empty($selectedCats)) {
            $categoryIds = [];
            foreach ($selectedCats as $scid) {
                $categoryIds = array_merge($categoryIds, getCategoryTreeIds($scid));
            }
            $categoryIds = array_unique($categoryIds);
        } else {
            $categoryIds = $row ? getCategoryTreeIds($row->id_code) : [];
        }

        // 4. Xây dựng Query
        $query = ProductModel::where('hien_thi', 1);
        if (!empty($categoryIds)) {
            $query->where('id_loai', $categoryIds, 'IN');
        }

        // --- Lọc giá ---
        $price_range = trim($_GET['price'] ?? '');
        if ($price_range !== '') {
            [$pMin, $pMax] = array_pad(explode('-', $price_range, 2), 2, 0);
            $pMin = (float)$pMin; $pMax = (float)$pMax;
            $effectivePriceSP = "IF(khuyen_mai > 0, khuyen_mai, gia)";
            $tableVariant = \ProductVariantModel::tableName();
            $variantSub = "SELECT 1 FROM $tableVariant v WHERE v.id_sanpham = id_code AND IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)";
            $noVariantCondition = "NOT EXISTS (SELECT 1 FROM $tableVariant WHERE id_sanpham = id_code)";
            
            if ($pMax > 0) {
                $query->whereRaw("(EXISTS ($variantSub BETWEEN $pMin AND $pMax) OR ($noVariantCondition AND $effectivePriceSP BETWEEN $pMin AND $pMax))");
            } else {
                $query->whereRaw("(EXISTS ($variantSub >= $pMin) OR ($noVariantCondition AND $effectivePriceSP >= $pMin))");
            }
        }

        // --- Sắp xếp ---
        switch ($sort) {
            case 'newest': $query->orderBy('id', 'DESC'); break;
            case 'price_asc': $query->orderBy('gia', 'ASC'); break;
            case 'price_desc': $query->orderBy('gia', 'DESC'); break;
            default: $query->orderBy('so_thu_tu', 'ASC')->orderBy('id', 'DESC'); break;
        }

        // 5. Thực thi
        $total_records = $query->count();
        $sanpham = $query->limit($limit, ($page - 1) * $limit)->with('variants')->get();
        $priceRange = ProductModel::getPriceRange($categoryIds);
        
        // 6. Trả về View
        return view('pages/products/index', [
            'sanpham' => $sanpham,
            'total_records' => $total_records,
            'limit' => $limit,
            'page' => $page,
            'sort' => $sort,
            'min_price_range' => $priceRange['min'],
            'max_price_range' => max($priceRange['max'], $priceRange['min'] + 1000000),
            'row' => $row,
            'com' => $GLOBALS['com'] ?? '',
            'paging_url' => getCurrentUrlWithoutPage()
        ]);
    }

    /**
     * Chi tiết sản phẩm
     * Hỗ trợ 2 cách gọi:
     *   1. Route tham số:  GET /san-pham/{slug}  → $request->param('slug')
     *   2. Dynamic route:  GET /ten-sp.html       → $GLOBALS['row'] (DynamicRouteController)
     */
    public function show($request, array $params = []) {
        // Ưu tiên slug từ route param (cách mới: /san-pham/{slug})
        $slug = $params['slug'] ?? $request->param('slug');

        if ($slug) {
            $row = ProductModel::where('alias', $slug)->first();
        } else {
            // Fallback: slug được inject bởi DynamicRouteController (cách cũ)
            $row = $GLOBALS['row'] ?? null;
        }

        if (!$row) return '404';

        // 1. Tăng lượt xem
        if (!empty($_SESSION['viewed_products'][$row->id])) {
            ProductModel::where('id', $row->id)->increment('view');
            $_SESSION['viewed_products'][$row->id] = true;
        }

        // 2. Nạp lại chi tiết Sản phẩm với Eager Loading (Variants, Albums)
        $row = ProductModel::where('id_code', $row->id_code)->with('variants', 'albums')->first();
        if (!$row) return '404';

        // Đăng ký URL dịch cho trang chi tiết sản phẩm
        $translations = ProductModel::where('id_code', $row->id_code)->get();
        $urls = [];
        foreach ($translations as $t) {
            $urls[$t->lang] = route('product.show.' . $t->lang, $t->alias);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        // 3. Eager Load lồng nhau (Nested Relations) cho thuộc tính của biến thể
        if (!empty($row->variants)) {
            $variants = $row->variants;
            \ProductVariantModel::loadNestedAttributes($variants);
            $row->variants = $variants;
        }

        // 4. Lấy danh mục & Sản phẩm liên quan
        $category = CategoryModel::where('id_code', $row->id_loai)->first();
        $related = ProductModel::where('hien_thi', 1)
            ->where('id_loai', $row->id_loai)
            ->where('id', $row->id, '!=')
            ->orderBy('id', 'DESC')
            ->limit(8)
            ->with('variants')
            ->get();

        // 5. Tính toán dữ liệu biến thể
        $min_variant_price = -1;
        $max_variant_price = -1;
        $total_variant_qty = 0;
        
        $gallery_products = !empty($row->albums) ? $row->albums : [];
        $existing_images = [$row->hinh_anh];
        foreach ($gallery_products as $gp) {
            $existing_images[] = $gp->hinh_anh;
        }

        if (!empty($row->variants)) {
            foreach ($row->variants as $variant) {
                $v_price = $variant->khuyen_mai > 0 ? $variant->khuyen_mai : $variant->gia;
                if ($min_variant_price == -1 || $v_price < $min_variant_price) $min_variant_price = $v_price;
                if ($max_variant_price == -1 || $v_price > $max_variant_price) $max_variant_price = $v_price;
                $total_variant_qty += (int)$variant->so_luong;

                // Gộp ảnh biến thể vào gallery nếu chưa có
                if (!empty($variant->hinh_anh) && !in_array($variant->hinh_anh, $existing_images)) {
                    $gallery_products[] = (object)['hinh_anh' => $variant->hinh_anh];
                    $existing_images[] = $variant->hinh_anh;
                }
            }
        }
        $variant_attributes = buildVariantAttributes($row->variants ?? []);
        
        // 6. Tính điểm đánh giá
        $rating = getProductRating($row->id_code);

        // 7. Cấu hình tính năng hiển thị
        $config_detail = [
            'show_video'           => true,
            'show_gallery'         => true,
            'show_variants'        => true,
            'show_rating'          => true,
            'show_related'         => true,
            'show_recently_viewed' => true,
            'sticky_cart'          => true,
            'show_social'          => true,
            'show_description'     => true,
            'show_content'         => true,
        ];

        // 8. Render View
        return view('pages/products/detail', [
            'row'                => $row,
            'category'           => $category,
            'related'            => $related,
            'gallery_products'   => $gallery_products,
            'variants'           => $row->variants ?? [],
            'variant_attributes' => $variant_attributes,
            'min_variant_price'  => $min_variant_price,
            'max_variant_price'  => $max_variant_price,
            'total_variant_qty'  => $total_variant_qty,
            'sp_avg_rating'      => $rating['avg'],
            'sp_total_rating'    => $rating['total'],
            'config_detail'      => $config_detail,
            'com'                => $GLOBALS['com'] ?? '',
        ]);
    }

    /**
     * Xem nhanh sản phẩm qua modal (AJAX)
     * POST /ajax/product/quick-view
     */
    public function quickView($request) {
        $id = (int) $request->input('id', 0);
        if (!$id) return Response::json([]);

        $sp = ProductModel::where('id_code', $id)->where('hien_thi', 1)->first();
        if (!$sp) return Response::json([]);

        // Lấy tất cả biến thể và nạp quan hệ đệ quy (Thay cho vòng lặp N+1 cũ)
        $variants = \ProductVariantModel::where('id_sanpham', $id)->get();
        \ProductVariantModel::loadNestedAttributes($variants);
        $variantsArr = (array) $variants;

        // Gom nhóm thuộc tính + giá trị
        $grouped_attrs = buildVariantAttributes($variants);

        // Tính giá min/max
        $prices = array_map(fn($v) => $v->khuyen_mai > 0 ? $v->khuyen_mai : $v->gia, (array) $variants);
        $min_price = !empty($prices) ? min($prices) : ($sp->khuyen_mai > 0 ? $sp->khuyen_mai : $sp->gia);
        $max_price = !empty($prices) ? max($prices) : $min_price;

        return Response::json([
            'id'         => $sp->id_code,
            'ten'        => $sp->ten,
            'alias'      => $sp->alias,
            'hinh_anh'   => $sp->hinh_anh,
            'mo_ta'      => $sp->mo_ta,
            'gia'        => $sp->gia,
            'khuyen_mai' => $sp->khuyen_mai,
            'min_price'  => $min_price,
            'max_price'  => $max_price,
            'so_luong'   => $sp->so_luong,
            'ma_sp'      => $sp->ma_sp,
            'variants'   => $variantsArr,
            'attrs'      => array_values($grouped_attrs),
            'urlpath'    => defined('URLPATH') ? URLPATH : '',
        ]);
    }

    /**
     * Legacy endpoint cho Frontend cũ
     * POST /ajax/product/legacy
     */
    public function legacy($request) {
        $do = $request->input('do');
        switch ($do) {
            case 'live_search':
                return $this->liveSearch($request);
            case 'get_huyen':
                return (new \App\Controllers\LocationController())->district($request);
            case 'get_xa':
                return (new \App\Controllers\LocationController())->ward($request);
            default:
                return Response::json(['success' => false, 'message' => 'Legacy do not found'], 404);
        }
    }

    /**
     * Tìm kiếm sản phẩm theo từ khoá (Live Search AJAX)
     * POST /ajax/product/live-search
     */
    public function liveSearch($request) {
        $keyword = trim($request->input('keyword', ''));
        $id_code = (int) $request->input('id_code', 0);

        if ($keyword === '') return Response::json(['html' => '']);

        $q = ProductModel::where('ten', '%' . $keyword . '%', 'LIKE')
                          ->where('hien_thi', 1);

        if ($id_code > 0) {
            $ids = getCategoryTreeIds($id_code);
            if (!empty($ids)) $q = $q->whereIn('id_loai', $ids);
        }

        $products = $q->orderBy('so_thu_tu', 'ASC')->limit(6)->get();

        $html = '';
        if (!empty($products)) {
            foreach ($products as $p) {
                $price_val = $p->khuyen_mai > 0 ? $p->khuyen_mai : $p->gia;
                $price_html = $price_val > 0 ? renderPrice($price_val) : __('Liên hệ');

                $old_price = '';
                if ($p->khuyen_mai > 0) {
                    $old_price = '<span class="ls-old-price">' . renderPrice($p->gia) . '</span>';
                }

                $html .= '<a href="' . url($p->alias . '.html') . '" class="ls-item">'
                    . '<div class="ls-img"><img src="' . getImageUrl($p->hinh_anh) . '" alt="' . e($p->ten) . '"></div>'
                    . '<div class="ls-info">'
                    .   '<div class="ls-name">' . e($p->ten) . '</div>'
                    .   '<div class="ls-prices"><span class="ls-price">' . $price_html . '</span>' . $old_price . '</div>'
                    . '</div>'
                    . '</a>';
            }
            $html .= '<a href="' . url('search.html?keyword=' . urlencode($keyword)) . '" class="ls-view-all">'
                . __('Xem tất cả kết quả cho') . ' "' . e($keyword) . '"</a>';
        } else {
            $html = '<div class="ls-no-result">' . __('Không tìm thấy sản phẩm nào') . '</div>';
        }

        return Response::json(['html' => $html]);
    }

    /**
     * Lấy danh sách sản phẩm đã xem gần đây (AJAX)
     * POST /ajax/product/recently-viewed
     */
    public function recentlyViewed($request) {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (empty($ids)) return Response::json([]);

        $products = ProductModel::whereIn('id_code', $ids)->where('hien_thi', 1)->get();

        // Giữ thứ tự theo mảng ids gốc
        $map = [];
        foreach ((array) $products as $p) $map[$p->id_code] = $p;
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($map[$id])) $ordered[] = $map[$id];
        }

        return Response::json(['urlpath' => defined('URLPATH') ? URLPATH : '', 'products' => $ordered]);
    }
}

