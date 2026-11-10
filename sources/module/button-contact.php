
<?php
    $button_contact = $d->o_fet("select * from #_button_contact order by sort asc");
    $stick_lms = $d->getContents(1458);
?>

<?php if ($button_contact): ?>
    <div class="button-contact-fixed">
        <?php foreach ($button_contact as $key => $value): ?>
            <div class="box-ring">
                <div class="box-ring-outline" style="border-color: <?= $value['color_background'] ?>;"></div>
                <div class="box-ring-inline" style="background: <?= $value['color_background_alpha'] ?>;"></div>
                <div class="box-ring-image" style="background: <?= $value['color_background'] ?>;">
                    <a href="<?= $value['link'] ?>" rel="nofollow" target="<?= $value['target'] ?>">
                        <img src="<?= getImageUrl($value['image']) ?>" alt="<?= $value['name'] ?>" class="image-cover">
                    </a>
                </div>
            </div>
        <?php endforeach ?>
        <div class="back-to-top">
            <i class="fa-regular fa-arrow-up-to-line"></i>
        </div>
    </div>
<?php endif ?>

<?php if (!empty($stick_lms)): ?>
    <div class="wrapper-stick-lms">
        <?php foreach ($stick_lms as $key => $item): ?>
            <a href="<?= $item['link'] ?>" class="item" data-bs-toggle="modal" data-bs-target="<?= $item['link'] ?>">
                <div class="icon">
                    <img src="<?= getImageUrl($item['hinh_anh']) ?>" alt="<?= $item['ten'] ?>" class="image-contain">
                </div>
                <div class="title"><?= $item['ten'] ?></div>
            </a>
        <?php endforeach ?>
    </div>
<?php endif ?>