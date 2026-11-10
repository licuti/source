<div class="block page-blog">
    <div class="container-fluid">
        <div class="row g-4">
            <div class="col-lg-9 d-flex flex-column gap-4">
                <div class="heading-content">
                    <h2 class="text-gradient d-inline-block">TIN TỨC & SỰ KIỆN</h2>
                    <p>Cập nhật những Tin tức & Sự kiện mới nhất tại Master Chuyên Anh</p>
                </div>
                <div class="row g-3">
                    <?php if (!empty($allPost)): ?>
                        <?php foreach ($allPost as $key => $value): ?>
                            <div class="col-md-6 col-lg-4">
                                <?php include _source."module/card-post.php"; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-4">
                            <p class="text-muted"><?= $d->getTxt(143) ?></p>
                        </div>
                    <?php endif; ?>
                </div>
                <?php include _source."/includes/block-register-test-free.php"; ?>
            </div>
            <div class="col-lg-3 d-flex flex-column gap-4">
                <?php include _source."/includes/block-course-list.php"; ?>
                <?php include _source."/includes/block-roadmap-consultation.php"; ?>
                <?php include _source."/includes/block-featured-post.php"; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <?= getPaginationHTML($total_posts, $limit, $page) ?>
            </div>
        </div>
    </div>
</div>