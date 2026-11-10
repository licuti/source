<?php
/**
 * LMS Form Component: form/date_picker
 * Ô chọn ngày kèm icon lịch chuẩn 📅.
 *
 * Props:
 * @var string $name
 * @var string $label
 * @var string $placeholder (default: 04/12/2003)
 * @var mixed  $value
 * @var bool   $required
 * @var bool   $readonly
 * @var bool   $disabled
 * @var string $hint
 * @var string $class       Class nối thêm vào input (class gốc lms-tf-input luôn giữ)
 * @var bool   $bare      Chỉ render control, không bọc field_row
 * @var array  $row
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : '';
$label       = isset($label) ? (string) $label : '';
$placeholder = isset($placeholder) && $placeholder !== '' ? (string) $placeholder : '04/12/2003';
$required    = !empty($required);
$readonly    = !empty($readonly);
$disabled    = !empty($disabled);
$hint        = isset($hint) ? (string) $hint : '';
$class       = isset($class) ? (string) $class : '';
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);

$dateClasses = array_values(array_unique(array_filter(array_merge(
    array('lms-tf-input', 'lms-tf-date'),
    $class !== '' ? preg_split('/\s+/', $class, -1, PREG_SPLIT_NO_EMPTY) : array()
))));

if (!isset($value)) {
    $rowArr = isset($row) && is_array($row) ? $row : array();
    $postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
    $value = function_exists('lms_form_old') ? lms_form_old($postArr, $rowArr, $name, '') : (isset($rowArr[$name]) ? $rowArr[$name] : '');
}

$controlHtml = '<div class="lms-tf-date-wrap">'
    . '<i class="fa-regular fa-calendar lms-tf-calendar-icon"></i>'
    . '<input type="date" class="' . htmlspecialchars(implode(' ', $dateClasses), ENT_QUOTES, 'UTF-8') . '" id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"'
    . ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"'
    . ' value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"'
    . ' placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"'
    . ($required ? ' required' : '')
    . ($readonly ? ' readonly' : '')
    . ($disabled ? ' disabled' : '')
    . '></div>';

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
