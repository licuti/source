<?php
/**
 * Component: form/select
 *
 * Props:
 * @var string $label
 * @var string $name
 * @var string $id
 * @var mixed  $value        Giá trị đang chọn
 * @var array  $options      ['value' => 'Nhãn']
 * @var string $options_html HTML <option> dựng sẵn (dùng cho cây danh mục
 *                           do $d->array_category() trả về)
 * @var string $placeholder  Option đầu tiên (value="")
 * @var array  $col
 * @var bool   $required
 * @var bool   $multiple
 * @var string $class        thêm 'select2' nếu muốn dùng Select2
 * @var string $hint
 * @var string $error        (tuỳ chọn) thông báo lỗi, thêm class is-invalid
 * @var bool   $valid        (tuỳ chọn) true thêm class is-valid
 */
$label        = $label        ?? '';
$name         = $name         ?? '';
$id           = $id           ?? '';
$value        = $value        ?? '';
$options      = $options      ?? [];
$options_html = $options_html ?? '';
$placeholder  = $placeholder  ?? '';
$col          = $col          ?? null;
$required     = !empty($required);
$multiple     = !empty($multiple);
$class        = $class        ?? '';
$hint         = $hint         ?? '';
$attr         = $attr         ?? [];
$error        = $error        ?? '';
$valid        = !empty($valid);

$inner = '';

if ($placeholder !== '') {
    $inner .= '<option value="">' . e($placeholder) . '</option>';
}

foreach ($options as $optValue => $optLabel) {
    // So sánh lỏng vì id từ DB có thể là string '5' vs int 5
    $selected = ((string) $optValue === (string) $value) ? ' selected' : '';
    $inner .= '<option value="' . e($optValue) . '"' . $selected . '>' . e($optLabel) . '</option>';
}

// $options_html được tin cậy (do hàm nội bộ sinh ra), không escape.
$inner .= $options_html;

$selectClass = 'form-select form-select-sm ' . $class;
if ($error !== '') {
    $selectClass .= ' is-invalid';
} elseif ($valid) {
    $selectClass .= ' is-valid';
}

$control = '<select' . attrs(array_merge([
    'class'    => trim($selectClass),
    'name'     => $name,
    'id'       => $id,
    'required' => $required,
    'multiple' => $multiple,
], $attr)) . '>' . $inner . '</select>';

if ($error !== '') {
    $control .= '<div class="invalid-feedback d-block">' . e($error) . '</div>';
}

component('form/field_wrap', [
    'label'    => $label,
    'for'      => $id,
    'col'      => $col,
    'required' => $required,
    'hint'     => $hint,
    'slot'     => $control,
]);