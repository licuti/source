<?php 
    $config_detail = [
        'show_video'           => true,  // Bật/Tắt video trong Gallery
        'show_gallery'         => true,  // Bật/Tắt ảnh trong Gallery
        'show_variants'        => true,  // Bật/Tắt chọn biến thể (màu sóc, kích thước)
        'show_rating'          => true,  // Bật/Tắt đánh giá sao & tab bình luận
        'show_related'         => true,  // Bật/Tắt sản phẩm tương tự
        'show_recently_viewed' => false,  // Bật/Tắt sản phẩm vừa xem
        'sticky_cart'          => true,  // Bật/Tắt thanh mua hàng cố định
        'show_social'          => true,  // Bật/Tắt nút chia sẻ mạng xã hội
        'show_description'     => true,  // Bật/Tắt mô tả ngắn
        'show_content'         => true,  // Bật/Tắt nội dung chi tiết (Tab)
    ];
    
    $gallery_products = [];
    if($config_detail['show_gallery']) {
        $gallery_products = $d->o_fet("select * from #_sanpham_hinhanh where id_sp = ".$row['id_code']." ");
    }

    $id_loai = $category['id_code'].$d->getIdsub($category['id_code']);

    $related = [];
    if ($config_detail['show_related']) {
        $related = $d->o_fet("select sp.*, 
            (select ten from #_category where id_code = sp.id_loai and lang = '".$_SESSION['lang']."') as category_name,
            (select min(if(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code) as min_price,
            (select max(if(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code) as max_price
            from #_sanpham sp where sp.id_code <> '".$row['id_code']."' and id_loai in ($id_loai) and sp.hien_thi=1 "._where_lang." order by sp.so_thu_tu ASC, sp.id DESC limit 20");
    }

    $thong_so = json_decode($row['thong_so_kt'], true) ? json_decode($row['thong_so_kt'], true) : "";

    // Lấy biến thể sản phẩm
    $variants = [];
    $variant_attributes = []; // Dùng để build UI chọn thuộc tính
    $min_variant_price = -1;
    $max_variant_price = -1;
    $total_variant_qty = 0; // Tổng số lượng tất cả biến thể

    if ($config_detail['show_variants']) {
        $variants = $d->o_fet("SELECT * FROM #_sanpham_bienthe WHERE id_sanpham = ".$row['id_code']." ORDER BY id ASC");
    }

    foreach ($variants as &$variant) {
        // Tìm giá thấp nhất & cao nhất để hiển thị ban đầu
        $v_price = $variant['khuyen_mai'] > 0 ? $variant['khuyen_mai'] : $variant['gia'];
        if ($min_variant_price == -1 || $v_price < $min_variant_price) {
            $min_variant_price = $v_price;
        }
        if ($max_variant_price == -1 || $v_price > $max_variant_price) {
            $max_variant_price = $v_price;
        }

        $total_variant_qty += $variant['so_luong']; // Cộng dồn số lượng

        $attrs = $d->o_fet("SELECT btt.id_thuoctinh, btt.id_thuoctinh_giatri, tt.ten as ten_thuoctinh, tt.loai, ttg.ten as ten_giatri, ttg.gia_tri 
                            FROM #_sanpham_bienthe_thuoctinh btt
                            JOIN #_thuoctinh tt ON btt.id_thuoctinh = tt.id_code
                            JOIN #_thuoctinh_giatri ttg ON btt.id_thuoctinh_giatri = ttg.id_code
                            WHERE btt.id_bienthe = {$variant['id']}");
        $variant['attributes'] = $attrs;
        
        // Gom nhóm thuộc tính để build UI
        foreach ($attrs as $attr) {
            $attr_id = $attr['id_thuoctinh'];
            if (!isset($variant_attributes[$attr_id])) {
                $variant_attributes[$attr_id] = [
                    'id' => $attr_id,
                    'ten' => $attr['ten_thuoctinh'],
                    'loai' => $attr['loai'],
                    'values' => []
                ];
            }
            // Chỉ thêm giá trị nếu chưa có
            $found = false;
            foreach ($variant_attributes[$attr_id]['values'] as $val) {
                if ($val['id'] == $attr['id_thuoctinh_giatri']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $variant_attributes[$attr_id]['values'][] = [
                    'id' => $attr['id_thuoctinh_giatri'],
                    'ten' => $attr['ten_giatri'],
                    'gia_tri' => $attr['gia_tri']
                ];
            }
        }
    }
    unset($variant);
    
    // Convert variants array sang JSON để xử lý JS dễ dàng
    $variants_json = json_encode($variants, JSON_UNESCAPED_UNICODE);

    // Get URL parameters to auto-select variants
    $params = $_GET;
    $params_json = json_encode($params, JSON_UNESCAPED_UNICODE);

    // Gộp ảnh biến thể vào thư viện ảnh nếu chưa có (Đã ẩn theo yêu cầu không gom ảnh biến thể vào slider ban đầu)
    /*
    foreach ($variants as $variant) {
        if (!empty($variant['hinh_anh'])) {
            $exists = false;
            if ($variant['hinh_anh'] == $row['hinh_anh']) {
                $exists = true;
            } else {
                foreach ($gallery_products as $gp) {
                    if ($gp['hinh_anh'] == $variant['hinh_anh']) {
                        $exists = true;
                        break;
                    }
                }
            }
            if (!$exists) {
                $gallery_products[] = ['hinh_anh' => $variant['hinh_anh']];
            }
        }
    }
    */

    // Tính điểm đánh giá trung bình để hiển thị ở đầu trang
    $sp_avg_rating  = 0;
    $sp_total_rating = 0;
    if ($config_detail['show_rating']) {
        $rating_summary = $d->simple_fetch("SELECT COUNT(id) as total, COALESCE(AVG(danh_gia), 0) as avg_rating FROM #_binhluan WHERE id_sanpham = " . (int)$row['id_code'] . " AND trang_thai = 1 AND parent = 0 AND danh_gia > 0");
        $sp_avg_rating  = isset($rating_summary['avg_rating']) ? round($rating_summary['avg_rating'], 1) : 0;
        $sp_total_rating = isset($rating_summary['total']) ? (int)$rating_summary['total'] : 0;
    }
?>
<div class="block page single-product-page">
    <div class="container-fluid">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-6">
                <div class="row flex-lg-row-reverse g-2">
                    <div class="col-lg-9">
                        <div class="swiper slide-show-product">
                            <div class="swiper-wrapper">
                                <?php if($config_detail['show_video'] && !empty($row['video'])): ?>
                                    <div class="swiper-slide video-slide">
                                        <a href="https://www.youtube.com/watch?v=<?= $row['video'] ?>" data-fancybox="gallery">
                                            <img src="https://img.youtube.com/vi/<?= $row['video'] ?>/hqdefault.jpg" class="image-cover">
                                            <div class="play-button-overlay">
                                                <i class="fa-solid fa-circle-play"></i>
                                            </div>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <div class="swiper-slide main-image-slide">
                                    <a href="<?= getImageUrl($row['hinh_anh']) ?>" data-fancybox="gallery" id="main-product-image-link" data-original="<?= getImageUrl($row['hinh_anh']) ?>">
                                        <img src="<?= getImageUrl($row['hinh_anh']) ?>" class="image-cover" id="main-product-image" data-original="<?= getImageUrl($row['hinh_anh']) ?>">
                                    </a>
                                </div>
                                <?php if (count($gallery_products)): ?>
                                    <?php foreach ($gallery_products as $key => $value): ?>
                                        <div class="swiper-slide">
                                            <a href="<?= getImageUrl($value['hinh_anh']) ?>" data-fancybox="gallery">
                                                <img src="<?= getImageUrl($value['hinh_anh']) ?>" class="image-cover">
                                            </a>
                                    </div>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 d-flex flex-column gap-2">
                        <div thumbsSlider="" class="swiper slide-thumb-product flex-fill">
                            <div class="swiper-wrapper">
                                <?php if($config_detail['show_video'] && !empty($row['video'])): ?>
                                    <div class="swiper-slide video-thumb text-center d-flex align-items-center justify-content-center" style="background: #000;">
                                        <img src="https://img.youtube.com/vi/<?= $row['video'] ?>/default.jpg" class="image-cover opacity-75">
                                        <div class="play-button-icon position-absolute text-white fs-4">
                                            <i class="fa-solid fa-play"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="swiper-slide main-image-thumb">
                                    <img src="<?= getImageUrl($row['hinh_anh']) ?>" class="image-cover" id="main-product-thumb" data-original="<?= getImageUrl($row['hinh_anh']) ?>">
                                </div>

                                <?php if (count($gallery_products) > 0): ?>
                                    <?php foreach ($gallery_products as $key => $value): ?>
                                        <div class="swiper-slide">
                                            <img src="<?= getImageUrl($value['hinh_anh']) ?>" class="image-cover">
                                        </div>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="swiper-button-next slide-show-product_next"><i class="fa-sharp fa-solid fa-angle-up"></i></div>
                            <div class="swiper-button-prev slide-show-product_prev"><i class="fa-sharp fa-solid fa-angle-down"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <form method="POST" action="" id="form-cart">
                    <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                    <input type="hidden" value="add-to-cart" name="action" />
                    <input type="hidden" value="<?= $row['id_code'] ?>" name="id_sp" />
                    <input type="hidden" value="" name="id_bienthe" id="id_bienthe" />
                    <input type="hidden" value="<?= $row['khuyen_mai'] > 0 ? $row['khuyen_mai'] : $row['gia']?> " name="gia" id="form_gia" />
                    <div class="box-detail-product">
                        <h2 class="single-title"><?= $row['ten'] ?></h2>
                        <div class="single-price">
                            <?php if ($config_detail['show_variants'] && count($variants) > 0 && $min_variant_price > 0): ?>
                                <?php if ($min_variant_price != $max_variant_price): ?>
                                    <div class="single-price-discount" id="display_price_discount">
                                        <?= renderPrice($min_variant_price) ?> - <?= renderPrice($max_variant_price) ?>
                                    </div>
                                    <div class="single-price-regular" id="display_price_regular" style="display:none;"></div>
                                    <div class="label_discount_price" id="display_percent_discount" style="display:none;"></div>
                                <?php else: ?>
                                    <div class="single-price-discount" id="display_price_discount"><?= renderPrice($min_variant_price) ?></div>
                                    <div class="single-price-regular" id="display_price_regular" style="display:none;"></div>
                                    <div class="label_discount_price" id="display_percent_discount" style="display:none;"></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($row['khuyen_mai'] > 0): ?>
                                    <div class="single-price-discount" id="display_price_discount"><?= renderPrice($row['khuyen_mai']) ?></div>
                                    <div class="single-price-regular" id="display_price_regular"><?= renderPrice($row['gia']) ?></div>
                                    <div class="label_discount_price" id="display_percent_discount">
                                        <?= getPercentDiscount($row['khuyen_mai'], $row['gia']) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="single-price-discount" id="display_price_discount"><?= renderPrice($row['gia']) ?></div>
                                    <div class="single-price-regular" id="display_price_regular" style="display:none;"></div>
                                    <div class="label_discount_price" id="display_percent_discount" style="display:none;"></div>
                                <?php endif ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($sp_total_rating > 0): ?>
                        <div class="single-rate mb-2">
                            <?= renderStars($sp_avg_rating, $sp_total_rating, 18) ?>
                        </div>
                        <?php endif; ?>

                        <div class="mb-2 fw-bold">
                            <a href="">Hướng dẫn chọn size</a>
                        </div>

                        <?php 
                            $initial_stock_qty = ($config_detail['show_variants'] && count($variants) > 0) ? (int)$total_variant_qty : (int)$row['so_luong'];
                            $is_in_stock = $initial_stock_qty > 0;
                        ?>
                        <div class="single-stock mb-2">
                            <span id="display_stock" class="<?= $is_in_stock ? 'text-success' : 'text-danger' ?> fw-semibold">
                                <i class="fa <?= $is_in_stock ? 'fa-check-circle' : 'fa-times-circle' ?> me-1"></i><?= $is_in_stock ? 'Còn hàng' : 'Hết hàng' ?>
                            </span>
                        </div>

                        <?php if ($config_detail['show_description'] && $row['mo_ta']): ?>
                            <div class="single-description mb-3">
                                <?= $row['mo_ta'] ?>
                            </div>
                        <?php endif ?>

                        <!-- hiển thị layout để chọn biến thể ở đây -->
                        <?php if ($config_detail['show_variants'] && !empty($variant_attributes)): ?>
                            <div class="product-variants mb-4">
                                <?php foreach ($variant_attributes as $attr): ?>
                                    <div class="variant-group mb-3" data-attr-id="<?= $attr['id'] ?>" data-attr-type="<?= $attr['loai'] ?>">
                                        <label class="fw-bold mb-1">
                                            <?= htmlspecialchars($attr['ten']) ?>: <span class="selected-variant-text fw-normal"></span>
                                        </label>
                                        
                                        <?php if ($attr['loai'] == 'select'): ?>
                                            <select class="form-select variant-select form-control">
                                                <option value="">Chọn <?= htmlspecialchars($attr['ten']) ?></option>
                                                <?php foreach ($attr['values'] as $val): ?>
                                                    <option value="<?= $val['id'] ?>"><?= htmlspecialchars($val['ten']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else: ?>
                                            <div class="variant-options">
                                                <?php foreach ($attr['values'] as $val): ?>
                                                    <?php if ($attr['loai'] == 'color'): ?>
                                                        <div class="variant-item variant-item-color" data-val-id="<?= $val['id'] ?>" style="background-color: <?= htmlspecialchars($val['gia_tri']) ?>;" title="<?= htmlspecialchars($val['ten']) ?>">
                                                        </div>
                                                    <?php elseif ($attr['loai'] == 'image'): ?>
                                                        <div class="variant-item variant-item-image" data-val-id="<?= $val['id'] ?>" title="<?= htmlspecialchars($val['ten']) ?>">
                                                            <img src="<?= getImageUrl($val['gia_tri']) ?>" alt="<?= htmlspecialchars($val['ten']) ?>">
                                                        </div>
                                                    <?php else: // label ?>
                                                        <div class="variant-item variant-item-text" data-val-id="<?= $val['id'] ?>">
                                                            <?= htmlspecialchars($val['ten']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                                <div id="variant-out-of-stock-msg" style="color: red; display: none; margin-top: 10px; font-weight: bold;">Sản phẩm với tùy chọn này đã hết hàng hoặc không tồn tại.</div>
                            </div>
                        <?php endif; ?>

                        <div class="single-group-button mt-4">
                            <div class="box-quantity">
                                <button class="btn-sub" type="button">-</button>
                                <input type="text" name="so_luong" value="1" id="soluong">
                                <button class="btn-add" type="button">+</button>
                            </div>
                            <button type="button" class="btn-custom btn-solid btn-y btn-add-cart" onclick="add_to_cart(0)"><?= $d->getTxt(23) ?></button>
                            <button type="button" class="btn-custom btn-solid btn-y btn-buy-now" onclick="add_to_cart(1)"><?= $d->getTxt(151) ?></button>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="<?= $row['link_shopee'] ?>" class="btn-custom btn-solid btn-buy-shoppe">Mua trên Shopee</a>
                            <a href="<?= $row['link_tiktok'] ?>" class="btn-custom btn-solid btn-buy-tiktok">Mua trên TikTok Shop</a>
                        </div>

                        <?php if ($row['ma_sp'] || ($config_detail['show_variants'] && count($variants) > 0)): ?>
                            <div class="single-code mt-3 pt-3 mb-2 border-top">
                                <span class="fw-semibold">Mã sản phẩm:</span>
                                <span id="display_sku"><?= $row['ma_sp'] ? $row['ma_sp'] : 'Đang cập nhật' ?></span>
                            </div>
                        <?php endif ?>

                        <?php if ($category): ?>
                            <div class="single-category">
                                <span class="fw-semibold"><?= $d->getTxt(3) ?>:</span>
                                <span><?= $category['ten'] ?></span>
                            </div>
                        <?php endif ?>

                        <?php if ($config_detail['show_social']): ?>
                            <ul class="group-share-social mt-2">
                                <span class="me-2"><strong><?= $d->getTxt(113) ?>:</strong></span>
                                <li>
                                    <a href="https://www.facebook.com/sharer.php?u=<?= _url_page ?>" target="_blank" rel="nofollow">
                                        <img width="30px" height="30" src="https://img.icons8.com/color/100/facebook.png" alt="facebook"/>
                                    </a>
                                </li>
                                <li>
                                    <a href="https://www.linkedin.com/shareArticle?mini=true&amp;url=<?= _url_page ?>" target="_blank" rel="nofollow">
                                        <img width="30" height="30" src="https://img.icons8.com/fluency/100/linkedin.png" alt="linkedin"/>
                                    </a>
                                </li>

                                <li>
                                    <a href="https://twitter.com/share?url=<?= _url_page ?>&amp;text=Simple%20Share%20Buttons&amp;hashtags=simplesharebuttons" target="_blank" rel="nofollow">
                                        <img width="30" height="30" src="https://img.icons8.com/color/100/twitter--v1.png" alt="twitter--v1"/>
                                    </a>
                                </li>
                                <li>
                                    <a href="javascript:void((function()%7Bvar%20e=document.createElement('script');e.setAttribute('type','text/javascript');e.setAttribute('charset','UTF-8');e.setAttribute('src','https://assets.pinterest.com/js/pinmarklet.js?r='+Math.random()*99999999);document.body.appendChild(e)%7D)());" target="_blank" rel="nofollow">
                                        <img width="30" height="30" src="https://img.icons8.com/color/100/pinterest--v1.png" alt="pinterest--v1"/>
                                    </a>
                                </li>
                            </ul>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        <!-- tab product -->
        <div class="row mt-4 mt-lg-5">
            <div class="col-12">
                <ul class="nav nav-tabs" id="single-tab-product" role="tablist">
                    <?php if($config_detail['show_content']): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="content-product" data-bs-toggle="tab" data-bs-target="#tab-content-product" type="button" role="tab" aria-controls="tab-content-product" aria-selected="true"><?= $d->getTxt(56) ?></button>
                    </li>
                    <?php endif; ?>

                    <?php if($config_detail['show_rating']): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= (!$config_detail['show_content']) ? 'active' : '' ?>" id="content-product" data-bs-toggle="tab" data-bs-target="#tab-content-review" type="button" role="tab" aria-controls="tab-content-review" aria-selected="true"><?= $d->getTxt(60) ?></button>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="tab-content mt-3" id="single-tab-content">
                    <?php if($config_detail['show_content']): ?>
                    <div class="tab-pane fade show active" id="tab-content-product" role="tabpanel" aria-labelledby="content-product" tabindex="0">
                        <?php 
                            $noiDungHienThi = $row['noi_dung'] ? $row['noi_dung'] : $d->getTxt(141);
                            if ($row['noi_dung'] && strip_tags($row['noi_dung']) === $row['noi_dung']) {
                                $noiDungHienThi = nl2br($row['noi_dung']);
                            }
                        ?>
                        <?= $noiDungHienThi ?>
                    </div>
                    <?php endif; ?>

                    <?php if($config_detail['show_rating']): ?>
                    <div class="tab-pane fade <?= (!$config_detail['show_content']) ? 'show active' : '' ?>" id="tab-content-review" role="tabpanel" aria-labelledby="content-product" tabindex="0">
                        <?php include _source."module/binh-luan.php"; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($config_detail['show_related'] && count($related) > 0): ?>
            <div class="row mt-4 mt-lg-5">
                <div class="col-12">
                    <h2 class="heading-title mb-3 text-uppercase"><?= $d->getTxt(25) ?></h2>
                    <div class="wrapper-slide">
                        <div class="swiper related-product p-1">
                            <div class="swiper-wrapper">
                                <?php foreach ($related as $key => $value): ?>
                                    <div class="swiper-slide">
                                        <?php include _source."module/card-product.php"; ?>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <!-- Recently Viewed Products -->
        <?php if ($config_detail['show_recently_viewed']): ?>
        <div class="row mt-4 mt-lg-5" id="recently-viewed-section" style="display:none;">
            <div class="col-12">
                <h2 class="main-title mb-3">Sản phẩm bạn vừa xem</h2>
                <div class="wrapper-slide">
                    <div class="swiper recently-viewed-swiper p-1">
                        <div class="swiper-wrapper" id="recently-viewed-list"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Sticky Add to Cart -->
<?php if ($config_detail['sticky_cart']): ?>
<div class="sticky-cart-bar" id="sticky-cart-bar">
    <div class="container-fluid h-100">
        <div class="row align-items-center h-100">
            <div class="col-auto d-none d-md-flex align-items-center gap-3">
                <img src="<?= getImageUrl($row['hinh_anh']) ?>" class="sticky-cart-img" id="sticky-cart-img" alt="<?= $row['ten'] ?>">
                <div class="sticky-cart-info">
                    <h5 class="mb-0 text-truncate" style="max-width: 300px; font-size: 1rem;"><?= $row['ten'] ?></h5>
                </div>
            </div>
            <div class="col d-flex justify-content-end justify-content-md-between justify-content-lg-end align-items-center gap-3">
                <div class="sticky-cart-price text-danger fw-bold fs-5" id="sticky-cart-price">
                    <?php 
                        if ($config_detail['show_variants'] && count($variants) > 0 && $min_variant_price > 0 && $min_variant_price != $max_variant_price) {
                            echo renderPrice($min_variant_price) . ' - ' . renderPrice($max_variant_price);
                        } else {
                            echo $row['khuyen_mai'] > 0 ? renderPrice($row['khuyen_mai']) : renderPrice($row['gia']);
                        }
                    ?>
                </div>
                <button type="button" class="btn btn-primary px-3 px-md-4 fw-bold btn-add-cart-sticky" style="background-color: var(--cl-x, #eb6100); border-color: var(--cl-x, #eb6100);" onclick="add_to_cart(0)">
                    <i class="fa fa-shopping-cart d-inline-block d-md-none me-1"></i>
                    <span class="d-none d-md-inline-block">Thêm vào giỏ hàng</span>
                    <span class="d-inline-block d-md-none">Mua</span>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script type="text/javascript">
    
    // Khởi tạo biến từ PHP để site.js sử dụng
    window.productVariants = <?= isset($variants_json) ? $variants_json : '[]' ?>;
    window.currentVariant = null;
    window.urlParams = <?= isset($params_json) ? $params_json : '{}' ?>;
    window.enableProactiveVariants = true; // Toggle proactive marking (true/false)
    window.hideButtonOnOutOfStock = false; // Hide "Add to Cart" buttons if invalid combo (true/false)
    window.totalVariantQty = <?= ($config_detail['show_variants']) ? (int)$total_variant_qty : 0 ?>;
    window.mainProductQty = <?= (int)$row['so_luong'] ?>;
    window.mainProductCode = '<?= addslashes($row['ma_sp']) ?>';
    window.currentProductId   = <?= (int)$row['id_code'] ?>;
    window.currentProductName = '<?= addslashes($row['ten']) ?>';
    window.currentProductImg  = '<?= addslashes($row['hinh_anh']) ?>';
    window.currentProductAlias= '<?= addslashes($row['alias']) ?>';
    window.currentProductSlug = '<?= addslashes($row['slug'] ?? '') ?>';

    // Logic di chuyển sang shop.js
</script>