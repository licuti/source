<?php
/**
 * Component: stt_input
 * Ô nhập số thứ tự, tự lưu bằng AJAX khi blur.
 *
 * ⚠️ Class .a_stt KHÔNG phải class trang trí mà là **hook JS**: admin.js
 * (2 chỗ) và admin-ui.js đang bắt sự kiện qua selector này. Bỏ đi là mất
 * tính năng tự lưu, nên giữ lại. Phần định dạng đã dùng class Bootstrap 5.
 *
 * Props:
 * @var string     $table Tên bảng, vd '#_category'
 * @var string     $col   Tên cột, mặc định 'so_thu_tu'
 * @var string|int $id    id_code của dòng
 * @var mixed      $value Giá trị hiện tại
 * @var bool       $can_edit Có quyền sửa; nếu không -> chỉ hiện badge
 */
$table    = $table ?? '';
$col      = $col   ?? 'so_thu_tu';
$id       = $id    ?? '';
$value    = $value ?? 0;
$can_edit = !empty($can_edit);
?>
<?php if ($can_edit): ?>
    <input type="number"
           class="a_stt form-control form-control-sm text-center mx-auto"
           style="width:64px"
           value="<?= e($value) ?>"
           data-table="<?= e($table) ?>"
           data-col="<?= e($col) ?>"
           data-id="<?= e($id) ?>"
           aria-label="Số thứ tự">
<?php else: ?>
    <span class="badge text-bg-secondary"><?= e($value) ?></span>
<?php endif; ?>