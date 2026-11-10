<?php
/**
 * Admin LMS — Quản lý yêu cầu: chi tiết + duyệt/từ chối (ADM-05).
 * Chuẩn hoá giao diện theo Kiến trúc Form Component MasterStudy admin-ver2.
 * Data: $row (payload đã decode, can_approve), $canEdit, $alias, $title.
 * PHP 7.3 compatible.
 */

$typeLabels   = \Lms\RequestService::ALL_REQUEST_TYPES;
$statusLabels = \Lms\RequestService::REQUEST_STATUSES;

$rtype   = (string) (isset($row['request_type']) ? $row['request_type'] : '');
$rstt    = (string) (isset($row['status']) ? $row['status'] : '');
$payload = isset($row['payload']) && is_array($row['payload']) ? $row['payload'] : array();
$isPending = ($rstt === 'pending');

// Xác định tab tương ứng khi quay lại danh sách
$returnTab = 'them-buoi';
if ($rtype === 'send_test') {
    $returnTab = 'test';
} elseif ($rtype === 'request_leave' || $rtype === 'change_schedule') {
    $returnTab = 'doi-lich';
}
$backUrl = lms_url($alias, '', array('tab' => $returnTab));
?>

<!-- Khối form component chuẩn MasterStudy -->
<div class="row">
    <div class="col-lg-8">

        <!-- 1. Thông tin chung -->
        <div class="mb-4">
            <h5 class="fw-bold mb-3" style="color:#e5501b;">
                <i class="fa-solid fa-circle-info me-2"></i>Thông tin chung
            </h5>

            <?php lms_component('form/input', array(
                'label'    => 'Mã yêu cầu',
                'name'     => 'view_req_id',
                'value'    => '#' . (int) $row['id'],
                'readonly' => true,
            )); ?>

            <?php lms_component('form/input', array(
                'label'    => 'Loại yêu cầu',
                'name'     => 'view_req_type',
                'value'    => isset($typeLabels[$rtype]) ? $typeLabels[$rtype] : $rtype,
                'readonly' => true,
            )); ?>

            <?php lms_component('form/input', array(
                'label'    => 'Trạng thái',
                'name'     => 'view_req_status',
                'value'    => isset($statusLabels[$rstt]) ? $statusLabels[$rstt] : $rstt,
                'readonly' => true,
            )); ?>

            <?php lms_component('form/input', array(
                'label'    => 'Tiêu đề yêu cầu',
                'name'     => 'view_title',
                'value'    => (string) (isset($row['title']) ? $row['title'] : ''),
                'readonly' => true,
            )); ?>

            <?php lms_component('form/input', array(
                'label'    => 'Lớp học',
                'name'     => 'view_class',
                'value'    => !empty($row['class_code'])
                    ? ($row['class_code'] . (!empty($row['class_name']) ? ' — ' . $row['class_name'] : ''))
                    : '—',
                'readonly' => true,
            )); ?>

            <?php lms_component('form/input', array(
                'label'    => 'Giáo viên',
                'name'     => 'view_teacher',
                'value'    => !empty($row['teacher_code'])
                    ? ($row['teacher_code'] . (!empty($row['teacher_name']) ? ' — ' . $row['teacher_name'] : ''))
                    : '—',
                'readonly' => true,
            )); ?>

            <?php if (!empty($row['student_name']) || !empty($row['student_code'])): ?>
                <?php lms_component('form/input', array(
                    'label'    => 'Học viên',
                    'name'     => 'view_student',
                    'value'    => (isset($row['student_name']) ? $row['student_name'] : '') . (!empty($row['student_code']) ? ' (' . $row['student_code'] . ')' : ''),
                    'readonly' => true,
                )); ?>
            <?php endif; ?>

            <?php lms_component('form/input', array(
                'label'    => 'Thời gian gửi',
                'name'     => 'view_created_at',
                'value'    => lms_format_datetime(isset($row['created_at']) ? $row['created_at'] : ''),
                'readonly' => true,
            )); ?>
        </div>

        <!-- 2. Chi tiết nội dung yêu cầu -->
        <div class="mb-4">
            <h5 class="fw-bold mb-3" style="color:#e5501b;">
                <i class="fa-regular fa-calendar-check me-2"></i>Nội dung chi tiết yêu cầu
            </h5>

            <?php if ($rtype === 'add_session'): ?>
                <?php lms_component('form/input', array(
                    'label'    => 'Ngày học xin thêm',
                    'name'     => 'view_session_date',
                    'value'    => lms_format_date(isset($payload['session_date']) ? $payload['session_date'] : ''),
                    'readonly' => true,
                )); ?>

                <?php
                $startT  = substr((string) (isset($payload['start_time']) ? $payload['start_time'] : ''), 0, 5);
                $endT    = substr((string) (isset($payload['end_time']) ? $payload['end_time'] : ''), 0, 5);
                $timeStr = ($startT !== '' && $endT !== '') ? ($startT . ' — ' . $endT) : '—';
                ?>
                <?php lms_component('form/input', array(
                    'label'    => 'Khung giờ học',
                    'name'     => 'view_time_slot',
                    'value'    => $timeStr,
                    'readonly' => true,
                )); ?>

                <?php lms_component('form/textarea', array(
                    'label'    => 'Ghi chú của GV',
                    'name'     => 'view_note',
                    'value'    => !empty($payload['note']) ? (string) $payload['note'] : 'Không có ghi chú',
                    'readonly' => true,
                    'rows'     => 2,
                )); ?>

            <?php elseif ($rtype === 'request_leave'): ?>
                <?php
                $leaveDate = isset($row['session_date']) ? $row['session_date'] : (isset($payload['session_date']) ? $payload['session_date'] : '');
                $leaveTime = (!empty($row['start_time']) && !empty($row['end_time']))
                    ? ' (' . substr((string) $row['start_time'], 0, 5) . ' — ' . substr((string) $row['end_time'], 0, 5) . ')'
                    : '';
                ?>
                <?php lms_component('form/input', array(
                    'label'    => 'Buổi xin nghỉ',
                    'name'     => 'view_leave_session',
                    'value'    => lms_format_date($leaveDate) . $leaveTime,
                    'readonly' => true,
                )); ?>

                <?php lms_component('form/textarea', array(
                    'label'    => 'Lý do nghỉ',
                    'name'     => 'view_reason',
                    'value'    => !empty($payload['reason']) ? (string) $payload['reason'] : '—',
                    'readonly' => true,
                    'rows'     => 2,
                )); ?>

                <?php lms_component('form/input', array(
                    'label'    => 'Ngày bù đề xuất',
                    'name'     => 'view_makeup_date',
                    'value'    => lms_format_date(isset($payload['makeup_date']) ? $payload['makeup_date'] : ''),
                    'readonly' => true,
                )); ?>

            <?php elseif ($rtype === 'change_schedule'): ?>
                <?php
                $slots = isset($payload['slots']) && is_array($payload['slots']) ? $payload['slots'] : array();
                $slotTexts = array();
                foreach ($slots as $slot) {
                    if (!is_array($slot)) continue;
                    $slotTexts[] = lms_format_date(isset($slot['date']) ? $slot['date'] : '') . ': ' . substr((string) (isset($slot['start']) ? $slot['start'] : ''), 0, 5) . ' — ' . substr((string) (isset($slot['end']) ? $slot['end'] : ''), 0, 5);
                }
                ?>
                <?php lms_component('form/textarea', array(
                    'label'    => 'Lịch học mới đề xuất',
                    'name'     => 'view_new_slots',
                    'value'    => !empty($slotTexts) ? implode("\n", $slotTexts) : '—',
                    'readonly' => true,
                    'rows'     => max(2, count($slotTexts)),
                )); ?>

            <?php else: ?>
                <?php if (!empty($payload)): ?>
                    <?php foreach ($payload as $k => $v): ?>
                        <?php lms_component('form/input', array(
                            'label'    => (string) $k,
                            'name'     => 'payload_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $k),
                            'value'    => is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v,
                            'readonly' => true,
                        )); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php lms_component('form/input', array(
                        'label'    => 'Dữ liệu',
                        'name'     => 'view_empty_payload',
                        'value'    => 'Không có dữ liệu đi kèm.',
                        'readonly' => true,
                    )); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- 3. Kết quả xử lý (nếu đã xử lý) -->
        <?php if (!$isPending): ?>
            <div class="mb-4">
                <h5 class="fw-bold mb-3" style="color:#e5501b;">
                    <i class="fa-solid fa-check-double me-2"></i>Kết quả xử lý
                </h5>

                <?php lms_component('form/input', array(
                    'label'    => 'Người xử lý',
                    'name'     => 'proc_name',
                    'value'    => !empty($row['processed_name']) ? (string) $row['processed_name'] : '—',
                    'readonly' => true,
                )); ?>

                <?php lms_component('form/input', array(
                    'label'    => 'Thời gian xử lý',
                    'name'     => 'proc_time',
                    'value'    => lms_format_datetime(isset($row['processed_at']) ? $row['processed_at'] : ''),
                    'readonly' => true,
                )); ?>

                <?php lms_component('form/textarea', array(
                    'label'    => 'Ghi chú của admin',
                    'name'     => 'proc_admin_note',
                    'value'    => !empty($row['admin_note']) ? (string) $row['admin_note'] : '—',
                    'readonly' => true,
                    'rows'     => 2,
                )); ?>
            </div>

            <!-- Nút quay lại khi đã xử lý (dùng Button component) -->
            <div class="row mt-4 mb-5">
                <div class="col-12 col-sm-9 offset-sm-3">
                    <?php lms_component('form/button', array(
                        'label'   => 'Quay lại danh sách',
                        'href'    => $backUrl,
                        'variant' => 'outline-secondary',
                        'icon'    => 'fa-solid fa-arrow-left',
                        'class'   => 'px-4',
                    )); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4. Thao tác xử lý yêu cầu (nếu đang chờ duyệt) -->
        <?php if ($isPending && $canEdit): ?>
            <div class="pt-3 border-top">
                <h5 class="fw-bold mb-3" style="color:#e5501b;">
                    <i class="fa-solid fa-gavel me-2"></i>Thao tác xử lý yêu cầu
                </h5>

                <?php if (empty($row['can_approve'])): ?>
                    <div class="alert alert-info mb-3">
                        <i class="fa-solid fa-circle-info me-1"></i>
                        Loại yêu cầu này chưa hỗ trợ duyệt tự động. Bạn có thể từ chối kèm lý do.
                    </div>
                <?php endif; ?>

                <!-- Form duyệt yêu cầu -->
                <?php if (!empty($row['can_approve'])): ?>
                    <form id="form-approve-req" method="post" action="<?= e(lms_url($alias, 'duyet')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                        <input type="hidden" name="id" value="<?= e((string) (int) $row['id']) ?>">
                        <input type="hidden" name="return_tab" value="<?= e($returnTab) ?>">
                    </form>
                <?php endif; ?>

                <!-- Form từ chối yêu cầu -->
                <form id="form-reject-req" method="post" action="<?= e(lms_url($alias, 'tu-choi')) ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e((string) (int) $row['id']) ?>">
                    <input type="hidden" name="return_tab" value="<?= e($returnTab) ?>">

                    <?php lms_component('form/textarea', array(
                        'name'        => 'admin_note',
                        'label'       => 'Lý do từ chối',
                        'placeholder' => 'Nhập lý do từ chối nếu không duyệt (tối thiểu 5 ký tự)...',
                        'required'    => true,
                        'rows'        => 2,
                        'hint'        => 'Chỉ bắt buộc khi bấm nút "Từ chối yêu cầu".',
                    )); ?>
                </form>

                <!-- Hàng nút bấm hành động (dùng Button component) -->
                <div class="row mt-4 mb-5">
                    <div class="col-12 col-sm-9 offset-sm-3 d-flex flex-wrap align-items-center gap-2">
                        <?php if (!empty($row['can_approve'])): ?>
                            <?php lms_component('form/button', array(
                                'label'   => 'Duyệt yêu cầu',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'icon'    => 'fa-solid fa-check',
                                'attrs'   => array('form' => 'form-approve-req'),
                                'class'   => 'px-4 fw-semibold',
                            )); ?>
                        <?php endif; ?>

                        <?php lms_component('form/button', array(
                            'label'   => 'Từ chối yêu cầu',
                            'type'    => 'submit',
                            'variant' => 'outline-danger',
                            'icon'    => 'fa-solid fa-xmark',
                            'confirm' => 'Bạn có chắc chắn muốn từ chối yêu cầu này?',
                            'attrs'   => array('form' => 'form-reject-req'),
                            'class'   => 'px-4 fw-semibold',
                        )); ?>

                        <?php lms_component('form/button', array(
                            'label'   => 'Huỷ / Quay lại',
                            'href'    => $backUrl,
                            'variant' => 'outline-secondary',
                            'icon'    => 'fa-solid fa-arrow-left',
                            'class'   => 'px-3',
                        )); ?>
                    </div>
                </div>
            </div>
        <?php elseif ($isPending): ?>
            <div class="alert alert-secondary mt-3">
                <i class="fa-solid fa-lock me-1"></i> Bạn chỉ có quyền xem yêu cầu này.
            </div>
            <div class="row mt-4 mb-5">
                <div class="col-12 col-sm-9 offset-sm-3">
                    <?php lms_component('form/button', array(
                        'label'   => 'Quay lại danh sách',
                        'href'    => $backUrl,
                        'variant' => 'outline-secondary',
                        'icon'    => 'fa-solid fa-arrow-left',
                        'class'   => 'px-4',
                    )); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
