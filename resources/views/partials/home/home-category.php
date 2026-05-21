<?php
    $pageProduct = $d->simple_fetch("select * from #_category where id_code=100 and hien_thi=1 "._where_lang."");
    $home_category = $d->o_fet("select ten,alias,hinh_anh from #_category where id_loai=".$pageProduct['id_code']." and tieu_bieu=1 and hien_thi=1 "._where_lang." order by so_thu_tu ASC, id DESC limit 20");
?>

<div class="block">
	<div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="box-content-title"><?= $pageProduct['mo_ta'] ?></div>
            </div>
        </div>
		<div class="row">
            <div class="col-12">
                <div class="swiper category-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($home_category as $key => $value): ?>
                            <div class="swiper-slide">
                                <a href="<?= route('category.show', $value['alias']) ?>">
                                    <div class="card-category card-hover-zoom">
                                        <div class="thumbnail">
                                            <div class="ratio ratio-16x9">
                                                <getImageUrl src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
                                            </div>
                                        </div>
                                        <h5 class="title"><?= $value['ten'] ?></h5>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
        </div>
	</div>
</div>
