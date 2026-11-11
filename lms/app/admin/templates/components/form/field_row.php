<?php
/**
 * LMS Form Component: form/field_row
 * Khung bao bọc dòng nhập liệu chuẩn 2 cột (Label bên trái | Control bên phải).
 *
 * Props:
 * @var string      $label
 * @var string      $name
 * @var string      $id
 * @var bool        $required
 * @var string      $control     HTML control đã render
 * @var string      $hint        Dòng hướng dẫn nhỏ bên dưới control
 * @var array       $errors      Mảng lỗi validation
 * @var string      $error       Thông báo lỗi cụ thể (nếu có)
 * @var string      $label_col   (default: 'col-12 col-sm-3')
 * @var string      $control_col (default: 'col-12 col-sm-9')
 * @var string      $label_align (default: 'text-sm-end')
 * @var string      $align       'start' | 'center' (default: 'center')
 */

$label    = isset($label) ? (string) $label : '';
$name     = isset($name) ? (string) $name : '';
$id       = isset($id) && $id !== '' ? (string) $id : ($name !== '' ? 'fld-' . $name : '');
$required = !empty($required);
$control  = isset($control) ? (string) $control : '';
$hint     = isset($hint) ? (string) $hint : '';
$errors   = isset($errors) && is_array($errors) ? $errors : array();

$fieldErr = isset($error) && $error !== ''
    ? $error
    : ($name !== '' && function_exists('lms_field_error') ? lms_field_error($errors, $name) : '');
$alignClass = (isset($align) && $align === 'start') ? 'align-items-start' : 'align-items-center';

$labelCol   = isset($label_col) && $label_col !== '' ? (string) $label_col : 'col-12 col-sm-3';
$controlCol = isset($control_col) && $control_col !== '' ? (string) $control_col : 'col-12 col-sm-9';
$labelAlign = isset($label_align) && $label_align !== '' ? (string) $label_align : 'text-sm-end';
$labelClass = $labelCol . ' col-form-label ' . $labelAlign . ' lms-tf-label' . ((isset($align) && $align === 'start') ? ' pt-sm-2' : '');
?>

<div class="row mb-3 <?= $alignClass ?> lms-tf-row">
    <?php if ($label !== ''): ?>
        <label class="<?= $labelClass ?>" <?= $id !== '' ? 'for="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            <?php if ($required): ?>
                <span class="text-danger">*</span>
            <?php endif; ?>
        </label>
    <?php else: ?>
        <div class="<?= $labelCol ?>"></div>
    <?php endif; ?>

    <div class="<?= $controlCol ?> lms-tf-control">
        <?= $control ?>
        <?php if ($hint !== ''): ?>
            <div class="lms-tf-hint"><?= htmlspecialchars($hint, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <?= $fieldErr ?>
    </div>
</div>
