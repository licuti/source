<?php
    $all_images = $d->o_fet("select * from #_album_hinhanh where id_album =".$row['id_code']."");
?>

<div class="block page-pagination" data-limit="20">
    <div class="container-fluid">
        <div class="row content-pagination">
            <?php foreach ($all_images as $key => $value): ?>
                <div class="col-6 col-md-4 col-lg-3 g-3">
                    <div class="card-hover-zoom">
                        <div class="thumbnail ratio ratio-1x1">
                            <a href="<?= getImageUrl($value['hinh_anh']) ?>" data-fancybox="gallery">
                                <getImageUrl class="image-cover" src="<?= getImageUrl($value['hinh_anh']) ?>" />
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
        </div>
        <?php if (count($all_images) > 0): ?>
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-center">
                    <div class="arrow-pagination"></div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>