<?php
/**
 * ============================================================
 *  Web Routes
 *  Nơi định nghĩa toàn bộ các đường dẫn URL của Website.
 *  Mỗi Controller tự xử lý 404 khi không tìm thấy slug.
 * ============================================================
 */

use App\Controllers\ProductController;
use App\Controllers\HomeController;
use App\Controllers\CartController;
use App\Controllers\AuthController;
use App\Controllers\ContactController;
use App\Controllers\CheckoutController;
use App\Controllers\SearchController;
use App\Controllers\NewsController;
use App\Controllers\ReviewController;
use App\Controllers\PageController;

/**
 * 1. Trang chủ
 */
$router->get('/', [HomeController::class, 'index'])->name('home');

/**
 * 2. Tìm kiếm
 */
$router->get('/search', [SearchController::class, 'index'])->name('search');

/**
 * 3. Liên hệ
 */
$router->get('/lien-he',  [ContactController::class, 'index'])->name('contact.index');
$router->get('/contact',  [ContactController::class, 'index']);
$router->post('/lien-he', [ContactController::class, 'store'])->name('contact.store');
$router->post('/contact', [ContactController::class, 'store']);

/**
 * 4. Giỏ hàng, Thanh toán, Tra cứu (Dynamic CMS Routing)
 * Nạp động từ bảng db_page theo view đã chọn.
 */
try {
    // Kết nối PDO thuần để độc lập, không phụ thuộc Model boot
    $dbConfig = config('database');
    $pdo = new \PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['database']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password']
    );
    
    // Lấy tất cả trang tĩnh thuộc các loại (view) đặc thù
    $stmt = $pdo->query("SELECT alias, view, lang FROM db_page WHERE view IN ('pages/cart/index', 'pages/cart/checkout', 'pages/order-tracking') AND hien_thi = 1");
    if ($stmt) {
        $specialPages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($specialPages as $p) {
            $alias = $p['alias'];
            $lang = $p['lang'];
            $viewType = $p['view'];

            if ($viewType === 'pages/cart/index') {
                $router->get('/' . $alias, [CartController::class, 'index'])->name('cart.index.' . $lang);
                // Đăng ký fallback cho route không có suffix lang
                $router->get('/' . $alias, [CartController::class, 'index'])->name('cart.index');
            } elseif ($viewType === 'pages/cart/checkout') {
                $router->get('/' . $alias, [CheckoutController::class, 'index'])->name('checkout.index.' . $lang);
                $router->post('/' . $alias, [CheckoutController::class, 'store'])->name('checkout.store.' . $lang);
                // Fallback
                $router->get('/' . $alias, [CheckoutController::class, 'index'])->name('checkout.index');
                $router->post('/' . $alias, [CheckoutController::class, 'store'])->name('checkout.store');
            } elseif ($viewType === 'pages/order-tracking') {
                $router->get('/' . $alias, [\App\Controllers\OrderController::class, 'tracking'])->name('order.tracking.' . $lang);
                // Fallback
                $router->get('/' . $alias, [\App\Controllers\OrderController::class, 'tracking'])->name('order.tracking');
            }
        }
    }
} catch (\Exception $e) {
    // Fallback phòng khi DB lỗi
    $router->get('/gio-hang',   [CartController::class,     'index'])->name('cart.index');
    $router->get('/thanh-toan', [CheckoutController::class, 'index'])->name('checkout.index');
    $router->post('/thanh-toan', [CheckoutController::class, 'store'])->name('checkout.store');
    $router->get('/tra-cuu',    [\App\Controllers\OrderController::class, 'tracking'])->name('order.tracking');
}


/**
 * 5. Xác thực (Auth)
 */
$router->get('/dang-nhap',      [AuthController::class, 'login']);
$router->post('/dang-nhap',     [AuthController::class, 'loginPost']);
$router->get('/dang-ky',        [AuthController::class, 'register']);
$router->get('/dang-xuat',      [AuthController::class, 'logout']);
$router->get('/quen-mat-khau',  [AuthController::class, 'forgotPassword']);

/**
 * 6. Nội dung — URL có prefix theo loại (Laravel-style).
 * Controller tự trả 404 nếu slug không tìm thấy trong DB.
 * Slug lấy trong Controller qua: $request->param('slug')
 */
$router->get('/san-pham',        [ProductController::class,                    'index'])->name('product.index');
$router->get('/san-pham/{slug}', [ProductController::class,                    'show'])->name('product.show');
$router->get('/tin-tuc',         [NewsController::class,                       'index'])->name('news.index');
$router->get('/tin-tuc/{slug}',  [NewsController::class,                       'show'])->name('news.show');
$router->get('/danh-muc/{slug}', [\App\Controllers\CategoryController::class,  'show'])->name('category.show');

/**
 * 7. AJAX — Giỏ hàng
 */
$router->post('/ajax/cart/legacy',        [CartController::class, 'legacy']);
$router->post('/ajax/cart/add',           [CartController::class, 'add']);
$router->post('/ajax/cart/update',        [CartController::class, 'update']);
$router->post('/ajax/cart/remove',        [CartController::class, 'remove']);
$router->post('/ajax/cart/coupon',        [CartController::class, 'applyCoupon']);
$router->post('/ajax/cart/coupon/remove', [CartController::class, 'removeCoupon']);
$router->post('/ajax/cart/coupons',       [CartController::class, 'getCoupons']);

/**
 * 8. AJAX — Sản phẩm
 */
$router->post('/ajax/product/legacy',          [ProductController::class, 'legacy']);
$router->post('/ajax/product/quick-view',      [ProductController::class, 'quickView']);
$router->post('/ajax/product/live-search',     [ProductController::class, 'liveSearch']);
$router->post('/ajax/product/recently-viewed', [ProductController::class, 'recentlyViewed']);

/**
 * 9. AJAX — Đánh giá
 */
$router->post('/ajax/reviews/load',  [ReviewController::class, 'load']);
$router->post('/ajax/reviews/media', [ReviewController::class, 'uploadMedia']);

/**
 * 10. AJAX — Địa chỉ
 */
$router->post('/ajax/location/district', [\App\Controllers\LocationController::class, 'district']);
$router->post('/ajax/location/ward',     [\App\Controllers\LocationController::class, 'ward']);

/**
 * 11. Catch-all — Trang tĩnh lưu trong DB (PageModel).
 * PHẢI đặt CUỐI CÙNG — để các route cụ thể ở trên được ưu tiên.
 * Nếu slug không tìm thấy trong PageModel → PageController tự trả 404.
 */
$router->get('/{slug}', [PageController::class, 'show']);
