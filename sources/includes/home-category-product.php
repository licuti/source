<?php
    $parentCate = $d->o_fet("select * from #_category where id_code = 100 and hien_thi =1 "._where_lang." order by so_thu_tu ASC, id DESC limit 1");
    $parentCate = !empty($parentCate) ? $parentCate[0] : null;
    $getChildCateProduct = $parentCate
        ? $d->o_fet("select * from #_category where id_loai=".$parentCate['id_code']." and hien_thi =1 "._where_lang." order by so_thu_tu ASC, id DESC")
        : [];
    $block_category = $d->getContent(528);
?>
<?php if (!empty($parentCate) && count($getChildCateProduct) > 0 && $block_category): ?>
    <div class="block block-home-product">
        <div class="container-fluid">
            <div class="heading-content mb-3 mb-md-4"><?= $block_category['noi_dung'] ?></div>

            <div class="row">
                <div class="col-12">
                    <div class="wrapper-category">
                        <div class="row mb-3 justify-content-center">
                            <div class="col-auto">
                                <ul class="nav nav-tabs home-product-tabs" id="Tab-<?= $parentCate['alias'] ?>" role="tablist">
                                    <?php foreach ($getChildCateProduct as $key => $value): ?>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link <?= $key == 0 ? 'active' : '' ?>" id="tab-<?= $value['id'] ?>" data-bs-toggle="tab" data-bs-target="#tab-pane-<?= $value['id'] ?>" type="button" role="tab" aria-controls="tab-pane-<?= $value['id'] ?>" aria-selected="true"><?= $value['ten'] ?></button>
                                        </li>
                                    <?php endforeach ?>
                                </ul>
                            </div>
                        </div>

                        <div class="tab-content" id="TabContent-<?= $parentCate['alias'] ?>">
                            <?php foreach ($getChildCateProduct as $key => $cate_child_product): ?>
                                <?php
                                    $getChildProduct = $d->o_fet("select *, (select ten from #_category where id_code = id_loai and lang = '".$_SESSION['lang']."') as category_name from #_sanpham where id_loai=".$cate_child_product['id_code']." and tieu_bieu=1 and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 12");
                                ?>
                                <div class="tab-pane fade <?= $key == 0 ? 'show active' : '' ?>" id="tab-pane-<?= $cate_child_product['id'] ?>" role="tabpanel" aria-labelledby="tab-<?= $cate_child_product['id'] ?>" tabindex="0">
                                    <?php if (count($getChildProduct) == 0): ?>
                                        <p class="text-center">Không có sản phẩm nào!</p>
                                    <?php endif ?>
                                    <div class="swiper slideProduct">
                                        <div class="swiper-wrapper">
                                            <?php foreach ($getChildProduct as $value): ?>
                                                <div class="swiper-slide">
                                                    <?php include _source."module/card-product.php"; ?>
                                                </div>
                                            <?php endforeach ?>
                                        </div>
                                        <!-- <div class="swiper-button-next"><i class="fa-solid fa-angle-right"></i></div>
                                        <div class="swiper-button-prev"><i class="fa-solid fa-angle-left"></i></div> -->
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>
