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

// Đọc cấu hình từ SettingModel để biết kiểu định tuyến
$urlStyle = (new \App\Models\SettingModel())->getValue('url_lang_style', 'query');
$defaultLang = config('app.locale', 'vi');

if ($urlStyle === 'path') {
    $supportedLangs = ['en', 'vi']; // Hoặc query DB nếu cần
    foreach ($supportedLangs as $code) {
        if ($code !== $defaultLang) {
            $router->get('/' . $code, [HomeController::class, 'index'])->name('home.' . $code);
        }
    }
}

/**
 * 2. Tìm kiếm
 */
$router->get('/search', [SearchController::class, 'index'])->name('search');

/**
 * 3. Liên hệ (đã tích hợp vào phần 6)
 */

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
    
    $urlStyle = (new \App\Models\SettingModel())->getValue('url_lang_style', 'query');
    $defaultLang = config('app.locale', 'vi');

    // Lấy tất cả trang tĩnh thuộc các loại (view) đặc thù
    $stmt = $pdo->query("SELECT alias, view, lang FROM db_page WHERE view IN ('pages/cart/index', 'pages/cart/checkout', 'pages/order-tracking') AND hien_thi = 1");
    if ($stmt) {
        $specialPages = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($specialPages as $p) {
            $alias = $p['alias'];
            $lang = $p['lang'];
            $viewType = $p['view'];

            $pathPrefix = '';
            if ($urlStyle === 'path' && $lang !== $defaultLang) {
                $pathPrefix = $lang . '/';
            }

            if ($viewType === 'pages/cart/index') {
                $router->get('/' . $pathPrefix . $alias, [CartController::class, 'index'])->name('cart.index.' . $lang);
                // Đăng ký fallback cho route không có suffix lang
                $router->get('/' . $pathPrefix . $alias, [CartController::class, 'index'])->name('cart.index');
            } elseif ($viewType === 'pages/cart/checkout') {
                $router->get('/' . $pathPrefix . $alias, [CheckoutController::class, 'index'])->name('checkout.index.' . $lang);
                $router->post('/' . $pathPrefix . $alias, [CheckoutController::class, 'store'])->name('checkout.store.' . $lang);
                // Fallback
                $router->get('/' . $pathPrefix . $alias, [CheckoutController::class, 'index'])->name('checkout.index');
                $router->post('/' . $pathPrefix . $alias, [CheckoutController::class, 'store'])->name('checkout.store');
            } elseif ($viewType === 'pages/order-tracking') {
                $router->get('/' . $pathPrefix . $alias, [\App\Controllers\OrderController::class, 'tracking'])->name('order.tracking.' . $lang);
                // Fallback
                $router->get('/' . $pathPrefix . $alias, [\App\Controllers\OrderController::class, 'tracking'])->name('order.tracking');
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
 * 6. Nội dung — Đa ngôn ngữ cho các tiền tố tĩnh
 */
$localizedPrefixes = [
    'vi' => ['product' => 'san-pham', 'news' => 'tin-tuc', 'category' => 'danh-muc', 'contact' => 'lien-he'],
    'en' => ['product' => 'product',  'news' => 'news',    'category' => 'category', 'contact' => 'contact'],
];

// Đọc cấu hình từ SettingModel để biết kiểu định tuyến
$urlStyle = (new \App\Models\SettingModel())->getValue('url_lang_style', 'query');
$defaultLang = config('app.locale', 'vi');

foreach ($localizedPrefixes as $lang => $prefixes) {
    // Xác định prefix path (chỉ thêm nếu là path style và không phải ngôn ngữ mặc định)
    $pathPrefix = '';
    if ($urlStyle === 'path' && $lang !== $defaultLang) {
        $pathPrefix = $lang . '/';
    }

    // Sản phẩm
    $router->get('/' . $pathPrefix . $prefixes['product'], [ProductController::class, 'index'])->name('product.index.' . $lang);
    $router->get('/' . $pathPrefix . $prefixes['product'] . '/{slug}', [ProductController::class, 'show'])->name('product.show.' . $lang);
    
    // Tin tức
    $router->get('/' . $pathPrefix . $prefixes['news'], [NewsController::class, 'index'])->name('news.index.' . $lang);
    $router->get('/' . $pathPrefix . $prefixes['news'] . '/{slug}', [NewsController::class, 'show'])->name('news.show.' . $lang);
    
    // Danh mục
    $router->get('/' . $pathPrefix . $prefixes['category'] . '/{slug}', [\App\Controllers\CategoryController::class, 'show'])->name('category.show.' . $lang);

    // Liên hệ
    $router->get('/' . $pathPrefix . $prefixes['contact'], [ContactController::class, 'index'])->name('contact.index.' . $lang);
    $router->post('/' . $pathPrefix . $prefixes['contact'], [ContactController::class, 'store'])->name('contact.store.' . $lang);
}

// Đăng ký fallback cho route chuẩn không có ngôn ngữ (Mặc định lấy tiếng Việt)
$viPrefix = $localizedPrefixes['vi'];
$router->get('/' . $viPrefix['product'], [ProductController::class, 'index'])->name('product.index');
$router->get('/' . $viPrefix['product'] . '/{slug}', [ProductController::class, 'show'])->name('product.show');
$router->get('/' . $viPrefix['news'], [NewsController::class, 'index'])->name('news.index');
$router->get('/' . $viPrefix['news'] . '/{slug}', [NewsController::class, 'show'])->name('news.show');
$router->get('/' . $viPrefix['category'] . '/{slug}', [\App\Controllers\CategoryController::class, 'show'])->name('category.show');
$router->get('/' . $viPrefix['contact'], [ContactController::class, 'index'])->name('contact.index');
$router->post('/' . $viPrefix['contact'], [ContactController::class, 'store'])->name('contact.store');


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
if ($urlStyle === 'path') {
    foreach ($localizedPrefixes as $lang => $prefixes) {
        if ($lang !== $defaultLang) {
            $router->get('/' . $lang . '/{slug}', [PageController::class, 'show']);
        }
    }
}
$router->get('/{slug}', [PageController::class, 'show']);
