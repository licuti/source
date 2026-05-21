<?php
    $current_id = $row->id ?? 0;
    
    // Sử dụng NewsModel để trả về object
    $post_sidebar = NewsModel::query()
        ->where('view', '>', 0)
        ->where('id', '!=', $current_id)
        ->where('hien_thi', 1)
        ->where('lang', $_SESSION['lang'] ?? 'vi')
        ->orderBy('view', 'DESC')
        ->limit(12)
        ->get();
?>

<div class="widdget mb-4">
    <h3 class="widdget-title border-bottom pb-2 mb-3 fw-bold h5 text-uppercase"><?= $d->getTxt(116) ?></h3>
    <div class="widdget-content">
        <div class="list-post-sidebar d-flex flex-column gap-3">
            <?php foreach ($post_sidebar as $value): ?> 
                <a href="<?= route('news.show', $value->alias) ?>" class="post-item-sidebar d-flex gap-3 text-decoration-none text-dark hover-opacity transition-300">
                    <div class="thumbnail ratio ratio-4x3 rounded overflow-hidden shadow-sm" style="flex: 0 0 80px;">
                        <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>" class="image-cover">  
                    </div>
                    <div class="content flex-grow-1">
                        <h4 class="title mb-1 small fw-bold text-truncate-2"><?= $value->ten ?></h4>
                        <div class="small opacity-50"><i class="fa-light fa-eye me-1"></i> <?= (int)$value->view ?></div>
                    </div>
                </a>
            <?php endforeach ?>
        </div>
    </div>
</div>