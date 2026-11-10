<?php
/**
 * Admin LMS — Quản lý sắp xếp lớp (ADM-04).
 *
 * Data do source chuẩn bị: $filters, $schedRows, $options, $items, $total,
 * $detail, $weeklyNote, $weekStart, $queryParams, $hiddenCount, $canEdit,
 * $autoError, $studentInfo, $autofillFor, $alias.
 */
$returnQuery = http_build_query($queryParams);
$listUrl = lms_url($alias, '', $queryParams);
?>
<div>
    <form id="xepLopFilter" method="get" action="<?= e(lms_url($alias)) ?>">
        <input type="hidden" name="student_filled" id="xepStudentFilled" value="<?= e($autofillFor) ?>">
        <!-- Block 1: Tìm kiếm theo tiêu chí học viên -->
        <div class="lms-card mb-3">
            <div class="lms-card-body">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input xep-block-toggle" type="checkbox" role="switch"
                               id="xepUseCriteria" name="use_criteria" value="1" data-block="criteria"
                               <?= $filters['use_criteria'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="xepUseCriteria">Tìm kiếm theo tiêu chí</label>
                    </div>
                    <small class="text-muted fst-italic">*có tìm theo tiêu chí này không</small>
                </div>

                <div class="xep-block-body row g-2 align-items-center mt-2<?= $filters['use_criteria'] ? '' : ' d-none' ?>"
                     data-block="criteria">
                    <div class="col-12 col-xl-3">
                        <?php lms_component('form/input', array(
                            'name'        => 'student_username',
                            'id'          => 'xepStudentUsername',
                            'value'       => $filters['student_username'],
                            'placeholder' => 'Username học viên',
                            'class'       => 'lms-tf-input',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-2">
                        <?php lms_component('form/select', array(
                            'name'        => 's_gender',
                            'options'     => $options['genders'],
                            'value'       => $filters['s_gender'],
                            'empty_label' => 'Giới tính',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-2">
                        <?php lms_component('form/select', array(
                            'name'        => 's_subject_id',
                            'options'     => $options['subjects'],
                            'value'       => $filters['s_subject_id'] > 0 ? (string) $filters['s_subject_id'] : '',
                            'empty_label' => 'Chương trình học',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-12 col-xl-3">
                        <?php lms_component('form/multi_select_tags', array(
                            'name'        => 's_districts[]',
                            'selected'    => $filters['s_districts'],
                            'groups'      => array('Quận / Phường' => $options['districts']),
                            'placeholder' => 'Quận / Phường',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-1">
                        <?php lms_component('form/select', array(
                            'name'        => 's_learning_type',
                            'options'     => $options['learning_types'],
                            'value'       => $filters['s_learning_type'],
                            'empty_label' => 'Online / Offline',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-1 text-end">
                        <?php lms_component('button', array(
                            'label'   => 'Tìm',
                            'type'    => 'submit',
                            'variant' => 'primary',
                            'size'    => 'md',
                        )); ?>
                    </div>
                </div>

                <div id="xepStudentHint"
                     class="small mt-1 <?= $autoError !== '' ? 'text-danger' : ($studentInfo !== array() ? 'text-success' : 'text-muted') ?>">
                    <?php if ($autoError !== ''): ?>
                        <?= e($autoError) ?>
                    <?php elseif ($studentInfo !== array()): ?>
                        Đã điền tiêu chí theo học viên <?= e((string) $studentInfo['student_code']) ?>
                        - <?= e((string) $studentInfo['full_name']) ?>.
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Block 2: Tìm theo lịch trống -->
        <div class="lms-card mb-3">
            <div class="lms-card-body">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input xep-block-toggle" type="checkbox" role="switch"
                               id="xepUseSchedule" name="use_schedule" value="1" data-block="schedule"
                               <?= $filters['use_schedule'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="xepUseSchedule">Tìm theo lịch trống</label>
                    </div>
                    <i class="fa-regular fa-circle-question text-muted" style="cursor: help;"
                       title="Nếu xoá hết khung giờ, mặc định tìm theo tất cả các khung giờ của ngày đó"
                       aria-label="Nếu xoá hết khung giờ, mặc định tìm theo tất cả các khung giờ của ngày đó"></i>
                </div>

                <div class="xep-block-body mt-2<?= $filters['use_schedule'] ? '' : ' d-none' ?>"
                     data-block="schedule">
                    <?php lms_component('form/schedule_slots', array(
                        'id'            => 'xepSchedSlots',
                        'rows'          => $schedRows,
                        'day_name'      => 'sched_day',
                        'start_name'    => 'sched_from',
                        'end_name'      => 'sched_to',
                        'default_start' => '',
                        'default_end'   => '',
                        'new_start'     => '08:30',
                        'new_end'       => '10:00',
                    )); ?>

                    <div class="d-flex justify-content-end mt-2">
                        <?php lms_component('button', array(
                            'label'   => 'Tìm',
                            'type'    => 'submit',
                            'variant' => 'primary',
                            'size'    => 'md',
                        )); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Block 3: Tiêu chí giáo viên + sắp xếp -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mt-4 mb-2">
            <h5 class="mb-0">Các giáo viên phù hợp</h5>
            <span class="lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?></span>
        </div>

        <div class="lms-card">
            <div class="lms-card-body">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-xl-3">
                        <?php lms_component('form/input', array(
                            'name'        => 'g_q',
                            'value'       => $filters['g_q'],
                            'placeholder' => 'Họ tên, username',
                            'class'       => 'lms-tf-input',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-2">
                        <?php lms_component('form/select', array(
                            'name'        => 'g_gender',
                            'options'     => $options['genders'],
                            'value'       => $filters['g_gender'],
                            'empty_label' => 'Giới tính',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-2">
                        <?php lms_component('form/select', array(
                            'name'        => 'g_subject_id',
                            'options'     => $options['subjects'],
                            'value'       => $filters['g_subject_id'] > 0 ? (string) $filters['g_subject_id'] : '',
                            'empty_label' => 'Chương trình dạy',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-12 col-xl-3">
                        <?php lms_component('form/multi_select_tags', array(
                            'name'        => 'g_districts[]',
                            'selected'    => $filters['g_districts'],
                            'groups'      => array('Quận / Phường' => $options['districts']),
                            'placeholder' => 'Quận / Phường có thể dạy',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-1">
                        <?php lms_component('form/select', array(
                            'name'        => 'g_status',
                            'options'     => $options['statuses'],
                            'value'       => $filters['g_status'],
                            'empty_label' => 'Trạng thái',
                            'bare'        => true,
                        )); ?>
                    </div>
                    <div class="col-6 col-xl-1 text-end">
                        <?php lms_component('button', array(
                            'label'   => 'Tìm',
                            'type'    => 'submit',
                            'variant' => 'primary',
                            'size'    => 'md',
                        )); ?>
                    </div>
                </div>
                <div class="small text-muted fst-italic mt-1">
                    * Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác (được thêm)
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                    <label class="small text-muted mb-0" for="xepSort">Sắp xếp</label>
                    <select id="xepSort" name="sort" class="lms-tf-select" style="max-width: 320px;">
                        <option value="rank_desc"<?= $filters['sort'] === 'rank_desc' ? ' selected' : '' ?>>Sắp xếp theo hạng giảm dần</option>
                        <option value="rank_asc"<?= $filters['sort'] === 'rank_asc' ? ' selected' : '' ?>>Sắp xếp theo hạng tăng dần</option>
                        <option value="updated_desc"<?= $filters['sort'] === 'updated_desc' ? ' selected' : '' ?>>Sắp xếp theo cập nhật gần nhất</option>
                        <option value="updated_asc"<?= $filters['sort'] === 'updated_asc' ? ' selected' : '' ?>>Sắp xếp theo cập nhật xa nhất</option>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <?php if ($hiddenCount > 0): ?>
        <div class="text-end mt-2">
            <form method="post" action="<?= e(lms_url($alias, 'bo-an-giao-vien')) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                <?php lms_component('button', array(
                    'label'   => 'Hiện lại ' . (int) $hiddenCount . ' giáo viên đã ẩn',
                    'type'    => 'submit',
                    'variant' => 'outline-secondary',
                    'size'    => 'sm',
                )); ?>
            </form>
        </div>
    <?php endif; ?>

    <div class="lms-card mt-3">
        <div class="lms-card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table lms-table-pin">
                    <thead class="table-light text-center">
                        <tr>
                            <th data-pin-left>Username</th>
                            <th data-pin-left>Họ tên</th>
                            <th>Giới tính</th>
                            <th>Hạng</th>
                            <th>Chương trình dạy</th>
                            <th>Quận / Phường có thể dạy</th>
                            <th>Số lớp đang dạy</th>
                            <th>Trạng thái</th>
                            <th>Lần cuối cập nhật</th>
                            <th style="width: 130px;" data-pin-right>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    Không tìm thấy giáo viên phù hợp với tiêu chí đã chọn.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $row): ?>
                                <?php
                                $teacherId = (int) $row['teacher_id'];
                                $detailUrl = lms_url($alias, '', array_merge($queryParams, array('teacher_id' => $teacherId)));
                                $statusLabel = isset($options['statuses'][$row['status']])
                                    ? $options['statuses'][$row['status']]
                                    : $row['status'];
                                $statusClass = $row['status'] === 'active' ? 'text-success' : 'text-muted';
                                $genderLabel = isset($options['genders'][$row['gender']])
                                    ? $options['genders'][$row['gender']]
                                    : $row['gender'];
                                ?>
                                <tr>
                                    <td title="Mã GV: <?= e((string) $row['teacher_code']) ?>" data-pin-left>
                                        <?= e($row['username'] !== '' ? $row['username'] : $row['teacher_code']) ?>
                                    </td>
                                    <td data-pin-left><?= e((string) $row['full_name']) ?></td>
                                    <td><?= e($genderLabel) ?></td>
                                    <td>
                                        <?php lms_component('badge', array(
                                            'label'   => lms_xep_lop_rank_label($row['rank']),
                                            'variant' => lms_xep_lop_rank_variant($row['rank']),
                                            'type'    => 'lms',
                                        )); ?>
                                    </td>
                                    <td>
                                        <?= $row['subjects'] !== array()
                                            ? e(implode(', ', $row['subjects']))
                                            : '<span class="text-muted">Tất cả</span>' ?>
                                    </td>
                                    <td>
                                        <?= $row['districts'] !== array()
                                            ? e(implode(' / ', $row['districts']))
                                            : '<span class="text-muted">Tất cả</span>' ?>
                                    </td>
                                    <td class="text-center"><?= e((string) $row['current_classes']) ?></td>
                                    <td><span class="<?= e($statusClass) ?>"><?= e($statusLabel) ?></span></td>
                                    <td class="text-nowrap"><?= e(lms_xep_lop_format_updated($row['updated_at'])) ?></td>
                                    <td class="text-center text-nowrap" data-pin-right>
                                        <a href="<?= e($detailUrl) ?>" class="lms-action-link-edit">Chi tiết</a>
                                        <span class="text-muted">|</span>
                                        <form method="post" action="<?= e(lms_url($alias, 'an-giao-vien')) ?>" class="d-inline"
                                              onsubmit="return confirm('Ẩn giáo viên này khỏi danh sách gợi ý?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                            <input type="hidden" name="teacher_id" value="<?= e((string) $teacherId) ?>">
                                            <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                                            <button type="submit" class="lms-action-btn lms-action-link">Xoá</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($detail !== array()): ?>
    <?php
    $teacher = $detail['teacher'];
    $days = $detail['days'];
    $schedules = $detail['schedules'];
    $freeSlots = $detail['free_slots'];
    $hourHeight = 36;

    $busyByDay = array();
    foreach ($schedules as $schedule) {
        $busyByDay[(int) $schedule['day_of_week']][] = $schedule;
    }
    $freeByDay = array();
    foreach ($freeSlots as $slot) {
        $freeByDay[(int) $slot['day_of_week']][] = $slot;
    }

    $startMinutes = function ($time) {
        $time = substr((string) $time, 0, 5);
        return ((int) substr($time, 0, 2)) * 60 + (int) substr($time, 3, 2);
    };
    $blockTop = function ($time) use ($hourHeight, $startMinutes) {
        return max(0, (int) round((($startMinutes($time) - 60) / 60) * $hourHeight));
    };
    $blockHeight = function ($start, $end) use ($hourHeight, $startMinutes) {
        $duration = max(0, $startMinutes($end) - $startMinutes($start));
        return max(22, (int) round(($duration / 60) * $hourHeight) - 2);
    };

    $closeUrl = $listUrl;
    $teacherDistricts = isset($teacher['teachable_districts']) && is_array($teacher['teachable_districts'])
        ? $teacher['teachable_districts']
        : array();
    ?>
    <div class="modal fade" id="xepLopDetailModal" tabindex="-1" aria-labelledby="xepLopDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="xepLopDetailLabel" style="color:#d97706;font-weight:700;">
                        Chi tiết lịch giáo viên - <?= e((string) $teacher['teacher_code']) ?> -
                        <?= e((string) $teacher['full_name']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3" style="font-size: .95rem;">
                        <div>Địa chỉ hiện tại:
                            <strong><?= e(!empty($teacher['current_address']) ? (string) $teacher['current_address'] : '—') ?></strong>
                        </div>
                        <div>Quận / Phường có thể dạy:
                            <strong><?= $teacherDistricts !== array() ? e(implode(' / ', $teacherDistricts)) : 'Tất cả' ?></strong>
                        </div>
                        <div>Số lớp đang dạy:
                            <strong><?= e((string) (int) $teacher['current_classes']) ?></strong>
                        </div>
                        <div>Số lớp tối đa có thể nhận:
                            <strong><?= (int) $teacher['max_classes'] > 0 ? e((string) (int) $teacher['max_classes']) : 'Không giới hạn' ?></strong>
                        </div>
                        <div>Có thể dạy online không:
                            <strong><?= !empty($teacher['can_teach_online']) ? 'Có' : 'Không' ?></strong>
                        </div>
                    </div>

                    <div class="lms-week-timeline lms-wt-tall">
                        <div class="lms-wt-corner">Giờ</div>
                        <?php foreach ($days as $dayLabel): ?>
                            <div class="lms-wt-day-head"><?= e($dayLabel) ?></div>
                        <?php endforeach; ?>

                        <div class="lms-wt-gutter">
                            <?php for ($hour = 1; $hour <= 24; $hour++): ?>
                                <div class="lms-wt-hour" style="height: <?= (int) $hourHeight ?>px"><?= e((string) $hour) ?>:00</div>
                            <?php endfor; ?>
                        </div>

                        <?php foreach ($days as $dayNumber => $dayLabel): ?>
                            <div class="lms-wt-day">
                                <div class="lms-wt-day-inner" style="height: <?= (int) ($hourHeight * 24) ?>px">
                                    <?php if (!empty($busyByDay[$dayNumber])): ?>
                                        <?php foreach ($busyByDay[$dayNumber] as $schedule): ?>
                                            <?php
                                            $start = substr((string) $schedule['start_time'], 0, 5);
                                            $end = substr((string) $schedule['end_time'], 0, 5);
                                            ?>
                                            <div class="lms-wt-card lms-wt-card-busy lms-wt-card-abs"
                                                 style="top: <?= $blockTop($start) ?>px; height: <?= $blockHeight($start, $end) ?>px;"
                                                 title="<?= e($start . ' - ' . $end) ?>">
                                                <div class="lms-wt-card-code">
                                                    <?php if (!empty($schedule['students'])): ?>
                                                        <?php foreach ($schedule['students'] as $student): ?>
                                                            <a class="lms-wt-student-link"
                                                               href="<?= e(lms_url('lms-tai-khoan', 'sua-hoc-vien', array('id' => (int) $student['user_id']))) ?>">
                                                                <?= e((string) $student['student_code']) ?> -
                                                                <?= e((string) $student['full_name']) ?>
                                                            </a>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <?= e((string) (!empty($schedule['class_name']) ? $schedule['class_name'] : $schedule['class_code'])) ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="lms-wt-card-time"><?= e($start) ?> - <?= e($end) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <?php if (!empty($freeByDay[$dayNumber])): ?>
                                        <?php foreach ($freeByDay[$dayNumber] as $slot): ?>
                                            <?php
                                            $start = substr((string) $slot['start_time'], 0, 5);
                                            $end = substr((string) $slot['end_time'], 0, 5);
                                            ?>
                                            <div class="lms-wt-card lms-wt-card-free lms-wt-card-abs"
                                                 style="top: <?= $blockTop($start) ?>px; height: <?= $blockHeight($start, $end) ?>px;"
                                                 title="Lịch trống <?= e($start . ' - ' . $end) ?>"
                                                 aria-label="Lịch trống <?= e($start . ' - ' . $end) ?>"></div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row g-3 mt-1 align-items-start">
                        <div class="col-md-5">
                            <div class="lms-wt-legend mt-0">
                                <div class="lms-wt-legend-item">
                                    <span class="lms-wt-legend-box lms-wt-legend-busy"></span>
                                    <span>Lịch cố định của lớp đang dạy</span>
                                </div>
                                <div class="lms-wt-legend-item">
                                    <span class="lms-wt-legend-box lms-wt-legend-free"></span>
                                    <span>Lịch trống có thể nhận thêm lớp</span>
                                </div>
                            </div>
                            <div class="small fst-italic mt-2">
                                *Bấm vào tên học viên sẽ nhảy qua trang chi tiết của học viên
                            </div>
                        </div>
                        <div class="col-md-7">
                            <?php if (!empty($canEdit)): ?>
                                <form method="post" action="<?= e(lms_url($alias, 'cap-nhat-ghi-chu')) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                    <input type="hidden" name="teacher_id" value="<?= e((string) (int) $teacher['user_id']) ?>">
                                    <input type="hidden" name="week_start" value="<?= e($weekStart) ?>">
                                    <input type="hidden" name="return_query" value="<?= e($returnQuery) ?>">
                                    <?php lms_component('form/textarea', array(
                                        'name'        => 'note',
                                        'value'       => isset($weeklyNote['note']) ? (string) $weeklyNote['note'] : '',
                                        'rows'        => 4,
                                        'placeholder' => 'Ghi chú...',
                                        'bare'        => true,
                                    )); ?>
                                    <div class="text-center mt-3">
                                        <?php lms_component('button', array(
                                            'label'   => 'Cập nhật',
                                            'type'    => 'submit',
                                            'variant' => 'primary',
                                            'size'    => 'md',
                                        )); ?>
                                    </div>
                                </form>
                            <?php else: ?>
                                <?php lms_component('form/textarea', array(
                                    'name'     => 'note',
                                    'value'    => isset($weeklyNote['note']) ? (string) $weeklyNote['note'] : '',
                                    'rows'     => 4,
                                    'readonly' => true,
                                    'bare'     => true,
                                )); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
(function () {
    var studentUrl = <?= json_encode(lms_url($alias, 'ajax-hoc-vien'), JSON_UNESCAPED_UNICODE) ?>;

    document.querySelectorAll('.xep-block-toggle').forEach(function (toggle) {
        var body = document.querySelector('.xep-block-body[data-block="' + toggle.dataset.block + '"]');
        if (!body) {
            return;
        }
        function sync() {
            body.classList.toggle('d-none', !toggle.checked);
        }
        toggle.addEventListener('change', sync);
        sync();
    });

    var sortSelect = document.getElementById('xepSort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function () {
            var form = document.getElementById('xepLopFilter');
            if (form) {
                form.submit();
            }
        });
    }

    var usernameInput = document.getElementById('xepStudentUsername');
    var studentHint = document.getElementById('xepStudentHint');
    var studentFilled = document.getElementById('xepStudentFilled');
    var lastFilledUsername = null;

    function fillStudentCriteria() {
        if (!usernameInput) {
            return;
        }
        var username = usernameInput.value.trim();
        if (username === '' || username === lastFilledUsername) {
            return;
        }

        var url = studentUrl + (studentUrl.indexOf('?') === -1 ? '?' : '&')
            + 'username=' + encodeURIComponent(username);

        fetch(url, {headers: {'Accept': 'application/json'}})
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (!data.ok) {
                    if (studentHint) {
                        studentHint.className = 'small mt-1 text-danger';
                        studentHint.textContent = data.error || 'Không tìm thấy học viên.';
                    }
                    return;
                }

                lastFilledUsername = username;
                var student = data.student;
                setSelectValue('s_gender', student.gender);
                setSelectValue('s_subject_id', student.subject_id ? String(student.subject_id) : '');
                setSelectValue('s_learning_type', student.learning_type);
                setDistrictValues(student.districts);
                if (studentFilled) {
                    studentFilled.value = username;
                }

                if (studentHint) {
                    studentHint.className = 'small mt-1 text-success';
                    studentHint.textContent = 'Đã điền tiêu chí theo học viên ' + student.student_code
                        + ' - ' + student.full_name + '.';
                }
            })
            .catch(function () {
                if (studentHint) {
                    studentHint.className = 'small mt-1 text-danger';
                    studentHint.textContent = 'Không thể tải thông tin học viên.';
                }
            });
    }

    function setSelectValue(name, value) {
        var select = document.querySelector('[name="' + name + '"]');
        if (select) {
            select.value = value === null || value === undefined ? '' : String(value);
        }
    }

    function setDistrictValues(values) {
        var wanted = Array.isArray(values) ? values : [];
        document.querySelectorAll('input[name="s_districts[]"]').forEach(function (checkbox) {
            var checked = wanted.indexOf(checkbox.value) !== -1;
            if (checkbox.checked !== checked) {
                checkbox.checked = checked;
                checkbox.dispatchEvent(new Event('change', {bubbles: true}));
            }
        });
    }

    if (usernameInput) {
        usernameInput.addEventListener('blur', fillStudentCriteria);
        usernameInput.addEventListener('change', fillStudentCriteria);
    }

    var modalElement = document.getElementById('xepLopDetailModal');
    if (modalElement) {
        var closeUrl = <?= json_encode($detail !== array() ? $closeUrl : lms_url($alias, '', $queryParams), JSON_UNESCAPED_UNICODE) ?>;
        document.addEventListener('DOMContentLoaded', function () {
            if (!window.bootstrap || !bootstrap.Modal) {
                return;
            }
            var detailModal = new bootstrap.Modal(modalElement);
            modalElement.addEventListener('hidden.bs.modal', function () {
                window.location.href = closeUrl;
            });
            detailModal.show();
        });
    }
})();
</script>
