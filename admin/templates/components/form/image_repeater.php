<?php
/**
 * Component: form/image_repeater
 * Repeater danh sách item gồm: Tên/Tiêu đề (text) + Ảnh scan/đính kèm (CKFinder).
 * Có hỗ trợ kéo thả sắp xếp (SortableJS).
 *
 * Props:
 * @var string $label             Tiêu đề nhóm
 * @var string $name              Tên định danh repeater (mặc định 'repeater')
 * @var array  $items             Mảng các item hiện có: [ ['name' => '...', 'image' => '...'], ... ]
 * @var string $name_field        Tên field input tên (mặc định 'diploma_names[]')
 * @var string $image_field       Tên field input ảnh (mặc định 'diploma_images[]')
 * @var string $name_label        Label cho ô nhập tên (mặc định 'Tên bằng cấp / chứng chỉ:')
 * @var string $name_placeholder  Placeholder cho ô nhập tên
 * @var string $choose_text       Text nút chọn ảnh (mặc định 'Chọn ảnh')
 * @var string $clear_text        Text nút bỏ ảnh (mặc định 'Bỏ ảnh')
 * @var string $add_text          Text nút thêm item (mặc định 'Thêm bằng cấp / chứng chỉ')
 * @var string $empty_text        Text hiển thị khi chưa có item
 * @var string $hint              Ghi chú phụ bên dưới
 */
$label            = $label            ?? 'Bằng cấp chuyên môn';
$name             = $name             ?? 'diploma_repeater';
$items            = $items            ?? [];
$name_field       = $name_field       ?? 'diploma_names[]';
$image_field      = $image_field      ?? 'diploma_images[]';
$name_label       = $name_label       ?? 'Tên bằng cấp / chứng chỉ:';
$name_placeholder = $name_placeholder ?? 'Ví dụ: Chứng chỉ TESOL 120 giờ quốc tế, IELTS 8.5...';
$choose_text      = $choose_text      ?? 'Chọn ảnh';
$clear_text       = $clear_text       ?? 'Bỏ ảnh';
$add_text         = $add_text         ?? 'Thêm bằng cấp / chứng chỉ';
$empty_text       = $empty_text       ?? 'Chưa có bằng cấp / chứng chỉ nào trong hồ sơ. Bấm nút bên dưới để thêm mới.';
$hint             = $hint             ?? '';
?>
<div class="image-repeater mb-3" id="repeater_<?= e($name) ?>" data-repeater="<?= e($name) ?>">
    <?php if ($label !== ''): ?>
        <label class="form-label fw-bold text-dark d-block mb-2">
            <i class="fa-solid fa-graduation-cap text-primary me-1"></i><?= e($label) ?>
        </label>
    <?php endif; ?>

    <div class="repeater-list d-flex flex-column gap-2 mb-2" id="list_<?= e($name) ?>">
        <?php if (!empty($items)): ?>
            <?php foreach ($items as $idx => $it):
                $itName  = is_array($it) ? ($it['name'] ?? $it['ten'] ?? $it['title'] ?? '') : '';
                $itImg   = is_array($it) ? ($it['image'] ?? $it['hinh_anh'] ?? '') : (is_string($it) ? $it : '');
                $fid     = $name . '_img_' . $idx . '_' . rand(100, 999);
                $hasImg  = trim((string) $itImg) !== '';
                $imgSrc  = $hasImg ? img_url($itImg) : 'img/no-image.png';
            ?>
                <div class="card border shadow-sm repeater-item" data-item-id="<?= e($fid) ?>">
                    <div class="card-body p-2 p-md-3">
                        <div class="row g-2 align-items-center">
                            <!-- Drag handle -->
                            <div class="col-auto">
                                <span class="drag-handle text-secondary px-1 cursor-grab" title="Kéo để đổi thứ tự" style="cursor: grab;">
                                    <i class="fa-solid fa-grip-vertical"></i>
                                </span>
                            </div>

                            <!-- Ảnh preview & Nút chọn ảnh -->
                            <div class="col-auto">
                                <div class="border rounded bg-light overflow-hidden d-flex align-items-center justify-content-center"
                                     style="width: 100px; height: 75px; flex-shrink: 0;">
                                    <img src="<?= e($imgSrc) ?>"
                                         id="review_<?= e($fid) ?>"
                                         class="w-100 h-100 object-fit-contain"
                                         alt="Xem trước"
                                         data-fallback="img/no-image.png"
                                         onerror="this.onerror=null;this.src='img/no-image.png';">
                                </div>
                            </div>
                            <div class="col-auto">
                                <div class="d-flex flex-column gap-1">
                                    <input type="hidden"
                                           name="<?= e($image_field) ?>"
                                           id="<?= e($fid) ?>"
                                           value="<?= e($itImg) ?>"
                                           data-preview="image">

                                    <a href="ckfinder/ckfinder.html?type=Images"
                                       class="btn btn-sm btn-outline-primary iframe-btn js-fm-open py-1 px-2"
                                       data-fm-driver="ckfinder"
                                       data-fm-field="<?= e($fid) ?>"
                                       data-fm-type="image"
                                       data-fm-resource="Images"
                                       data-fm-multiple="0">
                                        <i class="fa-solid fa-upload me-1"></i><?= e($choose_text) ?>
                                    </a>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-info js-copy-file-url py-1 px-2 <?= $hasImg ? '' : 'd-none' ?>"
                                            data-target="<?= e($fid) ?>"
                                            data-preview="image"
                                            title="Sao chép đường dẫn hình ảnh này">
                                        <i class="fa-solid fa-copy me-1"></i>Copy
                                    </button>

                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary js-clear-repeater-img py-1 px-2"
                                            data-target="<?= e($fid) ?>"
                                            title="Xóa ảnh này">
                                        <i class="fa-solid fa-xmark me-1"></i><?= e($clear_text) ?>
                                    </button>
                                </div>
                            </div>

                            <!-- Ô nhập tên -->
                            <div class="col">
                                <label class="form-label small fw-bold text-dark mb-1">
                                    <i class="fa-solid fa-certificate text-warning me-1"></i><?= e($name_label) ?>
                                </label>
                                <input type="text"
                                       name="<?= e($name_field) ?>"
                                       value="<?= e($itName) ?>"
                                       class="form-control form-control-sm"
                                       placeholder="<?= e($name_placeholder) ?>">
                            </div>

                            <!-- Nút xóa dòng -->
                            <div class="col-auto text-end">
                                <label class="form-label small fw-bold mb-1 d-none d-md-block opacity-0">Xóa</label>
                                <button type="button"
                                        class="btn btn-sm btn-outline-danger js-remove-repeater-row"
                                        title="Xóa mục này">
                                    <i class="fa-solid fa-trash me-1"></i>Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Thông báo khi rỗng -->
    <div class="alert alert-light border text-secondary small py-2 mb-2 <?= empty($items) ? '' : 'd-none' ?>" id="empty_<?= e($name) ?>">
        <i class="fa-solid fa-circle-info me-1"></i><?= e($empty_text) ?>
    </div>

    <!-- Nút thêm dòng -->
    <div class="d-flex justify-content-between align-items-center mt-2 flex-wrap gap-2">
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn_add_<?= e($name) ?>">
            <i class="fa-solid fa-plus me-1"></i><?= e($add_text) ?>
        </button>
        <?php if ($hint !== ''): ?>
            <span class="form-text small text-muted mb-0"><?= e($hint) ?></span>
        <?php endif; ?>
    </div>
</div>

<!-- Template dòng mới -->
<template id="tmpl_<?= e($name) ?>_item">
    <div class="card border shadow-sm repeater-item" data-item-id="__FID__">
        <div class="card-body p-2 p-md-3">
            <div class="row g-2 align-items-center">
                <!-- Drag handle -->
                <div class="col-auto">
                    <span class="drag-handle text-secondary px-1 cursor-grab" title="Kéo để đổi thứ tự" style="cursor: grab;">
                        <i class="fa-solid fa-grip-vertical"></i>
                    </span>
                </div>

                <!-- Ảnh preview & Nút chọn ảnh -->
                <div class="col-auto">
                    <div class="border rounded bg-light overflow-hidden d-flex align-items-center justify-content-center"
                         style="width: 100px; height: 75px; flex-shrink: 0;">
                        <img src="img/no-image.png"
                             id="review___FID__"
                             class="w-100 h-100 object-fit-contain"
                             alt="Xem trước"
                             data-fallback="img/no-image.png">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="d-flex flex-column gap-1">
                        <input type="hidden"
                               name="<?= e($image_field) ?>"
                               id="__FID__"
                               value=""
                               data-preview="image">

                        <a href="ckfinder/ckfinder.html?type=Images"
                           class="btn btn-sm btn-outline-primary iframe-btn js-fm-open py-1 px-2"
                           data-fm-driver="ckfinder"
                           data-fm-field="__FID__"
                           data-fm-type="image"
                           data-fm-resource="Images"
                           data-fm-multiple="0">
                            <i class="fa-solid fa-upload me-1"></i><?= e($choose_text) ?>
                        </a>

                        <button type="button"
                                class="btn btn-sm btn-outline-info js-copy-file-url py-1 px-2 d-none"
                                data-target="__FID__"
                                data-preview="image"
                                title="Sao chép đường dẫn hình ảnh này">
                            <i class="fa-solid fa-copy me-1"></i>Copy
                        </button>

                        <button type="button"
                                class="btn btn-sm btn-outline-secondary js-clear-repeater-img py-1 px-2"
                                data-target="__FID__"
                                title="Xóa ảnh này">
                            <i class="fa-solid fa-xmark me-1"></i><?= e($clear_text) ?>
                        </button>
                    </div>
                </div>

                <!-- Ô nhập tên -->
                <div class="col">
                    <label class="form-label small fw-bold text-dark mb-1">
                        <i class="fa-solid fa-certificate text-warning me-1"></i><?= e($name_label) ?>
                    </label>
                    <input type="text"
                           name="<?= e($name_field) ?>"
                           value=""
                           class="form-control form-control-sm"
                           placeholder="<?= e($name_placeholder) ?>">
                </div>

                <!-- Nút xóa dòng -->
                <div class="col-auto text-end">
                    <label class="form-label small fw-bold mb-1 d-none d-md-block opacity-0">Xóa</label>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger js-remove-repeater-row"
                            title="Xóa mục này">
                        <i class="fa-solid fa-trash me-1"></i>Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
(function() {
    var repName = <?= json_encode($name) ?>;
    var container = document.getElementById('repeater_' + repName);
    if (!container) return;

    var list = document.getElementById('list_' + repName);
    var emptyEl = document.getElementById('empty_' + repName);
    var btnAdd = document.getElementById('btn_add_' + repName);
    var template = document.getElementById('tmpl_' + repName + '_item');

    function checkEmpty() {
        if (!list || !emptyEl) return;
        var count = list.querySelectorAll('.repeater-item').length;
        if (count === 0) {
            emptyEl.classList.remove('d-none');
        } else {
            emptyEl.classList.add('d-none');
        }
    }

    if (btnAdd && template && list) {
        btnAdd.addEventListener('click', function(e) {
            e.preventDefault();
            var fid = repName + '_img_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            var html = template.innerHTML.replace(/__FID__/g, fid);
            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html.trim();
            var newRow = tempDiv.firstElementChild;
            if (newRow) {
                list.appendChild(newRow);
                checkEmpty();
                var nameInput = newRow.querySelector('input[type="text"]');
                if (nameInput) nameInput.focus();
            }
        });
    }

    // Xử lý xóa dòng & bỏ ảnh qua event delegation
    container.addEventListener('click', function(e) {
        var btnRemove = e.target.closest && e.target.closest('.js-remove-repeater-row');
        if (btnRemove) {
            e.preventDefault();
            var item = btnRemove.closest('.repeater-item');
            if (item) {
                item.remove();
                checkEmpty();
            }
            return;
        }

        var btnClear = e.target.closest && e.target.closest('.js-clear-repeater-img');
        if (btnClear) {
            e.preventDefault();
            var targetId = btnClear.getAttribute('data-target');
            if (targetId) {
                var input = document.getElementById(targetId);
                var review = document.getElementById('review_' + targetId);
                if (input) input.value = '';
                if (review) review.src = review.getAttribute('data-fallback') || 'img/no-image.png';
                var itemEl = btnClear.closest('.repeater-item');
                if (itemEl) {
                    var copyBtn = itemEl.querySelector('.js-copy-file-url');
                    if (copyBtn) copyBtn.classList.add('d-none');
                }
            }
            return;
        }
    });

    // Kéo thả sắp xếp nếu SortableJS đã tải
    if (typeof Sortable !== 'undefined' && list) {
        Sortable.create(list, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'bg-body-secondary'
        });
    }
})();
</script>
