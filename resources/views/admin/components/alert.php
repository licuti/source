<?php
/**
 * Component Alert
 * 
 * Các tham số truyền vào:
 * @param string $type Loại thông báo: success, danger, warning, info, primary (mặc định: info)
 * @param string $message Nội dung thông báo
 * @param bool   $dismissible Bật tính năng nút tắt (X) (mặc định: true)
 * @param string $icon Tên icon FontAwesome (nếu không cấp sẽ tự chọn icon theo $type)
 */

$type = $type ?? 'info';
$message = $message ?? '';
$dismissible = $dismissible ?? true;
$icon = $icon ?? '';

// Auto-select icon based on type if not provided
if (empty($icon)) {
    switch ($type) {
        case 'success':
            $icon = 'fa-check-circle';
            break;
        case 'danger':
            $icon = 'fa-xmark-circle';
            break;
        case 'warning':
            $icon = 'fa-triangle-exclamation';
            break;
        default:
            $icon = 'fa-circle-info';
    }
}
?>

<?php if (!empty($message)): ?>
<div class="alert alert-<?= htmlspecialchars($type) ?> <?= $dismissible ? 'alert-dismissible fade show' : '' ?> shadow-sm" role="alert">
    <?php if ($icon): ?>
        <i class="fa-solid <?= htmlspecialchars($icon) ?> me-2"></i>
    <?php endif; ?>
    <?= $message // Cho phép HTML thô như <strong> hoặc \n được render. Đảm bảo nguồn data đã an toàn. ?>
    
    <?php if ($dismissible): ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    <?php endif; ?>
</div>
<?php endif; ?>
