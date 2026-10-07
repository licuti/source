<?php
    $is_sidebar = true;
    $sidebar_position = ""; //left-sidebar | right-sidebar
    $is_share = true;
    $is_toc = true;
    $author = $d->simple_fetch("select * from #_user where id=".$row['id_user']."");
    $related = $d->o_fet("select * from #_tintuc where id_loai=".$category['id_code']." and id_code <> ".$row['id_code']." and hien_thi=1 "._where_lang." order by so_thu_tu ASC, id DESC limit 12");

    // if (!isset($_SESSION['viewed_posts'][$row['id']])){
    //     $d->query("update #_tintuc set view=view+1 where alias='".$com."'");
    //     $_SESSION['viewed_posts'][$row['id']] = true;
    // } -> move to file-router
?>

<div class="block page single-post-page">
    <div class="container-fluid">
        <div class="row g-3 content-post <?= empty($is_sidebar) ? 'justify-content-center' : '' ?>">
            <div class="col-lg-9">
                <div class="box-title">
                    <h2 class="title-single"><?= $row['ten'] ?></h2>
                    <div class="meta-post">
                        <div class="meta-post-item"><i class="fa-solid fa-eye"></i> <?= $d->getTxt(152) ?>: <?= $row['view'] ?></div>
                        <div class="meta-post-item"><i class="fa-regular fa-calendar-days"></i> <?= $d->getTxt(118) ?>: <?= date('d-m-Y',$row['ngay_dang']) ?></div>
                        <div class="meta-post-item"><i class="fa-solid fa-user"></i> <?= $d->getTxt(153) ?>: <?= $author['ho_ten'] ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <?php if ($row['mo_ta']): ?>
                    <div class="single-post-description"><?= $row['mo_ta'] ?></div>
                <?php endif ?>
                
                <?php if ($is_toc): ?>
                    <div id="toc">
                        <div class="toc-header">
                            <i class="fa-solid fa-list-ol toc-icon"></i>
                            Mục lục
                            <i class="fa-solid fa-chevron-right chevron"></i>
                        </div>
                        <div class="toc-content"></div>
                    </div>
                    <!-- FAB button -->
                    <button id="toc-fab" aria-label="Mở mục lục">
                        <i class="fa-solid fa-list-ol fab-icon-list"></i>
                        <i class="fa-solid fa-xmark fab-icon-close"></i>
                    </button>

                    <!-- TOC Panel -->
                    <div id="toc-panel" role="dialog" aria-label="Mục lục bài viết">
                        <div class="panel-title">Mục lục</div>
                        <div class="panel-toc-content"></div>
                    </div>
                <?php endif ?>

                <div id="content" class="content-page">
                    <?=$row['noi_dung']?>
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

        <?php if ($related): ?>
            <div class="row related-post mt-4">
                <div class="col-12">
                    <h2 class="main-title mb-3"><?= $d->getTxt(120) ?></h2>
                    <div class="wrapper-slide">
                        <div class="swiper related-news">
                            <div class="swiper-wrapper">
                                <?php foreach ($related as $key => $value): ?>
                                    <div class="swiper-slide">
                                        <?php include _source."module/card-post.php"; ?>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif ?>
    </div>
</div>
