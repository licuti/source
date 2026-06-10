<?php
/**
 * Component Gallery Upload tích hợp CKFinder & SortableJS
 * Các tham số cần truyền vào:
 * @param string $name Tên biến name của input ẩn (VD: 'gallery[]'). PHẢI CÓ [] Ở CUỐI.
 * @param array $values Mảng các giá trị ảnh hiện tại
 * @param string $id Tên id (nếu rỗng sẽ tự tạo id unique)
 * @param string $label Nhãn (Mặc định: 'Thư viện ảnh')
 * @param string $path Đường dẫn hiển thị ảnh (Mặc định: '/img_data/images/')
 * @param string $help_text Chú thích nhỏ bên dưới
 */
$name = $name ?? 'gallery[]';
$values = $values ?? [];
$id = $id ?? uniqid('gallery_');
$label = $label ?? 'Thư viện ảnh (Gallery)';
$path = rtrim($path ?? '/img_data/images/', '/') . '/';
$help_text = $help_text ?? 'Bạn có thể chọn nhiều ảnh, kéo thả để sắp xếp thứ tự.';
$attrs = $attrs ?? [];

$attrString = render_attrs($attrs);
?>

<div class="mb-3 gallery-component" id="gallery_wrap_<?= $id ?>">
    <?php if ($label): ?>
        <label class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    
    <div class="mb-2">
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="openGalleryCKFinder('<?= $id ?>', '<?= $path ?>', '<?= $name ?>')">
            <i class="fa-solid fa-images"></i> Chọn nhiều ảnh
        </button>
    </div>

    <!-- Grid chứa ảnh -->
    <div class="gallery-grid d-flex flex-wrap gap-2 mt-2 p-2 border rounded bg-light" id="<?= $id ?>" style="min-height: 120px;">
        <?php foreach ($values as $img): ?>
            <?php if (empty($img)) continue; ?>
            <div class="gallery-item position-relative border bg-white shadow-sm rounded" style="width: 100px; height: 100px; cursor: grab;">
                <img src="<?= $path . $img ?>" class="w-100 h-100 object-fit-cover rounded" alt="Gallery Image">
                <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($img) ?>">
                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle p-0 d-flex justify-content-center align-items-center" style="width: 20px; height: 20px; font-size: 10px;" onclick="this.parentElement.remove()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endforeach; ?>
        <?php if (empty($values)): ?>
            <div class="w-100 h-100 d-flex justify-content-center align-items-center text-muted fst-italic empty-msg">
                Chưa có ảnh nào được chọn...
            </div>
        <?php endif; ?>
    </div>

    <?php if ($help_text): ?>
        <small class="text-muted fst-italic mt-1 d-block"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>

<!-- Đảm bảo CKFinder script được nhúng -->
<?php if (!defined('CKFINDER_SCRIPT_LOADED')): ?>
    <?php define('CKFINDER_SCRIPT_LOADED', true); ?>
    <script src="/assets/admin/ckfinder/ckfinder.js"></script>
<?php endif; ?>

<!-- Tải SortableJS từ CDN nếu chưa có -->
<?php if (!defined('SORTABLEJS_LOADED')): ?>
    <?php define('SORTABLEJS_LOADED', true); ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<?php endif; ?>

<?php if (!defined('GALLERY_SCRIPT_LOADED')): ?>
    <?php define('GALLERY_SCRIPT_LOADED', true); ?>
    <script>
        // Kích hoạt Sortable cho tất cả các grid gallery trên trang
        document.addEventListener('DOMContentLoaded', function() {
            initGallerySortables();
        });

        function initGallerySortables() {
            var grids = document.querySelectorAll('.gallery-grid');
            grids.forEach(function(grid) {
                if (grid.sortableInstance) return;
                grid.sortableInstance = new Sortable(grid, {
                    animation: 150,
                    ghostClass: 'bg-primary',
                    onStart: function() { grid.classList.add('grabbing'); },
                    onEnd: function() { grid.classList.remove('grabbing'); }
                });
            });
        }

        function openGalleryCKFinder(gridId, basePath, inputName) {
            CKFinder.modal({
                chooseFiles: true,
                width: 800,
                height: 600,
                onInit: function(finder) {
                    finder.on('files:choose', function(evt) {
                        var files = evt.data.files.toArray();
                        var grid = document.getElementById(gridId);
                        
                        // Xóa dòng chữ "Chưa có ảnh nào" nếu có
                        var emptyMsg = grid.querySelector('.empty-msg');
                        if (emptyMsg) emptyMsg.remove();

                        files.forEach(function(file) {
                            var fullPath = file.getUrl();
                            var fileName = fullPath;
                            
                            // Lấy đường dẫn tương đối
                            if (basePath && fullPath.startsWith(basePath)) {
                                fileName = fullPath.substring(basePath.length);
                            } else if (fullPath.indexOf('/images/') !== -1) {
                                fileName = fullPath.substring(fullPath.indexOf('/images/') + 8);
                            } else {
                                fileName = fullPath.substring(fullPath.lastIndexOf('/') + 1);
                            }

                            // Tạo HTML element cho ảnh mới
                            var item = document.createElement('div');
                            item.className = 'gallery-item position-relative border bg-white shadow-sm rounded';
                            item.style.width = '100px';
                            item.style.height = '100px';
                            item.style.cursor = 'grab';
                            
                            item.innerHTML = `
                                <img src="${fullPath}" class="w-100 h-100 object-fit-cover rounded" alt="Gallery Image" style="object-fit: cover;">
                                <input type="hidden" name="${inputName}" value="${fileName}">
                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-1 rounded-circle p-0 d-flex justify-content-center align-items-center" style="width: 20px; height: 20px; font-size: 10px;" onclick="this.parentElement.remove()">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            `;
                            grid.appendChild(item);
                        });
                        
                        // Khởi tạo lại sortable nếu cần
                        initGallerySortables();
                    });
                }
            });
        }
    </script>
    <style>
        .gallery-grid.grabbing .gallery-item { cursor: grabbing !important; }
        .gallery-item:hover button { opacity: 1 !important; }
    </style>
<?php endif; ?>
