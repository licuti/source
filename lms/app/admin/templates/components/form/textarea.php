<?php
/**
 * LMS Form Component: form/textarea
 * Ô nhập văn bản nhiều dòng chuẩn hoá.
 *
 * Props:
 * @var string $name
 * @var string $label
 * @var int    $rows        (default: 3)
 * @var string $placeholder
 * @var mixed  $value
 * @var bool   $required
 * @var bool   $readonly
 * @var bool   $disabled
 * @var string $hint
 * @var string $class
 * @var array  $attrs      Thuộc tính HTML bổ sung (maxlength, aria-label, ...)
 * @var array  $row
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : '';
$label       = isset($label) ? (string) $label : '';
$rows        = isset($rows) ? (int) $rows : 3;
$placeholder = isset($placeholder) ? (string) $placeholder : '';
$required    = !empty($required);
$readonly    = !empty($readonly);
$disabled    = !empty($disabled);
$hint        = isset($hint) ? (string) $hint : '';
$class       = isset($class) ? (string) $class : '';
$attrs       = isset($attrs) && is_array($attrs) ? $attrs : array();
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);

// Tự động giải quyết giá trị (sticky form_old)
if (!isset($value)) {
    $rowArr = isset($row) && is_array($row) ? $row : array();
    $postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
    $value = function_exists('lms_form_old') ? lms_form_old($postArr, $rowArr, $name, '') : (isset($rowArr[$name]) ? $rowArr[$name] : '');
}

$inputClasses = array();
if (!isset($base_class) || (bool) $base_class) {
    $inputClasses[] = 'lms-tf-input';
}
if ($class !== '') {
    $inputClasses[] = $class;
} else {
    $inputClasses[] = 'form-control';
}
$inputClasses = array_values(array_unique($inputClasses));

$attrList = array(
    'id'          => $id,
    'name'        => $name,
    'rows'        => $rows,
    'class'       => implode(' ', $inputClasses),
    'placeholder' => $placeholder,
);
if ($required) $attrList['required'] = true;
if ($readonly) $attrList['readonly'] = true;
if ($disabled) $attrList['disabled'] = true;
foreach ($attrs as $ak => $av) {
    $attrList[$ak] = $av;
}

$attrStr = '';
foreach ($attrList as $attrKey => $attrVal) {
    if ($attrVal === true) {
        $attrStr .= ' ' . $attrKey;
    } elseif ($attrVal !== false && $attrVal !== null) {
        $attrStr .= ' ' . $attrKey . '="' . htmlspecialchars((string) $attrVal, ENT_QUOTES, 'UTF-8') . '"';
    }
}

$controlHtml = '<textarea' . $attrStr . '>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</textarea>';

if (!empty($bare) || !empty($no_wrap) || !empty($no_row)) {
    echo $controlHtml;
    return;
}

lms_component('form/field_row', array(
    'label'    => $label,
    'name'     => $name,
    'id'       => $id,
    'align'    => 'start',
    'required' => $required,
    'control'  => $controlHtml,
    'hint'     => $hint,
    'errors'   => isset($errors) ? $errors : array(),
));
