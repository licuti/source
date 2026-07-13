<?php
    $banner_info = \ContentModel::query()->setTable('#_category_noidung')->where('id_code', 389)->where('hien_thi', 1)->first();
    
    $row_ten = $row->ten ?? '';
    $home_ten = $home->ten ?? 'Trang chủ';
?>

<div class="block banner-page d-none">
    <div class="container-fluid">
        <div class="row" data-aos="fade-right" data-aos-duration="1500">
            <div class="col-12">
                <h1 class="title"><?= $row_ten ?></h1>
            </div>
        </div>
        <div class="row" data-aos="fade-left" data-aos-duration="1500">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= url() ?>"><?= $home_ten ?></a></li>
                        <?php if (!empty($category)): ?>
                            <li class="breadcrumb-item"><a href="<?= route('category.show', $category->slug) ?>"><?= $category->ten ?? '' ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= $row_ten ?></li>
                        <?php endif ?>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="bg-cover">
        <?php 
            $banner_path = !empty($row->banner) ? $row->banner : ($banner_info->hinh_anh ?? '');
            $fallback_path = !empty($row->hinh_anh) ? $row->hinh_anh : ($banner_info->hinh_anh ?? '');
        ?>
        <img src="<?= getImageUrl($banner_path ?: $fallback_path) ?>" alt="Banner" class="image-cover">
    </div>
    <div class="bg-cover bg-x opacity-75"></div>
</div>

<div class="block simple-breadcrumb py-3 bg-light border-bottom mb-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="<?= url() ?>"><?= $home_ten ?></a></li>
                        <?php if (!empty($category)): ?>
                            <li class="breadcrumb-item"><a href="<?= route('category.show', $category->slug) ?>"><?= $category->ten ?? '' ?></a></li>
                        <?php endif ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= $row_ten ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>