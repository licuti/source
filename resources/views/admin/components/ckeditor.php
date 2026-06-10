<?php
/**
 * Component CKEditor tích hợp CKFinder
 * Các tham số cần truyền vào:
 * @param string $name Tên biến name của textarea
 * @param string $value Giá trị hiện tại
 * @param string $id Tên id (nếu rỗng sẽ tự lấy bằng $name)
 * @param string $label Nhãn
 */
$name = $name ?? '';
$value = $value ?? '';
$label = $label ?? 'Nội dung';
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

// Khởi tạo ID tự động nếu không có
if (!isset($attrs['id'])) {
    // Xử lý ID an toàn nếu name có chứa ngoặc vuông (mảng) VD: noi_dung[vi] -> noi_dung_vi
    $attrs['id'] = str_replace(['[', ']'], ['_', ''], $name);
}
$idSafe = $attrs['id'];

$baseClass = 'form-control ckeditor-instance';
if (isset($attrs['class'])) {
    $attrs['class'] = $baseClass . ' ' . $attrs['class'];
} else {
    $attrs['class'] = $baseClass;
}

if (!isset($attrs['rows'])) $attrs['rows'] = 5;

$attrString = render_attrs($attrs);
?>
<div class="mb-3">
    <?php if ($label): ?>
        <label for="<?= htmlspecialchars($idSafe) ?>" class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    
    <textarea name="<?= htmlspecialchars($name) ?>" <?= $attrString ?>><?= htmlspecialchars((string)$value) ?></textarea>
    
    <?php if ($help_text): ?>
        <small class="text-muted fst-italic"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>

<!-- Tải CKEditor từ thư viện dùng chung (CHỈ TẢI 1 LẦN DUY NHẤT TRÊN TRANG) -->
<?php if (!defined('CKEDITOR_SCRIPT_LOADED')): ?>
    <?php define('CKEDITOR_SCRIPT_LOADED', true); ?>
    <script src="/assets/admin/ckeditor/ckeditor.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof CKEDITOR !== 'undefined') {
                // Tự động tìm tất cả các textarea có class ckeditor-instance và khởi tạo hàng loạt
                document.querySelectorAll('.ckeditor-instance').forEach(function(el) {
                    CKEDITOR.replace(el.id, {
                        filebrowserBrowseUrl: '/assets/admin/ckfinder/ckfinder.html',
                        filebrowserImageBrowseUrl: '/assets/admin/ckfinder/ckfinder.html?type=Images',
                        filebrowserUploadUrl: '/assets/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
                        filebrowserImageUploadUrl: '/assets/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images'
                    });
                });
            }
        });
    </script>
<?php endif; ?>
