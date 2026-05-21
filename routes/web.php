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
$router->get('/', [HomeController::class, 'index']);

/**
 * 2. Tìm kiếm
 */
$router->get('/search', [SearchController::class, 'index']);

/**
 * 3. Liên hệ
 */
$router->get('/lien-he',  [ContactController::class, 'index']);
$router->get('/contact',  [ContactController::class, 'index']);
$router->post('/lien-he', [ContactController::class, 'store']);
$router->post('/contact', [ContactController::class, 'store']);

/**
 * 4. Giỏ hàng & Thanh toán
 */
$router->get('/gio-hang',   [CartController::class,     'index']);
$router->get('/thanh-toan', [CheckoutController::class, 'index']);

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
