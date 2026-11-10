<?php
/**
 * LMS Form Component: form/select
 * Dropdown chọn đơn, hỗ trợ nạp mảng options hoặc tự động theo enum.
 *
 * Props:
 * @var string $name
 * @var string $label
 * @var array  $options     Mảng key => label
 * @var string $enum        Tên enum từ LmsEnum (VD: 'teacher_ranks', 'gender', 'teacher_statuses')
 * @var mixed  $value
 * @var bool   $required
 * @var bool   $disabled
 * @var string $empty_label Nhãn cho option rỗng đầu tiên (VD: '— Chọn —')
 * @var string $hint
 * @var array  $row
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : '';
$label       = isset($label) ? (string) $label : '';
$enum        = isset($enum) ? (string) $enum : '';
$required    = !empty($required);
$disabled    = !empty($disabled);
$empty_label = isset($empty_label) ? (string) $empty_label : '';
$hint        = isset($hint) ? (string) $hint : '';
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);

// Tự nạp options theo enum nếu có
$opts = isset($options) && is_array($options) ? $options : array();
if ($enum !== '' && empty($opts) && class_exists('LmsEnum')) {
    $opts = LmsEnum::options($enum);
}

// Tự động giải quyết giá trị
if (!isset($value)) {
    $rowArr = isset($row) && is_array($row) ? $row : array();
    $postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
    $defaultVal = isset($default) ? $default : '';
    $rawVal = function_exists('lms_form_old') ? lms_form_old($postArr, $rowArr, $name, $defaultVal) : (isset($rowArr[$name]) ? $rowArr[$name] : $defaultVal);

    if ($enum !== '' && class_exists('LmsEnum')) {
        $value = LmsEnum::normalize($enum, $rawVal);
    } else {
        $value = (string) $rawVal;
    }
} else {
    if ($enum !== '' && class_exists('LmsEnum')) {
        $value = LmsEnum::normalize($enum, $value);
    } else {
        $value = (string) $value;
    }
}

// Giữ class gốc .lms-tf-select; class truyền thêm được NỐI vào để đồng bộ style.
$selectClasses = array();
if (!isset($base_class) || (bool) $base_class) {
    $selectClasses[] = 'lms-tf-select';
}
if (isset($class) && $class !== '') {
    $selectClasses[] = (string) $class;
}
$selectClass = implode(' ', array_values(array_unique($selectClasses)));
$extraSelectAttrs = '';
if (isset($attrs) && is_array($attrs)) {
    foreach ($attrs as $ak => $av) {
        if ($av === true) {
            $extraSelectAttrs .= ' ' . htmlspecialchars((string) $ak, ENT_QUOTES, 'UTF-8');
        } elseif ($av !== false && $av !== null) {
            $extraSelectAttrs .= ' ' . htmlspecialchars((string) $ak, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $av, ENT_QUOTES, 'UTF-8') . '"';
        }
    }
}

$controlHtml = '<select class="' . htmlspecialchars($selectClass, ENT_QUOTES, 'UTF-8') . '" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"'
    . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
    . ($required ? ' required' : '')
    . ($disabled ? ' disabled' : '')
    . $extraSelectAttrs
    . '>';

if ($empty_label !== '') {
    $controlHtml .= '<option value="">' . htmlspecialchars($empty_label, ENT_QUOTES, 'UTF-8') . '</option>';
}

foreach ($opts as $optVal => $optInfo) {
    if (is_array($optInfo)) {
        $optLabel = isset($optInfo['label']) ? $optInfo['label'] : '';
        $optAttrs = isset($optInfo['attrs']) && is_array($optInfo['attrs']) ? $optInfo['attrs'] : array();
    } else {
        $optLabel = (string) $optInfo;
        $optAttrs = array();
    }
    $attrStr = '';
    foreach ($optAttrs as $ak => $av) {
        $attrStr .= ' ' . htmlspecialchars($ak, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $av, ENT_QUOTES, 'UTF-8') . '"';
    }
    $isSel = ((string) $optVal === (string) $value);
    $controlHtml .= '<option value="' . htmlspecialchars((string) $optVal, ENT_QUOTES, 'UTF-8') . '"'
        . $attrStr
        . ($isSel ? ' selected' : '') . '>'
        . htmlspecialchars((string) $optLabel, ENT_QUOTES, 'UTF-8')
        . '</option>';
}

$controlHtml .= '</select>';

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
