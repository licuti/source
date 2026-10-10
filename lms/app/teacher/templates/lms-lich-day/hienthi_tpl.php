<?php
/**
 * Teacher — Quản lý lịch (view, mockup §4).
 * Data do source chuẩn bị: $profile, $weekNote, $weekMonday, $weekLabel,
 * $prevWeek, $nextWeek, $calRows, $freeSlots, $classes, $classStudents,
 * $districts, $canEdit, $canDelete.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<!-- ===================== Form thông tin cá nhân + ghi chú tuần (§4.1 + §4.4) ===================== -->
<div class="lms-card lms-lich-day-profile">
    <div class="lms-card-header">
        <i class="fa-solid fa-user me-1"></i> Thông tin cá nhân
    </div>
    <div class="lms-card-body">
        <?php if (!$canEdit): ?>
            <div class="lms-alert lms-alert-warning">Bạn không có quyền chỉnh sửa thông tin này.</div>
        <?php endif; ?>
        <form method="post" action="<?= e(lms_url($alias, 'luu-thong-tin', lmld_week_params($weekMonday))) ?>"
              class="lms-form" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="lms-form-label" for="lmld-address">Địa chỉ hiện tại</label>
                    <input type="text" class="form-control" id="lmld-address" name="current_address"
                           maxlength="255"
                           value="<?= e(lms_form_old($_POST, $profile, 'current_address')) ?>"
                           <?= $canEdit ? '' : 'disabled' ?>>
                </div>
                <div class="col-md-6">
                    <label class="lms-form-label" for="lmld-districts">Các khu vực có thể dạy</label>
                    <select class="form-select" id="lmld-districts" name="teachable_districts[]"
                            multiple <?= $canEdit ? '' : 'disabled' ?>>
                        <?php $selDistricts = lms_form_old_array($_POST, $profile, 'teachable_districts'); ?>
                        <?php foreach ($districts as $district): ?>
                            <option value="<?= e($district) ?>"<?= lms_in_selected($district, $selDistricts) ?>><?= e($district) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="lms-text-muted-sm">Giữ Ctrl để chọn nhiều khu vực.</small>
                </div>
                <div class="col-md-3">
                    <label class="lms-form-label" for="lmld-online">Có thể dạy online</label>
                    <select class="form-select" id="lmld-online" name="can_teach_online"
                            <?= $canEdit ? '' : 'disabled' ?>>
                        <option value="0"<?= lms_selected(lms_form_old($_POST, $profile, 'can_teach_online'), '0') ?>>Không</option>
                        <option value="1"<?= lms_selected(lms_form_old($_POST, $profile, 'can_teach_online'), '1') ?>>Có</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="lms-form-label" for="lmld-max-classes">Số lớp tối đa có thể nhận</label>
                    <input type="number" class="form-control" id="lmld-max-classes" name="max_classes"
                           min="0" max="50"
                           value="<?= e(lms_form_old($_POST, $profile, 'max_classes', '1')) ?>"
                           <?= $canEdit ? '' : 'disabled' ?>>
                </div>
                <div class="col-12">
                    <label class="lms-form-label" for="lmld-week-note">Ghi chú tuần (<?= e($weekLabel) ?>)</label>
                    <textarea class="form-control" id="lmld-week-note" name="week_note" rows="3"
                              maxlength="5000" placeholder="Ghi chú cho tuần/đợt dạy..."
                              <?= $canEdit ? '' : 'disabled' ?>><?= e(isset($weekNote['note']) ? (string) $weekNote['note'] : '') ?></textarea>
                </div>
            </div>
            <?php if ($canEdit): ?>
                <div class="lms-form-actions">
                    <button type="submit" class="btn btn-lms">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Cập nhật
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ===================== Calendar tuần (§4.2 + §4.3) ===================== -->
<div class="lms-card mt-3">
    <div class="lms-card-header lms-cal-header">
        <div class="lms-cal-nav">
            <a href="<?= e(lms_url($alias, '', lmld_week_params($prevWeek))) ?>" class="btn btn-outline-lms btn-sm" aria-label="Tuần trước">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <strong>Tháng <?= e(date('n', strtotime($weekMonday))) ?> / <?= e(date('Y', strtotime($weekMonday))) ?></strong>
            <a href="<?= e(lms_url($alias, '', lmld_week_params($nextWeek))) ?>" class="btn btn-outline-lms btn-sm" aria-label="Tuần sau">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
        <div class="lms-cal-actions">
            <?php if ($canEdit): ?>
                <button type="button" class="btn btn-lms" data-bs-toggle="modal" data-bs-target="#lmld-modal-add-session">
                    <i class="fa-solid fa-plus me-1"></i> Đăng ký thêm buổi học
                </button>
                <button type="button" class="btn btn-lms" data-bs-toggle="collapse" data-bs-target="#lmld-free-slots" aria-expanded="false">
                    <i class="fa-solid fa-calendar-plus me-1"></i> Đăng ký lịch trống
                </button>
            <?php endif; ?>
        </div>
    </div>
    <div class="lms-card-body">
        <!-- Lịch trống đã đăng ký (§4.2, có thể ẩn/hiện) -->
        <?php if ($canEdit): ?>
            <div class="collapse lms-free-slots" id="lmld-free-slots">
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
                                <?php if ($canDelete): ?>
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
                            <select class="form-select" id="lmld-slot-day" name="day_of_week" required>
                                <?php foreach (\Lms\UserService::WEEKDAYS as $dow => $label): ?>
                                    <option value="<?= e((int) $dow) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
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
                            <button type="submit" class="btn btn-lms w-100">Thêm</button>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <div class="lms-cal-scroll">
            <table class="lms-cal">
                <thead>
                    <tr>
                        <th class="lms-cal-th lms-cal-th-hour">Giờ</th>
                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <?php
                            $ts = strtotime('+' . $i . ' days', strtotime($weekMonday));
                            $dow = (int) date('N', $ts) + 1;
                            ?>
                            <th class="lms-cal-th">
                                <?= e(isset(\Lms\UserService::WEEKDAYS[$dow]) ? \Lms\UserService::WEEKDAYS[$dow] : '') ?>
                                <small><?= e((int) date('j', $ts) . '/' . (int) date('n', $ts)) ?></small>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calRows as $row): ?>
                        <tr>
                            <th class="lms-cal-th lms-cal-th-hour" scope="row"><?= e($row['label']) ?></th>
                            <?php foreach ($row['cells'] as $cell): ?>
                                <?php
                                $hasSession = !empty($cell['sessions']);
                                $hasFree = !$hasSession && !empty($cell['free']);
                                ?>
                                <td class="lms-cal-cell<?php
                                    if ($hasSession) {
                                        $st = (string) $cell['sessions'][0]['status'];
                                        if ($st === 'cancelled') {
                                            echo ' lms-cal-cancelled';
                                        } elseif ($st === 'upcoming') {
                                            echo ' lms-cal-upcoming';
                                        } elseif (empty($cell['sessions'][0]['summary_submitted_at'])) {
                                            echo ' lms-cal-missing';
                                        } else {
                                            echo ' lms-cal-finished';
                                        }
                                    } elseif ($hasFree) {
                                        echo ' lms-cal-free';
                                    }
                                ?>">
                                    <?php foreach ($cell['sessions'] as $ses): ?>
                                        <?php
                                        $st = (string) $ses['status'];
                                        $missing = ($st === 'finished' && empty($ses['summary_submitted_at']));
                                        ?>
                                        <a class="lms-cal-link" href="<?= e(lms_url($alias, 'buoi', array('id' => (int) $ses['id'], 'week' => $weekMonday))) ?>">
                                            <span class="lms-cal-code"><?= e((string) $ses['class_code']) ?></span>
                                            <span class="lms-cal-time"><?= e(substr((string) $ses['start_time'], 0, 5)) ?>–<?= e(substr((string) $ses['end_time'], 0, 5)) ?></span>
                                        </a>
                                        <?php if ($missing): ?>
                                            <span class="lms-cal-missing-x" title="Chưa giao BTVN">✗</span>
                                        <?php endif; ?>
                                        <?php if ($canEdit && $st === 'upcoming'): ?>
                                            <button type="button" class="lms-cal-edit btn btn-sm btn-link"
                                                    data-bs-toggle="modal" data-bs-target="#lmld-modal-edit"
                                                    data-session-id="<?= e((int) $ses['id']) ?>"
                                                    data-class-id="<?= e((int) $ses['class_id']) ?>">
                                                Chỉnh lịch
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if ($hasFree && $canEdit): ?>
                                        <button type="button" class="lms-cal-free-btn"
                                                data-bs-toggle="modal" data-bs-target="#lmld-modal-add-session"
                                                data-class-id="">
                                            + nhận lớp
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Chú thích (§4.4) -->
        <ul class="lms-cal-legend">
            <li><span class="lms-cal-dot lms-cal-upcoming-dot"></span> Buổi học sắp tới</li>
            <li><span class="lms-cal-dot lms-cal-missing-dot"></span> Buổi học thiếu BTVN</li>
            <li><span class="lms-cal-dot lms-cal-finished-dot"></span> Buổi học đã hoàn thành</li>
            <li><span class="lms-cal-dot lms-cal-cancelled-dot"></span> Buổi học đã nghỉ</li>
            <li><span class="lms-cal-dot lms-cal-free-dot"></span> Lịch trống có thể nhận thêm lớp</li>
        </ul>
    </div>
</div>

<!-- ===================== Modal: Đăng ký thêm buổi học (§4.6) ===================== -->
<?php if ($canEdit && !empty($classes)): ?>
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
                    <div class="mb-3">
                        <label class="lms-form-label" for="lmld-as-class">Lớp học</label>
                        <select class="form-select" id="lmld-as-class" name="class_id" required>
                            <option value="">— Chọn lớp học —</option>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= e((int) $c['id']) ?>"><?= e((string) $c['name'] . ' (' . $c['class_code'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php foreach ($classes as $c): ?>
                        <?php if (empty($classStudents[(int) $c['id']])) { continue; } ?>
                        <div class="mb-3 lmld-as-students d-none" data-class-id="<?= e((int) $c['id']) ?>">
                            <label class="lms-form-label">Học viên</label>
                            <select class="form-select" name="student_id" disabled>
                                <?php foreach ($classStudents[(int) $c['id']] as $s): ?>
                                    <option value="<?= e((int) $s['student_id']) ?>"><?= e((string) $s['full_name'] . ' (' . $s['student_code'] . ')') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    <div class="mb-3">
                        <label class="lms-form-label" for="lmld-as-date">Ngày</label>
                        <input type="date" class="form-control" id="lmld-as-date" name="session_date" min="<?= e(date('Y-m-d')) ?>" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="lms-form-label" for="lmld-as-start">Giờ bắt đầu</label>
                            <input type="time" class="form-control" id="lmld-as-start" name="start_time" required>
                        </div>
                        <div class="col-6">
                            <label class="lms-form-label" for="lmld-as-end">Giờ kết thúc</label>
                            <input type="time" class="form-control" id="lmld-as-end" name="end_time" required>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="lms-form-label" for="lmld-as-note">Ghi chú thêm</label>
                        <textarea class="form-control" id="lmld-as-note" name="note" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-lms">Gửi</button>
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
                <div class="modal-header">
                    <h2 class="modal-title">Chỉnh lịch</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="lms-form-label" for="lmld-edit-action">Hành động</label>
                        <select class="form-select" id="lmld-edit-action">
                            <option value="request_leave">Xin nghỉ</option>
                            <option value="change_schedule">Đổi lịch cố định</option>
                        </select>
                    </div>

                    <!-- Xin nghỉ (§4.5) -->
                    <div id="lmld-edit-leave">
                        <div class="mb-3">
                            <label class="lms-form-label">Buổi học xin nghỉ</label>
                            <?php foreach ($classes as $c): ?>
                                <?php $upcoming = lmld_upcoming_sessions((int) $c['id']); ?>
                                <?php if (empty($upcoming)) { continue; } ?>
                                <select class="form-select lmld-edit-sessions d-none" data-class-id="<?= e((int) $c['id']) ?>"
                                        name="session_id" disabled>
                                    <?php foreach ($upcoming as $s): ?>
                                        <option value="<?= e((int) $s['id']) ?>">
                                            <?= e((string) $s['session_date'] . ' ' . substr((string) $s['start_time'], 0, 5) . '–' . substr((string) $s['end_time'], 0, 5)) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endforeach; ?>
                            <small class="lms-text-muted-sm">Chọn buổi học trên ô lịch đỏ hoặc trong danh sách.</small>
                        </div>
                        <div class="mb-3">
                            <label class="lms-form-label" for="lmld-edit-reason">Lý do</label>
                            <textarea class="form-control" id="lmld-edit-reason" name="reason" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="lms-form-label" for="lmld-edit-makeup">Ngày bù (không bắt buộc)</label>
                            <input type="date" class="form-control" id="lmld-edit-makeup" name="makeup_date" min="<?= e(date('Y-m-d')) ?>">
                        </div>
                    </div>

                    <!-- Đổi lịch cố định (§4.5) -->
                    <div id="lmld-edit-change" class="d-none">
                        <p class="lms-text-muted-sm">Lịch mới chỉ áp dụng từ tuần kế tiếp trở đi.</p>
                        <?php for ($i = 0; $i < 2; $i++): ?>
                            <div class="lms-change-slot">
                                <strong>Buổi <?= $i === 0 ? 'thứ nhất' : 'thứ hai' ?></strong>
                                <div class="row g-2 mt-1">
                                    <div class="col-12">
                                        <label class="lms-form-label">Ngày</label>
                                        <input type="date" class="form-control" name="slot_date[]">
                                    </div>
                                    <div class="col-6">
                                        <label class="lms-form-label">Giờ bắt đầu</label>
                                        <input type="time" class="form-control" name="slot_start[]">
                                    </div>
                                    <div class="col-6">
                                        <label class="lms-form-label">Giờ kết thúc</label>
                                        <input type="time" class="form-control" name="slot_end[]">
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Huỷ</button>
                    <button type="submit" class="btn btn-lms">Gửi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function ($) {
    // Modal thêm buổi: đổi select HV theo lớp (disabled để ô ẩn không submit)
    $('#lmld-as-class').on('change', function () {
        var cid = $(this).val();
        $('.lmld-as-students').addClass('d-none').find('select').prop('disabled', true);
        var $box = $('.lmld-as-students[data-class-id="' + cid + '"]');
        $box.removeClass('d-none').find('select').prop('disabled', false);
    });

    // Ô lịch trống: mở modal thêm buổi
    $('#lmld-modal-add-session').on('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        if (btn && btn.dataset && btn.dataset.classId) {
            var cid = btn.dataset.classId;
            $('#lmld-as-class').val(cid).trigger('change');
        }
    });

    // Modal chỉnh lịch: điền session/class khi mở từ ô lịch
    $('#lmld-modal-edit').on('show.bs.modal', function (event) {
        var btn = event.relatedTarget;
        var sid = btn && btn.dataset ? btn.dataset.sessionId : '';
        var cid = btn && btn.dataset ? btn.dataset.classId : '';
        $('#lmld-edit-class').val(cid);
        $('.lmld-edit-sessions').addClass('d-none').prop('disabled', true);
        var $sel = $('.lmld-edit-sessions[data-class-id="' + cid + '"]');
        $sel.removeClass('d-none').prop('disabled', false);
        if (sid) {
            $sel.val(sid);
        }
    });

    // Đổi loại hành động trong modal chỉnh lịch
    $('#lmld-edit-action').on('change', function () {
        var type = $(this).val();
        $('#lmld-edit-type').val(type);
        if (type === 'request_leave') {
            $('#lmld-edit-leave').removeClass('d-none');
            $('#lmld-edit-change').addClass('d-none');
        } else {
            $('#lmld-edit-leave').addClass('d-none');
            $('#lmld-edit-change').removeClass('d-none');
        }
    });
})(jQuery);
</script>
<?php endif; ?>
