<?php
/**
 * Admin LMS — Quản lý lớp học: view danh sách (ADM-03).
 * Thiết kế chuẩn theo mockup: C:\laragon\www\source\Tai-lieu-phat-trien\admin\Lớp học.png
 *
 * Data do source chuẩn bị: $items, $total, $totalPages, $page,
 * $filterQ, $filterTeacherId, $filterProgramIds, $filterProgramId, $filterDayOfWeek,
 * $filterStatus, $filterClassType, $teachers, $programs, $canEdit, $canDelete.
 */
$classTypes    = \Lms\ClassService::CLASS_TYPES;
$classStatuses = \Lms\ClassService::CLASS_STATUSES;
$weekdays      = \Lms\UserService::WEEKDAYS;

$typeBadge = array(
    'one_on_one' => 'info',
    'group'      => 'warning',
);
$statusBadge = array(
    'arranging' => 'warning',
    'active'    => 'success',
    'paused'    => 'secondary',
    'finished'  => 'info',
    'cancelled' => 'danger',
);

// Chuẩn bị options cho các components select
$teacherOpts = array();
foreach ($teachers as $t) {
    $teacherOpts[(int) $t['user_id']] = ($t['teacher_code'] ?? '') . ' — ' . ($t['full_name'] ?? '');
}

$progOpts = array();
foreach ($programs as $p) {
    $progOpts[(int) $p['id']] = (string) $p['name'];
}

$dowOpts = array();
foreach ($weekdays as $dw => $lbl) {
    $dowOpts[$dw] = $lbl;
}
?>


<!-- Thanh công cụ tìm kiếm và các nút thao tác -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <!-- Form tìm kiếm bộ lọc -->
    <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
        <!-- 1. Tìm theo lớp -->
        <div style="min-width: 170px;">
            <?php lms_component('form/input', array(
                'name'        => 'q',
                'value'       => $filterQ,
                'placeholder' => 'Tìm theo lớp',
                'bare'        => true,
                'class'       => 'form-control',
            )); ?>
        </div>

        <!-- 2. Tìm theo GV -->
        <div style="min-width: 170px;">
            <?php lms_component('form/select', array(
                'name'        => 'teacher_id',
                'value'       => $filterTeacherId,
                'options'     => $teacherOpts,
                'empty_label' => 'Tìm theo GV',
                'bare'        => true,
                'class'       => 'form-select',
            )); ?>
        </div>

        <!-- 3. Tìm theo chương trình học -->
        <div style="min-width: 200px;">
            <?php lms_component('form/select', array(
                'name'        => 'program_id',
                'value'       => $filterProgramId,
                'options'     => $progOpts,
                'empty_label' => 'Tìm theo chương trình học',
                'bare'        => true,
                'class'       => 'form-select',
            )); ?>
        </div>

        <!-- 4. Tìm theo TG -->
        <div style="min-width: 150px;">
            <?php lms_component('form/select', array(
                'name'        => 'day_of_week',
                'value'       => $filterDayOfWeek,
                'options'     => $dowOpts,
                'empty_label' => 'Tìm theo TG',
                'bare'        => true,
                'class'       => 'form-select',
            )); ?>
        </div>

        <!-- Nút Tìm -->
        <?php lms_component('button', array(
            'label'   => 'Tìm',
            'type'    => 'submit',
            'variant' => 'outline-secondary',
            'class'   => 'px-3',
        )); ?>
    </form>

    <!-- Các nút thao tác bên phải -->
    <div class="d-flex align-items-center gap-2">
        <?php lms_component('button', array(
            'label'   => 'Điều chỉnh số buổi học',
            'variant' => 'secondary',
            'type'    => 'button',
            'size'    => 'md',
            'attrs'   => array(
                'data-bs-toggle' => 'modal',
                'data-bs-target' => '#lmsAdjustSessionsModal',
            ),
        )); ?>

        <?php if ($canEdit): ?>
            <?php lms_component('button', array(
                'label'   => 'Thêm',
                'href'    => lms_url($alias, 'them'),
                'variant' => 'primary',
                'size'    => 'md',
                'icon'    => 'fa-solid fa-plus',
            )); ?>
        <?php endif; ?>
    </div>
</div>

<div class="mb-2 fw-bold text-dark">
    Tổng: <?= number_format((int) $total) ?> lớp
</div>

<!-- Bảng danh sách lớp học -->
<?php if (empty($items)): ?>
    <div class="bg-white border rounded p-4 text-center text-muted">
        <i class="fa-solid fa-inbox d-block mb-2" style="font-size: 1.8rem;"></i>
        Chưa có lớp học nào phù hợp với bộ lọc.
    </div>
<?php else: ?>
    <!-- Bảng danh sách lớp (ghim Mã lớp + Hành động: xem .lms-table-pin trong lms.css) -->
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table lms-table-pin">
            <thead class="table-light text-center">
                <tr>
                    <th style="min-width: 130px;" data-pin-left>Mã lớp</th>
                    <th>Chương trình học</th>
                    <th style="width: 70px;">Sĩ số</th>
                    <th style="min-width: 170px;">Thời gian</th>
                    <th style="min-width: 150px;">Giáo viên</th>
                    <th style="min-width: 240px;">Khoá học</th>
                    <th>Chuyên đề đang học</th>
                    <th style="width: 140px;" data-pin-right>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $r): ?>
                    <?php
                    $stt = isset($r['status']) ? (string) $r['status'] : '';
                    $ctype = isset($r['class_type']) ? (string) $r['class_type'] : '';
                    $times = array();
                    foreach ((isset($r['schedules']) ? $r['schedules'] : array()) as $sch) {
                        $dow = (int) $sch['day_of_week'];
                        $times[] = (isset($weekdays[$dow]) ? $weekdays[$dow] : '')
                            . ' ' . substr((string) $sch['start_time'], 0, 5)
                            . ' - ' . substr((string) $sch['end_time'], 0, 5);
                    }
                    $finished = (int) ($r['sessions_finished'] ?? 0);
                    $totalSess = (int) ($r['total_sessions'] ?? 0);
                    $cycles = !empty($r['cycles']) ? $r['cycles'] : array();
                    ?>
                    <tr>
                        <!-- Mã lớp & Tên lớp -->
                        <td data-pin-left>
                            <div class="fw-bold"><?= e((string) ($r['name'] ?? $r['class_code'])) ?></div>
                            <div class="text-muted" style="font-size: 0.78rem;">
                                <?= e((string) ($r['class_code'] ?? '')) ?>
                                <?php if (isset($classTypes[$ctype])): ?>
                                    <span class="ms-1">
                                        <?php lms_component('badge', array(
                                            'label'   => $classTypes[$ctype],
                                            'variant' => isset($typeBadge[$ctype]) ? $typeBadge[$ctype] : 'info',
                                            'type'    => 'lms',
                                        )); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Chương trình học -->
                        <td>
                            <div><?= e((string) ($r['program_name'] ?? '—')) ?></div>
                            <?php if (!empty($r['subject_name'])): ?>
                                <div class="text-muted" style="font-size: 0.78rem;"><?= e($r['subject_name']) ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- Sĩ số -->
                        <td class="text-center fw-bold">
                            <?= e((string) number_format((int) ($r['student_count'] ?? 0))) ?>
                        </td>

                        <!-- Thời gian -->
                        <td style="font-size: 0.85rem;">
                            <?php if ($times !== array()): ?>
                                <?= e(implode(' / ', $times)) ?>
                            <?php else: ?>
                                <span class="text-muted">Chưa có lịch</span>
                            <?php endif; ?>
                        </td>

                        <!-- Giáo viên -->
                        <td>
                            <div class="fw-bold"><?= e((string) ($r['teacher_code'] ?? '')) ?></div>
                            <div class="text-muted" style="font-size: 0.82rem;">[<?= e((string) ($r['teacher_name'] ?? '')) ?>]</div>
                        </td>

                        <!-- Khoá học: hiển thị 2 khoá gần nhất theo mockup -->
                        <td>
                            <div class="d-flex gap-3 text-start" style="font-size: 0.8rem;">
                                <?php if (!empty($cycles)): ?>
                                    <?php foreach (array_slice($cycles, 0, 2) as $c): ?>
                                        <div class="border-end pe-2" style="min-width: 110px;">
                                            <div class="fw-bold text-dark mb-1">
                                                <?= e($c['name'] ?: 'Khóa ' . $c['cycle_no']) ?>
                                                <?php if (($c['status'] ?? '') === 'closed'): ?>
                                                    <span class="badge bg-secondary ms-1" style="font-size: 0.65rem;">Đóng</span>
                                                <?php endif; ?>
                                            </div>
                                            <div><span class="text-muted">Bắt đầu:</span> <?= !empty($c['start_date']) ? date('d/m/Y', strtotime($c['start_date'])) : '—' ?></div>
                                            <div><span class="text-muted">Kết thúc:</span> <?= !empty($c['expected_end_date']) ? date('d/m/Y', strtotime($c['expected_end_date'])) : '—' ?></div>
                                            <div><span class="text-muted">Đã học:</span> <?= (int) ($c['finished_sessions'] ?? 0) ?></div>
                                            <div><span class="text-muted">Còn lại:</span> <?= (int) ($c['remaining_sessions'] ?? 0) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="min-width: 110px;">
                                        <div class="fw-bold text-dark mb-1">Khóa 1</div>
                                        <div><span class="text-muted">Bắt đầu:</span> <?= !empty($r['start_date']) ? date('d/m/Y', strtotime($r['start_date'])) : '—' ?></div>
                                        <div><span class="text-muted">Kết thúc:</span> <?= !empty($r['expected_end_date']) ? date('d/m/Y', strtotime($r['expected_end_date'])) : '—' ?></div>
                                        <div><span class="text-muted">Đã học:</span> <?= (int) $finished ?></div>
                                        <div><span class="text-muted">Còn lại:</span> <?= (int) max(0, $r['remaining_sessions'] ?? 0) ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>

                        <!-- Chuyên đề đang học -->
                        <td>
                            <?php if (!empty($r['current_topic'])): ?>
                                <span class="fw-semibold text-dark"><?= e((string) $r['current_topic']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">Chưa có</span>
                            <?php endif; ?>
                        </td>

                        <!-- Hành động: Sửa / Lịch học / Thêm khoá học / Xoá chuẩn mockup -->
                        <td class="text-center text-nowrap" data-pin-right>
                            <div>
                                <a href="<?= e(lms_url($alias, 'sua', array('id' => (int) $r['id']))) ?>" class="lms-action-link">Sửa</a>
                                <span class="text-muted mx-1">|</span>
                                <a href="<?= e(lms_url($alias, 'lich-hoc', array('id' => (int) $r['id']))) ?>" class="lms-action-link">Lịch học</a>
                            </div>
                            <div>
                                <a href="<?= e(lms_url($alias, 'sua', array('id' => (int) $r['id']))) ?>#khoa-hoc" class="lms-action-link text-primary">Thêm khoá học</a>
                                <?php if ($canDelete): ?>
                                    <span class="text-muted mx-1">|</span>
                                    <a href="<?= e(lms_url($alias, 'xoa', array('id' => (int) $r['id']))) ?>"
                                       class="text-danger lms-confirm-delete"
                                       title="Xoá lớp học">Xoá</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Phân trang chuẩn bằng component pagination -->
    <?php lms_component('pagination', array(
        'page'        => $page,
        'total_pages' => $totalPages,
        'base_url'    => lms_url($alias, 'man'),
        'params'      => array_filter(array(
            'q'           => $filterQ,
            'teacher_id'  => $filterTeacherId,
            'program_id'  => $filterProgramId,
            'day_of_week' => $filterDayOfWeek,
            'status'      => $filterStatus,
            'class_type'  => $filterClassType,
        ), function ($v) { return $v !== '' && $v !== 0 && $v !== null; }),
    )); ?>
<?php endif; ?>

<!-- ===================== MODAL ĐIỀU CHỈNH SỐ BUỔI HỌC HÀNG LOẠT (Quản lý lớp học - Điều chỉnh số buổi học.png) ===================== -->
<div class="modal fade" id="lmsAdjustSessionsModal" tabindex="-1" aria-labelledby="lmsAdjustSessionsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 820px;">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: 0; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header bg-white py-3 px-4 border-bottom">
                <h5 class="modal-title fw-bold text-dark fs-5" id="lmsAdjustSessionsLabel">
                    Điều chỉnh số buổi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>

            <div class="modal-body p-4">
                <!-- 1. Thanh tìm kiếm -->
                <div class="position-relative mb-3">
                    <i class="fa-solid fa-magnifying-glass text-muted position-absolute" style="left: 14px; top: 50%; transform: translateY(-50%); font-size: 0.95rem;"></i>
                    <input type="text" id="lms-adjust-search" class="form-control ps-5 py-2" placeholder="Tìm nội dung trong bảng" style="border-radius: 8px; border: 1px solid #d1d5db; font-size: 0.9rem;">
                </div>

                <!-- 2. Bảng danh sách lớp (Load qua AJAX) -->
                <div class="table-responsive border rounded" style="min-height: 250px;">
                    <table class="table table-hover align-middle mb-0 text-dark" style="font-size: 0.88rem;">
                        <thead style="background-color: #f3f4f6; border-bottom: 2px solid #e5e7eb;">
                            <tr>
                                <th style="width: 25%;" class="ps-3 py-2">Mã lớp</th>
                                <th style="width: 35%;" class="py-2">Mã học viên</th>
                                <th style="width: 30%;" class="py-2">Mã giáo viên</th>
                                <th style="width: 10%;" class="text-center py-2">Chọn</th>
                            </tr>
                        </thead>
                        <tbody id="lms-adjust-tbody">
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                    Đang tải danh sách lớp học...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 3. Phân trang AJAX & Chọn tất cả -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-3 pt-1">
                    <nav aria-label="Pagination">
                        <ul class="pagination pagination-sm mb-0" id="lms-adjust-pagination">
                            <!-- Rendered by JS -->
                        </ul>
                    </nav>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small" id="lms-adjust-selected-count">Đã chọn: <strong>0</strong> lớp</span>
                        <button type="button" class="btn btn-link p-0 text-decoration-none fw-bold" id="lms-adjust-btn-toggle-all" style="color: #ea580c; font-size: 0.88rem;">
                            Chọn tất cả
                        </button>
                    </div>
                </div>

                <hr class="my-4 text-muted opacity-25">

                <!-- 4. Form Xoá buổi học -->
                <form method="post" action="<?= e(lms_url($alias, 'dieu-chinh-xoa-buoi')) ?>" id="form-bulk-delete" class="mb-3">
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                    <div class="bulk-classes-hidden-container"></div>
                    <div class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">Xoá buổi học</div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text bg-white text-muted"><i class="fa-regular fa-calendar"></i></span>
                            <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text bg-white text-muted"><i class="fa-regular fa-calendar"></i></span>
                            <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-sm text-white px-4 fw-medium" style="background-color: #0f172a; border-color: #0f172a; min-width: 90px; border-radius: 6px;">
                            Xoá
                        </button>
                    </div>
                </form>

                <!-- 5. Form Thêm buổi học -->
                <form method="post" action="<?= e(lms_url($alias, 'dieu-chinh-them-buoi')) ?>" id="form-bulk-add">
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                    <div class="bulk-classes-hidden-container"></div>
                    <div class="fw-bold text-dark mb-2" style="font-size: 0.95rem;">Thêm buổi học</div>
                    <div class="d-flex align-items-center flex-wrap gap-2">
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text bg-white text-muted"><i class="fa-regular fa-calendar"></i></span>
                            <input type="date" name="from_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 200px;">
                            <span class="input-group-text bg-white text-muted"><i class="fa-regular fa-calendar"></i></span>
                            <input type="date" name="to_date" class="form-control" value="<?= date('Y-m-d', strtotime('+1 month')) ?>" required>
                        </div>
                        <button type="submit" class="btn btn-sm text-white px-4 fw-medium" style="background-color: #ea580c; border-color: #ea580c; min-width: 90px; border-radius: 6px;">
                            Thêm
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    (function ($) {
        'use strict';
        $(function () {
            $('.lms-confirm-delete').on('click', function (e) {
                if (!window.confirm('Bạn có chắc muốn xoá lớp học này? Các buổi học đã hoàn thành sẽ được giữ lại làm lịch sử.')) {
                    e.preventDefault();
                }
            });

            // ================= Quản lý AJAX Phân trang Modal Điều chỉnh số buổi =================
            var selectedClassIds = new Set();
            var currentMatchingIds = [];
            var currentPage = 1;
            var currentKeyword = '';
            var searchTimer = null;
            var ajaxUrl = '<?= e(lms_url($alias, 'ajax-danh-sach-lop')) ?>';

            function updateSelectedCount() {
                var count = selectedClassIds.size;
                $('#lms-adjust-selected-count').html('Đã chọn: <strong>' + count + '</strong> lớp');
                
                // Cập nhật nhãn nút toggle all
                if (currentMatchingIds.length > 0 && currentMatchingIds.every(function (id) { return selectedClassIds.has(id); })) {
                    $('#lms-adjust-btn-toggle-all').text('Bỏ chọn tất cả');
                } else {
                    $('#lms-adjust-btn-toggle-all').text('Chọn tất cả');
                }
            }

            function renderPagination(pg) {
                var $p = $('#lms-adjust-pagination').empty();
                if (pg.total_pages <= 1) return;

                var cur = pg.page;
                var total = pg.total_pages;

                // Nút Prev
                var prevDisabled = cur <= 1 ? ' disabled' : '';
                $p.append('<li class="page-item' + prevDisabled + '"><a class="page-link" href="#" data-page="' + (cur - 1) + '">&laquo;</a></li>');

                var startP = Math.max(1, cur - 3);
                var endP = Math.min(total, cur + 3);

                if (startP > 1) {
                    $p.append('<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>');
                    if (startP > 2) {
                        $p.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                    }
                }

                for (var i = startP; i <= endP; i++) {
                    var activeCls = i === cur ? ' active' : '';
                    $p.append('<li class="page-item' + activeCls + '"><a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
                }

                if (endP < total) {
                    if (endP < total - 1) {
                        $p.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
                    }
                    $p.append('<li class="page-item"><a class="page-link" href="#" data-page="' + total + '">' + total + '</a></li>');
                }

                // Nút Next
                var nextDisabled = cur >= total ? ' disabled' : '';
                $p.append('<li class="page-item' + nextDisabled + '"><a class="page-link" href="#" data-page="' + (cur + 1) + '">&raquo;</a></li>');
            }

            function loadModalClasses(page, kw) {
                currentPage = page || 1;
                currentKeyword = kw !== undefined ? kw : currentKeyword;

                $('#lms-adjust-tbody').html('<tr><td colspan="4" class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>Đang tải danh sách...</td></tr>');

                $.getJSON(ajaxUrl, { page: currentPage, q: currentKeyword, per_page: 7 })
                    .done(function (res) {
                        if (!res.success) {
                            $('#lms-adjust-tbody').html('<tr><td colspan="4" class="text-center py-4 text-danger">Lỗi tải dữ liệu.</td></tr>');
                            return;
                        }

                        currentMatchingIds = res.all_matching_ids || [];
                        var items = res.items || [];
                        if (items.length === 0) {
                            $('#lms-adjust-tbody').html('<tr><td colspan="4" class="text-center py-4 text-muted">Không tìm thấy lớp học nào phù hợp.</td></tr>');
                            $('#lms-adjust-pagination').empty();
                            updateSelectedCount();
                            return;
                        }

                        var html = '';
                        items.forEach(function (it) {
                            var isChecked = selectedClassIds.has(it.id) ? ' checked' : '';
                            html += '<tr>'
                                + '<td class="ps-3 py-2 fw-medium text-dark">' + escapeHtml(it.class_name) + '</td>'
                                + '<td class="py-2 text-muted" style="white-space: pre-line;">' + escapeHtml(it.students_label) + '</td>'
                                + '<td class="py-2 text-muted" style="white-space: pre-line;">' + escapeHtml(it.teacher_label) + '</td>'
                                + '<td class="text-center py-2">'
                                + '  <input type="checkbox" class="form-check-input lms-adjust-chk-class" value="' + it.id + '"' + isChecked + ' style="cursor: pointer; width: 1.15rem; height: 1.15rem;">'
                                + '</td>'
                                + '</tr>';
                        });

                        $('#lms-adjust-tbody').html(html);
                        renderPagination(res.pagination);
                        updateSelectedCount();
                    })
                    .fail(function () {
                        $('#lms-adjust-tbody').html('<tr><td colspan="4" class="text-center py-4 text-danger">Không thể kết nối đến máy chủ. Vui lòng thử lại.</td></tr>');
                    });
            }

            function escapeHtml(text) {
                if (!text) return '';
                return $('<div>').text(text).html().replace(/\n/g, '<br>');
            }

            // Tải danh sách khi modal mở
            $('#lmsAdjustSessionsModal').on('show.bs.modal', function () {
                loadModalClasses(1, '');
            });

            // Click phân trang
            $(document).on('click', '#lms-adjust-pagination a.page-link', function (e) {
                e.preventDefault();
                var targetPage = parseInt($(this).data('page'), 10);
                if (targetPage && targetPage !== currentPage) {
                    loadModalClasses(targetPage, currentKeyword);
                }
            });

            // Tìm kiếm debounce
            $('#lms-adjust-search').on('input', function () {
                var val = $(this).val().trim();
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () {
                    loadModalClasses(1, val);
                }, 300);
            });

            // Tích chọn checkbox từng dòng
            $(document).on('change', '.lms-adjust-chk-class', function () {
                var id = parseInt($(this).val(), 10);
                if ($(this).is(':checked')) {
                    selectedClassIds.add(id);
                } else {
                    selectedClassIds.delete(id);
                }
                updateSelectedCount();
            });

            // Chọn tất cả / Bỏ chọn tất cả
            $('#lms-adjust-btn-toggle-all').on('click', function () {
                if (currentMatchingIds.length === 0) return;
                var isAllSelected = currentMatchingIds.every(function (id) { return selectedClassIds.has(id); });
                if (isAllSelected) {
                    currentMatchingIds.forEach(function (id) { selectedClassIds.delete(id); });
                } else {
                    currentMatchingIds.forEach(function (id) { selectedClassIds.add(id); });
                }
                $('.lms-adjust-chk-class').each(function () {
                    var id = parseInt($(this).val(), 10);
                    $(this).prop('checked', selectedClassIds.has(id));
                });
                updateSelectedCount();
            });

            // Submit form Xoá
            $('#form-bulk-delete').on('submit', function (e) {
                if (selectedClassIds.size === 0) {
                    e.preventDefault();
                    alert('Vui lòng tích chọn ít nhất một lớp học trong bảng để xoá buổi.');
                    return false;
                }
                if (!confirm('Bạn chắc chắn muốn XOÁ các buổi học chưa diễn ra của ' + selectedClassIds.size + ' lớp học đã chọn trong khoảng ngày này?')) {
                    e.preventDefault();
                    return false;
                }
                var $holder = $(this).find('.bulk-classes-hidden-container').empty();
                selectedClassIds.forEach(function (id) {
                    $holder.append('<input type="hidden" name="class_ids[]" value="' + id + '">');
                });
            });

            // Submit form Thêm
            $('#form-bulk-add').on('submit', function (e) {
                if (selectedClassIds.size === 0) {
                    e.preventDefault();
                    alert('Vui lòng tích chọn ít nhất một lớp học trong bảng để thêm buổi.');
                    return false;
                }
                if (!confirm('Bạn chắc chắn muốn THÊM các buổi học theo lịch cố định của ' + selectedClassIds.size + ' lớp học đã chọn trong khoảng ngày này?')) {
                    e.preventDefault();
                    return false;
                }
                var $holder = $(this).find('.bulk-classes-hidden-container').empty();
                selectedClassIds.forEach(function (id) {
                    $holder.append('<input type="hidden" name="class_ids[]" value="' + id + '">');
                });
            });

        });
    })(jQuery);
</script>
