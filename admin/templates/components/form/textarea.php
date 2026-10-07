<?php
/**
 * Component: form/textarea
 *
 * Props: giống form/input, thêm:
 * @var int  $rows      Số dòng, mặc định 3
 * @var bool $counter   true = hiện bộ đếm ký tự (dùng cho title/description SEO)
 * @var int  $max_hint  Ngưỡng ký tự khuyến nghị (để đổi màu bộ đếm)
 * @var string $error       (tuỳ chọn) thông báo lỗi, thêm class is-invalid
 * @var bool   $valid       (tuỳ chọn) true thêm class is-valid
 */
$label       = $label       ?? '';
$name        = $name        ?? '';
$id          = $id          ?? '';
$value       = $value       ?? '';
$placeholder = $placeholder ?? '';
$col         = $col         ?? null;
$rows        = (int) ($rows ?? 3);
$required    = !empty($required);
$hint        = $hint        ?? '';
$note        = $note        ?? '';
$class       = $class       ?? '';
$attr        = $attr        ?? [];
$counter     = !empty($counter);
$max_hint    = (int) ($max_hint ?? 0);
$error       = $error       ?? '';
$valid       = !empty($valid);

$textareaClass = 'form-control form-control-sm ' . $class;
if ($error !== '') {
    $textareaClass .= ' is-invalid';
} elseif ($valid) {
    $textareaClass .= ' is-valid';
}

$control = '<textarea' . attrs(array_merge([
    'class'       => trim($textareaClass),
    'name'        => $name,
    'id'          => $id,
    'rows'        => $rows,
    'placeholder' => $placeholder,
    'required'    => $required,
], $attr)) . '>' . e($value) . '</textarea>';

if ($counter && $max_hint > 0) {
    // Bộ đếm ký tự: JS nằm trong admin-ui.js, kích hoạt qua data-counter-for
    $control .= '<div class="form-text js-char-counter" data-counter-for="' . e($id) . '"'
        . ' data-counter-max="' . $max_hint . '"></div>';
}

if ($error !== '') {
    $control .= '<div class="invalid-feedback d-block">' . e($error) . '</div>';
}

component('form/field_wrap', [
    'label'    => $label,
    'for'      => $id,
    'col'      => $col,
    'required' => $required,
    'hint'     => $hint,
    'note'     => $note,
    'slot'     => $control,
]);