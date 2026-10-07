<?php
/**
 * Component: row_actions
 * Nhóm nút tác vụ trên 1 dòng: Sửa / Xóa / (các nút tuỳ ý).
 *
 * Props:
 * @var string $edit_url    URL sửa; rỗng = không hiện nút sửa
 * @var string $delete_url  URL xoá; rỗng = không hiện nút xoá
 * @var string $view_url    (tuỳ chọn) URL xem trên website (mở tab mới)
 * @var string $name        Tên bản ghi (hiện trong hộp thoại xác nhận)
 * @var int    $child_count Số mục con sẽ bị xoá kèm (để cảnh báo)
 * @var bool   $can_edit
 * @var bool   $can_delete
 * @var string $extra       (tuỳ chọn) HTML nút thêm
 */
$edit_url   = $edit_url   ?? '';
$delete_url = $delete_url ?? '';
$view_url   = $view_url   ?? '';
$name       = $name       ?? '';
$child_count = (int) ($child_count ?? 0);
$can_edit   = !empty($can_edit);
$can_delete = !empty($can_delete);
$extra      = $extra      ?? '';
?>
<div class="btn-group btn-group-sm" role="group" aria-label="Tác vụ">
    <?php if ($view_url !== ''): ?>
        <a href="<?= e($view_url) ?>" target="_blank" rel="noopener"
           class="btn btn-outline-secondary" title="Xem trên website">
            <i class="fa-solid fa-arrow-up-right-from-square"></i>
        </a>
    <?php endif; ?>

    <?php if ($can_edit && $edit_url !== ''): ?>
        <a href="<?= e($edit_url) ?>" class="btn btn-sm btn-outline-primary" title="Sửa">
            <i class="fa-solid fa-pen-to-square"></i>
        </a>
    <?php endif; ?>

    <?php if ($can_delete && $delete_url !== ''): ?>
        <a href="<?= e($delete_url) ?>" class="btn btn-sm btn-outline-danger" title="Xóa"
           data-confirm-delete
           data-name="<?= e($name) ?>"
           data-child-count="<?= $child_count ?>">
            <i class="fa-solid fa-trash"></i>
        </a>
    <?php endif; ?>

    <?= $extra ?>
</div>