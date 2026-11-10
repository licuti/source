<?php
/**
 * LMS Form Component: form/input
 * Ô nhập văn bản / số / email / sđt chuẩn hoá.
 *
 * Props:
 * @var string $name
 * @var string $label
 * @var string $type        text|email|tel|number|password (default: text)
 * @var string $placeholder
 * @var mixed  $value
 * @var bool   $required
 * @var bool   $readonly
 * @var bool   $disabled
 * @var bool   $uppercase
 * @var string $hint
 * @var string $class       Class bổ sung, ĐƯA THÊM vào class gốc (không thay thế).
 * @var bool   $base_class  Mặc định true. Truyền false chỉ khi thật sự cần bỏ style LMS.
 * @var array  $row
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : '';
$label       = isset($label) ? (string) $label : '';
$type        = isset($type) ? (string) $type : 'text';
$placeholder = isset($placeholder) ? (string) $placeholder : '';
$required    = !empty($required);
$readonly    = !empty($readonly);
$disabled    = !empty($disabled);
$uppercase   = !empty($uppercase);
$hint        = isset($hint) ? (string) $hint : '';
$class       = isset($class) ? (string) $class : '';
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);

// Tự động giải quyết giá trị (sticky form_old)
if (!isset($value)) {
    $rowArr = isset($row) && is_array($row) ? $row : array();
    $postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
    $defaultVal = isset($default) ? $default : '';
    $value = function_exists('lms_form_old') ? lms_form_old($postArr, $rowArr, $name, $defaultVal) : (isset($rowArr[$name]) ? $rowArr[$name] : $defaultVal);
}

// Class gốc luôn được giữ để input đồng bộ style với các field khác (lms.css nạp sau
// Bootstrap nên .lms-tf-input thắng .form-control khi truyền thêm class).
$inputClasses = array();
if (!isset($base_class) || (bool) $base_class) {
    $inputClasses[] = 'lms-tf-input';
}
if ($class !== '') {
    $inputClasses[] = $class;
}
if ($uppercase) {
    $inputClasses[] = 'text-uppercase';
}
$inputClasses = array_values(array_unique($inputClasses));

$attrList = array(
    'type'        => $type,
    'id'          => $id,
    'name'        => $name,
    'value'       => (string) $value,
    'class'       => implode(' ', $inputClasses),
    'placeholder' => $placeholder,
);
if ($required) $attrList['required'] = true;
if ($readonly) $attrList['readonly'] = true;
if ($disabled) $attrList['disabled'] = true;
if (isset($min)) $attrList['min'] = $min;
if (isset($max)) $attrList['max'] = $max;
if (isset($step)) $attrList['step'] = $step;
if (isset($attrs) && is_array($attrs)) {
    foreach ($attrs as $ak => $av) {
        $attrList[$ak] = $av;
    }
}

$attrStr = '';
foreach ($attrList as $attrKey => $attrVal) {
    if ($attrVal === true) {
        $attrStr .= ' ' . $attrKey;
    } elseif ($attrVal !== false && $attrVal !== null) {
        $attrStr .= ' ' . $attrKey . '="' . htmlspecialchars((string) $attrVal, ENT_QUOTES, 'UTF-8') . '"';
    }
}

$controlHtml = '<input' . $attrStr . '>';

if (!empty($bare) || !empty($no_wrap) || !empty($no_row)) {
    echo $controlHtml;
    return;
}

lms_component('form/field_row', array(
    'label'    => $label,
    'name'     => $name,
    'id'       => $id,
    'required' => $required,
    'control'  => $controlHtml,
    'hint'     => $hint,
    'errors'   => isset($errors) ? $errors : array(),
));
