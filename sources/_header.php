<?php
    $params = getQueryParams();

    $pageCart = $d->getCate(122);
    $pageCheckout = $d->getCate(254);

    $home = $d->getCate(172);

    // Lấy cấu hình ngôn ngữ/tiền tệ hiện tại cho JS
    $current_lang_oc = defined('_lang') ? _lang : ($_SESSION['lang'] ?? 'vi');
    $lang_conf_oc = null;
    foreach ($config['lang'] as $l) {
        if ($l['code'] == $current_lang_oc) {
            $lang_conf_oc = $l;
            break;
        }
    }
    $currency_code_oc = $lang_conf_oc['price'] ?? 'VND';
    $currency_rate_oc = getExchangeRate('VND', $currency_code_oc);
?>
<script>
    var currencyConfig = {
        code: '<?= $currency_code_oc ?>',
        rate: <?= (float) $currency_rate_oc ?>,
        symbol: '<?= $currency_code_oc == 'USD' ? '$' : ' ₫' ?>',
        position: '<?= $currency_code_oc == 'USD' ? 'prefix' : 'suffix' ?>'
    };
    var URLPATH = '<?= URLPATH ?>';
    var AJAX_URL = '<?= URLPATH ?>sources/ajax/ajax.php';
</script>

<?php ?>

<nav class="navbar navbar-expand-lg" aria-label="Offcanvas navbar large">
    <div class="block navbar-main">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-auto d-flex justify-content-between align-items-center order-1">
                    <a href="<?= URLPATH ?>" class="navbar-brand">
                        <img src="<?= _logo ?>" alt="<?= $info['company'] ?>" class="logo">
                    </a>
                </div>
                <div class="col-auto d-flex align-items-center gap-4 order-2 order-lg-3 ms-auto">
    
                    <?php //include _source . "/module/header/search-header.php" ?>
                    
                    <button type="button" class="btn-custom btn-popup-search" data-bs-toggle="modal"
                        data-bs-target="#searchModal">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-search-icon lucide-search"><path d="m21 21-4.34-4.34"/><circle cx="11" cy="11" r="8"/></svg>
                    </button>

                    <?php //include _source . "/module/header/language-switcher-dropdown.php" ?>
                    
                    <a href="yeu-thich.html" class="cart-shopping position-relative" title="Sản phẩm yêu thích">
                        <div id="wishlist-count" class="label-quantity header-wishlist-count">0</div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-heart-icon lucide-heart"><path d="M2 9.5a5.5 5.5 0 0 1 9.591-3.676.56.56 0 0 0 .818 0A5.49 5.49 0 0 1 22 9.5c0 2.29-1.5 4-3 5.5l-5.492 5.313a2 2 0 0 1-3 .019L5 15c-1.5-1.5-3-3.2-3-5.5"/></svg>
                    </a>

                    <a href="#offcanvasCart" class="cart-shopping" data-bs-toggle="offcanvas" role="button" aria-controls="offcanvasCart">
                        <div id="header-cart-count" class="label-quantity header-cart-count"><?= count($_SESSION['cart'] ?? []) ?></div>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-handbag-icon lucide-handbag"><path d="M2.048 18.566A2 2 0 0 0 4 21h16a2 2 0 0 0 1.952-2.434l-2-9A2 2 0 0 0 18 8H6a2 2 0 0 0-1.952 1.566z"/><path d="M8 11V6a4 4 0 0 1 8 0v5"/></svg>
                    </a>

                    <?php // LMS MasterStudy (D8): nút vào trang chọn vai trò LMS. Chỉ THÊM thẻ <a> + CSS scoped (btn-lms-login), không sửa code cũ. ?>
                    <link rel="stylesheet" href="<?= URLPATH ?>lms/public/css/lms-header-button.css">
                    <a href="<?= URLPATH ?>lms/" class="btn-lms-login" title="Đăng nhập LMS MasterStudy" aria-label="Đăng nhập LMS MasterStudy">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 3 3 7l9 4 7.5-3.333"/>
                            <path d="M6 10.5V15c0 1.657 2.686 3 6 3s6-1.343 6-3v-4.5"/>
                            <path d="M21 7v5"/>
                        </svg>
                    </a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar"
                        aria-label="Toggle navigation">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-menu-icon lucide-menu"><path d="M4 5h16"/><path d="M4 12h16"/><path d="M4 19h16"/></svg>
                    </button>
                </div>
                <div class="col-lg-auto flex-lg-fill order-3 order-lg-2">
                    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasNavbar"
                        aria-labelledby="offcanvasNavbarLabel">
                        <div class="offcanvas-header align-items-start">
                            <img src="<?= _logo ?>" alt="<?= $info['company'] ?>" width="100px">
                                <button type="button" class="btn-close btn-close-dark shadow-none"
                                    data-bs-dismiss="offcanvas" aria-label="Close"></button>
                        </div>
                        <div class="offcanvas-body justify-content-center">
                            <?php include _source . "/module/header/main-menu.php" ?>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
    </div>
</nav>

<!-- Modal -->
<div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-transparent border-0">
            <form class="box-search-header" method="get" action="search.html">
                <input type="text" name="keyword" class="form-control" placeholder="<?= $d->getTxt(57) ?>"
                    value="<?= $params['keyword'] ?>">
                <button type="submit" class="btn-custom btn-x"><i
                        class="fa-solid fa-magnifying-glass fa-rotate-90 me-1"></i></button>
            </form>
        </div>
    </div>
</div>

<!-- Offcanvas Cart -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCart" aria-labelledby="offcanvasCartLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold" id="offcanvasCartLabel">
            <i class="fa-solid fa-basket-shopping me-2"></i> Giỏ hàng của bạn
        </h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Khu vực hiển thị sản phẩm trong giỏ -->
        <div class="cart-offcanvas-list">
            <?php if (!empty($_SESSION['cart'])): ?>
                <?php
                $total_offcanvas = 0;
                foreach ($_SESSION['cart'] as $key => $item):
                    $total_offcanvas += $item['gia'] * $item['so_luong'];
                    ?>
                    <div class="d-flex mb-3 pb-3 border-bottom offcanvas-cart-item" data-price="<?= $item['gia'] ?>"
                        data-key="<?= $key ?>">
                        <img src="<?= Img($item['hinh_anh']) ?>" alt="Product" class="img-thumbnail me-3"
                            style="width: 70px; height: 70px; object-fit: cover;">
                        <div class="flex-grow-1 position-relative">
                            <?php
                            $temp_sp = $d->simple_fetch("SELECT ten FROM #_sanpham WHERE id_code = {$item['id_sp']}");
                            $sp_name = $temp_sp ? $temp_sp['ten'] : 'Sản phẩm';
                            ?>
                            <h6 class="mb-1 text-truncate pe-4" style="max-width: 200px;"><?= $sp_name ?></h6>
                            <button type="button" class="btn-close btn-delete-offcanvas position-absolute text-danger"
                                aria-label="Xóa" data-key="<?= $key ?>" style="top:2px; right:0; font-size: 0.65rem;"></button>
                            <?php if (!empty($item['thuoctinh_text'])): ?>
                                <small class="text-muted d-block"><?= $item['thuoctinh_text'] ?></small>
                            <?php endif; ?>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="fw-bold text-y"><?= renderPrice($item['gia']) ?></span>
                                <div class="input-group input-group-sm" style="width: 90px;">
                                    <button class="btn btn-outline-secondary offcanvas-qty-btn" type="button" data-dir="down"
                                        data-key="<?= $key ?>">-</button>
                                    <input type="text" class="form-control text-center px-1 offcanvas-qty-input"
                                        value="<?= $item['so_luong'] ?>" data-key="<?= $key ?>">
                                    <button class="btn btn-outline-secondary offcanvas-qty-btn" type="button" data-dir="up"
                                        data-key="<?= $key ?>">+</button>
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
                <span class="fw-bold text-y fs-5 offcanvas-total-price"><?= renderPrice($total_offcanvas) ?></span>
            </div>
            <a <?= createAlias($pageCart) ?> class="btn-custom btn-outline btn-x w-100 mb-2">Xem giỏ hàng</a>
            <a <?= createAlias($pageCheckout) ?> class="btn-custom btn-solid btn-y w-100"
                style="background-color: var(--cl-x, #f59e0b); border-color: var(--cl-x, #f59e0b);">Thanh toán ngay</a>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($source) && $source == "index"): ?>
    <?php include 'module/slider.php'; ?>
<?php else: ?>
    <?php include 'module/banner.php'; ?>
<?php endif ?>