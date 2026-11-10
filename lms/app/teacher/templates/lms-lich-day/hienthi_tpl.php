<?php
/**
 * Teacher — Quản lý lịch (view, mockup: Lịch tuần + 3 modal).
 * Data do source chuẩn bị: $profile, $weekNote, $weekMonday,
 * $prevWeek, $nextWeek, $calRows, $freeSlots, $classes, $classStudents,
 * $classSchedules, $canEdit.
 */
$csrf = lms_csrf_token();

$districtGroups = array(
    'Các quận TP.HCM' => array(
        'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
        'Quận 10', 'Quận 11', 'Quận 12', 'Bình Thạnh', 'Gò Vấp', 'Phú Nhuận',
        'Tân Bình', 'Tân Phú', 'Bình Tân', 'Bình Chánh', 'Cần Giờ', 'Củ Chi', 'Hóc Môn', 'Nhà Bè',
    ),
    'Khu vực TP. Thủ Đức (Tất cả phường)' => array(
        'P. An Khánh (Thủ Đức)', 'P. An Lợi Đông (Thủ Đức)', 'P. An Phú (Thủ Đức)',
        'P. Bình Chiểu (Thủ Đức)', 'P. Bình Thọ (Thủ Đức)', 'P. Hiệp Bình Chánh (Thủ Đức)',
        'P. Hiệp Bình Phước (Thủ Đức)', 'P. Hiệp Phú (Thủ Đức)', 'P. Linh Chiểu (Thủ Đức)',
        'P. Linh Đông (Thủ Đức)', 'P. Linh Tây (Thủ Đức)', 'P. Linh Trung (Thủ Đức)',
        'P. Linh Xuân (Thủ Đức)', 'P. Long Bình (Thủ Đức)', 'P. Long Phước (Thủ Đức)',
        'P. Long Thạnh Mỹ (Thủ Đức)', 'P. Long Trường (Thủ Đức)', 'P. Phú Hữu (Thủ Đức)',
        'P. Phước Bình (Thủ Đức)', 'P. Phước Long A (Thủ Đức)', 'P. Phước Long B (Thủ Đức)',
        'P. Tam Bình (Thủ Đức)', 'P. Tam Phú (Thủ Đức)', 'P. Tăng Nhơn Phú A (Thủ Đức)',
        'P. Tăng Nhơn Phú B (Thủ Đức)', 'P. Thạnh Mỹ Lợi (Thủ Đức)', 'P. Thảo Điền (Thủ Đức)',
        'P. Thủ Thiêm (Thủ Đức)', 'P. Trường Thạnh (Thủ Đức)', 'P. Trường Thọ (Thủ Đức)',
    ),
);
$selDistricts = isset($profile['teachable_districts']) && is_array($profile['teachable_districts'])
    ? $profile['teachable_districts']
    : array();
?>
<?php if (!$canEdit): ?>
    <div class="lms-alert lms-alert-warning">Bạn không có quyền chỉnh sửa thông tin này.</div>
<?php endif; ?>

<!-- ===================== Thông tin cá nhân (§4.1) ===================== -->
<div class="lms-ld-profile">
    <form method="post" action="<?= e(lms_url($alias, 'luu-thong-tin', lmld_week_params($weekMonday))) ?>" class="lms-form">
        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">

        <div class="row g-2 align-items-center mb-2">
            <label class="col-md-4 col-lg-3 col-form-label" for="lmld-address">Địa chỉ hiện tại</label>
            <div class="col-md-8 col-lg-9">
                <?php lms_component('form/input', array(
                    'name'  => 'current_address',
                    'id'    => 'lmld-address',
                    'value' => lms_form_old($_POST, $profile, 'current_address'),
                    'class' => 'form-control',
                    'bare'  => true,
                    'attrs' => array('maxlength' => 255, 'disabled' => !$canEdit),
                )); ?>
            </div>
        </div>

        <div class="row g-2 align-items-start mb-2">
            <label class="col-md-4 col-lg-3 col-form-label" for="lms-picker-teachable-districts-trigger">Các khu vực có thể dạy</label>
            <div class="col-md-8 col-lg-9">
                <?php lms_component('form/multi_select_tags', array(
                    'name'        => 'teachable_districts[]',
                    'selected'    => $selDistricts,
                    'groups'      => $districtGroups,
                    'placeholder' => 'Quận cũ; nếu Thủ Đức thì chọn phường',
                    'bare'        => true,
                )); ?>
            </div>
        </div>

        <div class="row g-2 align-items-center mb-2">
            <label class="col-md-4 col-lg-3 col-form-label" for="lmld-online">Có thể dạy online</label>
            <div class="col-md-8 col-lg-9">
                <?php lms_component('form/select', array(
                    'name'    => 'can_teach_online',
                    'id'      => 'lmld-online',
                    'options' => array('0' => 'Không', '1' => 'Có'),
                    'value'   => lms_form_old($_POST, $profile, 'can_teach_online', '0'),
                    'class'   => 'form-select',
                    'bare'    => true,
                    'attrs'   => array('disabled' => !$canEdit),
                )); ?>
            </div>
        </div>

        <div class="row g-2 align-items-center mb-2">
            <label class="col-md-4 col-lg-3 col-form-label" for="lmld-max-classes">Số lớp tối đa có thể nhận</label>
            <div class="col-md-8 col-lg-9">
                <?php lms_component('form/input', array(
                    'name'  => 'max_classes',
                    'id'    => 'lmld-max-classes',
                    'type'  => 'number',
                    'value' => lms_form_old($_POST, $profile, 'max_classes', '1'),
                    'min'   => 0,
                    'max'   => 50,
                    'class' => 'form-control',
                    'bare'  => true,
                    'attrs' => array('disabled' => !$canEdit),
                )); ?>
            </div>
        </div>

        <?php if ($canEdit): ?>
            <div class="text-end">
                <?php lms_component('button', array(
                    'label'   => 'Lưu thông tin',
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'icon'    => 'fa-solid fa-floppy-disk',
                )); ?>
            </div>
        <?php endif; ?>
    </form>
</div>

<!-- ===================== Lịch tuần (§4.2 + §4.3) ===================== -->
<div class="lms-ld-calendar mt-4">
    <div class="lms-ld-cal-toolbar">
        <div class="lms-cal-nav">
            <a href="<?= e(lms_url($alias, '', lmld_week_params($prevWeek))) ?>" class="btn btn-sm btn-outline-lms" aria-label="Tuần trước">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <strong>Tháng <?= e(date('n', strtotime($weekMonday))) ?> / <?= e(date('Y', strtotime($weekMonday))) ?></strong>
            <a href="<?= e(lms_url($alias, '', lmld_week_params($nextWeek))) ?>" class="btn btn-sm btn-outline-lms" aria-label="Tuần sau">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <?php if ($canEdit): ?>
            <div class="lms-ld-cal-actions">
                <?php lms_component('button', array(
                    'label' => 'Đăng ký thêm buổi học',
                    'type'  => 'button',
                    'variant' => 'primary',
                    'attrs' => array('data-bs-toggle' => 'modal', 'data-bs-target' => '#lmld-modal-add-session'),
                )); ?>
                <?php lms_component('button', array(
                    'label' => 'Đăng ký lịch trống',
                    'type'  => 'button',
                    'variant' => 'primary',
                    'attrs' => array(
                        'data-bs-toggle' => 'collapse',
                        'data-bs-target' => '#lmld-free-slots',
                        'aria-expanded'  => 'false',
                    ),
                )); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Lịch trống đã đăng ký -->
    <?php if ($canEdit): ?>
        <div class="collapse lms-free-slots mb-3" id="lmld-free-slots">
            <h3 class="lms-free-slots-title">Lịch trống đã đăng ký</h3>
            <?php if (empty($freeSlots)): ?>
                <p class="text-muted">Chưa có lịch trống nào. Thêm khung giờ bạn có thể nhận thêm lớp.</p>
            <?php else: ?>
                <ul class="lms-free-slot-list">
                    <?php foreach ($freeSlots as $slot): ?>
                        <li>
                            <span class="lms-cal-dot lms-cal-free-dot"></span>
                            <?= e(isset(\Lms\UserService::WEEKDAYS[(int) $slot['day_of_week']]) ? \Lms\UserService::WEEKDAYS[(int) $slot['day_of_week']] : '') ?>
                            — <?= e(substr((string) $slot['start_time'], 0, 5)) ?>–<?= e(substr((string) $slot['end_time'], 0, 5)) ?>
                            <?php if ($canEdit): ?>
                                <form method="post" action="<?= e(lms_url($alias, 'xoa-slot', lmld_week_params($weekMonday))) ?>" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="slot_id" value="<?= e((int) $slot['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger lms-slot-del" title="Xoá lịch trống">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post" action="<?= e(lms_url($alias, 'them-slot', lmld_week_params($weekMonday))) ?>" class="lms-form lms-free-slot-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="lms-form-label" for="lmld-slot-day">Thứ</label>
                        <?php lms_component('form/select', array(
                            'name'    => 'day_of_week',
                            'id'      => 'lmld-slot-day',
                            'options' => \Lms\UserService::WEEKDAYS,
                            'bare'    => true,
                        )); ?>
                    </div>
                    <div class="col-md-3">
                        <label class="lms-form-label" for="lmld-slot-start">Giờ bắt đầu</label>
                        <input type="time" class="form-control" id="lmld-slot-start" name="start_time" required>
                    </div>
                    <div class="col-md-3">
                        <label class="lms-form-label" for="lmld-slot-end">Giờ kết thúc</label>
                        <input type="time" class="form-control" id="lmld-slot-end" name="end_time" required>
                    </div>
                    <div class="col-md-2">
                        <?php lms_component('button', array(
                            'label'   => 'Thêm',
                            'type'    => 'submit',
                            'variant' => 'primary',
                            'class'   => 'w-100',
                        )); ?>
                    </div>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php
    $calLanes = lms_cal_lanes_for_rows($calRows);
    lms_component('calendar_week', array(
        'monday'    => $weekMonday,
        'weekdays'  => \Lms\UserService::WEEKDAYS,
        'rows'      => $calRows,
        'hour_label' => 'Khung giờ',
        'cell_class' => function (array $cell) {
            if (empty($cell['sessions'])) {
                return '';
            }
            $first = $cell['sessions'][0];
            return 'lms-cal-cell--' . lms_cal_variant(
                (string) $first['status'],
                lms_cal_missing_hw($first)
            );
        },
        'cell_renderer' => function (array $cell) use ($canEdit, $alias, $weekMonday, $calLanes) {
            $html = '';
            $date = (string) $cell['date'];
            foreach ($cell['sessions'] as $ses) {
                $variant = lms_cal_variant((string) $ses['status'], lms_cal_missing_hw($ses));
                $footer = '';
                if ($canEdit && (string) $ses['status'] === 'upcoming'
                    && (string) $ses['session_date'] >= date('Y-m-d')) {
                    $footer = '<button type="button" class="lms-cal-edit"'
                        . ' data-bs-toggle="modal" data-bs-target="#lmld-modal-edit"'
                        . ' data-session-id="' . (int) $ses['id'] . '"'
                        . ' data-class-id="' . (int) $ses['class_id'] . '"'
                        . ' data-class-name="' . e((string) $ses['class_name']) . '"'
                        . ' data-session-date="' . e((string) $ses['session_date']) . '">Chỉnh lịch</button>';
                }
                $lane = lms_cal_lane_of($calLanes, $date, 's' . (int) $ses['id']);
                $html .= lms_component_render('calendar_week_event', array(
                    'variant' => $variant,
                    'title'   => (string) $ses['class_name'],
                    'start'   => (string) $ses['start_time'],
                    'end'     => (string) $ses['end_time'],
                    'lane'    => $lane['lane'],
                    'lanes'   => $lane['lanes'],
                    'href'    => lms_url($alias, 'buoi', array('id' => (int) $ses['id'], 'week' => $weekMonday)),
                    'flag'    => $variant === 'missing' ? '✗' : '',
                    'footer'  => $footer,
                ));
            }
            // Lịch trống = dải nền full chiều ngang, nằm sau thẻ buổi học (z-index thấp hơn)
            foreach ($cell['free'] as $slot) {
                $html .= lms_component_render('calendar_week_event', array(
                    'variant' => 'free',
                    'title'   => 'Lịch trống',
                    'start'   => (string) $slot['start'],
                    'end'     => (string) $slot['end'],
                    'show_time' => true,
                ));
            }
            return $html;
        },
    ));
    ?>

    <!-- Chú thích + Ghi chú tuần -->
    <div class="row g-4 mt-3">
        <div class="col-lg-5">
            <div class="lms-ld-legend">
                <strong>Chú thích:</strong>
                <ul class="lms-cal-legend lms-cal-legend--stack">
                    <li><span class="lms-cal-dot lms-cal-upcoming-dot"></span> Buổi học sắp tới</li>
                    <li><span class="lms-cal-dot lms-cal-missing-dot"></span> Buổi học thiếu BTVN</li>
                    <li><span class="lms-cal-dot lms-cal-finished-dot"></span> Buổi học đã hoàn thành</li>
                    <li><span class="lms-cal-dot lms-cal-cancelled-dot"></span> Buổi học đã nghỉ</li>
                    <li><span class="lms-cal-dot lms-cal-free-dot"></span> Lịch trống có thể nhận thêm lớp</li>
                </ul>
            </div>
        </div>
        <div class="col-lg-7">
            <form method="post" action="<?= e(lms_url($alias, 'luu-ghi-chu', lmld_week_params($weekMonday))) ?>" class="lms-form">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <label class="lms-form-label" for="lmld-week-note">Ghi chú:</label>
                <?php lms_component('form/textarea', array(
                    'name'     => 'week_note',
                    'id'       => 'lmld-week-note',
                    'value'    => isset($weekNote['note']) ? (string) $weekNote['note'] : '',
                    'rows'     => 4,
                    'bare'     => true,
                    'attrs'    => array('maxlength' => 5000, 'disabled' => !$canEdit),
                )); ?>
                <?php if ($canEdit): ?>
                    <div class="text-end mt-2">
                        <?php lms_component('button', array(
                            'label'   => 'Cập nhật',
                            'type'    => 'submit',
                            'variant' => 'primary',
                        )); ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php if ($canEdit && !empty($classes)): ?>
<!-- ===================== Modal: Đăng ký thêm buổi học (§4.6) ===================== -->
<div class="modal fade" id="lmld-modal-add-session" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(lms_url($alias, 'gui-yeu-cau', lmld_week_params($weekMonday))) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="request_type" value="add_session">
                <div class="modal-header">
                    <h2 class="modal-title">Đăng ký thêm buổi học</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <?php
                    $classOptions = array();
                    foreach ($classes as $c) {
                        $classOptions[(string) (int) $c['id']] = (string) $c['name'];
                    }
                    lms_component('form/select', array(
                        'name'        => 'class_id',
                        'id'          => 'lmld-as-class',
                        'options'     => $classOptions,
                        'empty_label' => '— Chọn lớp học —',
                        'bare'        => true,
                        'attrs'       => array('required' => true, 'aria-label' => 'Lớp học'),
                    )); ?>

                    <?php foreach ($classes as $c): ?>
                        <?php if (empty($classStudents[(int) $c['id']])) { continue; } ?>
                        <div class="mb-2 lmld-as-students d-none" data-class-id="<?= e((int) $c['id']) ?>">
                            <select class="form-select" name="student_id" aria-label="Học viên" disabled>
                                <?php foreach ($classStudents[(int) $c['id']] as $s): ?>
                                    <option value="<?= e((int) $s['student_id']) ?>"><?= e((string) $s['full_name'] . ' (' . $s['student_code'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>

                    <div class="lms-ld-datetime-row mt-2">
                        <label class="visually-hidden" for="lmld-as-date">Ngày</label>
                        <?php lms_component('form/date_picker', array(
                            'name'     => 'session_date',
                            'id'       => 'lmld-as-date',
                            'value'    => '',
                            'required' => true,
                            'bare'     => true,
                        )); ?>
                        <label class="visually-hidden" for="lmld-as-start">Giờ bắt đầu</label>
                        <input type="time" class="form-control" id="lmld-as-start" name="start_time" required aria-label="Giờ bắt đầu">
                        <span class="lms-ld-time-sep">-</span>
                        <label class="visually-hidden" for="lmld-as-end">Giờ kết thúc</label>
                        <input type="time" class="form-control" id="lmld-as-end" name="end_time" required aria-label="Giờ kết thúc">
                    </div>

                    <div class="mt-2">
                        <?php lms_component('form/input', array(
                            'name'        => 'note',
                            'id'          => 'lmld-as-note',
                            'placeholder' => 'Ghi chú thêm',
                            'class'       => 'form-control',
                            'bare'        => true,
                            'attrs'       => array('aria-label' => 'Ghi chú thêm'),
                        )); ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php lms_component('button', array(
                        'label'   => 'Gửi',
                        'type'    => 'submit',
                        'variant' => 'primary',
                    )); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ===================== Modal: Chỉnh lịch (§4.5) ===================== -->
<div class="modal fade" id="lmld-modal-edit" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(lms_url($alias, 'gui-yeu-cau', lmld_week_params($weekMonday))) ?>">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="request_type" value="request_leave" id="lmld-edit-type">
                <input type="hidden" name="class_id" id="lmld-edit-class" value="">
                <input type="hidden" name="session_id" id="lmld-edit-session" value="">
                <div class="modal-header">
                    <h2 class="modal-title" id="lmld-edit-title">Chỉnh lịch</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <?php lms_component('form/select', array(
                        'name'    => 'request_type_ui',
                        'id'      => 'lmld-edit-action',
                        'options' => array(
                            'request_leave'   => 'Xin nghỉ',
                            'change_schedule' => 'Đổi lịch cố định',
                        ),
                        'value'   => 'request_leave',
                        'bare'    => true,
                        'attrs'   => array('aria-label' => 'Hành động'),
                    )); ?>

                    <div id="lmld-edit-leave" class="mt-3">
                        <label class="lms-form-label" for="lmld-edit-date">Ngày</label>
                        <input type="date" class="form-control" id="lmld-edit-date" readonly aria-label="Ngày xin nghỉ">

                        <label class="lms-form-label mt-3" for="lmld-edit-reason">Lý do</label>
                        <input type="text" class="form-control" id="lmld-edit-reason" name="reason" placeholder="Lý do" required>

                        <label class="lms-form-label mt-3" for="lmld-edit-makeup">Ngày bù (không bắt buộc)</label>
                        <?php lms_component('form/date_picker', array(
                            'name'  => 'makeup_date',
                            'id'    => 'lmld-edit-makeup',
                            'value' => '',
                            'bare'  => true,
                        )); ?>
                    </div>

                    <div id="lmld-edit-change" class="d-none mt-3">
                        <p class="lms-text-muted-sm">Lịch mới chỉ áp dụng từ tuần kế tiếp trở đi.</p>
                        <?php for ($i = 0; $i < 2; $i++): ?>
                            <div class="lmld-change-slot mb-3">
                                <strong>Buổi <?= $i === 0 ? 'thứ nhất' : 'thứ hai' ?></strong>
                                <div class="row g-2 mt-1">
                                    <div class="col-12 col-md-5">
                                        <select class="form-select" name="slot_day[]" aria-label="Thứ">
                                            <option value="">— Chọn thứ —</option>
                                            <?php foreach (\Lms\UserService::WEEKDAYS as $dow => $dLabel): ?>
                                                <option value="<?= e((int) $dow) ?>"><?= e($dLabel) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="lms-ld-time-range">
                                            <input type="time" class="form-control" name="slot_start[]" aria-label="Giờ bắt đầu">
                                            <span class="lms-ld-time-sep">-</span>
                                            <input type="time" class="form-control" name="slot_end[]" aria-label="Giờ kết thúc">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php lms_component('button', array(
                        'label'   => 'Gửi',
                        'type'    => 'submit',
                        'variant' => 'primary',
                    )); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function ($) {
    var classSchedules = <?= json_encode($classSchedules, JSON_UNESCAPED_UNICODE) ?>;

    var $classSelect = $('#lmld-as-class');

    function syncStudents() {
        var cid = $classSelect.val();
        $('.lmld-as-students').addClass('d-none').find('select').prop('disabled', true);
        var $box = $('.lmld-as-students[data-class-id="' + cid + '"]');
        if (!$box.length) { return; }
        var $sel = $box.find('select').prop('disabled', false);
        // Lớp 1-1: chỉ 1 HV → ẩn select nhưng vẫn submit.
        $box.toggleClass('d-none', $sel.find('option').length <= 1);
    }
    $classSelect.on('change', syncStudents);

    // Ô lịch trống: mở modal thêm buổi
    $('#lmld-modal-add-session').on('show.bs.modal', function () {
        $classSelect.val('').trigger('change');
    });

    function fillChangeSlots(cid) {
        var slots = classSchedules[cid] || [];
        $('.lmld-change-slot').each(function (i) {
            var s = slots[i] || null;
            $(this).find('select[name="slot_day[]"]').val(s ? String(s.day) : '');
            $(this).find('input[name="slot_start[]"]').val(s ? s.start : '');
            $(this).find('input[name="slot_end[]"]').val(s ? s.end : '');
        });
    }

    // Modal chỉnh lịch: điền session/class + tiêu đề như mockup
    $('#lmld-modal-edit').on('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        var sid = btn && btn.dataset ? btn.dataset.sessionId : '';
        var cid = btn && btn.dataset ? btn.dataset.classId : '';
        var cname = btn && btn.dataset ? btn.dataset.className : '';
        var sdate = btn && btn.dataset ? btn.dataset.sessionDate : '';

        $('#lmld-edit-class').val(cid);
        $('#lmld-edit-session').val(sid);
        $('#lmld-edit-date').val(sdate || '');
        $('#lmld-edit-title').text('Chỉnh lịch' + (cname ? ' - ' + cname : ''));
        $('#lmld-edit-action').val('request_leave').trigger('change');
        fillChangeSlots(cid);
    });

    // Đổi loại hành động trong modal chỉnh lịch
    $('#lmld-edit-action').on('change', function () {
        var type = $(this).val();
        $('#lmld-edit-type').val(type);
        var isLeave = type === 'request_leave';
        $('#lmld-edit-leave').toggleClass('d-none', !isLeave);
        $('#lmld-edit-change').toggleClass('d-none', isLeave);
        // Chỉ field của chế độ đang hiện mới required/submit.
        $('#lmld-edit-reason').prop('required', isLeave).prop('disabled', !isLeave);
        $('input[name="makeup_date"]').prop('required', false).prop('disabled', !isLeave);
        $('#lmld-edit-change').find('select, input').prop('disabled', isLeave);
        if (!isLeave) {
            fillChangeSlots($('#lmld-edit-class').val());
        }
    });
})(jQuery);
</script>
<?php endif; ?>
