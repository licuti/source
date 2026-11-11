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

<!-- ===================== KHỐI 1: THÔNG TIN LỚP & CẤU HÌNH LỊCH CỐ ĐỊNH ===================== -->
<div class="mb-4">
    <div class="row g-4 g-lg-5">
        <!-- Cột trái: Thông tin lớp học (Readonly) -->
        <div class="col-12 col-lg-5 pe-lg-3">
            <?php lms_component('form/input', array(
                'label'       => 'Loại lớp',
                'name'        => 'class_type_display',
                'value'       => $classTypeLabel,
                'required'    => true,
                'readonly'    => true,
                'disabled'    => true,
                'class'       => 'lms-input-static',
                'label_col'   => 'col-12 col-sm-4',
                'control_col' => 'col-12 col-sm-8',
                'label_align' => 'text-sm-start',
            )); ?>

            <?php lms_component('form/input', array(
                'label'       => 'Giáo viên',
                'name'        => 'teacher_display',
                'value'       => $teacherLabel ?: 'Chưa phân công',
                'required'    => true,
                'readonly'    => true,
                'disabled'    => true,
                'class'       => 'lms-input-static',
                'label_col'   => 'col-12 col-sm-4',
                'control_col' => 'col-12 col-sm-8',
                'label_align' => 'text-sm-start',
            )); ?>

            <?php lms_component('form/input', array(
                'label'       => 'Học viên',
                'name'        => 'student_display',
                'value'       => $studentText,
                'required'    => true,
                'readonly'    => true,
                'disabled'    => true,
                'class'       => 'lms-input-static',
                'label_col'   => 'col-12 col-sm-4',
                'control_col' => 'col-12 col-sm-8',
                'label_align' => 'text-sm-start',
                'attrs'       => array('title' => $studentText),
            )); ?>
        </div>

        <!-- Cột phải: Cấu hình Lịch cố định & Ngày áp dụng lịch -->
        <div class="col-12 col-lg-6">
            <form method="post" action="<?= e(lms_url($alias, 'cap-nhat-lich-co-dinh')) ?>" id="form-update-fixed-schedule">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="week" value="<?= e($weekMonday) ?>">

                <?php
                $slotsHtml = lms_component_render('form/class_schedule_slots', array(
                    'id'       => 'lms-fixed-schedules-container',
                    'rows'     => $fixedRows,
                    'can_edit' => $canEdit,
                ));
                lms_component('form/field_row', array(
                    'label'       => 'Lịch cố định',
                    'required'    => true,
                    'control'     => $slotsHtml,
                    'label_col'   => 'col-12 col-sm-4',
                    'control_col' => 'col-12 col-sm-8',
                    'label_align' => 'text-sm-start',
                    'align'       => 'start',
                ));
                ?>

                <?php
                ob_start();
                ?>
                <div class="d-flex align-items-center gap-3">
                    <div style="max-width: 220px; flex: 1 1 auto;">
                        <?php lms_component('form/date_picker', array(
                            'name'     => 'effective_from',
                            'value'    => $effectiveFromDate,
                            'required' => true,
                            'bare'     => true,
                        )); ?>
                    </div>
                    <?php if ($canEdit): ?>
                        <?php lms_component('button', array(
                            'type'    => 'submit',
                            'label'   => 'Lưu',
                            'variant' => 'primary',
                            'class'   => '',
                        )); ?>
                    <?php endif; ?>
                </div>
                <?php
                $effectiveControl = ob_get_clean();

                lms_component('form/field_row', array(
                    'label'       => 'Ngày áp dụng lịch',
                    'required'    => true,
                    'control'     => $effectiveControl,
                    'label_col'   => 'col-12 col-sm-4',
                    'control_col' => 'col-12 col-sm-8',
                    'label_align' => 'text-sm-start',
                ));
                ?>
            </form>
        </div>
    </div>
</div>

<!-- ===================== KHỐI 2: ĐIỀU HƯỚNG TUẦN / THÁNG ===================== -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
    <!-- Nút Lùi / Tiến & Tiêu đề Tháng -->
    <div class="d-flex align-items-center gap-2">
        <a href="<?= e(lms_url($alias, 'lich-hoc', array('id' => (int) $row['id'], 'week' => $prevWeek))) ?>"
           class="btn btn-outline-secondary btn-sm px-2 py-1 rounded-2" title="Tuần trước">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <a href="<?= e(lms_url($alias, 'lich-hoc', array('id' => (int) $row['id'], 'week' => $nextWeek))) ?>"
           class="btn btn-outline-secondary btn-sm px-2 py-1 rounded-2" title="Tuần sau">
            <i class="fa-solid fa-chevron-right"></i>
        </a>
        <h4 class="mb-0 ms-2 fw-bold fs-5">
            Tháng <?= (int) $weekMonth ?> / <?= (int) $weekYear ?>
        </h4>
        <?php if ($weekMonday !== $currWeek): ?>
            <?php lms_component('button', array(
                'href'    => lms_url($alias, 'lich-hoc', array('id' => (int) $row['id'], 'week' => $currWeek)),
                'label'   => 'Hôm nay',
                'variant' => 'outline-primary',
                'size'    => 'sm',
                'class'   => '',
            )); ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== KHỐI 3: LƯỚI LỊCH TUẦN TRỰC QUAN (CALENDAR TIME GRID) ===================== -->
<div class="lms-card">
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
            return '<div class="fw-semibold text-dark small">' . e($dInfo['label']) . '</div>'
                . '<div class="fw-bold fs-6 lh-1 mt-1 ' . ($dInfo['is_today'] ? 'text-primary' : 'text-dark')
                . '">' . (int) $dInfo['day'] . '/' . (int) $dInfo['month'] . '</div>';
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

<!-- ===================== KHỐI 4: CHÚ THÍCH ===================== -->
<div class="mt-4 text-dark">
    <h6 class="fw-bold mb-3">Chú thích</h6>
    <ul class="lms-cal-legend lms-cal-legend--stack">
        <li class="d-flex align-items-center">
            <span class="lms-cal-dot lms-cal-upcoming-dot me-2"></span>
            Buổi học sắp tới
        </li>
        <li class="d-flex align-items-center">
            <span class="lms-cal-dot lms-cal-missing-dot me-2 d-inline-flex align-items-center justify-content-center" style="font-size: 10px; color: #dc3545; font-weight: 900;">✕</span>
            Buổi học thiếu BTVN
        </li>
        <li class="d-flex align-items-center">
            <span class="lms-cal-dot lms-cal-finished-dot me-2"></span>
            Buổi học đã hoàn thành
        </li>
        <li class="d-flex align-items-center">
            <span class="lms-cal-dot lms-cal-cancelled-dot me-2"></span>
            Buổi học đã nghỉ
        </li>
    </ul>
</div>

<!-- ===================== MODAL 1: CHI TIẾT BUỔI HỌC ===================== -->
<div class="modal fade" id="modal-session-detail" tabindex="-1" aria-labelledby="modalSessionDetailLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold fs-5" id="modalSessionDetailLabel">
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
                        <?php lms_component('badge', array(
                            'label'   => '—',
                            'variant' => 'secondary',
                            'class'   => 'badge',
                            'attrs'   => array('id' => 'md-detail-badge', 'style' => 'font-size: 0.85rem; padding: 6px 10px;'),
                        )); ?>
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
                                'size'    => '',
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
                                'size'    => '',
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
                        'size'    => '',
                        'id'      => 'md-detail-link-report',
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'label'   => 'Đóng',
                        'variant' => 'secondary',
                        'size'    => '',
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
        <div class="modal-content">
            <form method="post" action="<?= e(lms_url($alias, 'them-buoi-hoc')) ?>" id="form-add-session">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <input type="hidden" name="week" value="<?= e($weekMonday) ?>">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold fs-5" id="modalAddSessionLabel">
                        <i class="fa-solid fa-calendar-plus me-2"></i>Thêm buổi học mới
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark" for="add-session-date">Ngày học <span class="text-danger">*</span></label>
                        <?php lms_component('form/date_picker', array(
                            'id'       => 'add-session-date',
                            'name'     => 'session_date',
                            'value'    => date('Y-m-d'),
                            'required' => true,
                            'bare'     => true,
                        )); ?>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-start">Giờ bắt đầu <span class="text-danger">*</span></label>
                            <?php lms_component('form/input', array(
                                'type'     => 'time',
                                'id'       => 'add-session-start',
                                'name'     => 'start_time',
                                'value'    => '17:30',
                                'required' => true,
                                'bare'     => true,
                            )); ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-end">Giờ kết thúc <span class="text-danger">*</span></label>
                            <?php lms_component('form/input', array(
                                'type'     => 'time',
                                'id'       => 'add-session-end',
                                'name'     => 'end_time',
                                'value'    => '19:00',
                                'required' => true,
                                'bare'     => true,
                            )); ?>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-duration">Số giờ học</label>
                            <?php lms_component('form/input', array(
                                'id'       => 'add-session-duration',
                                'value'    => '1.5 giờ',
                                'readonly' => true,
                                'disabled' => true,
                                'class'    => 'lms-input-static',
                                'bare'     => true,
                            )); ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-price">Học phí 1 buổi (đ)</label>
                            <?php lms_component('form/input', array(
                                'type'  => 'number',
                                'id'    => 'add-session-price',
                                'name'  => 'price_per_session',
                                'value' => (int) $defaultPrice,
                                'bare'  => true,
                                'attrs' => array('min' => 0, 'step' => 1000),
                            )); ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-percent">% GV nhận (%)</label>
                            <?php lms_component('form/input', array(
                                'type'  => 'number',
                                'id'    => 'add-session-percent',
                                'name'  => 'teacher_percentage',
                                'value' => $defaultTeacherPct,
                                'bare'  => true,
                                'attrs' => array('min' => 0, 'max' => 100, 'step' => 0.5),
                            )); ?>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-medium text-dark" for="add-session-teacher-amount">Số tiền GV nhận (đ)</label>
                            <?php lms_component('form/input', array(
                                'id'       => 'add-session-teacher-amount',
                                'value'    => number_format($defaultPrice * $defaultTeacherPct / 100, 0, ',', '.') . ' đ',
                                'readonly' => true,
                                'disabled' => true,
                                'class'    => 'lms-input-static text-success fw-bold',
                                'bare'     => true,
                            )); ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium text-dark" for="add-session-topic">Chuyên đề học</label>
                        <?php
                        $topicOptions = array();
                        foreach ($topics as $t) {
                            $topicOptions[(int) $t['id']] = $t['name'];
                        }
                        lms_component('form/select', array(
                            'id'          => 'add-session-topic',
                            'name'        => 'topic_id',
                            'options'     => $topicOptions,
                            'empty_label' => '— Chọn chuyên đề (không bắt buộc) —',
                            'value'       => '',
                            'bare'        => true,
                        ));
                        ?>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-medium text-dark" for="add-session-status">Trạng thái buổi học</label>
                        <?php lms_component('form/select', array(
                            'id'      => 'add-session-status',
                            'name'    => 'status',
                            'options' => array(
                                'upcoming'  => 'Sắp diễn ra (Tương lai - Đỏ)',
                                'finished'  => 'Đã hoàn thành (Quá khứ - Hồng)',
                                'missing'   => 'Thiếu BTVN / Báo cáo (Quá khứ - Hồng có ✗)',
                                'cancelled' => 'Đã nghỉ học (Xám)',
                            ),
                            'value'   => 'upcoming',
                            'bare'    => true,
                        )); ?>
                        <div class="form-text text-muted" id="add-session-status-hint">
                            *Nếu ngày chọn ở quá khứ, hệ thống sẽ gợi ý trạng thái Đã hoàn thành hoặc Thiếu BTVN.
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <?php lms_component('button', array(
                        'type'    => 'button',
                        'label'   => 'Huỷ',
                        'variant' => 'secondary',
                        'size'    => '',
                        'attrs'   => array(
                            'data-bs-dismiss' => 'modal',
                        ),
                    )); ?>
                    <?php lms_component('button', array(
                        'type'    => 'submit',
                        'label'   => 'Lưu buổi học',
                        'icon'    => 'fa-solid fa-check',
                        'variant' => 'primary',
                        'size'    => '',
                        'class'   => '',
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
