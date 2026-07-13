<?php
/**
 * View: Danh sách sản phẩm
 * Toàn bộ logic đã được chuyển sang App\Controllers\ProductController
 */
?>

<div class="block">
    <div class="container-fluid">
        <!-- Tiêu đề & điều hướng -->
        <div class="row align-items-center mb-4 g-3">
            <div class="col-lg">
                <?= renderBreadcrumbs($row ? [$row] : [['ten' => 'Sản phẩm', 'slug' => 'san-pham']]) ?>
                <h1 class="main-title h2 mb-0">
                    <?= $row ? htmlspecialchars($row->ten) : 'Sản phẩm' ?>
                    <small class="text-muted fs-6 fw-normal ms-2">(<?= number_format($total_records) ?> sản phẩm)</small>
                </h1>
            </div>
            <div class="col-lg-auto d-flex align-items-center gap-2">
                <!-- Nút filter mobile -->
                <button class="btn btn-light border d-lg-none" type="button"
                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasFilter">
                    <i class="fa-solid fa-filter me-2"></i> Bộ lọc
                </button>

                <!-- Sắp xếp -->
                <form action="" method="get" id="sort-form" class="d-flex align-items-center gap-2">
                    <span class="d-none d-md-inline text-muted small">Sắp xếp:</span>
                    <select name="sort" class="form-select form-select-sm shadow-none"
                            onchange="this.form.submit()" style="min-width:150px;">
                        <option value="default"    <?= $sort === 'default'    ? 'selected' : '' ?>>Mặc định</option>
                        <option value="newest"     <?= $sort === 'newest'     ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="price_asc"  <?= $sort === 'price_asc'  ? 'selected' : '' ?>>Giá tăng dần</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Giá giảm dần</option>
                    </select>
                    <?php
                    // Giữ lại tất cả filter hiện tại khi đổi sort
                    foreach (['price', 'in_stock', 'min_rating', 'key'] as $k) {
                        if (!empty($_GET[$k])) echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($_GET[$k]).'">';
                    }
                    foreach (['cats', 'attrs'] as $k) {
                        foreach ((array)($_GET[$k] ?? []) as $v) {
                            echo '<input type="hidden" name="'.htmlspecialchars($k).'[]" value="'.htmlspecialchars($v).'">';
                        }
                    }
                    ?>
                </form>
            </div>
        </div>

        <!-- Nội dung chính -->
        <div class="row g-4">
            <!-- Sidebar Desktop -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="sticky-top" style="top:100px;z-index:10;">
                    <?php echo view('partials/components/shop-sidebar', [
                        'com' => $com ?? '',
                        'min_price_range' => $min_price_range ?? 0,
                        'max_price_range' => $max_price_range ?? 0
                    ]); ?>
                </div>
            </div>

            <!-- Lưới sản phẩm -->
            <div class="col-lg-9">
                <?php if (!empty($sanpham)): ?>
                    <div class="row g-3 g-lg-4 content-pagination">
                        <?php foreach ($sanpham as $value): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <?php echo view('partials/components/card-product', ['value' => $value]); ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                <?php else: ?>
                    <div class="py-5 text-center bg-light rounded-4 border">
                        <i class="fa fa-search fa-4x mb-4 text-muted opacity-25"></i>
                        <h2 class="h5 text-dark">Không tìm thấy sản phẩm nào</h2>
                        <p class="text-muted small">Hãy thử thay đổi bộ lọc hoặc xóa bộ lọc đang áp dụng.</p>
                        <a href="<?= ($com ?: 'san-pham') ?>.html" class="btn btn-primary rounded-pill px-4 mt-2">Xóa tất cả bộ lọc</a>
                    </div>
                <?php endif ?>

                <!-- Phân trang -->
                <?php if ($total_records > $limit): ?>
                    <div class="mt-5 d-flex justify-content-center">
                        <?= paging($total_records, $limit, $page, $paging_url) ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<!-- Offcanvas Filter (Mobile) -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasFilter">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold">Bộ lọc sản phẩm</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <?php echo view('partials/components/shop-sidebar', [
                        'com' => $com ?? '',
                        'min_price_range' => $min_price_range ?? 0,
                        'max_price_range' => $max_price_range ?? 0
                    ]); ?>
    </div>
</div>

