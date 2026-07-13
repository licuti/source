<?php
    $getCateProduct = $d->o_fet("select * from #_category where id_loai=100 and home=1 and hien_thi =1 "._where_lang." order by so_thu_tu ASC, id DESC");

?>
<?php foreach ($getCateProduct as $key => $cate_product): ?>
    <div class="block block-home-product">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12">
                    <h2 class="main-title text-center"><?= $cate_product['ten'] ?></h2>
                </div>
            </div>
            <?php
                $getChildCateProduct = $d->o_fet("select * from #_category where id_loai=".$cate_product['id_code']." and home=1 and hien_thi =1 "._where_lang." order by so_thu_tu ASC, id DESC"); 
                $getProduct = $d->o_fet("select * from #_sanpham where id_loai=".$cate_product['id_code']."  and tieu_bieu=1 and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 12");
            ?>

            <?php if (count($getChildCateProduct) > 0): ?>
                <div class="row mt-2 mt-md-3">
                    <div class="col-12">
                        <div class="wrapper-category">
                            <div class="row mb-2">
                                <div class="col-auto flex-fill">
                                    <ul class="nav nav-tabs" id="Tab-<?= $cate_product['slug'] ?>" role="tablist">
                                        <?php foreach ($getChildCateProduct as $key => $value): ?>
                                            <li class="nav-item" role="presentation">
                                                <button class="nav-link <?= $key == 0 ? 'active' : '' ?>" id="tab-<?= $value['id'] ?>" data-bs-toggle="tab" data-bs-target="#tab-pane-<?= $value['id'] ?>" type="button" role="tab" aria-controls="tab-pane-<?= $value['id'] ?>" aria-selected="true"><?= $value['ten'] ?></button>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= route('category.show', $cate_product['slug']) ?>" class="btn-custom btn-x">Xem thêm</a>
                                </div>
                            </div>
                            <div class="tab-content" id="TabContent-<?= $cate_product['slug'] ?>">
                                <?php foreach ($getChildCateProduct as $key => $cate_child_product): ?>
                                    <?php
                                        $getChildProduct = $d->o_fet("select * from #_sanpham where id_loai=".$cate_child_product['id_code']."  and tieu_bieu=1 and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 12"); 
                                    ?>
                                    <div class="tab-pane fade <?= $key == 0 ? 'show active' : '' ?>" id="tab-pane-<?= $cate_child_product['id'] ?>" role="tabpanel" aria-labelledby="tab-<?= $cate_child_product['id'] ?>" tabindex="0">
                                        <?php if (count($getChildProduct) == 0): ?>
                                            <p class="text-center">Không có sản phẩm nào!</p> 
                                        <?php endif ?>
                                        <div class="swiper slideProduct">
                                            <div class="swiper-wrapper">
                                                <?php foreach ($getChildProduct as $value): ?>
                                                    <div class="swiper-slide">
                                                        <?php echo view('partials/components/card-product', ['value' => $value]); ?>
                                                    </div>
                                                <?php endforeach ?>
                                            </div>
                                            <div class="swiper-button-next"><i class="fa-solid fa-angle-right"></i></div>
                                            <div class="swiper-button-prev"><i class="fa-solid fa-angle-left"></i></div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-12">
                        <div class="wrapper-category">
                            <div class="row mb-2">
                                <div class="col-auto flex-fill"></div>
                                <div class="col-auto">
                                    <a href="<?= route('category.show', $cate_product['slug']) ?>" class="btn-custom btn-x">Xem thêm <i class="fa-solid fa-chevron-right fa-xs"></i></a>
                                </div>
                            </div>

                            <?php if (count($getProduct) == 0): ?>
                                <p class="text-center">Không có sản phẩm nào!</p> 
                            <?php endif ?>
                            <div class="swiper slideProduct">
                                <div class="swiper-wrapper">
                                    <?php foreach ($getProduct as $value): ?>
                                        <div class="swiper-slide">
                                            <?php echo view('partials/components/card-product', ['value' => $value]); ?>
                                        </div>
                                    <?php endforeach ?>
                                </div>
                                <div class="swiper-button-next"><i class="fa-solid fa-angle-right"></i></div>
                                <div class="swiper-button-prev"><i class="fa-solid fa-angle-left"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif ?>
        </div>
    </div>
<?php endforeach ?>

