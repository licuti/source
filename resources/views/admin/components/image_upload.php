<?php
/**
 * Component Upload Ảnh tích hợp CKFinder
 * Các tham số cần truyền vào:
 * @param string $name Tên biến name của input ẩn (VD: 'hinh_anh')
 * @param string $value Giá trị ảnh hiện tại (tên file ảnh cũ)
 * @param string $id Tên id (nếu rỗng sẽ tự lấy bằng $name)
 * @param string $label Nhãn (Mặc định: 'Chọn hình ảnh')
 * @param string $path Đường dẫn hiển thị ảnh cũ (Mặc định: '/img_data/images/')
 */
$name = $name ?? '';
$value = $value ?? '';
$label = $label ?? 'Chọn hình ảnh';
$path = $path ?? '/img_data/images/';
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

if (!isset($attrs['id'])) {
    $attrs['id'] = $name ?: uniqid('img_');
}
$id = $attrs['id'];

$imageSrc = (!empty($value)) ? $path . $value : '/assets/admin/img/no-image.png';

$attrString = render_attrs($attrs);
?>
<div class="mb-3">
    <?php if ($label): ?>
        <label class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    <div class="input-group input-group-sm">
        <input type="text" class="form-control" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value ?? '') ?>" readonly <?= $attrString ?>>
        <button class="btn btn-outline-secondary" type="button" onclick="openCKFinder('<?= htmlspecialchars($id) ?>', '<?= htmlspecialchars($path) ?>')">Chọn ảnh</button>
    </div>
    <div class="mt-2 text-center" style="max-width: 200px; border: 1px dashed #ced4da; padding: 5px; border-radius: 4px; background: #f8f9fa;">
        <img src="<?= $imageSrc ?>" id="preview_<?= htmlspecialchars($id) ?>" alt="Preview" style="max-width: 100%; height: auto;">
    </div>
    <?php if ($help_text): ?>
        <small class="text-muted fst-italic"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>

<!-- Đảm bảo CKFinder script được nhúng 1 lần duy nhất -->
<?php if (!defined('CKFINDER_SCRIPT_LOADED')): ?>
    <?php define('CKFINDER_SCRIPT_LOADED', true); ?>
    <script src="/assets/admin/ckfinder/ckfinder.js"></script>
<?php endif; ?>

<?php if (!defined('IMAGE_UPLOAD_SCRIPT_LOADED')): ?>
    <?php define('IMAGE_UPLOAD_SCRIPT_LOADED', true); ?>
    <script>
        function openCKFinder(inputId, basePath) {
            CKFinder.modal({
                chooseFiles: true,
                width: 800,
                height: 600,
                onInit: function(finder) {
                    finder.on('files:choose', function(evt) {
                        var file = evt.data.files.first();
                        var fullPath = file.getUrl();
                        
                        // Lấy đường dẫn tương đối để lưu DB
                        var fileName = fullPath;
                        if (basePath && fullPath.startsWith(basePath)) {
                            fileName = fullPath.substring(basePath.length);
                        } else if (fullPath.indexOf('/images/') !== -1) {
                            fileName = fullPath.substring(fullPath.indexOf('/images/') + 8);
                        } else {
                            fileName = fullPath.substring(fullPath.lastIndexOf('/') + 1);
                        }
                        
                        document.getElementById(inputId).value = fileName;
                        document.getElementById('preview_' + inputId).src = fullPath;
                    });
                    
                    finder.on('file:choose:resizedImage', function(evt) {
                        var fullPath = evt.data.resizedUrl;
                        
                        var fileName = fullPath;
                        if (basePath && fullPath.startsWith(basePath)) {
                            fileName = fullPath.substring(basePath.length);
                        } else if (fullPath.indexOf('/images/') !== -1) {
                            fileName = fullPath.substring(fullPath.indexOf('/images/') + 8);
                        } else {
                            fileName = fullPath.substring(fullPath.lastIndexOf('/') + 1);
                        }
                        
                        document.getElementById(inputId).value = fileName;
                        document.getElementById('preview_' + inputId).src = fullPath;
                    });
                }
            });
        }
    </script>
<?php endif; ?>
