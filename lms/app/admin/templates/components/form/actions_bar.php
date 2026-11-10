<?php
/**
 * LMS Form Component: form/actions_bar
 * Thanh nút bấm hành động của form (Lưu / Thêm / Huỷ / Lưu tiếp / Xoá...).
 * Tự động tích hợp và điều phối bộ component button chuẩn.
 *
 * Props:
 * @var string      $align              'center' | 'start' | 'end' | 'between' (mặc định: 'center')
 * @var string      $label              Nhãn nút chính (mặc định tự động: $isEditMode ? 'Lưu' : 'Thêm')
 * @var string      $variant            Biến thể màu nút chính (mặc định: 'primary')
 * @var string      $size               Kích thước nút chính (mặc định: 'lg')
 * @var string      $icon               Icon nút chính (VD: 'fa-solid fa-floppy-disk')
 * @var string      $cancel_url         URL nút huỷ / quay lại (tuỳ chọn)
 * @var string      $cancel_label       Nhãn nút huỷ (mặc định: 'Huỷ')
 * @var string      $cancel_icon        Icon nút huỷ (mặc định: '')
 * @var bool        $save_continue      Hiện nút Lưu & Tiếp tục (mặc định: false)
 * @var string      $save_continue_label Nhãn nút Lưu tiếp (mặc định: 'Lưu & Tiếp tục')
 * @var string      $delete_url         URL xoá trực tiếp (tuỳ chọn)
 * @var string      $delete_confirm     Nội dung xác nhận xoá
 * @var array       $extra_buttons      Danh sách các button bổ sung (mỗi item là mảng props cho button)
 * @var bool        $sticky             Dính đáy màn hình (mặc định: false)
 * @var string      $class              Class CSS bổ sung
 *
 * PHP 7.3 compatible.
 */

global $isEditMode;

// Xử lý nhãn nút chính
$defaultLabel = !empty($isEditMode) ? 'Lưu' : 'Thêm';
$label        = isset($label) && $label !== '' ? (string) $label : $defaultLabel;
$variant      = isset($variant) && $variant !== '' ? (string) $variant : 'primary';
$size         = isset($size) && $size !== '' ? (string) $size : 'lg';
$icon         = isset($icon) ? (string) $icon : '';
$align        = isset($align) && $align !== '' ? (string) $align : 'center';
$cancel_url   = isset($cancel_url) ? (string) $cancel_url : '';
$cancel_label = isset($cancel_label) && $cancel_label !== '' ? (string) $cancel_label : 'Huỷ';
$cancel_icon  = isset($cancel_icon) ? (string) $cancel_icon : '';
$save_continue = !empty($save_continue);
$save_continue_label = isset($save_continue_label) && $save_continue_label !== '' ? (string) $save_continue_label : 'Lưu & Tiếp tục';
$delete_url   = isset($delete_url) ? (string) $delete_url : '';
$delete_confirm = isset($delete_confirm) && $delete_confirm !== '' ? (string) $delete_confirm : 'Bạn chắc chắn muốn xoá bản ghi này?';
$extra_buttons = isset($extra_buttons) && is_array($extra_buttons) ? $extra_buttons : array();
$sticky       = !empty($sticky);
$extraCls     = isset($class) ? (string) $class : '';

// Căn lề flexbox
$alignClasses = array(
    'center'  => 'justify-content-center text-center',
    'start'   => 'justify-content-start text-start',
    'end'     => 'justify-content-end text-end',
    'between' => 'justify-content-between align-items-center',
);
$justifyCls = isset($alignClasses[$align]) ? $alignClasses[$align] : 'justify-content-center text-center';

$containerClasses = array('row', 'mt-4', 'mb-5');
if ($sticky) {
    $containerClasses[] = 'sticky-bottom bg-white border-top py-3 shadow-sm';
}
if ($extraCls !== '') {
    $containerClasses[] = $extraCls;
}
?>

<div class="<?= implode(' ', $containerClasses) ?>">
    <div class="col-12 d-flex flex-wrap gap-2 <?= $justifyCls ?>">
        <?php if ($align === 'between' && $cancel_url !== ''): ?>
            <!-- Nút huỷ bên trái khi dùng layout between -->
            <?php lms_component('button', array(
                'label'   => $cancel_label,
                'href'    => $cancel_url,
                'variant' => 'outline-secondary',
                'size'    => $size === 'lg' ? 'md' : $size,
                'icon'    => $cancel_icon !== '' ? $cancel_icon : 'fa-solid fa-arrow-left',
            )); ?>
        <?php endif; ?>

        <div class="d-flex flex-wrap gap-2 align-items-center <?= ($align === 'center') ? 'justify-content-center' : '' ?>">
            <!-- 1. Nút Submit chính -->
            <?php lms_component('button', array(
                'label'   => $label,
                'type'    => 'submit',
                'variant' => $variant,
                'size'    => $size,
                'icon'    => $icon,
                'name'    => 'action_save',
                'value'   => 'save',
            )); ?>

            <!-- 2. Nút Lưu và Tiếp tục (tuỳ chọn) -->
            <?php if ($save_continue): ?>
                <?php lms_component('button', array(
                    'label'   => $save_continue_label,
                    'type'    => 'submit',
                    'variant' => 'outline-primary',
                    'size'    => $size,
                    'name'    => 'action_save',
                    'value'   => 'save_continue',
                    'icon'    => 'fa-regular fa-floppy-disk',
                )); ?>
            <?php endif; ?>

            <!-- 3. Nút Huỷ / Quay lại (nếu không phải layout between) -->
            <?php if ($align !== 'between' && $cancel_url !== ''): ?>
                <?php lms_component('button', array(
                    'label'   => $cancel_label,
                    'href'    => $cancel_url,
                    'variant' => 'outline-secondary',
                    'size'    => $size === 'lg' ? 'md' : $size,
                    'icon'    => $cancel_icon,
                )); ?>
            <?php endif; ?>

            <!-- 4. Nút Xoá (nếu có trong form sửa) -->
            <?php if ($delete_url !== ''): ?>
                <?php lms_component('button', array(
                    'label'   => 'Xoá',
                    'href'    => $delete_url,
                    'variant' => 'outline-danger',
                    'size'    => $size === 'lg' ? 'md' : $size,
                    'icon'    => 'fa-solid fa-trash',
                    'confirm' => $delete_confirm,
                )); ?>
            <?php endif; ?>

            <!-- 5. Các nút tuỳ biến bổ sung -->
            <?php foreach ($extra_buttons as $btnProps): ?>
                <?php if (is_array($btnProps)): ?>
                    <?php lms_component('button', $btnProps); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</div>
