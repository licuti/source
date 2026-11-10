<?php
/**
 * Admin LMS — Quản lý lộ trình: Màn hình danh sách chuyên đề (ADM-10).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/Quản lý lộ trình.png
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§10. Quản lý lộ trình)
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components:
 *  - form/input, button, pagination
 *
 * Data từ source lms-lo-trinh.php:
 *  $items, $total, $page, $totalPages, $q, $subjectId, $testFilter,
 *  $canEdit, $canDelete, $alias.
 */
?>

<!-- Thanh tìm kiếm bên trái & Nút Thêm bên phải (theo mockup Quản lý lộ trình.png) -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
        <div style="min-width: 240px;">
            <?php lms_component('form/input', array(
                'name'        => 'q',
                'value'       => isset($q) ? $q : '',
                'placeholder' => 'Tìm nội dung trong bảng...',
                'bare'        => true,
                'class'       => 'form-control',
            )); ?>
        </div>
        <?php lms_component('button', array(
            'label'   => 'Tìm',
            'type'    => 'submit',
            'variant' => 'outline-secondary',
            'class'   => 'px-3',
        )); ?>
    </form>

    <?php if (!empty($canEdit)): ?>
        <div>
            <?php lms_component('button', array(
                'label'   => 'Thêm',
                'href'    => lms_url($alias, 'them'),
                'variant' => 'primary',
                'size'    => 'md',
                'class'   => '',
            )); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bảng danh sách chuyên đề (6 cột theo mockup Quản lý lộ trình.png) -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0 lms-table">
        <thead class="table-light text-center">
            <tr>
                <th style="min-width: 180px;">Tên chuyên đề</th>
                <th style="min-width: 140px;">Danh mục</th>
                <th style="min-width: 130px;">Loại chuyên đề</th>
                <th style="min-width: 240px;">Nội dung</th>
                <th style="width: 160px;">Số buổi học tối đa</th>
                <th style="width: 120px;">Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        Chưa có chuyên đề phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $r):
                    $topicId = (int) $r['id'];
                    $topicName = isset($r['name']) ? (string) $r['name'] : '';
                    $categoryName = isset($r['subject_name']) ? (string) $r['subject_name'] : '';
                    $hasTest = !empty($r['has_test']);
                    $typeLabel = $hasTest ? 'Có test' : 'Không test';
                    $rawContent = isset($r['content']) && trim((string) $r['content']) !== ''
                        ? trim((string) $r['content'])
                        : (isset($r['report_template']) ? trim(strip_tags((string) $r['report_template'])) : '');
                    $maxSessions = ($r['max_sessions'] !== null && $r['max_sessions'] !== '')
                        ? (string) (int) $r['max_sessions']
                        : '—';
                ?>
                    <tr>
                        <td class="text-center"><?= e($topicName) ?></td>
                        <td class="text-center"><?= e($categoryName) ?></td>
                        <td class="text-center"><?= e($typeLabel) ?></td>
                        <td class="text-center"><?= $rawContent !== '' ? nl2br(e($rawContent)) : '<span class="text-muted">—</span>' ?></td>
                        <td class="text-center"><?= e($maxSessions) ?></td>
                        <td class="text-center">
                            <div class="d-flex flex-column align-items-center gap-1">
                                <?php if (!empty($canEdit)): ?>
                                    <?php lms_component('button', array(
                                        'label'   => 'Sửa',
                                        'href'    => lms_url($alias, 'sua', array('id' => $topicId)),
                                        'variant' => 'link',
                                        'size'    => 'sm',
                                        'class'   => 'p-0 text-primary text-decoration-underline border-0 bg-transparent fw-normal',
                                    )); ?>

                                    <form method="post" action="<?= e(lms_url($alias, 'duplicate')) ?>" class="d-inline m-0">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= $topicId ?>">
                                        <?php lms_component('button', array(
                                            'label'   => 'Duplicate',
                                            'type'    => 'submit',
                                            'variant' => 'link',
                                            'size'    => 'sm',
                                            'class'   => 'p-0 text-dark text-decoration-underline border-0 bg-transparent fw-normal',
                                        )); ?>
                                    </form>
                                <?php endif; ?>

                                <?php if (!empty($canDelete)): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'xoa')) ?>" class="d-inline m-0">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= $topicId ?>">
                                        <?php lms_component('button', array(
                                            'label'   => 'Xoá',
                                            'type'    => 'submit',
                                            'variant' => 'link',
                                            'size'    => 'sm',
                                            'confirm' => 'Bạn chắc chắn muốn xoá chuyên đề "' . $topicName . '"?',
                                            'class'   => 'p-0 text-danger text-decoration-underline border-0 bg-transparent fw-normal',
                                        )); ?>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang (component tự thêm mt-4) -->
<?php lms_component('pagination', array(
    'base_url'    => lms_url($alias),
    'page'        => isset($page) ? $page : 1,
    'total_pages' => isset($totalPages) ? $totalPages : 1,
    'params'      => array(
        'q'          => isset($q) ? $q : '',
        'subject_id' => !empty($subjectId) ? $subjectId : '',
        'has_test'   => isset($testFilter) ? $testFilter : '',
    ),
)); ?>
