<?php
    $banner = $d->getContent(389);
?>
<?php  ?>
<div class="block banner-page d-none">
    <div class="container-fluid">
        <div class="row" data-aos="fade-right" data-aos-duration="1500">
            <div class="col-12">
                <h1 class="title"><?= $row['ten'] ?></h1>
            </div>
        </div>
        <div class="row" data-aos="fade-left" data-aos-duration="1500">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= URLPATH ?>"><?= $home['ten'] ?></a></li>
                        <?php if ($category): ?>
                            <li class="breadcrumb-item"><a <?= createAlias($category)?> ><?= $category['ten'] ?></a></li>
                        <?php else: ?>
                            <li class="breadcrumb-item active" aria-current="page"><?= $row['ten'] ?></li>
                        <?php endif ?>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    <div class="bg-cover">
        <?php if ($row['banner']): ?>
            <getImageUrl src="<?= $row['banner'] ? getImageUrl($row['banner']) : getImageUrl($banner['hinh_anh']) ?>" alt="Banner" class="image-cover">
        <?php else: ?>
            <getImageUrl src="<?= $row['hinh_anh'] ? getImageUrl($row['hinh_anh']) : getImageUrl($banner['hinh_anh']) ?>" alt="Banner" class="image-cover">
        <?php endif ?>
    </div>
    <div class="bg-cover bg-x opacity-75"></div>
</div>
<?php  ?>
<div class="block simple-breadcrumb">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= URLPATH ?>"><?= $home['ten'] ?></a></li>
                        <?php if ($category): ?>
                            <li class="breadcrumb-item"><a <?= createAlias($category)?> ><?= $category['ten'] ?></a></li>
                        <?php endif ?>
                        <li class="breadcrumb-item active" aria-current="page"><?= $row['ten'] ?></li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>