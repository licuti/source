<?php
/**
 * Component: switch_toggle
 * Công tắc bật/tắt 1 cột boolean, lưu ngay bằng AJAX.
 *
 * Việc lưu do admin-ui.js xử lý qua AdminUI.http (endpoint
 * sources/aj_checkbox.php). Xem ghi chú phía dưới.
 *
 * Props:
 * @var string $table    Tên bảng, vd '#_category'
 * @var string $col      Tên cột, vd 'hien_thi'
 * @var string|int $id   id_code của dòng
 * @var bool   $checked  Trạng thái hiện tại
 * @var bool   $disabled Không có quyền sửa -> khoá
 * @var string $title    (tuỳ chọn) tooltip
 */
$table    = $table    ?? '';
$col      = $col      ?? '';
$id       = $id       ?? '';
$checked  = !empty($checked);
$disabled = !empty($disabled);
$title    = $title    ?? '';

// id duy nhất để <label> gắn với <input> (hỗ trợ accessibility)
$inputId = 'sw_' . e($col) . '_' . e($id);

/* Dùng class 'js-toggle' riêng để không lẫn với checkbox chọn dòng. */
?>
<div class="form-check form-switch d-inline-flex justify-content-center m-0">
    <input class="form-check-input js-toggle"
           type="checkbox"
           role="switch"
           id="<?= $inputId ?>"
           data-table="<?= e($table) ?>"
           data-col="<?= e($col) ?>"
           data-id="<?= e($id) ?>"
           <?= $checked  ? 'checked' : '' ?>
           <?= $disabled ? 'disabled' : '' ?>
           <?= $title !== '' ? 'title="' . e($title) . '"' : '' ?>>
    <label class="visually-hidden" for="<?= $inputId ?>"><?= e($title !== '' ? $title : $col) ?></label>
</div>