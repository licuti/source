<?php
/**
 * Component: multi_switches
 * Nhiều switch_toggle gộp trong 1 ô (1 dòng = 1 switch kèm nhãn ngắn).
 * Dùng cho cột "Trạng thái" khi muốn gom nhiều cờ boolean vào 1 ô cho gọn bảng.
 * Mỗi switch vẫn dùng class "js-toggle" nên admin-ui.js tự lưu AJAX + hoàn tác.
 *
 * Props:
 * @var string $table    Tên bảng (vd '#_sanpham')
 * @var array  $switches ['tieu_bieu' => 'Nổi bật', ...] cột -> nhãn hiển thị
 * @var array  $row      Dữ liệu 1 dòng (để biết checked ban đầu)
 * @var bool   $disabled (mặc định false) Khoá tất cả switch
 */
$table    = $table    ?? '';
$switches = $switches ?? [];
$row      = $row      ?? [];
$disabled = !empty($disabled);

if (empty($switches)) { return; }
?>
<div class="d-flex flex-column gap-1 text-start">
    <?php foreach ($switches as $col => $label):
        $idKey    = ($row['id_code'] ?? '') . '_' . $col;
        $checked  = ($row[$col] ?? 0) == 1;
    ?>
        <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
            <input class="form-check-input js-toggle"
                   type="checkbox"
                   role="switch"
                   id="ms_<?= e($idKey) ?>"
                   data-table="<?= e($table) ?>"
                   data-col="<?= e($col) ?>"
                   data-id="<?= e($row['id_code'] ?? '') ?>"
                   <?= $checked  ? 'checked' : '' ?>
                   <?= $disabled ? 'disabled' : '' ?>>
            <label class="form-check-label small" for="ms_<?= e($idKey) ?>"><?= e($label) ?></label>
        </div>
    <?php endforeach; ?>
</div>