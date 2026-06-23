<?php
/**
 * Daterange Picker Component
 * 
 * @param string $name Tên biến input (mặc định: 'date_range')
 * @param string $value Giá trị hiện tại
 * @param string $placeholder Placeholder (mặc định: 'Chọn khoảng thời gian')
 * @param string $width Chiều rộng của khối input (mặc định: '260px')
 * @param bool $include_assets Tự động load CSS/JS (mặc định: true)
 */

$name = $name ?? 'date_range';
$value = $value ?? '';
$placeholder = $placeholder ?? 'Chọn khoảng thời gian';
$width = $width ?? '260px';
$include_assets = $include_assets ?? true;
?>

<div class="input-group" style="width: <?= $width ?>;">
    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-regular fa-calendar"></i></span>
    <input type="text" name="<?= $name ?>" class="form-control border-start-0 daterange-picker-init ps-0" value="<?= htmlspecialchars($value) ?>" placeholder="<?= htmlspecialchars($placeholder) ?>" style="cursor: pointer;" readonly>
</div>

<?php if ($include_assets): ?>
<!-- Load once: Daterangepicker Assets -->
<?php if (!defined('DATERANGE_ASSETS_LOADED')): ?>
    <?php define('DATERANGE_ASSETS_LOADED', true); ?>
    
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Khởi tạo Daterangepicker cho mọi phần tử có class 'daterange-picker-init'
        $('.daterange-picker-init').daterangepicker({
            autoUpdateInput: false,
            locale: {
                format: 'DD/MM/YYYY',
                applyLabel: 'Áp dụng',
                cancelLabel: 'Hủy',
                daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                monthNames: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                firstDay: 1
            }
        });
        
        // Cập nhật giá trị khi người dùng bấm "Áp dụng"
        $(document).on('apply.daterangepicker', '.daterange-picker-init', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        });

        // Xóa giá trị khi người dùng bấm "Hủy"
        $(document).on('cancel.daterangepicker', '.daterange-picker-init', function(ev, picker) {
            $(this).val('');
        });
    });
    </script>
<?php endif; ?>
<?php endif; ?>
