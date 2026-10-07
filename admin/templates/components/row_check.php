<?php
/**
 * Component: row_check
 * Checkbox chọn 1 dòng để thực hiện tác vụ hàng loạt (xoá nhiều).
 *
 * Tên field giữ nguyên `chk_child[]` để tương thích controller hiện tại
 * (xoadulieu_mang đọc $_POST['chk_child']).
 *
 * Props:
 * @var string|int $value  Giá trị gửi lên (id_code)
 * @var bool       $show   Có quyền xoá thì mới hiện
 * @var string     $label  (tuỳ chọn) aria-label
 */
$value = $value ?? '';
$show  = !empty($show);
$label = $label ?? 'Chọn dòng';
?>
<?php if ($show): ?>
    <input type="checkbox"
           class="form-check-input js-row-check"
           name="chk_child[]"
           value="<?= e($value) ?>"
           aria-label="<?= e($label) ?>">
<?php endif; ?>