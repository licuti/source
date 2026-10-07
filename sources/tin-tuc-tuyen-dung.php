<div class="block page-recruitment">
    <div class="container-fluid">
        <div class="row g-4">
            <?php if (!empty($allPost)): ?>
                <?php foreach ($allPost as $key => $value): ?>
                    <div class="col-md-6">
                        <?php include _source."module/card-post.php"; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted"><?= $d->getTxt(143) ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?= getPaginationHTML($total_posts, $limit, $page) ?>
    </div>
</div>