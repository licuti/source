<?php
/**
 * Component: page_header
 * Tiêu đề trang + breadcrumb (chuẩn AdminLTE 4 / Bootstrap 5).
 *
 * CHỈ phụ trách header (title, subtitle, breadcrumb, actions). KHÔNG chứa
 * form tìm kiếm vì:
 *  - Tìm kiếm của danh sách đã do components/toolbar.php đảm nhiệm
 *    (ô #key-search + #search-input).
 *  - Form là thứ đặc thù từng trang, nếu gộp vào đây sẽ phình prop và
 *    kéo component lệch khỏi mục đích "header".
 * Nếu 1 trang cần ô tìm nhanh riêng (vd lọc client-side trên cây danh
 * mục) thì render inline ngay trong template trang đó, đặt ngoài toolbar.
 *
 * Props:
 * @var string $title       Tiêu đề trang
 * @var string $subtitle    (tuỳ chọn) chữ nhỏ cạnh tiêu đề, vd "[Sửa]"
 * @var array  $breadcrumb  Mảng các mục: ['label' => 'Trang chủ', 'url' => '...']
 *                          Mục cuối không có 'url' sẽ là mục active.
 * @var string $actions     (tuỳ chọn) HTML các nút đặt bên phải tiêu đề
 */
$title      = $title      ?? '';
$subtitle   = $subtitle   ?? '';
$breadcrumb = $breadcrumb ?? [];
$actions    = $actions    ?? '';
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-semibold">
                    <?= e($title) ?>
                    <?php if ($subtitle !== ''): ?>
                        <small class="text-secondary fs-6"><?= e($subtitle) ?></small>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="col-sm-6">
                <?php if ($actions !== ''): ?>
                    <div class="d-flex justify-content-sm-end gap-2"><?= $actions ?></div>
                <?php elseif ($breadcrumb): ?>
                    <ol class="breadcrumb float-sm-end mb-0">
                        <?php foreach ($breadcrumb as $crumb):
                            $label = $crumb['label'] ?? '';
                            $url   = $crumb['url']   ?? '';
                            $icon  = $crumb['icon']  ?? '';
                            ?>
                            <?php if ($url !== ''): ?>
                                <li class="breadcrumb-item">
                                    <a href="<?= e($url) ?>">
                                        <?php if ($icon !== ''): ?><i class="<?= e($icon) ?> me-1"></i><?php endif; ?>
                                        <?= e($label) ?>
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="breadcrumb-item active" aria-current="page"><?= e($label) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
