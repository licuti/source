<?php
    $block_new = $d->simple_fetch("select * from #_category where id_code=129 and hien_thi=1 order by so_thu_tu,id desc limit 40");
    $featured_post = $d->o_fet("select * from #_tintuc where id_loai=129 and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 5");
    $news_first = $featured_post[0];
    $news_second = array_slice($featured_post, 1);
?>

<div class="block">
    <div class="container-fluid">
        <div class="row mb-3 mb-md-4 pb-3">
            <div class="col-12">
                <h2 class="main-title text-center"><?= $d->getTxt(137) ?></h2>
            </div>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <a <?= createAlias($news_first) ?>>
                    <div class="box-home-blog box-hover-zoom">
                        <div class="box-thumbnail rounded-3 overflow-hidden">
                            <div class="inner-thumbnail ratio ratio-16x9">
                                <img src="<?= getImageUrl($news_first['hinh_anh']) ?>" alt="<?= $news_first['ten'] ?>" class="image-cover">
                            </div>
                        </div>
                        <div class="box-content pt-2">
                            <div class="badge-date text-x mb-2"><i class="fa-light fa-calendar-days"></i> <?= date('d-m-Y', $news_first['ngay_dang']) ?></div>
                            <h3 class="title fw-bold fs-20"><?= $news_first['ten'] ?></h3>
                            <?= $news_first['mo_ta'] ?>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <?php foreach ($news_second as $key => $p): ?>
                        <div class="col-12">
                            <a <?=createAlias($value) ?>>
                                <div class="box-home-blog box-hover-zoom">
                                    <div class="row align-items-center g-3">
                                        <div class="col-lg-4">
                                            <div class="box-thumbnail rounded-3 overflow-hidden">
                                                <div class="inner-thumbnail ratio ratio-16x9">
                                                    <img src="<?= getImageUrl($p['hinh_anh']) ?>" alt="<?= $p['ten'] ?>" class="image-cover">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-lg-8">
                                            <div class="box-content">
                                                <div class="badge-date text-x mb-2"><i class="fa-light fa-calendar-days"></i> <?= date('d-m-Y', $news_first['ngay_dang']) ?></div>
                                                <h3 class="title fw-bold fs-6"><?= $p['ten'] ?></h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach ?>
                </div>                   

            </div>
        </div>
    </div>
    <div class="bg-cover" style="background-color: #F2F9F0;"></div>
</div>