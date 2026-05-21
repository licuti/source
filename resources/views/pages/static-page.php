<?php
/**
 * View: Trang nội dung tĩnh
 * Hiển thị dữ liệu từ db_page
 */
?>

<article class="static-page py-5">
    <div class="container">
        <header class="page-header mb-4">
            <h1 class="display-4"><?= $row->ten ?? 'Tiêu đề trang' ?></h1>
            <hr>
        </header>

        <div class="page-content">
            <?php if (!empty($row->noi_dung)): ?>
                <?= $row->noi_dung ?>
            <?php else: ?>
                <p class="text-muted">Nội dung đang được cập nhật...</p>
            <?php endif; ?>
        </div>
    </div>
</article>
