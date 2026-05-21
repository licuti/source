<?php
    $button_contact = \ButtonContactModel::orderBy('sort', 'ASC')->get();
?>

<?php if (!empty($button_contact)): ?>
    <div class="button-contact-fixed">
        <?php foreach ($button_contact as $value): ?>
            <div class="box-ring">
                <div class="box-ring-outline" style="border-color: <?= $value->color_background ?>;"></div>
                <div class="box-ring-inline" style="background: <?= $value->color_background_alpha ?>;"></div>
                <div class="box-ring-image" style="background: <?= $value->color_background ?>;">
                    <a href="<?= $value->link ?>" rel="nofollow" target="<?= $value->target ?>">
                        <img src="<?= getImageUrl($value->image) ?>" alt="<?= $value->name ?>" class="image-cover">
                    </a>
                </div>
            </div>
        <?php endforeach ?>
        <div class="back-to-top">
            <i class="fa-regular fa-arrow-up-to-line"></i>
        </div>
    </div>
<?php endif ?>