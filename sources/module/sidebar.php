<?php
    $post_sidebar = $d->o_fet("select * from #_tintuc where view > 0 and not id=".$row['id']." and hien_thi=1 "._where_lang." order by view desc limit 12");
?>

<div class="widdget">
    <h3 class="widdget-title"><?= $d->getTxt(116) ?></h3>
    <div class="widdget-content">
        <div class="list-post-sidebar">
            <?php foreach ($post_sidebar as $key => $value): ?> 
                <a <?= createAlias($value) ?> class="post-item-slidebar card-hover-zoom">
                    <div class="thumbnail ratio ratio-4x3">
                        <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">  
                    </div>
                    <div class="content">
                        <h4 class="title"><?= $value['ten'] ?></h4>
                    </div>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</div>