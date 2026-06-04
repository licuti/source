<?php
/**
 * Component CKEditor tích hợp CKFinder
 * Các tham số cần truyền vào:
 * @param string $name Tên biến name của textarea
 * @param string $value Giá trị hiện tại
 * @param string $id Tên id (nếu rỗng sẽ tự lấy bằng $name)
 * @param string $label Nhãn
 */
$id = $id ?? $name;
$label = $label ?? 'Nội dung';

// Xử lý ID an toàn nếu name có chứa ngoặc vuông (mảng) VD: noi_dung[vi] -> noi_dung_vi
$idSafe = str_replace(['[', ']'], ['_', ''], $id);
?>
<div class="mb-3">
    <label for="<?= htmlspecialchars($idSafe) ?>" class="form-label"><?= htmlspecialchars($label) ?></label>
    <textarea name="<?= htmlspecialchars($name) ?>" id="<?= htmlspecialchars($idSafe) ?>" class="form-control ckeditor-instance" rows="5"><?= htmlspecialchars($value ?? '') ?></textarea>
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
