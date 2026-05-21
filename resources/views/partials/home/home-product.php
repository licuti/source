<?php
    // 1. Lấy thông tin danh mục và các danh mục con
    $pageProduct = CategoryModel::query()
        ->where('id_code', 100)
        ->where('hien_thi', 1)
        ->first('ten, id_code');
        
    $list_id_product = CategoryModel::query()->getChildrenIds($pageProduct->id_code);
    
    // 2. Lấy danh sách sản phẩm bằng Query Builder (Laravel Style) đã gộp (Eager Loading)
    $home_product = ProductModel::query()
        ->where('id_loai', $list_id_product, 'IN')
        ->where('tieu_bieu', 1)
        ->where('hien_thi', 1)
        ->withCategory()
        ->withVariants()
        ->orderBy('so_thu_tu')
        ->orderBy('id', 'DESC')
        ->limit(24)
        ->get();
?>
<?php if ($home_product): ?>
    <div class="block">
        <div class="container-fluid">
            <div class="row mb-3 mb-md-4" data-aos="fade-up" data-aos-duration="1000">
                <div class="col-12">
                    <h2 class="main-title text-center"><span class="fw-normal">SẢN PHẨM</span> <span class="text-x">CỦA CHÚNG TÔI</span></h2>
                </div>
            </div>

            <div class="row" data-aos="fade-up" data-aos-duration="1200">
                <div class="col-12">
                    <div class="swiper swiper-button-circle hover-show-button product-slide p-1">
                        <div class="swiper-wrapper">
                            <?php foreach ($home_product as $key => $value): ?>
                                <div class="swiper-slide">
                                    <?php echo view('partials/components/card-product', ['value' => $value]); ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="swiper-button-next product-next"><i class="fa-solid fa-arrow-right-long"></i></div>
                        <div class="swiper-button-prev product-prev"><i class="fa-solid fa-arrow-left-long"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
