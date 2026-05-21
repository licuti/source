    <?php
        /**
         * Chống lỗi Undefined variable: value
         * Hỗ trợ các tên biến phổ biến khác như $item, $product
         */
        if (!isset($value)) {
            if (isset($item)) $value = $item;
            elseif (isset($product)) $value = $product;
            else return; // Thoát nếu không có dữ liệu
        }

        // Chuyển đổi Array sang Object nếu cần để đồng nhất cách gọi $value->field
        if (is_array($value)) {
            $value = (object)$value;
        }

        // === Badges ===
        $total_stock_card = 0;
        // Sử dụng dữ liệu đã Eager Load thay vì Query N+1
        if (!empty($value->variants)) {
            $total_stock_card = array_sum(array_column($value->variants, 'so_luong'));
            
            // Tự động tính min/max price từ variants
            $prices = [];
            foreach ($value->variants as $v) {
                $prices[] = ($v->khuyen_mai > 0) ? $v->khuyen_mai : $v->gia;
            }
            if (!empty($prices)) {
                $value->min_price = min($prices);
                $value->max_price = max($prices);
            }
        } elseif (isset($value->so_luong)) {
            $total_stock_card = (int)$value->so_luong;
        }
        // "Mới" nếu id nằm trong top 10 ID cao nhất (mới nhất)
        $is_new = false;
        // Hỗ trợ field ngay_dang nếu có, fallback: id_code cao
        if (isset($value->ngay_dang) && strtotime($value->ngay_dang) > strtotime('-30 days')) {
            $is_new = true;
        }
        // "Low stock" nếu tồn kho 1-5
        $is_low_stock = ($total_stock_card > 0 && $total_stock_card <= 5);
    ?>
    <div class="box-product box-hover-zoom" data-id="<?= (int)$value->id_code ?>">
        <div class="box-thumbnail">
            <div class="inner-thumbnail ratio ratio-1x1">
                <a href="<?= route('product.show', $value->alias) ?>">
                    <img src="<?= Img($value->hinh_anh) ?>" alt="<?= $value->ten ?>" class="image-cover">
                </a>
            </div>
            <!-- Badges -->
            <div class="box-badges">
                <?= getProductBadge($value) ?>
            </div>
            <!-- Actions: Quick View + Wishlist -->
            <div class="box-product-actions">
                <button type="button" class="btn-product-action btn-wishlist" data-id="<?= (int)$value->id_code ?>" title="Yêu thích" onclick="toggleWishlist(this)">
                    <i class="fa fa-heart"></i>
                </button>
                <button type="button" class="btn-product-action btn-quickview" data-id="<?= (int)$value->id_code ?>" title="Xem nhanh" onclick="openQuickView(<?= (int)$value->id_code ?>)">
                    <i class="fa fa-eye"></i>
                </button>
            </div>
        </div>
        <div class="box-content">
            <?= renderProductCategory($value) ?>
            <div class="box-title">
                <h3 class="title">
                    <a href="<?= route('product.show', $value->alias) ?>" class="text-decoration-none text-dark"><?= $value->ten ?></a>
                </h3>
            </div>
            <?= renderProductStars($value->id_code) ?>
            <div class="box-excerpt"><?= $value->mo_ta ?></div>
            <div class="box-price">
                <?= renderProductPrice($value) ?>
            </div>
            <div class="box-add-cart mt-2">
                <?php if (isset($value->min_price) && $value->min_price > 0 && ($value->min_price != $value->max_price || $value->gia == 0)): ?>
                    <button type="button" onclick="openQuickView(<?= (int)$value->id_code ?>)" class="btn btn-outline-primary btn-sm w-100" style="border-radius:20px;">Tùy chọn</button>
                <?php else: ?>
                    <button type="button" onclick="event.preventDefault(); quickAddToCart(<?= $value->id_code ?>);" class="btn btn-primary btn-sm w-100" style="border-radius:20px; background-color: var(--cl-x, #f59e0b); border-color: var(--cl-x, #f59e0b);">
                        <i class="fa fa-shopping-cart me-1"></i> Thêm giỏ hàng
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
