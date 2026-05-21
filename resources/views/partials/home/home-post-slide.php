<?php
    $pageNews = $d->simple_fetch("select * from #_category where id_code=129 and hien_thi=1 "._where_lang."");
    $getNews = $d->o_fet("select * from #_tintuc where id_loai=".$pageNews['id_code']." and noi_bat=1 and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 12");
?>

<div class="block">
    <div class="container-fluid">
        <div class="row mb-3 mb-md-4">
            <div class="col-12">
                <h2 class="main-title text-x" style="opacity: 0.2;"><?= $pageNews['ten'] ?></h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="swiper swiper-button-circle hover-show-button post-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($getNews as $key => $value): ?>
                            <div class="swiper-slide">
                                <?php echo view('partials/components/card-post'); ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <div class="swiper-button-prev post-prev"><i class="fa-solid fa-arrow-left-long"></i></div>
                    <div class="swiper-button-next post-next"><i class="fa-solid fa-arrow-right-long"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>
