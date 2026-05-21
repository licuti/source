<?php
    // Sử dụng Model để lấy dữ liệu thay vì $d->getContent
    $block_about = CategoryModel::query()->where('id_code', 455)->first();
    
    // Giả sử có NewsModel hoặc ContentModel cho các item bên trong
    // Ở đây dùng query trực tiếp từ table nếu chưa có Model chuyên biệt cho CategoryNoidung
    $content_about = $d->o_fet("SELECT * FROM #_category_noidung WHERE id_loai = 455 AND hien_thi = 1 ORDER BY so_thu_tu ASC");
    $content_about_objs = array_map(function($item) { return (object)$item; }, $content_about);
?>

<?php if ($block_about): ?>
    <section class="about-section py-5 overflow-hidden">
        <div class="container">
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="row g-3">
                        <?php foreach ($content_about_objs as $key => $item): ?>
                            <div class="col-4" data-aos="fade-up" data-aos-delay="<?= $key * 150 ?>">
                                <div class="ratio ratio-1x1 rounded-3 overflow-hidden shadow-sm hover-zoom">
                                    <img src="<?= getImageUrl($item->hinh_anh) ?>" alt="<?= $item->ten ?>" class="img-fluid object-fit-cover">
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left">
                    <div class="about-content ps-lg-4">
                        <h2 class="display-6 fw-bold mb-4"><?= $block_about->ten ?></h2>
                        <div class="description text-muted mb-4 lead">
                            <?= htmlspecialchars_decode($block_about->noi_dung ?? '') ?>
                        </div>
                        <?php if (!empty($block_about->link)): ?>
                            <a href="<?= $block_about->link ?>" class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm">
                                <?= $d->getTxt(12) ?> <i class="fas fa-arrow-right ms-2 small"></i>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif ?>