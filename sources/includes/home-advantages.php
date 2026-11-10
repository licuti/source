<?php
    $block_advantages = $d->getContent(727);
    $block_advantages_1 = $d->getContent(729);
    $content_advantages_1 = $d->getContents(729);
    $block_advantages_2 = $d->getContent(730);
    $content_advantages_2 = $d->getContents(730);
    $block_advantages_3 = $d->getContent(737);
    $content_advantages_3 = $d->getContents(737);
?>


<div class="block">
    <div class="container-fluid">
        <div class="heading-content text-center mb-3 pb-3">
            <?= $block_advantages['noi_dung'] ?>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-11">
                <div class="row g-4">
                    <div class="col-lg-3">
                        <div class="box-advantages box-advantages-1">
                            <div class="box-header">
                                <?= $block_advantages_1['ten'] ?>
                            </div>
                            <div class="box-content">
                                <?php foreach ($content_advantages_1 as $key => $value): ?>
                                <div class="advantages-item">
                                    <div class="icon">
                                        <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
                                    </div>
                                    <div class="fw-semibold"><?= $value['ten'] ?></div>
                                </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="box-advantages">
                            <div class="box-header">
                                <i class="fa-solid fa-user"></i> <?= $block_advantages_2['ten'] ?>
                            </div>
                            <div class="box-content">
                                <?php foreach ($content_advantages_1 as $key => $value): ?>
                                <div class="advantages-item">
                                    <?= $value['ten'] ?>
                                </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="box-advantages">
                            <div class="box-header" style="background-color: #FFD8C2;">
                                <i class="fa-solid fa-users"></i> <?= $block_advantages_3['ten'] ?>
                            </div>
                            <div class="box-content">
                                <?php foreach ($content_advantages_3 as $key => $value): ?>
                                    <div class="advantages-item">
                                        <div class="icon">
                                            <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-contain">
                                        </div>
                                        <div class="content">
                                            <div class="fw-semibold text-x fs-20"><?= $value['ten'] ?></div>
                                            <?= $value['noi_dung'] ?>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-cover" style="background-image: url('<?= getImageUrl($block_advantages['background']) ?>');"></div>
</div>