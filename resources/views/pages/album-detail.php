<?php
    $all_images_raw = $d->o_fet("select * from #_album_hinhanh where id_album =" . (int)$row->id_code);
    $all_images = [];
    foreach ($all_images_raw as $img) {
        $all_images[] = (object)$img;
    }
?>

<div class="block page-pagination" data-limit="20">
    <div class="container-fluid">
        <h2 class="main-title mb-4"><?= $row->ten ?></h2>
        <div class="row content-pagination g-3">
            <?php foreach ($all_images as $value): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card-hover-zoom overflow-hidden border rounded">
                        <div class="thumbnail ratio ratio-1x1">
                            <a href="<?= getImageUrl($value->hinh_anh) ?>" data-fancybox="gallery">
                                <img class="image-cover" src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $row->ten ?>" />
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach ?>
            <?php if (empty($all_images)): ?>
                <div class="col-12 text-center py-5">
                    <p><?= $d->getTxt(141) ?: 'Nội dung đang được cập nhật' ?></p>
                </div>
            <?php endif ?>
        </div>
        <?php if (count($all_images) > 20): ?>
            <div class="row mt-4">
                <div class="col-12 d-flex justify-content-center">
                    <div class="arrow-pagination"></div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>