<?php
/**
 * Teacher LMS — Thư viện tài liệu (TCH-07).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/teacher/Thư viện.png
 *  - Tai-lieu-phat-trien/teacher/Tài liệu teacher.md (§10. Thư viện)
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components:
 *  - form/input, button, badge, pagination, library_icon
 *
 * PHP 7.3 compatible.
 */
?>

<div class="lms-thu-vien-container pb-4">
    <!-- Thanh tìm kiếm (Chuẩn Mockup teacher/Thư viện.png: Ô input bên trái + Nút Tìm kiếm màu cam bên phải) -->
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
                    'label'   => 'Tìm kiếm',
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'class'   => 'px-4',
                )); ?>
            </div>
        </form>
    </div>

    <!-- Lưới danh sách tài liệu (Card Grid chuẩn Mockup teacher/Thư viện.png) -->
    <?php if (empty($items)): ?>
        <div class="text-center py-5 bg-white rounded border my-3">
            <div class="text-muted mb-3" style="font-size: 2.8rem;"><i class="fa-regular fa-folder-open"></i></div>
            <h5 class="text-secondary fw-normal mb-0">Chưa có tài liệu nào phù hợp</h5>
        </div>
    <?php else: ?>
        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-3 mb-4">
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
                        <div class="lms-lib-box-wrapper d-flex align-items-center justify-content-center bg-white position-relative <?= $isDownload ? '' : 'is-readonly' ?>"
                             <?php if ($isDownload): ?>
                                 onclick="if (!event.target.closest('.lms-lib-hover-actions')) { window.open('<?= e($downUrl) ?>', '<?= $isUrl ? '_blank' : '_self' ?>'); }"
                             <?php endif; ?>>

                            <?php lms_component('library_icon', array(
                                'file_type' => $fileType,
                                'title'     => $rawTitle,
                                'file_url'  => $fileUrl,
                            )); ?>

                            <?php if (!$isDownload): ?>
                                <div class="position-absolute top-0 end-0 m-1" title="Admin không cho phép tải xuống tài liệu này">
                                    <?php lms_component('badge', array(
                                        'label'   => 'Chỉ xem',
                                        'variant' => 'warning',
                                        'icon'    => 'fa-solid fa-lock',
                                    )); ?>
                                </div>
                            <?php else: ?>
                                <div class="lms-lib-hover-actions position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                    <?php lms_component('button', array(
                                        'href'    => $downUrl,
                                        'target'  => $isUrl ? '_blank' : '',
                                        'variant' => 'light',
                                        'size'    => 'sm',
                                        'icon'    => $isUrl ? 'fa-solid fa-arrow-up-right-from-square' : 'fa-solid fa-download',
                                        'class'   => 'lms-lib-action-btn text-primary shadow-sm',
                                        'attrs'   => array('title' => $isUrl ? 'Mở liên kết' : 'Tải xuống'),
                                    )); ?>
                                </div>
                            <?php endif; ?>
                        </div>

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
