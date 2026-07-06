<?php
/**
 * ============================================================
 *  Web Routes — Phiên bản gọn, dễ bảo trì
 *
 *  Nguyên tắc:
 *  - Chỉ định nghĩa 1 bộ route duy nhất (slug tiếng Việt mặc định).
 *  - LanguageMiddleware đã tự cắt prefix /en/ trước khi Router xử lý.
 *  - Dịch slug & thêm prefix /{lang}/ được xử lý trong hàm route().
 *  - Mọi trang CMS & trang đặc biệt (giỏ hàng, thanh toán...) đều
 *    đi qua catch-all → PageController::dispatch().
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

// ————————————————————————————————————————
// 1. Trang chủ & tìm kiếm
// ————————————————————————————————————————
$router->get('/',       [HomeController::class,   'index'])->name('home');
$router->get('/search', [SearchController::class, 'index'])->name('search');

// ————————————————————————————————————————
// 2. Nội dung (slug theo ngôn ngữ mặc định — vi)
//    Middleware đã dịch /en/ -> / nên chỉ cần 1 bộ
// ————————————————————————————————————————
$t = config('route_translations', []); // ['product' => ['vi'=>'san-pham', 'en'=>'product'], ...]

$router->get('/' . ($t['product']['vi']  ?? 'san-pham'),         [ProductController::class, 'index'])->name('product.index');
$router->get('/' . ($t['product']['vi']  ?? 'san-pham') . '/{slug}', [ProductController::class, 'show'])->name('product.show');

$router->get('/' . ($t['news']['vi']     ?? 'tin-tuc'),          [NewsController::class, 'index'])->name('news.index');
$router->get('/' . ($t['news']['vi']     ?? 'tin-tuc')  . '/{slug}', [NewsController::class, 'show'])->name('news.show');

$router->get('/' . ($t['category']['vi'] ?? 'danh-muc') . '/{slug}', [\App\Controllers\CategoryController::class, 'show'])->name('category.show');

$router->get( '/' . ($t['contact']['vi'] ?? 'lien-he'), [ContactController::class, 'index'])->name('contact.index');
$router->post('/' . ($t['contact']['vi'] ?? 'lien-he'), [ContactController::class, 'store'])->name('contact.store');

$router->post('/submit-form/{id}', [\App\Controllers\Frontend\FormController::class, 'submit'])->name('frontend.form.submit');

// ————————————————————————————————————————
// 3. Xác thực (Auth)
// ————————————————————————————————————————
$router->get( '/' . ($t['login']['vi']    ?? 'dang-nhap'), [AuthController::class, 'login'])->name('login');
$router->post('/' . ($t['login']['vi']    ?? 'dang-nhap'), [AuthController::class, 'loginPost']);
$router->get( '/' . ($t['register']['vi'] ?? 'dang-ky'),   [AuthController::class, 'register'])->name('register');
$router->get( '/' . ($t['logout']['vi']   ?? 'dang-xuat'), [AuthController::class, 'logout'])->name('logout');
$router->get('/quen-mat-khau', [AuthController::class, 'forgotPassword'])->name('forgot-password');

// ————————————————————————————————————————
// 4. AJAX
// ————————————————————————————————————————
$router->group('/ajax', function($r) {
    // Giỏ hàng
    $r->post('/cart/legacy',        [CartController::class, 'legacy']);
    $r->post('/cart/add',           [CartController::class, 'add']);
    $r->post('/cart/update',        [CartController::class, 'update']);
    $r->post('/cart/remove',        [CartController::class, 'remove']);
    $r->post('/cart/coupon',        [CartController::class, 'applyCoupon']);
    $r->post('/cart/coupon/remove', [CartController::class, 'removeCoupon']);
    $r->post('/cart/coupons',       [CartController::class, 'getCoupons']);
    // Sản phẩm
    $r->post('/product/legacy',          [ProductController::class, 'legacy']);
    $r->post('/product/quick-view',      [ProductController::class, 'quickView']);
    $r->post('/product/live-search',     [ProductController::class, 'liveSearch']);
    $r->post('/product/recently-viewed', [ProductController::class, 'recentlyViewed']);
    // Đánh giá
    $r->post('/reviews/load',  [ReviewController::class, 'load']);
    $r->post('/reviews/media', [ReviewController::class, 'uploadMedia']);
    // Địa chỉ
    $r->post('/location/district', [\App\Controllers\LocationController::class, 'district']);
    $r->post('/location/ward',     [\App\Controllers\LocationController::class, 'ward']);
});

// ————————————————————————————————————————
// Sitemap Động (Dynamic Sitemap)
// ————————————————————————————————————————
$router->get('/sitemap.xml', [\App\Controllers\Frontend\SitemapController::class, 'index'])->name('sitemap.index');
$router->get('/sitemap-posts.xml', [\App\Controllers\Frontend\SitemapController::class, 'posts'])->name('sitemap.posts');
$router->get('/sitemap-products.xml', [\App\Controllers\Frontend\SitemapController::class, 'products'])->name('sitemap.products');
$router->get('/sitemap-categories.xml', [\App\Controllers\Frontend\SitemapController::class, 'categories'])->name('sitemap.categories');

// ————————————————————————————————————————
// 5. Catch-all — Trang CMS + trang đặc biệt (giỏ hàng, thanh toán, tra cứu...)
//    PHẢI đặt CUỐI CÙNG.
// ————————————————————————————————————————
$router->any('/{slug}', [PageController::class, 'dispatch']);
