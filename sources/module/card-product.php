    <?php
        // === Badges ===
        $total_stock_card = 0;
        $stock_result = $d->simple_fetch("SELECT COALESCE(SUM(so_luong),0) as ts FROM #_sanpham_bienthe WHERE id_sanpham=".(int)$value['id_code']);
        if ($stock_result) {
            $total_stock_card = (int)$stock_result['ts'];
        } elseif (isset($value['so_luong'])) {
            $total_stock_card = (int)$value['so_luong'];
        }
        // "Mới" nếu id nằm trong top 10 ID cao nhất (mới nhất)
        $is_new = false;
        // Hỗ trợ field ngay_dang nếu có, fallback: id_code cao
        if (isset($value['ngay_dang']) && strtotime($value['ngay_dang']) > strtotime('-30 days')) {
            $is_new = true;
        }
        // "Low stock" nếu tồn kho 1-5
        $is_low_stock = ($total_stock_card > 0 && $total_stock_card <= 5);

        // Ảnh hover: lấy ảnh đầu tiên trong album sản phẩm, nếu không có thì dùng ảnh chính
        $hoverImage = $value['hinh_anh'] ?? '';
        $album = $d->o_fet("select hinh_anh from #_sanpham_hinhanh where id_sp = ".(int)$value['id_code']." order by stt ASC, id ASC limit 1");
        if (!empty($album)) {
            $hoverImage = $album[0]['hinh_anh'];
        }
    ?>
    <div class="box-product box-hover-zoom" data-id="<?= (int)$value['id_code'] ?>">
        <div class="box-thumbnail">
            <div class="inner-thumbnail ratio">
                <a <?=createAlias($value)?> >
                    <img src="<?= Img($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover img-product-main">
                    <?php if ($hoverImage): ?>
                        <img src="<?= Img($hoverImage) ?>" alt="<?= $value['ten'] ?>" class="image-cover img-product-hover">
                    <?php endif ?>
                </a>
            </div>
            <!-- Badges -->
            <div class="box-badges">
                <?php if (isset($value['sp_moi']) && $value['sp_moi'] == 1): ?>
                    <span class="badge-product badge-new">Mới</span>
                <?php elseif ($is_new): ?>
                    <span class="badge-product badge-new">Mới</span>
                <?php endif ?>

                <?php if (isset($value['sp_hot']) && $value['sp_hot'] == 1): ?>
                    <span class="badge-product badge-hot">Hot</span>
                <?php elseif (isset($value['noi_bat']) && $value['noi_bat'] == 1): ?>
                    <span class="badge-product badge-hot">Hot</span>
                <?php endif ?>

                <?php if (isset($value['sp_sale']) && $value['sp_sale'] == 1): ?>
                    <span class="badge-product badge-sale-flag">Sale</span>
                <?php elseif ($value['khuyen_mai'] && $value['khuyen_mai'] < $value['gia']): ?>
                    <span class="badge-product badge-sale"><?= getPercentDiscount($value['khuyen_mai'], $value['gia']) ?></span>
                <?php endif ?>

                <?php if (isset($value['sp_top']) && $value['sp_top'] == 1): ?>
                    <span class="badge-product badge-top">Top</span>
                <?php endif ?>

                <?php if ($is_low_stock): ?>
                    <span class="badge-product badge-low-stock">Còn <?= $total_stock_card ?></span>
                <?php endif ?>
            </div>
            <!-- Actions: Quick View + Wishlist -->
            <div class="box-product-actions">
                <button type="button" class="btn-product-action btn-wishlist" data-id="<?= (int)$value['id_code'] ?>" title="Yêu thích" onclick="toggleWishlist(this)">
                    <i class="fa fa-heart"></i>
                </button>
                <button type="button" class="btn-product-action btn-quickview" data-id="<?= (int)$value['id_code'] ?>" title="Xem nhanh" onclick="openQuickView(<?= (int)$value['id_code'] ?>)">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="box-content">
            <?php /*if (!empty($value['category_name'])): ?>
                <div class="box-category-name text-x mb-1 fs-12 text-uppercase fw-500 ls-1">
                    <?= $value['category_name'] ?>
                </div>
            <?php endif;*/ ?>
            <div class="box-title">
                <h3 class="title"><a <?=createAlias($value)?>><?= $value['ten'] ?></a></h3>
            </div>

            <?php
                //$r = getProductRating($value['id_code']);
                //if ($r['total'] > 0) echo renderStars($r['avg'], $r['total'], 14);
            ?>
            
            <?php /* ?>
            <div class="box-excerpt"><?= $value['mo_ta'] ?></div>
            <?php */ ?>
            <div class="box-price">
                <?php if (isset($value['min_price']) && $value['min_price'] > 0): ?>
                    <?php if ($value['min_price'] != $value['max_price']): ?>
                        <div class="label-sale-price">
                            <?= renderPrice($value['min_price']) ?> - <?= renderPrice($value['max_price']) ?>
                        </div>
                    <?php else: ?>
                        <div class="label-sale-price">
                            <?= renderPrice($value['min_price']) ?>
                        </div>
                        <?php if (isset($value['gia']) && $value['gia'] > $value['min_price']): ?>
                            <div class="label-regular-price">
                                <?= renderPrice($value['gia']) ?>
                            </div>
                        <?php endif ?>
                    <?php endif ?>
                <?php else: ?>
                    <?php if ($value['khuyen_mai'] > 0): ?>
                        <div class="label-regular-price"><?= renderPrice($value['gia']) ?></div>
                        <div class="label-sale-price"><?= renderPrice($value['khuyen_mai']) ?></div>
                    <?php else: ?>
                        <div class="label-sale-price"><?= renderPrice($value['gia']) ?></div>
                    <?php endif ?>
                <?php endif ?>
            </div>
            <?php /* ?>
            <div class="box-add-cart mt-2">
                <?php if (isset($value['min_price']) && $value['min_price'] > 0 && ($value['min_price'] != $value['max_price'] || $value['gia'] == 0)): ?>
                    <button type="button" onclick="openQuickView(<?= (int)$value['id_code'] ?>)" class="btn-custom btn-outline-y btn-y py-2 fs-14">Tùy chọn</button>
                <?php else: ?>
                    <button type="button" onclick="event.preventDefault(); quickAddToCart(<?= $value['id_code'] ?>);" class="btn-custom btn-y py-2 fs-14">
                        <?= $d->getTxt(23) ?> <i class="fa-light fa-angle-right"></i>
                    </button>
                <?php endif; ?>
            </div>
            <?php */ ?>
        </div>
    </div>
