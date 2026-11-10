<?php
/**
 * Admin LMS — Lịch học của 1 lớp (ADM-03 mockup "Lịch học").
 * Data do source chuẩn bị:
 *   $row, $students, $studentText, $topics, $schedules,
 *   $weekMonday, $prevWeek, $nextWeek, $currWeek, $weekSunday,
 *   $weekMonth, $weekYear, $daysOfWeek, $calRows, $weekSessions,
 *   $weekdays, $alias, $canEdit, $canDelete.
 */
$csrf = lms_csrf_token();
$classTypeLabel = ($row['class_type'] ?? '') === 'group' ? 'Đông người' : '1 kèm 1';
$teacherLabel = (($row['teacher_code'] ?? '') ? $row['teacher_code'] . ' - ' : '') . ($row['teacher_name'] ?? '');
$defaultPrice = (float) ($row['price_per_session'] ?? 0);
$defaultTeacherPct = isset($row['teacher_percentage']) && $row['teacher_percentage'] !== null
    ? (float) $row['teacher_percentage']
    : (float) ($row['service_fee_percentage'] ?? 0);
$fixedRows = array();
$effectiveFromDate = !empty($row['start_date']) ? (string) $row['start_date'] : date('Y-m-d');
foreach ($schedules as $sch) {
    $fixedRows[] = array(
        'day'   => (int) ($sch['day_of_week'] ?? 0),
        'start' => substr((string) ($sch['start_time'] ?? '17:30'), 0, 5),
        'end'   => substr((string) ($sch['end_time'] ?? '19:00'), 0, 5),
    );
    if (!empty($sch['effective_from'])) {
        $effectiveFromDate = (string) $sch['effective_from'];
    }
}
?>

<div class="lms-page-header mb-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="text-muted small">
            Lớp: <strong><?= e($row['class_code']) ?></strong> — <?= e($row['name'] ?: 'Lớp học') ?>
        </div>
        <div class="d-flex align-items-center gap-2">
            <?php lms_component('button', array(
                'href'    => lms_url($alias, 'sua', array('id' => (int) $row['id'])),
                'label'   => 'Sửa lớp',
                'icon'    => 'fa-solid fa-pen',
                'variant' => 'outline-secondary',
                'size'    => 'sm',
            )); ?>
            <?php lms_component('button', array(
                'href'    => lms_url($alias),
                'label'   => 'Danh sách lớp',
                'icon'    => 'fa-solid fa-arrow-left',
                'variant' => 'outline-secondary',
                'size'    => 'sm',
            )); ?>
        </div>
    </div>
</div>

<!-- ===================== KHỐI 1: THÔNG TIN LỚP & CẤU HÌNH LỊCH CỐ ĐỊNH ===================== -->
<div class="lms-card mb-4">
    <div class="lms-card-body p-3 p-md-4">
        <div class="row g-4">
            <!-- Cột trái: Thông tin lớp học (Readonly) -->
            <div class="col-12 col-lg-5 border-end-lg pe-lg-4">
                <div class="row align-items-center mb-3">
                    <label class="col-12 col-sm-4 col-form-label text-sm-start fw-medium text-dark">
                        Loại lớp <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm-8">
                        <input type="text" class="form-control bg-light" value="<?= e($classTypeLabel) ?>" disabled readonly style="color: #4b5563;">
                    </div>
                </div>

                <div class="row align-items-center mb-3">
                    <label class="col-12 col-sm-4 col-form-label text-sm-start fw-medium text-dark">
                        Giáo viên <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm-8">
                        <input type="text" class="form-control bg-light" value="<?= e($teacherLabel ?: 'Chưa phân công') ?>" disabled readonly style="color: #4b5563;">
                    </div>
                </div>

                <div class="row align-items-center mb-1">
                    <label class="col-12 col-sm-4 col-form-label text-sm-start fw-medium text-dark">
                        Học viên <span class="text-danger">*</span>
                    </label>
                    <div class="col-12 col-sm-8">
                        <input type="text" class="form-control bg-light" value="<?= e($studentText) ?>" disabled readonly style="color: #4b5563;" title="<?= e($studentText) ?>">
                    </div>
                </div>
            </div>

            <!-- Cột phải: Cấu hình Lịch cố định & Ngày áp dụng lịch -->
            <div class="col-12 col-lg-7 ps-lg-4">
                <form method="post" action="<?= e(lms_url($alias, 'cap-nhat-lich-co-dinh')) ?>" id="form-update-fixed-schedule">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                    <input type="hidden" name="week" value="<?= e($weekMonday) ?>">

                    <div class="row mb-2">
                        <label class="col-12 col-sm-4 col-form-label fw-medium text-dark">
                            Lịch cố định <span class="text-danger">*</span>
                        </label>
                        <div class="col-12 col-sm-8">
                            <?php lms_component('form/class_schedule_slots', array(
                                'id'       => 'lms-fixed-schedules-container',
                                'rows'     => $fixedRows,
                                'can_edit' => $canEdit,
                            )); ?>
                        </div>
                    </div>

                    <div class="row align-items-center mt-3">
                        <label class="col-12 col-sm-4 col-form-label fw-medium text-dark text-nowrap">
                            Ngày áp dụng lịch <span class="text-danger">*</span>
                        </label>
                        <div class="col-12 col-sm-8 d-flex align-items-center gap-3">
                            <div class="input-group input-group-sm" style="max-width: 215px;">
                                <span class="input-group-text bg-white text-muted"><i class="fa-regular fa-calendar"></i></span>
                                <input type="date" class="form-control form-control-sm" name="effective_from" value="<?= e($effectiveFromDate) ?>" required>
                            </div>
                            <?php if ($canEdit): ?>
                                <?php lms_component('button', array(
                                    'type'    => 'submit',
                                    'label'   => 'Lưu',
                                    'variant' => 'primary',
                                    'size'    => 'sm',
                                    'class'   => 'px-3 fw-medium',
                                    'attrs'   => array(
                                        'style' => 'background-color: #0b3a75; border-color: #0b3a75; min-width: 75px;',
                                    ),
                                )); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ===================== KHỐI 2: ĐIỀU HƯỚNG TUẦN / THÁNG ===================== -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <!-- Nút Lùi / Tiến & Tiêu đề Tháng -->
    <div class="d-flex align-items-center gap-2">
        <a href="<?= e(lms_url($alias, 'lich-hoc', array('id' => (int) $row['id'], 'week' => $prevWeek))) ?>"
           class="btn btn-outline-secondary btn-sm px-2 py-1" title="Tuần trước" style="border-radius: 6px;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <a href="<?= e(lms_url($alias, 'lich-hoc', array('id' => (int) $row['id'], 'week' => $nextWeek))) ?>"
           class="btn btn-outline-secondary btn-sm px-2 py-1" title="Tuần sau" style="border-radius: 6px;">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        <h4 class="mb-0 ms-2 fw-bold text-dark" style="font-size: 1.25rem;">
            Tháng <?= (int) $weekMonth ?> / <?= (int) $weekYear ?>
        </h4>
        <?php if ($weekMonday !== $currWeek): ?>
            <?php lms_component('button', array(
                'href'    => lms_url($alias, 'lich-hoc', array('id' => (int) $row['id'], 'week' => $currWeek)),
                'label'   => 'Hôm nay',
                'variant' => 'outline-primary',
                'size'    => 'sm',
                'class'   => 'ms-2 py-0 px-2',
                'attrs'   => array('style' => 'font-size: 0.78rem;'),
            )); ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== KHỐI 3: LƯỚI LỊCH TUẦN TRỰC QUAN (CALENDAR TIME GRID) ===================== -->
<div class="lms-card" style="border: 1px solid #e8eaee; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
    <?php
    $calLanes = lms_cal_lanes_for_rows($calRows);
    lms_component('calendar_week', array(
        'monday'      => $weekMonday,
        'days'        => $daysOfWeek,
        'rows'        => $calRows,
        'hour_label'  => 'Giờ',
        'hour_style'  => 'width: 75px; background: #fafbfc; font-size: 0.75rem; vertical-align: top; padding-top: 8px;',
        'hour_class'  => 'text-muted',
        'head_style'  => 'padding: 10px 4px;',
        'head_class'  => function (array $dInfo) {
            return $dInfo['is_today'] ? 'bg-light text-primary' : '';
        },
        // Mockup: trên là thứ, dưới là ngày/tháng — không lặp lại số thứ
        'head_renderer' => function (array $dInfo) {
            return '<div class="fw-semibold text-dark" style="font-size: 0.85rem;">' . e($dInfo['label']) . '</div>'
                . '<div class="fw-bold fs-6 mt-1 ' . ($dInfo['is_today'] ? 'text-primary' : 'text-dark')
                . '" style="line-height: 1;">' . (int) $dInfo['day'] . '/' . (int) $dInfo['month'] . '</div>';
        },
        'cell_class'  => function (array $cell) {
            return empty($cell['sessions']) ? 'lms-cal-cell-empty' : '';
        },
        'cell_attrs'  => function (array $cell, array $r) {
            return array(
                'data-date' => (string) $cell['date'],
                'data-hour' => (int) $r['hour'],
            );
        },
        'cell_renderer' => function (array $cell) use ($studentText, $defaultPrice, $defaultTeacherPct, $calLanes) {
            $html = '';
            foreach ($cell['sessions'] as $ses) {
                $missingHw = lms_cal_missing_hw($ses, true);
                $variant = lms_cal_variant((string) ($ses['status'] ?? ''), $missingHw);
                $startStr = substr((string) ($ses['start_time'] ?? '17:30'), 0, 5);
                $endStr   = substr((string) ($ses['end_time'] ?? '19:00'), 0, 5);
                $sesPrice = isset($ses['price_per_session']) && $ses['price_per_session'] !== null
                    ? (float) $ses['price_per_session']
                    : $defaultPrice;
                $sesPct   = isset($ses['teacher_percentage']) && $ses['teacher_percentage'] !== null
                    ? (float) $ses['teacher_percentage']
                    : $defaultTeacherPct;
                $lane = lms_cal_lane_of($calLanes, (string) $ses['session_date'], 's' . (int) $ses['id']);
                $html .= lms_component_render('calendar_week_event', array(
                    'variant' => $variant,
                    'class'   => 'lms-cal-event--clickable',
                    'title'   => $studentText,
                    'start'   => $startStr,
                    'end'     => $endStr,
                    'lane'    => $lane['lane'],
                    'lanes'   => $lane['lanes'],
                    'tooltip' => 'Bấm để xem thông tin chi tiết buổi học',
                    'flag'    => $missingHw ? '✗' : '',
                    'attrs'   => array(
                        'data-session-id'     => (int) $ses['id'],
                        'data-date'           => (string) $ses['session_date'],
                        'data-start'          => $startStr,
                        'data-end'            => $endStr,
                        'data-duration'       => round((strtotime($endStr) - strtotime($startStr)) / 3600, 2),
                        'data-price'          => number_format($sesPrice, 0, ',', '.'),
                        'data-percent'        => $sesPct,
                        'data-teacher-amount' => number_format(round($sesPrice * ($sesPct / 100)), 0, ',', '.'),
                        'data-status'         => (string) ($ses['status'] ?? ''),
                        'data-variant'        => $variant,
                        'data-status-label'   => lms_cal_variant_label($variant),
                        'data-topic'          => ($ses['topic_name'] ?: 'Chưa chọn chuyên đề'),
                        'data-summary'        => ($ses['lesson_summary'] ?: 'Chưa ghi báo cáo'),
                        'data-hw'             => ($ses['hw_content'] ?: 'Chưa giao bài tập'),
                    ),
                ));
            }
            return $html;
        },
    ));
    ?>
</div>

<!-- ===================== KHỐI 4: CHÚ THÍCH & HƯỚNG DẪN THAO TÁC ===================== -->
<div class="row mt-4 g-4 text-dark" style="font-size: 0.88rem;">
    <!-- Chú thích màu sắc (Legend) -->
    <div class="col-12 col-md-5">
        <h6 class="fw-bold mb-3">Chú thích:</h6>
        <ul class="lms-cal-legend lms-cal-legend--stack">
            <li><span class="lms-cal-dot lms-cal-upcoming-dot"></span> Buổi học sắp tới</li>
            <li><span class="lms-cal-dot lms-cal-missing-dot"></span> Buổi học thiếu BTVN (✗)</li>
            <li><span class="lms-cal-dot lms-cal-finished-dot"></span> Buổi học đã hoàn thành</li>
            <li><span class="lms-cal-dot lms-cal-cancelled-dot"></span> Buổi học đã nghỉ</li>
        </ul>

        <div class="mt-4 p-3 bg-light rounded" style="border-left: 3px solid #0b3a75;">
            <div class="fw-bold mb-1">** Click chọn một buổi, pop up, hiện thông tin:</div>
            <ul class="mb-0 ps-3">
                <li>Số giờ học, học phí buổi đó, phần trăm giáo viên nhận, số tiền gv nhận</li>
                <li>Có nút xoá buổi học</li>
                <li>Có nút đánh dấu buổi nghỉ</li>
            </ul>
        </div>
    </div>

    <!-- Hướng dẫn thao tác thêm/xoá -->
    <div class="col-12 col-md-7">
        <div class="p-3 bg-light rounded h-100" style="border-left: 3px solid #ea580c;">
            <div class="fw-bold mb-2">** Thao tác xoá và thêm buổi:</div>
            <ul class="mb-3 ps-3">
                <li><strong>Xoá:</strong> Bấm vào thẻ buổi học trên lưới lịch, popup xuất hiện, lựa chọn Xoá.</li>
                <li>
                    <strong>Thêm:</strong> Click vào ô trống khung giờ bất kỳ trên lịch, popup mở ra, điền số giờ học (mặc định lấy theo ô vừa click), số tiền học phí buổi đó, phần trăm GV nhận:
                    <ul class="mt-1">
                        <li><strong>QUÁ KHỨ:</strong> Đánh dấu buổi đó là đã hoàn thành (màu hồng) hoặc thiếu BTVN (màu hồng có dấu ✗).</li>
                        <li><strong>TƯƠNG LAI:</strong> Tự động gán trạng thái Buổi học sắp tới (màu đỏ).</li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- ===================== MODAL 1: CHI TIẾT BUỔI HỌC ===================== -->
<div class="modal fade" id="modal-session-detail" tabindex="-1" aria-labelledby="modalSessionDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: 0; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <div class="modal-header text-white" style="background-color: #0b3a75;">
                <h5 class="modal-title fw-bold" id="modalSessionDetailLabel">
                    <i class="fa-regular fa-calendar-check me-2"></i>Thông tin chi tiết buổi học
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                    <div>
                        <div class="text-muted small">Thời gian</div>
                        <div class="fs-6 fw-bold text-dark" id="md-detail-datetime">—</div>
                    </div>
                    <div>
                        <span class="badge" id="md-detail-badge" style="font-size: 0.85rem; padding: 6px 10px;">—</span>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="text-muted small">Số giờ học</div>
                            <div class="fw-bold text-dark fs-6" id="md-detail-duration">—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="text-muted small">Học phí 1 buổi</div>
                            <div class="fw-bold text-dark fs-6" id="md-detail-price">—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="text-muted small">% Giáo viên nhận</div>
                            <div class="fw-bold text-primary fs-6" id="md-detail-percent">—</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded">
                            <div class="text-muted small">Số tiền GV nhận</div>
                            <div class="fw-bold text-success fs-6" id="md-detail-teacher-amount">—</div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">Chuyên đề</div>
                    <div class="p-2 bg-light rounded fw-medium" id="md-detail-topic">—</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1">Báo cáo buổi học</div>
                    <div class="p-2 bg-light rounded small" id="md-detail-summary">—</div>
                </div>

                <div class="mb-0">
                    <div class="text-muted small mb-1">Bài tập về nhà (BTVN)</div>
                    <div class="p-2 bg-light rounded small" id="md-detail-hw">—</div>
                </div>
            </div>

            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <?php if ($canEdit): ?>
                        <form method="post" action="<?= e(lms_url($alias, 'danh-dau-nghi')) ?>" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn đánh dấu buổi học này là NGHỈ HỌC?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="week" value="<?= e($weekMonday) ?>">
                            <input type="hidden" name="session_id" id="md-detail-input-absent-id" value="">
                            <?php lms_component('button', array(
                                'type'    => 'submit',
                                'label'   => 'Đánh dấu buổi nghỉ',
                                'icon'    => 'fa-solid fa-user-xmark',
                                'variant' => 'warning',
                                'size'    => 'sm',
                                'class'   => 'btn-outline-warning text-dark',
                            )); ?>
                        </form>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                        <form method="post" action="<?= e(lms_url($alias, 'xoa-buoi-hoc')) ?>" class="d-inline" onsubmit="return confirm('Bạn chắc chắn muốn XOÁ VĨNH VIỄN buổi học này?');">
                            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                            <input type="hidden" name="week" value="<?= e($weekMonday) ?>">
                            <input type="hidden" name="session_id" id="md-detail-input-delete-id" value="">
                            <?php lms_component('button', array(
                                'type'    => 'submit',
                                'label'   => 'Xoá buổi',
                                'icon'    => 'fa-solid fa-trash',
                                'variant' => 'outline-danger',
                                'size'    => 'sm',
                            )); ?>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php lms_component('button', array(
                        'href'    => '#',
                        'label'   => 'Báo cáo buổi học',
                        'icon'    => 'fa-solid fa-file-lines',
                        'variant' => 'primary',
                        'size'    => 'sm',
                        'id'      => 'md-detail-link-report',
                        'attrs'   => array(
                            'style' => 'background-color: #0b3a75; border-color: #0b3a75;',
                        ),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'label'   => 'Đóng',
                        'variant' => 'secondary',
                        'size'    => 'sm',
                        'attrs'   => array(
                            'data-bs-dismiss' => 'modal',
                        ),
                    )); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MODAL 2: THÊM BUỔI HỌC MỚI ===================== -->
<div class="modal fade" id="modal-add-session" tabindex="-1" aria-labelledby="modalAddSessionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; overflow: hidden; border: 0; box-shadow: 0 10px 25px rgba(0,0,0,0.15);">
            <form method="post" action="<?= e(lms_url($alias, 'them-buoi-hoc')) ?>" id="form-add-session">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="week" value="<?= e($weekMonday) ?>">

                <div class="modal-header text-white" style="background-color: #ea580c;">
                    <h5 class="modal-title fw-bold" id="modalAddSessionLabel">
                        <i class="fa-solid fa-calendar-plus me-2"></i>Thêm buổi học mới
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark" for="add-session-date">Ngày học <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="add-session-date" name="session_date" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-start">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="add-session-start" name="start_time" value="17:30" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-end">Giờ kết thúc <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="add-session-end" name="end_time" value="19:00" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark">Số giờ học</label>
                            <input type="text" class="form-control bg-light" id="add-session-duration" value="1.5 giờ" readonly>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-price">Học phí 1 buổi (đ)</label>
                            <input type="number" class="form-control" id="add-session-price" name="price_per_session" value="<?= e((int)$defaultPrice) ?>" min="0" step="1000">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-percent">% GV nhận (%)</label>
                            <input type="number" class="form-control" id="add-session-percent" name="teacher_percentage" value="<?= e($defaultTeacherPct) ?>" min="0" max="100" step="0.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark">Số tiền GV nhận (đ)</label>
                            <input type="text" class="form-control bg-light text-success fw-bold" id="add-session-teacher-amount" value="<?= e(number_format($defaultPrice * $defaultTeacherPct / 100, 0, ',', '.')) ?> đ" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark" for="add-session-topic">Chuyên đề học</label>
                        <select class="form-select" id="add-session-topic" name="topic_id">
                            <option value="">— Chọn chuyên đề (không bắt buộc) —</option>
                            <?php foreach ($topics as $t): ?>
                                <option value="<?= (int) $t['id'] ?>"><?= e($t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-medium text-dark" for="add-session-status">Trạng thái buổi học</label>
                        <select class="form-select" id="add-session-status" name="status">
                            <option value="upcoming">Sắp diễn ra (Tương lai - Đỏ)</option>
                            <option value="finished">Đã hoàn thành (Quá khứ - Hồng)</option>
                            <option value="missing">Thiếu BTVN / Báo cáo (Quá khứ - Hồng có ✗)</option>
                            <option value="cancelled">Đã nghỉ học (Xám)</option>
                        </select>
                        <div class="form-text text-muted" id="add-session-status-hint">
                            *Nếu ngày chọn ở quá khứ, hệ thống sẽ gợi ý trạng thái Đã hoàn thành hoặc Thiếu BTVN.
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light px-4 py-3">
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'label'   => 'Huỷ',
                        'variant' => 'secondary',
                        'size'    => 'sm',
                        'attrs'   => array(
                            'data-bs-dismiss' => 'modal',
                        ),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'submit',
                        'label'   => 'Lưu buổi học',
                        'icon'    => 'fa-solid fa-check',
                        'variant' => 'primary',
                        'size'    => 'sm',
                        'class'   => 'px-3',
                        'attrs'   => array(
                            'style' => 'background-color: #ea580c; border-color: #ea580c;',
                        ),
                    )); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== JAVASCRIPT XỬ LÝ TƯƠNG TÁC ===================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    (function ($) {
        'use strict';

        // ---------- Click vào buổi học -> Mở Modal Chi tiết ----------
        $(document).on('click', '.lms-cal-event', function (e) {
            e.stopPropagation();
            var $card = $(this);
            var dateStr = $card.data('date') || '';
            var start = $card.data('start') || '';
            var end = $card.data('end') || '';
            var duration = $card.data('duration') || '';
            var price = $card.data('price') || '0';
            var percent = $card.data('percent') || '0';
            var teacherAmt = $card.data('teacher-amount') || '0';
            var statusLabel = $card.data('status-label') || '';
            var topic = $card.data('topic') || '';
            var summary = $card.data('summary') || '';
            var hw = $card.data('hw') || '';
            var sessionId = $card.data('session-id') || '';

            $('#md-detail-datetime').text(dateStr + ' (' + start + ' - ' + end + ')');
            $('#md-detail-badge').text(statusLabel);
            var variant = $card.data('variant') || '';
            var badgeClass = 'badge bg-secondary';
            if (variant === 'upcoming') {
                badgeClass = 'badge bg-danger';
            } else if (variant === 'missing') {
                badgeClass = 'badge bg-danger-subtle text-danger border border-danger';
            } else if (variant === 'finished') {
                badgeClass = 'badge bg-pink-subtle text-danger-emphasis';
            }
            $('#md-detail-badge').attr('class', badgeClass);

            $('#md-detail-duration').text(duration + ' giờ');
            $('#md-detail-price').text(price + ' đ');
            $('#md-detail-percent').text(percent + '%');
            $('#md-detail-teacher-amount').text(teacherAmt + ' đ');
            $('#md-detail-topic').text(topic);
            $('#md-detail-summary').text(summary);
            $('#md-detail-hw').text(hw);

            $('#md-detail-input-absent-id').val(sessionId);
            $('#md-detail-input-delete-id').val(sessionId);

            var reportUrl = '<?= e(lms_url($alias, 'buoi')) ?>?id=' + sessionId;
            $('#md-detail-link-report').attr('href', reportUrl);

            var modal = new bootstrap.Modal(document.getElementById('modal-session-detail'));
            modal.show();
        });

        // ---------- 5. Click vào ô trống -> Mở Modal Thêm buổi học ----------
        $(document).on('click', '.lms-cal-cell-empty', function () {
            var date = $(this).data('date');
            var hour = $(this).data('hour');

            if (date) {
                $('#add-session-date').val(date);
            }
            if (hour !== undefined) {
                var startH = String(hour).padStart(2, '0') + ':00';
                var endH = String(Math.min(23, hour + 1)).padStart(2, '0') + ':30';
                $('#add-session-start').val(startH);
                $('#add-session-end').val(endH);
            }

            syncAddSessionCalculations();
            autoDetectSessionStatus();

            var modal = new bootstrap.Modal(document.getElementById('modal-add-session'));
            modal.show();
        });

        // Tính toán giờ học và tiền GV nhận trong form thêm
        function syncAddSessionCalculations() {
            var s = $('#add-session-start').val();
            var e = $('#add-session-end').val();
            if (s && e) {
                var startParts = s.split(':');
                var endParts = e.split(':');
                var startMins = parseInt(startParts[0], 10) * 60 + parseInt(startParts[1], 10);
                var endMins = parseInt(endParts[0], 10) * 60 + parseInt(endParts[1], 10);
                var diff = Math.max(0, endMins - startMins);
                var hours = (diff / 60).toFixed(2);
                $('#add-session-duration').val(hours + ' giờ');
            }

            var price = parseFloat($('#add-session-price').val()) || 0;
            var pct = parseFloat($('#add-session-percent').val()) || 0;
            var amount = Math.round(price * pct / 100);
            $('#add-session-teacher-amount').val(amount.toLocaleString('vi-VN') + ' đ');
        }

        function autoDetectSessionStatus() {
            var date = $('#add-session-date').val();
            var today = new Date().toISOString().slice(0, 10);
            if (date && date < today) {
                $('#add-session-status').val('finished');
                $('#add-session-status-hint').text('Ngày trong quá khứ: Mặc định là đã hoàn thành (hoặc thiếu BTVN nếu chưa nộp).');
            } else {
                $('#add-session-status').val('upcoming');
                $('#add-session-status-hint').text('Ngày trong tương lai: Tự động gán trạng thái Buổi học sắp tới.');
            }
        }

        $('#add-session-start, #add-session-end, #add-session-price, #add-session-percent').on('input change', syncAddSessionCalculations);
        $('#add-session-date').on('change', autoDetectSessionStatus);

    })(jQuery);
});
</script>
