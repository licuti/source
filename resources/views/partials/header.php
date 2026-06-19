<?php
$params = $_GET;

$pageCart = \App\Models\CategoryModel::where('id', 122)->first();
$home = \App\Models\CategoryModel::where('id', 172)->first();

// Lấy cấu hình ngôn ngữ/tiền tệ hiện tại cho JS
$current_lang_oc = defined('_lang') ? _lang : ($_SESSION['lang'] ?? 'vi');
$lang_conf_oc = null;
foreach (config('lang', []) as $l) {
    if ($l['code'] == $current_lang_oc) {
        $lang_conf_oc = $l;
        break;
    }
}
$currency_code_oc = $lang_conf_oc['price'] ?? 'VND';
$currency_rate_oc = ($currency_code_oc == 'USD') ? (1 / config('currency.vnd_usd', 25450)) : 1;
?>
<script>
    var currencyConfig = {
        code: '<?= $currency_code_oc ?>',
        rate: <?= (float) $currency_rate_oc ?>,
        symbol: '<?= $currency_code_oc == 'USD' ? '$' : ' ₫' ?>',
        position: '<?= $currency_code_oc == 'USD' ? 'prefix' : 'suffix' ?>'
    };
    var URLPATH = '<?= URLPATH ?>';
    // Bản đồ URL AJAX tập trung — cập nhật tại đây nếu cần thay đổi endpoint
    var AJAX_ROUTES = {
        cart:          URLPATH + 'ajax/cart/',
        product:       URLPATH + 'ajax/product/',
        reviews:       URLPATH + 'ajax/reviews/',
        location:      URLPATH + 'ajax/location/',
        // Legacy alias (sẽ xóa sau khi toàn bộ code đã dùng AJAX_ROUTES)
        ajax:          URLPATH + 'ajax/product/legacy',
        ajax_cart:     URLPATH + 'ajax/cart/legacy'
    };
    // Backward compat
    var AJAX_URL = AJAX_ROUTES.product + 'live-search';
</script>

<nav class="navbar navbar-expand-lg" aria-label="Offcanvas navbar large">
    <div class="block navbar-top d-none d-lg-block">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-4">
                        <div><i class="fa-light fa-envelope text-x"></i> <?= site('email') ?></div>
                        <div><i class="fa-light fa-mobile text-x"></i> <?= site('hotline') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="block navbar-main">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-auto d-flex justify-content-between align-items-center">
                    <a href="<?= URLPATH ?>" class="navbar-brand">
                        <img src="<?= site('logo') ?>" alt="<?= site('company') ?>" class="logo">
                    </a>
                    <div class="d-flex d-lg-none align-items-center">
                        <a href="<?= url('gio-hang') ?>" class="cart-shopping">
                            <div id="label-quantity" class="label-quantity"><?= count($_SESSION['cart'] ?? []) ?></div>
                            <i class="fa-solid fa-basket-shopping"></i>
                        </a>
                        <button type="button" class="btn-custom btn-popup-search ms-4" data-bs-toggle="modal" data-bs-target="#searchModal">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                        <button class="navbar-toggler ms-4" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-auto flex-lg-fill">
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
                        <div class="offcanvas-header align-items-start">
                             <img src="<?= site('logo') ?>" alt="<?= site('company') ?>" width="100px">
                             <button type="button" class="btn-close btn-close-dark shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body justify-content-center">
                            <?php echo view('partials/header/main-menu') ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-auto d-flex align-items-center gap-4">
                    <button type="button" class="btn-custom btn-popup-search" data-bs-toggle="modal" data-bs-target="#searchModal">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </button>
                    <?php echo view('partials/header/language-switcher') ?>
                    <a href="yeu-thich.html" class="cart-shopping position-relative" title="Sản phẩm yêu thích">
                        <div id="wishlist-count" class="label-quantity header-wishlist-count">0</div>
                        <i class="fa-regular fa-heart"></i>
                    </a>
                    <a href="#offcanvasCart" class="cart-shopping" data-bs-toggle="offcanvas" role="button">
                        <div id="header-cart-count" class="label-quantity header-cart-count"><?= count($_SESSION['cart'] ?? []) ?></div>
                        <i class="fa-solid fa-basket-shopping"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</nav>

<div class="modal fade" id="searchModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <form class="box-search-header" method="get" action="search.html">
                <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm sản phẩm..." value="<?= $params['keyword'] ?? '' ?>">
                <button type="submit" class="btn-custom btn-x">
                    <i class="fa-solid fa-magnifying-glass fa-rotate-90"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($source) && $source == "index"): ?>
    <?php echo view('partials/components/slider'); ?>
<?php else: ?>
    <?php echo view('partials/components/banner'); ?>
<?php endif ?>

<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCart">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">
            <i class="fa-solid fa-basket-shopping me-2"></i> Giỏ hàng của bạn
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <div class="cart-offcanvas-list">
            <?php if (!empty($_SESSION['cart'])): ?>
                <?php
                $total_offcanvas = 0;
                foreach ($_SESSION['cart'] as $key => $item):
                    $total_offcanvas += $item['gia'] * $item['so_luong'];
                    ?>
                    <div class="d-flex mb-3 pb-3 border-bottom offcanvas-cart-item" data-price="<?= $item['gia'] ?>" data-key="<?= $key ?>">
                        <img src="<?= Img($item['hinh_anh']) ?>" alt="Product" class="img-thumbnail me-3" style="width: 70px; height: 70px; object-fit: cover;">
                        <div class="flex-grow-1 position-relative">
                            <?php
                            $sp_name = \ProductModel::where('id_code', $item['id_sp'])->first()->ten ?? 'Sản phẩm';
                            ?>
                            <h6 class="mb-1 text-truncate pe-4"><?= $sp_name ?></h6>
                            <button type="button" class="btn-close btn-delete-offcanvas position-absolute text-danger" style="top:2px; right:0;" data-key="<?= $key ?>"></button>
                            <?php if (!empty($item['thuoctinh_text'])): ?>
                                <small class="text-muted d-block"><?= $item['thuoctinh_text'] ?></small>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-danger"><?= renderPrice($item['gia']) ?></span>
                                <div class="input-group input-group-sm" style="width: 90px;">
                                    <button class="btn btn-outline-secondary offcanvas-qty-btn" type="button" data-dir="down" data-key="<?= $key ?>">-</button>
                                    <input type="text" class="form-control text-center px-1 offcanvas-qty-input" value="<?= $item['so_luong'] ?>" data-key="<?= $key ?>">
                                    <button class="btn btn-outline-secondary offcanvas-qty-btn" type="button" data-dir="up" data-key="<?= $key ?>">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-cart-circle-xmark fa-3x mb-3"></i>
                    <p>Giỏ hàng đang trống.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php if (!empty($_SESSION['cart'])): ?>
        <div class="offcanvas-footer border-top p-3 bg-light">
            <div class="d-flex justify-content-between mb-3">
                <span class="fw-bold">Tổng tiền:</span>
                <span class="fw-bold text-danger fs-5 offcanvas-total-price"><?= renderPrice($total_offcanvas) ?></span>
            </div>
            <a href="<?= url('gio-hang') ?>" class="btn btn-outline-secondary w-100 mb-2">Xem giỏ hàng</a>
            <a href="thanh-toan.html" class="btn btn-primary w-100" style="background-color: var(--cl-x, #f59e0b); border-color: var(--cl-x, #f59e0b);">Thanh toán ngay</a>
        </div>
    <?php endif; ?>
</div>
