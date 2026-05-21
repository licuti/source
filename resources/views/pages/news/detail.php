<?php
    $is_sidebar = true;
    $sidebar_position = "";
    $is_share = true;
    $is_toc = true;
    
    // Sử dụng Model để lấy thông tin tác giả
    $author = $d->simple_fetch("select * from #_user where id=" . (int)$row->id_user);
    if ($author) $author = (object)$author;

    // Sử dụng NewsModel để lấy bài viết liên quan
    $related = NewsModel::query()
        ->where('id_loai', $category->id_code)
        ->where('id_code', $row->id_code, '<>')
        ->where('hien_thi', 1)
        ->limit(12)
        ->orderBy('so_thu_tu')
        ->orderBy('id', 'DESC')
        ->get();
?>

<div class="block page single-post-page">
    <div class="container-fluid">
        <div class="row g-3 content-post <?= empty($is_sidebar) ? 'justify-content-center' : '' ?>">
            <div class="col-lg-9">
                <div class="box-title">
                    <h2 class="title-single"><?= $row->ten ?></h2>
                    <div class="meta-post">
                        <div class="meta-post-item"><i class="fa-solid fa-eye"></i> <?= $d->getTxt(152) ?>: <?= $row->view ?></div>
                        <div class="meta-post-item"><i class="fa-regular fa-calendar-days"></i> <?= $d->getTxt(118) ?>: <?= $row->ngay_dang ? date('d-m-Y', $row->ngay_dang) : 'Đang cập nhật' ?></div>
                        <?php if ($author): ?>
                            <div class="meta-post-item"><i class="fa-solid fa-user"></i> <?= $d->getTxt(153) ?>: <?= $author->ho_ten ?></div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <?php if (!empty($row->mo_ta)): ?>
                    <div class="single-post-description"><?= $row->mo_ta ?></div>
                <?php endif ?>
                
                <?php if ($is_toc): ?>
                    <div id="toc">
                        <div class="toc-header"><i class="fa-solid fa-list-ol toc-icon"></i> Mục lục <i class="fa-solid fa-chevron-right chevron"></i></div>
                        <div class="toc-content"></div>
                    </div>
                    <!-- FAB button -->
                    <button id="toc-fab" aria-label="Mở mục lục"><i class="fa-solid fa-list-ol fab-icon-list"></i><i class="fa-solid fa-xmark fab-icon-close"></i></button>

                    <!-- TOC Panel -->
                    <div id="toc-panel" role="dialog" aria-label="Mục lục bài viết">
                        <div class="panel-title">Mục lục</div>
                        <div class="panel-toc-content"></div>
                    </div>
                <?php endif ?>

                <div id="content" class="content-page">
                    <?= $row->noi_dung ?>
                </div>

                <?php if ($is_share): ?>
                    <div class="mt-3">
                        <?php include 'module/share.php'; ?>
                    </div>
                <?php endif ?>
            </div>
            <?php if ($is_sidebar): ?>
                <div class="col-lg-3">
                    <?php include 'module/sidebar.php'; ?>
                </div>
            <?php endif ?>
        </div>

        <?php if (!empty($related)): ?>
            <div class="row related-post mt-4">
                <div class="col-12">
                    <h2 class="main-title mb-3"><?= $d->getTxt(120) ?></h2>
                    <div class="wrapper-slide">
                        <div class="swiper related-news swiper-button-circle">
                            <div class="swiper-wrapper">
                                <?php foreach ($related as $value): ?>
                                    <div class="swiper-slide"><?php echo view('partials/components/card-post'); ?></div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>

