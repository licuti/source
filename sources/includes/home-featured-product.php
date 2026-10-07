<?php
    $block_product = $d->getContent(455);
    $pageProduct = $d->simple_fetch("select ten,id_code from #_category where id_code=100 and hien_thi=1 "._where_lang."");
    $list_id_product = $pageProduct['id_code'].$d->getIdsub($pageProduct['id_code']);
    $home_product = $d->o_fet("select sp.*, 
        (select ten from #_category where id_code = sp.id_loai and lang = '".$_SESSION['lang']."') as category_name,
        COALESCE((select min(IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code), IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia)) as min_price,
        COALESCE((select max(IF(v.khuyen_mai > 0, v.khuyen_mai, v.gia)) from #_sanpham_bienthe v where v.id_sanpham = sp.id_code), IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia)) as max_price
        from #_sanpham sp 
        where sp.id_loai in ($list_id_product) and sp.sp_sale=1 and sp.hien_thi=1 "._where_lang." 
        order by sp.so_thu_tu, sp.id desc 
        limit 24");
?>

<?php if ($block_product): ?>
    <div class="block px-0">
        <div class="container-fluid">
            <div class="mb-3 mb-md-4">
                <h2 class="heading-title text-center"><?= $block_product['ten'] ?></h2>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="wrapper-slide">
                        <div class="swiper product-slide p-1">
                            <div class="swiper-wrapper">
                                <?php foreach ($home_product as $key => $value): ?>
                                    <div class="swiper-slide" style="height: auto;">
                                        <?php include _source."module/card-product.php"; ?>
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <div class="swiper-button-next product-next"></div>
                            <div class="swiper-button-prev product-prev"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3 mb-md-4 text-center">
                <a href="<?= $block_product['link'] ?>" class="btn-custom btn-outline btn-x">Xem thêm</a>
            </div>
        </div>
    </div>
<?php endif ?>