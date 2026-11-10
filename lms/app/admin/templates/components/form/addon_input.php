<?php
/**
 * LMS Form Component: form/addon_input
 * Ô nhập có tiền tố/hậu tố đơn vị (tick, đồng, %). Hỗ trợ chế độ readonly/disabled nền xám.
 *
 * Props:
 * @var string $name
 * @var string $label
 * @var string $suffix      Nhãn hậu tố (VD: 'tick', 'đồng', '%')
 * @var string $prefix      Nhãn tiền tố
 * @var string $type        text|number (default: text)
 * @var string $placeholder
 * @var mixed  $value
 * @var bool   $readonly
 * @var bool   $disabled
 * @var string $step
 * @var string $min
 * @var string $max
 * @var string $hint
 * @var string $class      Class nối thêm vào input trong nhóm
 * @var array  $row
 * @var array  $errors
 */

$name        = isset($name) ? (string) $name : '';
$label       = isset($label) ? (string) $label : '';
$suffix      = isset($suffix) ? (string) $suffix : '';
$prefix      = isset($prefix) ? (string) $prefix : '';
$type        = isset($type) ? (string) $type : 'text';
$placeholder = isset($placeholder) ? (string) $placeholder : '';
$readonly    = !empty($readonly);
$disabled    = !empty($disabled);
$hint        = isset($hint) ? (string) $hint : '';
$id          = isset($id) && $id !== '' ? (string) $id : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);

if (!isset($value)) {
    $rowArr = isset($row) && is_array($row) ? $row : array();
    $postArr = isset($_POST) && is_array($_POST) ? $_POST : array();
    $defaultVal = isset($default) ? $default : '';
    $value = function_exists('lms_form_old') ? lms_form_old($postArr, $rowArr, $name, $defaultVal) : (isset($rowArr[$name]) ? $rowArr[$name] : $defaultVal);
}

$required    = !empty($required);

$groupClass = 'lms-tf-addon-group';
if ($readonly) {
    $groupClass .= ' lms-tf-addon-group-readonly';
}

$inputClass = 'lms-tf-addon-input';
if (isset($class) && $class !== '') {
    $inputClass .= ' ' . trim((string) $class);
}

$controlHtml = '<div class="' . $groupClass . '">';
if ($prefix !== '') {
    $controlHtml .= '<span class="lms-tf-addon-label">' . htmlspecialchars($prefix, ENT_QUOTES, 'UTF-8') . '</span>';
}

$controlHtml .= '<input type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"'
    . ' class="' . htmlspecialchars($inputClass, ENT_QUOTES, 'UTF-8') . '"'
    . ' id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"'
    . ($name !== '' ? ' name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"' : '')
    . ' value="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"'
    . ' placeholder="' . htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8') . '"'
    . ($required ? ' required' : '')
    . ($readonly ? ' readonly' : '')
    . ($disabled ? ' disabled' : '')
    . (isset($step) ? ' step="' . htmlspecialchars((string) $step, ENT_QUOTES, 'UTF-8') . '"' : '')
    . (isset($min) ? ' min="' . htmlspecialchars((string) $min, ENT_QUOTES, 'UTF-8') . '"' : '')
    . (isset($max) ? ' max="' . htmlspecialchars((string) $max, ENT_QUOTES, 'UTF-8') . '"' : '')
    . '>';

if ($suffix !== '') {
    $controlHtml .= '<span class="lms-tf-addon-label">' . htmlspecialchars($suffix, ENT_QUOTES, 'UTF-8') . '</span>';
}
$controlHtml .= '</div>';

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
