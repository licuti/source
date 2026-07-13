<?php
    $block_new = $d->simple_fetch("select * from #_category where id_code=129 and hien_thi=1 order by so_thu_tu,id desc limit 40");
    $news_first = $d->o_fet("select * from #_tintuc where id_loai=".$block_new['id_code']." and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 2");
    $news_second = $d->o_fet("select * from #_tintuc where id_loai=".$block_new['id_code']." and hien_thi=1 "._where_lang." order by so_thu_tu,id desc limit 10 offset 2");
?>

<div class="block bg-gray block-post">
    <div class="container-fluid">
        <div class="row mb-4 mb-lg-5">
            <div class="col-12">
                <h2 class="main-title main-title-underline text-x text-center"><?= $block_new['ten'] ?></h2>
            </div>
        </div>
        <div class="row gx-3 flex-lg-row-reverse">
            <div class="col-lg-7">
                <div class="row g-3">
                    <?php foreach ($news_first as $key => $value): ?>
                        <div class="col-md-6">
                            <?php echo view('partials/components/card-post'); ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="col-lg-5 mt-3 mt-lg-0">
                <div class="list-post-vertical">                                
                    <?php foreach ($news_second as $key => $value): ?>
                        <a href="<?= route('news.show', $value['slug']) ?>">
                            <div class="card-blog card-blog-vertical">
                                <div class="thumbnail">
                                    <getImageUrl src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                                </div>
                                <div class="content">
                                    <h4 class="title"><?= $value['ten'] ?></h4>
                                    <div class="description">
                                        <?= $value['mo_ta'] ?>
                                    </div>
                                    <span class="view-more">Xem thêm</span>
                                </div>
                            </div>
                        </a>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
    </div>
</div>
