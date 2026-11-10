<?php
    $block_header_library = $d->getContent(1444)
?>

<div class="block block-header-page tin-tuc-thu-vien">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4">
                <div class="header-page">
                    <div class="fs-20"><?= $block_header_library['noi_dung'] ?></div>
                    <form method="get" class="form-search-header mt-4">
                        <i class="fa-regular fa-magnifying-glass"></i>
                        <input type="text" name="keyword" class="form-control" placeholder="Tìm kiếm bài viết">
                        <button class="btn-custom btn-solid btn-gradient">Tìm kiếm</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-cover" style="background-image: url('<?= getImageUrl($block_header_library['hinh_anh']) ?>'); background-size: contain; background-position: right bottom;"></div>
</div>

<div class="block">
    <div class="container-fluid">
        <div class="row g-4">
            <?php if (!empty($allPost)): ?>
                <?php foreach ($allPost as $key => $value): ?>
                    <div class="col-12">
                        <div class="box-blog box-library box-hover-zoom">
                            <div class="box-thumbnail">
                                <div class="inner-thumbnail ratio ratio-4x3">
                                    <a <?= createAlias($value) ?>>
                                        <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                                    </a>
                                </div>
                            </div>
                            <div class="box-content">
                                <h3 class="title">
                                    <a <?= createAlias($value) ?>><?= $value['ten'] ?></a>
                                </h3>
                                <div class="meta">
                                    <div class="meta-item date"><i class="fa-solid fa-calendar-days me-2"></i><?= date('d-m-Y', $value['ngay_dang']) ?></div>
                                    <div class="meta-item view"><i class="fa-solid fa-message me-2"></i><?= $value['view'] ?></div>
                                </div>
                                <div class="excerpt"><?= $value['mo_ta'] ?></div>
                                <div class="pt-2 mt-auto text-end">
                                    <a <?= createAlias($value) ?> class="view-more"><span class="text-decoration-underline">Đọc thêm</span> <i class="fa-regular fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted"><?= $d->getTxt(143) ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?= getPaginationHTML($total_posts, $limit, $page) ?>
    </div>
</div>