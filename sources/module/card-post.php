<div class="box-blog box-hover-zoom">
    <div class="box-thumbnail">
        <div class="inner-thumbnail ratio ratio-4x3">
            <a <?= createAlias($value) ?>>
                <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
            </a>
        </div>
    </div>
    <div class="box-content">
        <div class="meta">
            <div class="meta-item date"><i class="fa-solid fa-calendar-days me-2"></i><?= date('d-m-Y', $value['ngay_dang']) ?></div>
            <div class="meta-item view"><i class="fa-solid fa-message me-2"></i><?= $value['view'] ?></div>
        </div>
        <h3 class="title">
            <a <?= createAlias($value) ?>><?= $value['ten'] ?></a>
        </h3>
        <div class="excerpt"><?= $value['mo_ta'] ?></div>
        <div class="pt-2 mt-auto">
            <a <?= createAlias($value) ?> class="view-more text-gradient">Xem chi tiết</a>
        </div>
    </div>
</div>