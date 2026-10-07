<?php
/**
 * Component: form/actions_bar
 * Nhóm nút hành động của form: Quay lại / Lưu / Lưu và Sửa.
 *
 * Giữ nguyên name="capnhat" và value 'capnhat' | 'capnhat_sua' để controller
 * hiện tại (luudulieu) hoạt động không cần sửa.
 *
 * Props:
 * @var string $back_url
 * @var bool   $can_edit   Không có quyền -> chỉ hiện nút Quay lại
 * @var bool   $sticky     true = dính đáy khung
 * @var string $save_label
 * @var bool   $save_edit  false = ẩn nút "Lưu và Sửa"
 * @var bool   $save_add   true  = hiện nút "Lưu và Thêm mới" (value capnhat_them)
 * @var string $save_add_label
 */
$back_url    = $back_url    ?? '';
$cancel_url   = $cancel_url   ?? '';
$can_edit     = !empty($can_edit);
$sticky       = $sticky       ?? true;
$save_label   = $save_label   ?? 'Lưu';
$save_edit    = $save_edit    ?? true;
$save_add     = !empty($save_add);
$save_add_label = $save_add_label ?? 'Lưu và Thêm mới';
?>
<div class="d-flex flex-wrap gap-2 <?= $sticky ? 'sticky-bottom bg-body border-top pt-3 mt-3' : '' ?>">
    <?php if ($back_url !== ''): ?>
        <a href="<?= e($back_url) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-arrow-left me-1"></i>Quay lại
        </a>
    <?php endif; ?>

    <?php if ($cancel_url !== ''): ?>
        <a href="<?= e($cancel_url) ?>" class="btn btn-sm btn-outline-warning">
            <i class="fa-solid fa-xmark me-1"></i>Hủy
        </a>
    <?php endif; ?>

    <?php if ($can_edit): ?>
        <button type="submit" name="capnhat" value="capnhat" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-floppy-disk me-1"></i><?= e($save_label) ?>
        </button>
        <?php if ($save_edit): ?>
            <button type="submit" name="capnhat" value="capnhat_sua" class="btn btn-sm btn-outline-primary">
                <i class="fa-regular fa-floppy-disk me-1"></i>Lưu và Sửa
            </button>
        <?php endif; ?>
        <?php if ($save_add): ?>
            <button type="submit" name="capnhat" value="capnhat_them" class="btn btn-sm btn-outline-success">
                <i class="fa-solid fa-plus me-1"></i><?= e($save_add_label) ?>
            </button>
        <?php endif; ?>
    <?php endif; ?>
</div>