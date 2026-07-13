<?php
// Lấy ID tác giả từ URL tham số
$id_tacgia = (int)($_GET['code'] ?? 0);

// Lấy thông tin tác giả
$row_raw = $d->simple_fetch("select hinh_anh, ho_ten, noi_dung, id_code from #_user where id = $id_tacgia");
$row = $row_raw ? (object)$row_raw : null;

// Lấy bài viết liên quan của tác giả này
$tinlienquan_raw = \App\Models\PostModel::query()
    ->where('status', 1)
    ->where('id_user', $id_tacgia)
    ->orderBy('so_thu_tu')
    ->orderBy('id', 'DESC')
    ->limit(20)
    ->get();
$tinlienquan = $tinlienquan_raw;
?>
<main>
    <div class="sodotrang">
        <div class="container">
            <ol vocab="https://schema.org/" typeof="BreadcrumbList" class="breadcrumb"> 
                <li property="itemListElement" typeof="ListItem" class="breadcrumb-item">
                    <a property="item" typeof="WebPage" href="<?= URLPATH ?>">
                        <span property="name">Trang chủ</span>
                    </a>
                    <meta property="position" content="1">
                </li>
                <?php if ($row): ?>
                    <li property="itemListElement" typeof="ListItem" class="breadcrumb-item active">
                        <a property="item" typeof="WebPage" href="<?= _url_page ?>">
                            <span property="name"><?= $row->ho_ten ?></span>
                        </a>
                        <meta property="position" content="2">
                    </li>
                <?php endif ?>
            </ol>
        </div>
    </div>

    <!-- news-details-area-start -->
    <div class="blog-area py-5">
        <div class="container py-lg-5" style="background-color: #fff">
            <?php if ($row): ?>
                <div class="news-details-content mb-5">
                    <h1 class="title-news_ct mb-4"><?= $row->ho_ten ?></h1>
                    <div class="news-details">
                        <?php if (!empty($row->noi_dung)): ?>
                            <?= content_mucluc($row->noi_dung, _url_page) ?>
                        <?php endif ?>
                    </div>
                </div>
            <?php endif ?>

            <?php 
            $lienquan_label = $d->getContent(101); // Giả sử getContent trả về array và cần object
            if ($lienquan_label) $lienquan_label = (object)$lienquan_label;
            ?>
            <h2 class="video_home_title mb-4 mt-5"><?= $lienquan_label->ten ?? 'Bài viết của tác giả' ?></h2>
            
            <div class="row">
                <?php foreach ($tinlienquan as $value): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="single-smblog">
                        <div class="smblog-thum">
                            <div class="blog-image border rounded overflow-hidden">
                                <a href="<?= route('news.show', $value->slug) ?>">
                                    <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>" class="image-cover ratio ratio-4x3">
                                </a>
                            </div>
                        </div>
                        <div class="smblog-content mt-3">
                            <h3 class="tintuc_home_item_title fs-6">
                                <a href="<?= route('news.show', $value->slug) ?>"><?= $value->ten ?></a>
                            </h3>
                            <div class="text-muted small mb-2"><?= $d->getTxt(118) ?>: <?= date('d/m/Y', $value->ngay_dang) ?></div>
                            <p class="text-secondary small mb-3"><?= catchuoi($value->mo_ta, 100) ?></p>
                            <div class="smblog-foot">
                                <a href="<?= route('news.show', $value->slug) ?>" class="btn btn-link p-0 text-decoration-none small"><?= $d->getTxt(114) ?> →</a>
                            </div>
                        </div>
                    </div>
                </div>   
                <?php endforeach ?>
                <?php if (empty($tinlienquan)): ?>
                    <div class="col-12 text-center py-4">Chưa có bài viết nào từ tác giả này.</div>
                <?php endif ?>
            </div>
        </div>
    </div>
</main>

<?php 
$why_raw = $d->getContent(70);
$why = $why_raw ? (object)$why_raw : null;
$why_items_raw = $d->getContents(70);
$why_items = [];
foreach ($why_items_raw as $item) $why_items[] = (object)$item;
?>
<?php if ($why): ?>
<div class="why py-5 bg-light">
    <div class="container text-center">
        <h2 class="why_title mb-4"><?= $why->ten ?></h2>
        <div class="row">
            <?php foreach ($why_items as $value): ?>
            <div class="col-md-4 mb-4">
                <div class="why_item d-flex flex-column align-items-center">
                    <img class="mb-3" src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>" style="max-width: 80px;">
                    <div class="why_item_content">
                        <h4 class="why_item_content_title fs-5"><?= $value->ten ?></h4>
                        <div class="text-muted small"><?= strip_tags($value->noi_dung) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>