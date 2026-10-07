<?php
/**
 * Component: form/input
 * Ô input 1 dòng, layout dọc (label trên, control dưới).
 *
 * Props:
 * @var string $label
 * @var string $name
 * @var string $id
 * @var mixed  $value
 * @var string $type        text|number|email|url... (mặc định text)
 * @var string $placeholder
 * @var array|null $col     null = dọc (mặc định). [label, control] nếu muốn ngang.
 * @var bool   $required
 * @var bool   $disabled
 * @var bool   $readonly
 * @var string $hint
 * @var string $note
 * @var string $class       class thêm cho input
 * @var array  $attr        attribute thêm, vd ['onkeyup' => '...']
 * @var string $prefix      (tuỳ chọn) text đứng trước input (input-group)
 * @var string $suffix      (tuỳ chọn) text đứng sau input
 * @var string $icon_prefix (tuỳ chọn) icon class đứng trước input
 * @var string $icon_suffix (tuỳ chọn) icon class đứng sau input
 * @var string $error       (tuỳ chọn) thông báo lỗi, thêm class is-invalid
 * @var bool   $valid       (tuỳ chọn) true thêm class is-valid
 */
$label       = $label       ?? '';
$name        = $name        ?? '';
$id          = $id          ?? '';
$value       = $value       ?? '';
$type        = $type        ?? 'text';
$placeholder = $placeholder ?? '';
$col         = $col         ?? null;
$required    = !empty($required);
$disabled    = !empty($disabled);
$readonly    = !empty($readonly);
$hint        = $hint        ?? '';
$note        = $note        ?? '';
$class       = $class       ?? '';
$attr        = $attr        ?? [];
$prefix      = $prefix      ?? '';
$suffix      = $suffix      ?? '';
$icon_prefix = $icon_prefix ?? '';
$icon_suffix = $icon_suffix ?? '';
$error       = $error       ?? '';
$valid       = !empty($valid);

$inputClass = 'form-control form-control-sm ' . $class;
if ($error !== '') {
    $inputClass .= ' is-invalid';
} elseif ($valid) {
    $inputClass .= ' is-valid';
}

$control = '<input' . attrs(array_merge([
    'type'        => $type,
    'class'       => trim($inputClass),
    'name'        => $name,
    'id'          => $id,
    'value'       => $value,
    'placeholder' => $placeholder,
    'required'    => $required,
    'disabled'    => $disabled,
    'readonly'    => $readonly,
], $attr)) . '>';

if ($prefix !== '' || $suffix !== '' || $icon_prefix !== '' || $icon_suffix !== '') {
    $control = '<div class="input-group">'
        . ($icon_prefix !== '' ? '<span class="input-group-text"><i class="' . e($icon_prefix) . '"></i></span>' : '')
        . ($prefix !== '' ? '<span class="input-group-text">' . e($prefix) . '</span>' : '')
        . $control
        . ($suffix !== '' ? '<span class="input-group-text">' . e($suffix) . '</span>' : '')
        . ($icon_suffix !== '' ? '<span class="input-group-text"><i class="' . e($icon_suffix) . '"></i></span>' : '')
        . '</div>';
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