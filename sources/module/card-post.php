<a <?= createAlias($value) ?> >
    <div class="box-blog box-hover-zoom">
        <div class="box-thumbnail">
            <div class="inner-thumbnail ratio ratio-4x3">
                <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
            </div>
        </div>
        <div class="box-content">
            <!-- <div class="badge-date"><?= date('d-m-Y', $value['ngay_dang']) ?></div> -->
            <div class="box-title">
                <h3 class="title"><?= $value['ten'] ?></h3>
            </div>
        </div>
    </div>
</a>