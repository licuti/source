<div class="block page-blog">
    <div class="container-fluid">
        <div class="row g-4">
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
        <div class="row">
            <div class="col-12 d-flex justify-content-center">
                <?= getPaginationHTML($total_posts, $limit, $page) ?>
            </div>
        </div>
    </div>
</div>