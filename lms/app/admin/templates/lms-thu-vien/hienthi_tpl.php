<?php
/**
 * Admin LMS — Quản lý thư viện tài liệu (ADM-12).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/Quản lý thư viện.png
 *  - Tai-lieu-phat-trien/admin/Quản lý thư viện - Thêm.png
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§12. Quản lý thư viện)
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components & LmsEnum:
 *  - form/input, form/select, button, badge, pagination, library_icon
 *
 * PHP 7.3 compatible.
 */
?>

<div class="lms-thu-vien-container pb-4">
    <!-- Thanh tìm kiếm & Nút thao tác (Chuẩn Mockup Quản lý thư viện.png) -->
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-grow-1 align-items-center gap-2" style="min-width: 280px;">
            <div class="flex-grow-1">
                <?php lms_component('form/input', array(
                    'name'        => 'q',
                    'value'       => $filterQ,
                    'placeholder' => 'Tìm tên tài liệu...',
                    'bare'        => true,
                    'class'       => 'form-control lms-lib-search-input',
                )); ?>
            </div>
            <div>
                <?php lms_component('button', array(
                    'label'   => 'Tìm',
                    'type'    => 'submit',
                    'variant' => 'secondary',
                    'class'   => 'px-4',
                )); ?>
            </div>
        </form>

        <?php if (!empty($canEdit)): ?>
            <div>
                <?php lms_component('button', array(
                    'label'   => 'Upload',
                    'type'    => 'button',
                    'variant' => 'primary',
                    'class'   => 'px-4',
                    'attrs'   => array(
                        'data-bs-toggle' => 'modal',
                        'data-bs-target' => '#lmsAddLibraryModal',
                    ),
                )); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lưới danh sách tài liệu (Card Grid 6 cột chuẩn Mockup Quản lý thư viện.png) -->
    <?php if (empty($items)): ?>
        <div class="text-center py-5 bg-white rounded border my-3">
            <div class="text-muted mb-3" style="font-size: 2.8rem;"><i class="fa-regular fa-folder-open"></i></div>
            <h5 class="text-secondary fw-normal">Chưa có tài liệu nào trong thư viện</h5>
            <?php if (!empty($canEdit)): ?>
                <p class="text-muted small mb-0">Hãy nhấn nút <strong>Upload</strong> ở trên để thêm tài liệu hoặc liên kết mới.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 mb-4">
            <?php foreach ($items as $file):
                $fid        = (int) $file['id'];
                $rawTitle   = (string) $file['title'];
                $fileUrl    = (string) $file['file_url'];
                $isUrl      = (bool) preg_match('#^https?://#i', $fileUrl);
                $fileType   = isset($file['file_type']) ? (string) $file['file_type'] : 'other';
                $isDownload = !empty($file['is_downloadable']);
                $downUrl    = lms_url($alias, 'tai', array('id' => $fid));
            ?>
                <div class="col">
                    <div class="lms-lib-item-card position-relative d-flex flex-column h-100">
                        <!-- Khung hình chữ nhật hiển thị Icon tài liệu -->
                        <div class="lms-lib-box-wrapper d-flex align-items-center justify-content-center bg-white position-relative"
                             data-open-url="<?= e($downUrl) ?>"
                             data-open-target="<?= $isUrl ? '_blank' : '_self' ?>">

                            <?php lms_component('library_icon', array(
                                'file_type' => $fileType,
                                'title'     => $rawTitle,
                                'file_url'  => $fileUrl,
                            )); ?>

                            <!-- Huy hiệu nhỏ nếu tắt tải xuống cho GV -->
                            <?php if (!$isDownload): ?>
                                <div class="position-absolute top-0 end-0 m-1" title="Chưa cho phép giáo viên tải xuống">
                                    <?php lms_component('badge', array(
                                        'label'   => 'Chỉ xem',
                                        'variant' => 'warning',
                                        'icon'    => 'fa-solid fa-lock',
                                    )); ?>
                                </div>
                            <?php endif; ?>

                            <!-- Hover Overlay: Nút hành động nổi -->
                            <div class="lms-lib-hover-actions position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center gap-2">
                                <?php lms_component('button', array(
                                    'href'    => $downUrl,
                                    'target'  => $isUrl ? '_blank' : '',
                                    'variant' => 'light',
                                    'size'    => 'sm',
                                    'icon'    => $isUrl ? 'fa-solid fa-arrow-up-right-from-square' : 'fa-solid fa-download',
                                    'class'   => 'lms-lib-action-btn text-primary shadow-sm',
                                    'attrs'   => array('title' => $isUrl ? 'Mở liên kết' : 'Tải xuống'),
                                )); ?>

                                <?php if (!empty($canEdit)): ?>
                                    <?php lms_component('button', array(
                                        'type'    => 'button',
                                        'variant' => 'light',
                                        'size'    => 'sm',
                                        'icon'    => 'fa-solid fa-pen',
                                        'class'   => 'lms-lib-action-btn text-dark shadow-sm lms-btn-edit-file',
                                        'attrs'   => array(
                                            'data-id' => (string) $fid,
                                            'title'   => 'Chỉnh sửa tài liệu',
                                        ),
                                    )); ?>
                                <?php endif; ?>

                                <?php if (!empty($canDelete)): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'xoa')) ?>" class="d-inline m-0 p-0">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= $fid ?>">
                                        <?php lms_component('button', array(
                                            'type'    => 'submit',
                                            'variant' => 'light',
                                            'size'    => 'sm',
                                            'icon'    => 'fa-solid fa-trash',
                                            'class'   => 'lms-lib-action-btn text-danger shadow-sm',
                                            'confirm' => 'Bạn chắc chắn muốn xoá tài liệu này khỏi thư viện?',
                                            'attrs'   => array('title' => 'Xoá tài liệu'),
                                        )); ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tên tài liệu bên dưới (Chuẩn Mockup: Cắt ngắn dấu ...) -->
                        <div class="lms-lib-title text-center mt-2 text-dark px-1" title="<?= e($rawTitle) ?>">
                            <?= e($rawTitle) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Phân trang Component -->
        <?php lms_component('pagination', array(
            'page'        => $page,
            'total_pages' => $totalPages,
            'base_url'    => lms_url($alias),
            'params'      => array('q' => $filterQ),
        )); ?>
    <?php endif; ?>
</div>

<?php if (!empty($canEdit)): ?>
<!-- ===================== MODAL THÊM TÀI LIỆU MỚI (Mockup: Quản lý thư viện - Thêm.png) ===================== -->
<div class="modal fade" id="lmsAddLibraryModal" tabindex="-1" aria-labelledby="lmsAddLibraryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content shadow border-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h4 class="modal-title fw-bold text-dark" id="lmsAddLibraryModalLabel">
                    Thêm tài liệu mới
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <form method="post" action="<?= e(lms_url($alias, 'them')) ?>" enctype="multipart/form-data" id="lms-form-add-library">
                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

                <div class="modal-body px-4 pt-3 pb-4">
                    <!-- 1. Liên kết chứa tài liệu -->
                    <div class="mb-3">
                        <label for="add_file_link" class="form-label fw-medium text-dark mb-1">
                            Liên kết chứa tài liệu
                        </label>
                        <?php lms_component('form/input', array(
                            'name'        => 'file_link',
                            'id'          => 'add_file_link',
                            'value'       => '',
                            'placeholder' => 'Dán link tại đây ...',
                            'bare'        => true,
                            'class'       => 'form-control',
                        )); ?>
                    </div>

                    <!-- 2. Tải file lên (Dropzone chuẩn mockup Quản lý thư viện - Thêm.png) -->
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark mb-1">
                            Tải file lên
                        </label>
                        <div id="lms-dropzone-add" class="lms-upload-dropzone text-center position-relative">
                            <div class="lms-upload-dropzone-inner">
                                <div class="mb-2">
                                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#85a3ad" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7 10 12 15 17 10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </div>
                                <div style="font-size: 0.92rem; color: #134e6f;">
                                    <strong>Choose a file</strong> <span class="fw-normal">or drag it here.</span>
                                </div>
                                <div id="lms-dropzone-filename" class="mt-2 text-primary fw-semibold small d-none"></div>
                            </div>
                            <input type="file" name="library_file" id="add_library_file" class="d-none" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mp3,.mp4,.zip">
                        </div>
                    </div>

                    <!-- 3. Phân loại tiền tố & Tên tài liệu -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label for="add_category_prefix" class="form-label fw-medium text-dark mb-1 small">
                                Phân loại danh mục
                            </label>
                            <?php lms_component('form/select', array(
                                'name'  => 'category_prefix',
                                'id'    => 'add_category_prefix',
                                'enum'  => 'library_categories',
                                'value' => 'none',
                                'bare'  => true,
                                'class' => 'form-select form-select-sm',
                            )); ?>
                        </div>
                        <div class="col-md-7">
                            <label for="add_title" class="form-label fw-medium text-dark mb-1 small">
                                Tên tài liệu
                            </label>
                            <?php lms_component('form/input', array(
                                'name'        => 'title',
                                'id'          => 'add_title',
                                'value'       => '',
                                'placeholder' => 'Tự động lấy theo tên file/link nếu để trống',
                                'bare'        => true,
                                'class'       => 'form-control form-control-sm',
                            )); ?>
                        </div>
                    </div>

                    <!-- 4. Phân loại tải được hay không & Nút Thêm (Chuẩn bố cục đáy Modal trong Quản lý thư viện - Thêm.png) -->
                    <div class="d-flex align-items-end justify-content-between gap-3 pt-1">
                        <div class="flex-grow-1">
                            <label for="add_is_downloadable" class="form-label fw-medium text-dark mb-1 small">
                                Phân loại tải được hay không
                            </label>
                            <?php lms_component('form/select', array(
                                'name'  => 'is_downloadable',
                                'id'    => 'add_is_downloadable',
                                'enum'  => 'library_downloadable',
                                'value' => '1',
                                'bare'  => true,
                                'class' => 'form-select form-select-sm',
                            )); ?>
                        </div>
                        <div>
                            <?php lms_component('button', array(
                                'label'   => 'Thêm',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'class'   => 'px-4',
                            )); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== MODAL SỬA TÀI LIỆU ===================== -->
<div class="modal fade" id="lmsEditLibraryModal" tabindex="-1" aria-labelledby="lmsEditLibraryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 540px;">
        <div class="modal-content shadow border-0">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h4 class="modal-title fw-bold text-dark" id="lmsEditLibraryModalLabel">
                    Sửa thông tin tài liệu
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <form method="post" action="<?= e(lms_url($alias, 'sua')) ?>" enctype="multipart/form-data" id="lms-form-edit-library">
                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                <input type="hidden" name="id" id="edit_file_id" value="">

                <div class="modal-body px-4 pt-3 pb-4">
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <label for="edit_category_prefix" class="form-label fw-medium text-dark mb-1 small">
                                Phân loại danh mục
                            </label>
                            <?php lms_component('form/select', array(
                                'name'  => 'category_prefix',
                                'id'    => 'edit_category_prefix',
                                'enum'  => 'library_categories',
                                'value' => 'none',
                                'bare'  => true,
                                'class' => 'form-select form-select-sm',
                            )); ?>
                        </div>
                        <div class="col-md-7">
                            <label for="edit_title" class="form-label fw-medium text-dark mb-1 small">
                                Tên tài liệu <span class="text-danger">*</span>
                            </label>
                            <?php lms_component('form/input', array(
                                'name'     => 'title',
                                'id'       => 'edit_title',
                                'value'    => '',
                                'required' => true,
                                'bare'     => true,
                                'class'    => 'form-control form-control-sm',
                            )); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_file_link" class="form-label fw-medium text-dark mb-1 small">
                            Đường dẫn file hoặc Liên kết
                        </label>
                        <?php lms_component('form/input', array(
                            'name'  => 'file_link',
                            'id'    => 'edit_file_link',
                            'value' => '',
                            'bare'  => true,
                            'class' => 'form-control form-control-sm',
                        )); ?>
                    </div>

                    <div class="mb-3">
                        <label for="edit_library_file" class="form-label fw-medium text-dark mb-1 small">
                            Tải file mới thay thế (tuỳ chọn)
                        </label>
                        <?php lms_component('form/input', array(
                            'type'  => 'file',
                            'name'  => 'library_file',
                            'id'    => 'edit_library_file',
                            'value' => '',
                            'bare'  => true,
                            'class' => 'form-control form-control-sm',
                            'attrs' => array('accept' => '.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.mp3,.mp4,.zip'),
                        )); ?>
                    </div>

                    <div class="d-flex align-items-end justify-content-between gap-3 pt-1">
                        <div class="flex-grow-1">
                            <label for="edit_is_downloadable" class="form-label fw-medium text-dark mb-1 small">
                                Phân loại tải được hay không
                            </label>
                            <?php lms_component('form/select', array(
                                'name'  => 'is_downloadable',
                                'id'    => 'edit_is_downloadable',
                                'enum'  => 'library_downloadable',
                                'value' => '1',
                                'bare'  => true,
                                'class' => 'form-select form-select-sm',
                            )); ?>
                        </div>
                        <div class="d-flex gap-2">
                            <?php lms_component('button', array(
                                'label'   => 'Đóng',
                                'type'    => 'button',
                                'variant' => 'light',
                                'size'    => 'sm',
                                'attrs'   => array('data-bs-dismiss' => 'modal'),
                            )); ?>
                            <?php lms_component('button', array(
                                'label'   => 'Lưu',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'class'   => 'px-4',
                            )); ?>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    (function ($) {
        'use strict';
        $(function () {
            var $dropzone = $('#lms-dropzone-add');
            var $fileInput = $('#add_library_file');
            var $filenamePreview = $('#lms-dropzone-filename');
            var $titleInput = $('#add_title');
            var $categorySelect = $('#add_category_prefix');

            $dropzone.on('click', function (e) {
                if ($(e.target).is('input[type="file"]')) return;
                $fileInput.trigger('click');
            });

            $dropzone.on('dragover dragenter', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.addClass('dragover');
            });

            $dropzone.on('dragleave dragend drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                $dropzone.removeClass('dragover');
            });

            $dropzone.on('drop', function (e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files && files.length > 0) {
                    $fileInput[0].files = files;
                    handleFileSelect(files[0]);
                }
            });

            $fileInput.on('change', function () {
                if (this.files && this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });

            function handleFileSelect(file) {
                var sizeMb = (file.size / (1024 * 1024)).toFixed(2);
                $filenamePreview.removeClass('d-none').html(
                    '<i class="fa-solid fa-paperclip me-1"></i>' + $('<div>').text(file.name).html() + ' (' + sizeMb + ' MB)'
                );
                if (!$titleInput.val().trim()) {
                    $titleInput.val(file.name.replace(/\.[^/.]+$/, ''));
                }
                if ($categorySelect.val() === 'none') {
                    var ext = (file.name.split('.').pop() || '').toLowerCase();
                    if (ext === 'pdf') {
                        $categorySelect.val('[Review]');
                    } else if (ext === 'doc' || ext === 'docx' || ext === 'xls' || ext === 'xlsx') {
                        $categorySelect.val('[Test]');
                    }
                }
            }

            $('#add_file_link').on('input', function () {
                if ($(this).val().trim() && $categorySelect.val() === 'none') {
                    $categorySelect.val('[Sách]');
                }
            });

            // Click vào khung thẻ (ngoài vùng nút hành động) thì mở/tải tài liệu
            $(document).on('click', '.lms-lib-box-wrapper', function (e) {
                if ($(e.target).closest('.lms-lib-hover-actions').length) {
                    return;
                }
                var url = $(this).data('open-url');
                var target = $(this).data('open-target') || '_self';
                if (url) {
                    window.open(url, target);
                }
            });

            var detailUrl = '<?= e(lms_url($alias, 'chi-tiet')) ?>';
            $(document).on('click', '.lms-btn-edit-file', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var fid = $(this).data('id');
                if (!fid) return;

                $.getJSON(detailUrl, { id: fid })
                    .done(function (res) {
                        if (!res.success) {
                            alert(res.message || 'Không thể tải thông tin tài liệu.');
                            return;
                        }
                        $('#edit_file_id').val(res.id);
                        $('#edit_title').val(res.title);
                        $('#edit_category_prefix').val(res.category_prefix || 'none');
                        $('#edit_file_link').val(res.file_url);
                        $('#edit_library_file').val('');
                        $('#edit_is_downloadable').val(String(parseInt(res.is_downloadable, 10) === 1 ? 1 : 0));

                        var editModalEl = document.getElementById('lmsEditLibraryModal');
                        if (editModalEl && window.bootstrap) {
                            bootstrap.Modal.getOrCreateInstance(editModalEl).show();
                        }
                    })
                    .fail(function () {
                        alert('Không thể kết nối máy chủ. Vui lòng thử lại.');
                    });
            });
        });
    })(jQuery);
</script>
