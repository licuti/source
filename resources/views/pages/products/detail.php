
<div class="block page single-product-page">
    <div class="container-fluid">
        <div class="row g-4">
            <!-- Cột hình ảnh sản phẩm -->
            <div class="col-lg-6">
                <!-- Gallery & Images -->
                <div class="row g-2">
                    <div class="col-12">
                        <div class="swiper slide_show">
                            <div class="swiper-wrapper">
                                <?php if ($config_detail['show_video'] && !empty($row->video)): ?>
                                    <div class="swiper-slide video-slide">
                                        <a href="https://www.youtube.com/watch?v=<?= $row->video ?>" data-fancybox="gallery">
                                            <img src="https://img.youtube.com/vi/<?= $row->video ?>/hqdefault.jpg" class="image-cover" alt="Video">
                                            <div class="play-button-overlay"><i class="fa-solid fa-circle-play"></i></div>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <div class="swiper-slide">
                                    <a href="<?= getImageUrl($row->hinh_anh) ?>" data-fancybox="gallery">
                                        <img src="<?= getImageUrl($row->hinh_anh) ?>" class="image-cover" alt="<?= htmlspecialchars($row->ten) ?>">
                                    </a>
                                </div>

                                <?php foreach ($gallery_products as $gp): ?>
                                    <div class="swiper-slide">
                                        <a href="<?= getImageUrl($gp->hinh_anh) ?>" data-fancybox="gallery">
                                            <img src="<?= getImageUrl($gp->hinh_anh) ?>" class="image-cover" alt="Gallery">
                                        </a>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <div class="swiper-button-next slide_show_next"><i class="fa-solid fa-angle-right"></i></div>
                            <div class="swiper-button-prev slide_show_prev"><i class="fa-solid fa-angle-left"></i></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div thumbsSlider="" class="swiper slide_thumb">
                            <div class="swiper-wrapper">
                                <?php if ($config_detail['show_video'] && !empty($row->video)): ?>
                                    <div class="swiper-slide video-thumb text-center d-flex align-items-center justify-content-center" style="background: #000;">
                                        <img src="https://img.youtube.com/vi/<?= $row->video ?>/default.jpg" class="image-cover opacity-75" alt="Video Thumb">
                                        <div class="play-button-icon position-absolute text-white fs-4"><i class="fa-solid fa-play"></i></div>
                                    </div>
                                <?php endif; ?>

                                <div class="swiper-slide">
                                    <img src="<?= getImageUrl($row->hinh_anh) ?>" class="image-cover" alt="<?= htmlspecialchars($row->ten) ?>">
                                </div>

                                <?php foreach ($gallery_products as $gp): ?>
                                    <div class="swiper-slide">
                                        <img src="<?= getImageUrl($gp->hinh_anh) ?>" class="image-cover" alt="Gallery Thumb">
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cột thông tin sản phẩm -->
            <div class="col-lg-6">
                <!-- Info & Form -->
                <form method="POST" action="" id="form-cart">
                    <?= csrf_field() ?>
                    <input type="hidden" value="add-to-cart" name="action" />
                    <input type="hidden" value="<?= $row->id_code ?>" name="id_sp" />
                    <input type="hidden" value="" name="id_bienthe" id="id_bienthe" />
                    <input type="hidden" value="<?= $row->khuyen_mai > 0 ? $row->khuyen_mai : $row->gia ?>" name="gia" id="form_gia" />
                    
                    <div class="box-detail-product">
                        <h2 class="single-title"><?= $row->ten ?></h2>
                        
                        <!-- Price section -->
                        <div class="single-price">
                            <?php if ($config_detail['show_variants'] && count($variants) > 0 && $min_variant_price > 0): ?>
                                <?php if ($min_variant_price != $max_variant_price): ?>
                                    <div class="single-price-discount" id="display_price_discount">
                                        <?= renderPrice($min_variant_price) ?> - <?= renderPrice($max_variant_price) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="single-price-discount" id="display_price_discount">
                                        <?= renderPrice($min_variant_price) ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if ($row->khuyen_mai > 0): ?>
                                    <div class="single-price-discount" id="display_price_discount">
                                        <?= renderPrice($row->khuyen_mai) ?>
                                    </div>
                                    <div class="single-price-regular" id="display_price_regular">
                                        <?= renderPrice($row->gia) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="single-price-discount" id="display_price_discount">
                                        <?= renderPrice($row->gia) ?>
                                    </div>
                                <?php endif ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($sp_total_rating > 0): ?>
                            <div class="single-rate">
                                <?= renderStars($sp_avg_rating, $sp_total_rating, 18) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="single-code">
                            <?= __('Mã sản phẩm') ?>: 
                            <span id="display_sku"><?= $row->ma_sp ?: __('Đang cập nhật') ?></span>
                        </div>
                        
                        <div class="single-stock">
                            <?= __('Số lượng') ?>: 
                            <span id="display_stock">
                                <?= ($config_detail['show_variants'] && count($variants) > 0) ? $total_variant_qty : $row->so_luong ?>
                            </span>
                        </div>

                        <?php if ($config_detail['show_description'] && $row->mo_ta): ?>
                            <div class="single-description">
                                <?= $row->mo_ta ?>
                            </div>
                        <?php endif ?>

                        <!-- Variants -->
                        <?php if ($config_detail['show_variants'] && !empty($variant_attributes)): ?>
                            <div class="product-variants mb-4">
                                <?php foreach ($variant_attributes as $attr): ?>
                                    <div class="variant-group mb-3" data-attr-id="<?= $attr['id'] ?>" data-attr-type="<?= $attr['loai'] ?>">
                                        <label class="fw-bold d-block mb-1">
                                            <?= htmlspecialchars($attr['ten']) ?>: 
                                            <span class="selected-variant-text fw-normal text-muted"></span>
                                        </label>
                                        <div class="variant-options">
                                            <?php foreach ($attr['values'] as $val): ?>
                                                <?php if ($attr['loai'] == 'color'): ?>
                                                    <div class="variant-item variant-item-color" data-val-id="<?= $val['id'] ?>" style="background-color: <?= htmlspecialchars($val['gia_tri']) ?>;" title="<?= htmlspecialchars($val['ten']) ?>"></div>
                                                <?php elseif ($attr['loai'] == 'image'): ?>
                                                    <div class="variant-item variant-item-image" data-val-id="<?= $val['id'] ?>">
                                                        <img src="<?= getImageUrl($val['gia_tri']) ?>" alt="<?= htmlspecialchars($val['ten']) ?>">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="variant-item variant-item-text" data-val-id="<?= $val['id'] ?>">
                                                        <?= htmlspecialchars($val['ten'] ?: $val['gia_tri'] ?: 'N/A') ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="single-group-button mt-4">
                            <div class="box-quantity">
                                <button class="btn-sub" type="button">-</button>
                                <input type="text" name="so_luong" value="1" id="soluong">
                                <button class="btn-add" type="button">+</button>
                            </div>
                            <button type="button" class="btn-custom btn-x btn-add-cart" onclick="add_to_cart(0)">
                                <?= __('Thêm vào giỏ hàng') ?>
                            </button>
                            <button type="button" class="btn-custom btn-y btn-buy-now" onclick="add_to_cart(1)">
                                <?= __('Mua ngay') ?>
                            </button>
                        </div>

                        <?php if ($category): ?>
                            <div class="single-category mt-3">
                                <strong><?= __('Danh mục') ?>:</strong> <?= $category->ten ?>
                            </div>
                        <?php endif ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Detail Tabs -->
        <div class="row mt-5">
            <div class="col-12">
                <ul class="nav nav-tabs" id="productTab" role="tablist">
                    <?php if ($config_detail['show_content']): ?>
                        <li class="nav-item">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-content" type="button">
                                <?= __('Chi tiết sản phẩm') ?>
                            </button>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($config_detail['show_rating']): ?>
                        <li class="nav-item">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reviews" type="button">
                                <?= __('Đánh giá') ?>
                            </button>
                        </li>
                    <?php endif; ?>
                </ul>
                <div class="tab-content border-start border-end border-bottom p-3">
                    <?php if ($config_detail['show_content']): ?>
                        <div class="tab-pane fade show active" id="tab-content">
                            <?= $row->noi_dung ?: __('Nội dung đang cập nhật') ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($config_detail['show_rating']): ?>
                        <div class="tab-pane fade" id="tab-reviews">
                            <?= view('partials/components/binh-luan', ['row' => $row]) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Related Products -->
        <?php if ($config_detail['show_related'] && count($related) > 0): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <h2 class="main-title mb-3"><?= __('Sản phẩm liên quan') ?></h2>
                    <div class="swiper related-product swiper-button-circle">
                        <div class="swiper-wrapper">
                            <?php foreach ($related as $value): ?>
                                <div class="swiper-slide">
                                    <?= view('partials/components/card-product', ['value' => $value]) ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

<script type="text/javascript">
    window.productVariants = <?= json_encode($variants, JSON_UNESCAPED_UNICODE) ?>;
    window.currentProductId = <?= (int)$row->id_code ?>;
</script>