<?php
/**
 * Admin LMS — Quản lý yêu cầu: 5 tab (ADM-05).
 * Mockup: Tai-lieu-phat-trien/admin/Quản lý yêu cầu - *.png.
 *
 * Data do source chuẩn bị: $tab, $q, $phone, $items, $total, $totalPages,
 * $page, $canEdit, $canDelete, $alias.
 */
$typeLabels = array(
    'request_leave'   => 'Nghỉ 1 buổi',
    'change_schedule' => 'Lịch cố định',
);
$testLabels = \Lms\AdminRequestService::TEST_TYPES;

$fmtTime = function ($value) {
    $ts = strtotime((string) $value);
    if ($ts === false) {
        return '—';
    }
    $dow   = (int) date('N', $ts) + 1;
    $label = isset(\Lms\UserService::WEEKDAYS[$dow]) ? \Lms\UserService::WEEKDAYS[$dow] : '';
    return $label . ' ' . date('d-m-Y H:i', $ts);
};

$fmtDayDate = function ($date) {
    $ts = strtotime((string) $date);
    if ($ts === false) {
        return '—';
    }
    $dow   = (int) date('N', $ts) + 1;
    $label = isset(\Lms\UserService::WEEKDAYS[$dow]) ? str_replace('Thứ ', 'T', \Lms\UserService::WEEKDAYS[$dow]) : '';
    if ($dow === 8) {
        $label = 'CN';
    }
    return $label . ', ' . date('d/m/Y', $ts);
};

// '18:00' → '18h', '17:30' → '17h30' (khớp mockup: 17h30-19h)
$fmtHm = function ($time) {
    $time = substr((string) $time, 0, 5);
    if (substr($time, -3) === ':00') {
        return ((int) substr($time, 0, 2)) . 'h';
    }
    return ((int) substr($time, 0, 2)) . 'h' . substr($time, 3, 2);
};

$fmtSlot = function ($date, $start, $end) use ($fmtHm) {
    $ts = strtotime((string) $date);
    if ($ts === false) {
        return '—';
    }
    $dow = (int) date('N', $ts) + 1;
    $day = $dow === 8 ? 'CN' : 'T' . $dow;
    return $day . ', ' . $fmtHm($start) . ' - ' . $fmtHm($end);
};

$studied = function ($total, $remaining) {
    $total = (int) $total;
    $done  = $total - (int) $remaining;
    if ($done < 0) {
        $done = 0;
    }
    return $done . '/' . $total;
};

// Tiến độ chuyên đề: '4/4' hoặc '4' khi chuyên đề chưa đặt số buổi tối đa.
$topicProgress = function ($done, $max) {
    if ($done === null) {
        return '—';
    }
    return $max === null ? (string) (int) $done : (int) $done . '/' . (int) $max;
};

$paginationParams = array_filter(array(
    'tab'   => $tab,
    'q'     => $q,
    'phone' => $phone,
), function ($v) { return $v !== '' && $v !== null; });
?>

<?php lms_component('tabs', array(
    'aria_label' => 'Quản lý yêu cầu',
    'items'      => array(
        array('label' => 'Request thêm buổi', 'href' => lms_url($alias, '', array('tab' => 'them-buoi')), 'active' => $tab === 'them-buoi'),
        array('label' => 'Request test', 'href' => lms_url($alias, '', array('tab' => 'test')), 'active' => $tab === 'test'),
        array('label' => 'Duyệt KQ test', 'href' => lms_url($alias, '', array('tab' => 'duyet-kq')), 'active' => $tab === 'duyet-kq'),
        array('label' => 'Request đổi lịch', 'href' => lms_url($alias, '', array('tab' => 'doi-lich')), 'active' => $tab === 'doi-lich'),
        array('label' => 'Kiểm tra số buổi', 'href' => lms_url($alias, '', array('tab' => 'kiem-buoi')), 'active' => $tab === 'kiem-buoi'),
    ),
)); ?>

<!-- ===================== Bộ lọc ===================== -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="tab" value="<?= e($tab) ?>">
        <?php if ($tab === 'them-buoi'): ?>
            <div style="min-width: 220px;">
                <label for="fld-q" class="visually-hidden">Mã / Tên học viên</label>
                <?php lms_component('form/input', array(
                    'name'        => 'q',
                    'type'        => 'search',
                    'value'       => $q,
                    'placeholder' => 'Mã / Tên học viên',
                    'bare'        => true,
                    'class'       => 'form-control',
                )); ?>
            </div>
            <div style="min-width: 180px;">
                <label for="fld-phone" class="visually-hidden">Số điện thoại</label>
                <?php lms_component('form/input', array(
                    'name'        => 'phone',
                    'type'        => 'search',
                    'value'       => $phone,
                    'placeholder' => 'SĐT',
                    'bare'        => true,
                    'class'       => 'form-control',
                )); ?>
            </div>
        <?php else: ?>
            <div style="min-width: 240px;">
                <label for="fld-q" class="visually-hidden">Mã lớp, mã giáo viên</label>
                <?php lms_component('form/input', array(
                    'name'        => 'q',
                    'type'        => 'search',
                    'value'       => $q,
                    'placeholder' => 'Mã lớp, GV',
                    'bare'        => true,
                    'class'       => 'form-control',
                )); ?>
            </div>
        <?php endif; ?>
        <?php lms_component('button', array(
            'label'   => 'Tìm',
            'type'    => 'submit',
            'variant' => 'outline-secondary',
            'class'   => 'px-3',
        )); ?>
    </form>
    <?php if ($tab === 'kiem-buoi'): ?>
        <div class="fw-semibold text-dark">** ở đây chỉ hiện các lớp còn ít hơn 5 buổi</div>
    <?php endif; ?>
</div>

<!-- ===================== Bảng theo tab ===================== -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table">
        <thead class="table-light text-center">
            <?php if ($tab === 'them-buoi'): ?>
                <tr>
                    <th>Mã lớp</th>
                    <th>Mã GV</th>
                    <th style="min-width: 200px;">Chuyên đề</th>
                    <th>Số buổi đã học</th>
                    <th style="min-width: 180px;">Thời gian request</th>
                </tr>
            <?php elseif ($tab === 'test'): ?>
                <tr>
                    <th>Mã lớp</th>
                    <th>Mã GV</th>
                    <th>Loại test</th>
                    <th style="min-width: 200px;">Chuyên đề</th>
                    <th>Số buổi đã học</th>
                    <th style="min-width: 180px;">Thời gian request</th>
                    <th style="width: 110px;">Hành động</th>
                </tr>
            <?php elseif ($tab === 'duyet-kq'): ?>
                <tr>
                    <th>Mã lớp</th>
                    <th>Mã GV</th>
                    <th>Loại test</th>
                    <th style="min-width: 200px;">Chuyên đề</th>
                    <th style="min-width: 180px;">Thời gian nộp bài chấm</th>
                    <th style="width: 110px;">Hành động</th>
                </tr>
            <?php elseif ($tab === 'doi-lich'): ?>
                <tr>
                    <th>Mã lớp</th>
                    <th>Mã GV</th>
                    <th>Loại</th>
                    <th style="min-width: 190px;">Buổi cũ</th>
                    <th style="min-width: 190px;">Buổi mới / Buổi bù</th>
                    <th style="min-width: 180px;">Thời gian request</th>
                    <th style="width: 110px;">Hành động</th>
                </tr>
            <?php else: ?>
                <tr>
                    <th>Mã lớp</th>
                    <th>Mã GV</th>
                    <th>Số buổi đã học</th>
                    <th>Số buổi còn lại</th>
                    <th style="width: 150px;">Hành động</th>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?= $tab === 'them-buoi' ? 5 : ($tab === 'kiem-buoi' ? 5 : ($tab === 'duyet-kq' ? 6 : 7)) ?>"
                        class="text-center py-4 text-muted">
                        Chưa có dữ liệu phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $r): ?>
                    <?php if ($tab === 'them-buoi'): ?>
                        <tr>
                            <td class="text-center">
                                <a href="<?= e(lms_url($alias, 'xem', array('id' => (int) $r['id']))) ?>">
                                    <strong><?= e((string) $r['class_code']) ?></strong>
                                </a>
                            </td>
                            <td class="text-center"><?= e((string) ($r['teacher_code'] ?? '—')) ?></td>
                            <td class="text-wrap" style="max-width: 240px;">
                                <?= !empty($r['current_topic']) ? e((string) $r['current_topic']) : '—' ?>
                            </td>
                            <td class="text-center"><?= e($topicProgress($r['topic_done'], $r['topic_max'])) ?></td>
                            <td class="text-center"><?= e($fmtTime($r['created_at'])) ?></td>
                        </tr>
                    <?php elseif ($tab === 'test'): ?>
                        <tr>
                            <td class="text-center"><strong><?= e((string) $r['class_code']) ?></strong></td>
                            <td class="text-center"><?= e((string) ($r['teacher_code'] ?? '—')) ?></td>
                            <td class="text-center">
                                <?= e(isset($testLabels[$r['test_type']]) ? $testLabels[$r['test_type']] : (string) $r['test_type']) ?>
                            </td>
                            <td class="text-wrap" style="max-width: 240px;">
                                <?= !empty($r['topic_name']) ? e((string) $r['topic_name']) : '—' ?>
                            </td>
                            <td class="text-center"><?= e($topicProgress($r['topic_done'], $r['topic_max'])) ?></td>
                            <td class="text-center"><?= e($fmtTime($r['created_at'])) ?></td>
                            <td class="text-center">
                                <?php if ($canEdit): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'duyet-test')) ?>" class="d-block mb-1">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= e((string) (int) $r['id']) ?>">
                                        <button type="submit" class="lms-action-btn lms-action-link-success">Xác nhận</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'xoa-test')) ?>" class="d-block"
                                          onsubmit="return confirm('Xoá bài test này?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= e((string) (int) $r['id']) ?>">
                                        <button type="submit" class="lms-action-btn lms-action-link">Xoá</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php elseif ($tab === 'duyet-kq'): ?>
                        <tr>
                            <td class="text-center">
                                <strong><?= e((string) $r['class_code']) ?></strong>
                                <div class="text-muted" style="font-size:.8rem;">
                                    <?= e(trim((string) $r['student_code'] . ' - ' . (string) $r['student_name'], ' -')) ?>
                                </div>
                            </td>
                            <td class="text-center"><?= e((string) ($r['teacher_code'] ?? '—')) ?></td>
                            <td class="text-center">
                                <?= e(isset($testLabels[$r['test_type']]) ? $testLabels[$r['test_type']] : (string) $r['test_type']) ?>
                            </td>
                            <td class="text-wrap" style="max-width: 240px;">
                                <?= !empty($r['topic_name']) ? e((string) $r['topic_name']) : '—' ?>
                            </td>
                            <td class="text-center"><?= e($fmtTime($r['submitted_at'])) ?></td>
                            <td class="text-center">
                                <a href="<?= e(lms_url($alias, 'ket-qua', array(
                                    'class_id' => (int) $r['class_id'],
                                    'student_id' => (int) $r['student_id'],
                                    'topic_id' => (int) $r['topic_id'],
                                ))) ?>" class="d-block mb-1 lms-action-link-edit">Chi tiết</a>
                                <?php if ($canEdit): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'xac-nhan-kq')) ?>" class="d-block mb-1">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="submission_id" value="<?= e((string) (int) $r['submission_id']) ?>">
                                        <input type="hidden" name="class_id" value="<?= e((string) (int) $r['class_id']) ?>">
                                        <input type="hidden" name="student_id" value="<?= e((string) (int) $r['student_id']) ?>">
                                        <input type="hidden" name="topic_id" value="<?= e((string) (int) $r['topic_id']) ?>">
                                        <button type="submit" class="lms-action-btn lms-action-link-success">Xác nhận</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'xoa-kq')) ?>" class="d-block"
                                          onsubmit="return confirm('Xoá bài nộp này?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="id" value="<?= e((string) (int) $r['submission_id']) ?>">
                                        <button type="submit" class="lms-action-btn lms-action-link">Xoá</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php elseif ($tab === 'doi-lich'): ?>
                        <?php
                        $payload = isset($r['payload']) && is_array($r['payload']) ? $r['payload'] : array();
                        $isLeave = (string) $r['request_type'] === 'request_leave';

                        if ($isLeave) {
                            $oldText = !empty($r['session_date'])
                                ? $fmtDayDate($r['session_date'])
                                : (!empty($payload['session_date']) ? $fmtDayDate($payload['session_date']) : '—');
                            $newText = !empty($payload['makeup_date']) ? $fmtDayDate($payload['makeup_date']) : '-';
                        } else {
                            $oldText = !empty($r['old_schedule']) ? (string) $r['old_schedule'] : '—';
                            $oldText = preg_replace_callback('/(\d{2}):(\d{2})-(\d{2}):(\d{2})/', function ($m) {
                                $from = (int) $m[1] . 'h' . ($m[2] === '00' ? '' : $m[2]);
                                $to   = (int) $m[3] . 'h' . ($m[4] === '00' ? '' : $m[4]);
                                return $from . ' - ' . $to;
                            }, $oldText);
                            $oldText = preg_replace('/(T\d|CN) (\d)/', '$1, $2', $oldText);
                            $oldText = str_replace(' / ', "\n", $oldText);
                            $slots   = isset($payload['slots']) && is_array($payload['slots']) ? $payload['slots'] : array();
                            $parts   = array();
                            foreach ($slots as $slot) {
                                if (!is_array($slot)) {
                                    continue;
                                }
                                $parts[] = $fmtSlot(
                                    isset($slot['date']) ? $slot['date'] : '',
                                    isset($slot['start']) ? $slot['start'] : '',
                                    isset($slot['end']) ? $slot['end'] : ''
                                );
                            }
                            $newText = !empty($parts) ? implode("\n", $parts) : '—';
                        }
                        ?>
                        <tr>
                            <td class="text-center"><strong><?= e((string) $r['class_code']) ?></strong></td>
                            <td class="text-center"><?= e((string) ($r['teacher_code'] ?? '—')) ?></td>
                            <td class="text-center"><?= e(isset($typeLabels[$r['request_type']]) ? $typeLabels[$r['request_type']] : (string) $r['request_type']) ?></td>
                            <td class="text-center text-wrap" style="max-width: 200px;"><?= nl2br(e($oldText)) ?></td>
                            <td class="text-center text-wrap" style="max-width: 200px;"><?= nl2br(e($newText)) ?></td>
                            <td class="text-center"><?= e($fmtTime($r['created_at'])) ?></td>
                            <td class="text-center">
                                <?php if ($canEdit): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'duyet')) ?>" class="d-block mb-1">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="return_tab" value="doi-lich">
                                        <input type="hidden" name="id" value="<?= e((string) (int) $r['id']) ?>">
                                        <button type="submit" class="lms-action-btn lms-action-link-success">Xác nhận</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <form method="post" action="<?= e(lms_url($alias, 'xoa-yeu-cau')) ?>" class="d-block"
                                          onsubmit="return confirm('Xoá yêu cầu này?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                        <input type="hidden" name="tab" value="doi-lich">
                                        <input type="hidden" name="id" value="<?= e((string) (int) $r['id']) ?>">
                                        <button type="submit" class="lms-action-btn lms-action-link">Xoá</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td class="text-center"><strong><?= e((string) $r['class_code']) ?></strong></td>
                            <td class="text-center"><?= e((string) ($r['teacher_code'] ?? '—')) ?></td>
                            <td class="text-center"><?= e($studied($r['total_sessions'], $r['remaining_sessions'])) ?></td>
                            <td class="text-center"><?= e((string) max(0, (int) $r['remaining_sessions'])) ?></td>
                            <td class="text-center">
                                <?php if ($canEdit): ?>
                                    <a href="#"
                                       class="d-block mb-1 lms-action-link-edit js-open-add-course-cycle"
                                       data-bs-toggle="modal"
                                       data-bs-target="#lmsAddCourseCycleModal"
                                       data-class-id="<?= (int) $r['id'] ?>"
                                       data-remaining="<?= max(0, (int) $r['remaining_sessions']) ?>">Thêm khoá học</a>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <a href="<?= e(lms_url('lms-lop', 'xoa', array('id' => (int) $r['id']))) ?>"
                                       class="d-block lms-action-link lms-confirm-delete"
                                       data-confirm="Xoá lớp học này?">Xoá</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php lms_component('pagination', array(
    'base_url'    => lms_url($alias),
    'page'        => $page,
    'total_pages' => $totalPages,
    'params'      => $paginationParams,
)); ?>

<?php if ($tab === 'kiem-buoi' && $canEdit): ?>
<div class="modal fade" id="lmsAddCourseCycleModal" tabindex="-1" aria-labelledby="lmsAddCourseCycleLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold fs-5" id="lmsAddCourseCycleLabel">Thêm khóa học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form method="post" action="<?= e(lms_url('lms-lop', 'them-khoa-hoc')) ?>">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                    <input type="hidden" name="class_id" id="lms-add-cycle-class-id" value="">
                    <input type="hidden" name="redirect_to" value="lms-yeu-cau">

                    <?php lms_component('form/input', array(
                        'name'  => 'current_remaining_sessions',
                        'id'    => 'lms-add-cycle-current-remaining',
                        'label' => 'Số buổi còn lại của khóa học hiện tại',
                        'type'  => 'number',
                        'value' => '0',
                        'attrs' => array('min' => 0, 'max' => 10000, 'required' => true),
                    )); ?>

                    <?php lms_component('form/input', array(
                        'name'  => 'new_total_sessions',
                        'id'    => 'lms-add-cycle-new-total',
                        'label' => 'Số buổi của khóa mới',
                        'type'  => 'number',
                        'value' => '',
                        'attrs' => array('min' => 1, 'max' => 10000, 'required' => true),
                    )); ?>

                    <div class="d-flex justify-content-end mt-4">
                        <?php lms_component('button', array(
                            'label'   => 'Cập nhật',
                            'type'    => 'submit',
                            'variant' => 'primary',
                            'size'    => '',
                            'class'   => 'px-4',
                        )); ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.querySelectorAll('.lms-confirm-delete').forEach(function (el) {
    el.addEventListener('click', function (ev) {
        var msg = el.getAttribute('data-confirm') || 'Bạn có chắc muốn xoá?';
        if (!window.confirm(msg)) {
            ev.preventDefault();
        }
    });
});
if (window.jQuery) {
    jQuery(function ($) {
        $(document).on('click', '.js-open-add-course-cycle', function () {
            var cid = $(this).data('class-id') || '';
            var rem = $(this).data('remaining');
            $('#lms-add-cycle-class-id').val(cid);
            $('#lms-add-cycle-current-remaining').val(rem !== undefined ? rem : 0);
            $('#lms-add-cycle-new-total').val('');
        });
        $('#lmsAddCourseCycleModal').on('shown.bs.modal', function () {
            $('#lms-add-cycle-new-total').trigger('focus');
        });
    });
} else {
    document.querySelectorAll('.js-open-add-course-cycle').forEach(function (el) {
        el.addEventListener('click', function () {
            var cid = el.getAttribute('data-class-id') || '';
            var rem = el.getAttribute('data-remaining');
            var cidInput = document.getElementById('lms-add-cycle-class-id');
            var remInput = document.getElementById('lms-add-cycle-current-remaining');
            var newInput = document.getElementById('lms-add-cycle-new-total');
            if (cidInput) cidInput.value = cid;
            if (remInput) remInput.value = rem !== null ? rem : '0';
            if (newInput) newInput.value = '';
        });
    });
}
</script>
