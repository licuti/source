<?php
    $block_policy = $d->getContent(576);
    $content_policy = $d->getContents(576);
?>

<?php if ($content_policy): ?>
    <div class="block">
        <div class="container-fluid">
            <div class="row g-4">
                <?php foreach ($content_policy as $key => $value): ?>
                    <div class="col-6 col-lg-3">
                        <div class="icon-box icon-box-horizontal">
                            <div class="icon">
                                <getImageUrl src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
                            </div>
                            <div class="content">
                                <h4 class="title text-x"><?= $value['ten'] ?></h4>
                                <?= $value['noi_dung'] ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>
<?php endif ?>