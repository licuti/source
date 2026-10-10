<?php
/**
 * Admin LMS — Quản lý yêu cầu: chi tiết + form duyệt/từ chối (ADM-05).
 * Data do source chuẩn bị: $row (payload đã decode, can_approve), $canEdit,
 * $alias, $title.
 */
$typeLabels   = \Lms\RequestService::ALL_REQUEST_TYPES;
$statusLabels = \Lms\RequestService::REQUEST_STATUSES;

$typeBadge = array(
    'add_session'     => 'lms-badge-info',
    'request_leave'   => 'lms-badge-warning',
    'change_schedule' => 'lms-badge-primary',
);
$statusBadge = array(
    'pending'  => 'lms-badge-warning',
    'approved' => 'lms-badge-success',
    'rejected' => 'lms-badge-danger',
);

$rtype = (string) (isset($row['request_type']) ? $row['request_type'] : '');
$rstt  = (string) (isset($row['status']) ? $row['status'] : '');
$payload = isset($row['payload']) && is_array($row['payload']) ? $row['payload'] : array();
$isPending = $rstt === 'pending';

if (!function_exists('lmyc_fmt_date')) {
    /**
     * Format ngày 'Y-m-d' → 'd/m/Y' an toàn.
     *
     * @param string $date
     * @return string
     */
    function lmyc_fmt_date($date)
    {
        $date = (string) $date;
        if ($date === '' || $date === '0000-00-00') {
            return '—';
        }
        $ts = strtotime($date);
        return $ts === false ? e($date) : e(date('d/m/Y', $ts));
    }
}

if (!function_exists('lmyc_fmt_dt')) {
    /**
     * Format datetime → 'd/m/Y H:i' an toàn.
     *
     * @param string $dt
     * @return string
     */
    function lmyc_fmt_dt($dt)
    {
        $dt = (string) $dt;
        if ($dt === '' || $dt === '0000-00-00 00:00:00') {
            return '—';
        }
        $ts = strtotime($dt);
        return $ts === false ? e($dt) : e(date('d/m/Y H:i', $ts));
    }
}
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= e(lms_url($alias)) ?>" class="btn btn-sm btn-outline-secondary me-1">
            <i class="fa-solid fa-arrow-left"></i> Danh sách
        </a>
        Yêu cầu #<?= e((string) (int) $row['id']) ?>
        <span class="ms-2 lms-badge <?= e(isset($typeBadge[$rtype]) ? $typeBadge[$rtype] : 'lms-badge-secondary') ?>">
            <?= e(isset($typeLabels[$rtype]) ? $typeLabels[$rtype] : $rtype) ?>
        </span>
        <?php if (isset($statusLabels[$rstt])): ?>
            <span class="lms-badge <?= e(isset($statusBadge[$rstt]) ? $statusBadge[$rstt] : 'lms-badge-secondary') ?>">
                <?= e($statusLabels[$rstt]) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="lms-card-body">
        <h5 class="mb-1"><?= e((string) (isset($row['title']) ? $row['title'] : '')) ?></h5>
        <?php if (!empty($row['description'])): ?>
            <p class="text-muted"><?= e((string) $row['description']) ?></p>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-3">
                <div class="lms-text-muted-sm">Lớp học</div>
                <?php if (!empty($row['class_code'])): ?>
                    <strong><?= e((string) $row['class_code']) ?></strong>
                    <div><?= e((string) (isset($row['class_name']) ? $row['class_name'] : '')) ?></div>
                <?php else: ?>
                    <span class="lms-text-muted-sm">—</span>
                <?php endif; ?>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="lms-text-muted-sm">Giáo viên</div>
                <?= e((string) (isset($row['teacher_code']) ? $row['teacher_code'] : '')) ?>
                <?= !empty($row['teacher_name']) ? '— ' . e((string) $row['teacher_name']) : '—' ?>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="lms-text-muted-sm">Học viên</div>
                <?php if (!empty($row['student_name'])): ?>
                    <?= e((string) $row['student_name']) ?>
                    <div class="lms-text-muted-sm"><?= e((string) (isset($row['student_code']) ? $row['student_code'] : '')) ?></div>
                <?php else: ?>
                    <span class="lms-text-muted-sm">—</span>
                <?php endif; ?>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="lms-text-muted-sm">Ngày tạo</div>
                <?= lmyc_fmt_dt(isset($row['created_at']) ? $row['created_at'] : '') ?>
            </div>
        </div>

        <h6 class="mb-2"><i class="fa-solid fa-circle-info me-1"></i> Nội dung yêu cầu</h6>
        <div class="table-responsive mb-4">
            <table class="lms-table">
                <tbody>
                    <?php if ($rtype === 'add_session'): ?>
                        <tr>
                            <th style="width:200px;">Ngày học</th>
                            <td><?= lmyc_fmt_date(isset($payload['session_date']) ? $payload['session_date'] : '') ?></td>
                        </tr>
                        <tr>
                            <th>Khung giờ</th>
                            <td>
                                <?= e(substr((string) (isset($payload['start_time']) ? $payload['start_time'] : ''), 0, 5)) ?>
                                —
                                <?= e(substr((string) (isset($payload['end_time']) ? $payload['end_time'] : ''), 0, 5)) ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Ghi chú</th>
                            <td><?= !empty($payload['note']) ? e((string) $payload['note']) : '<span class="lms-text-muted-sm">—</span>' ?></td>
                        </tr>
                    <?php elseif ($rtype === 'request_leave'): ?>
                        <tr>
                            <th style="width:200px;">Buổi xin nghỉ</th>
                            <td>
                                <?= lmyc_fmt_date(isset($row['session_date']) ? $row['session_date'] : (isset($payload['session_date']) ? $payload['session_date'] : '')) ?>
                                <?php if (!empty($row['start_time'])): ?>
                                    <?= e(substr((string) $row['start_time'], 0, 5)) ?>–<?= e(substr((string) $row['end_time'], 0, 5)) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Lý do</th>
                            <td><?= !empty($payload['reason']) ? e((string) $payload['reason']) : '<span class="lms-text-muted-sm">—</span>' ?></td>
                        </tr>
                        <tr>
                            <th>Ngày bù đề xuất</th>
                            <td><?= lmyc_fmt_date(isset($payload['makeup_date']) ? $payload['makeup_date'] : '') ?></td>
                        </tr>
                    <?php elseif ($rtype === 'change_schedule'): ?>
                        <tr>
                            <th style="width:200px;">Lịch mới</th>
                            <td>
                                <?php $slots = isset($payload['slots']) && is_array($payload['slots']) ? $payload['slots'] : array(); ?>
                                <?php if (empty($slots)): ?>
                                    <span class="lms-text-muted-sm">—</span>
                                <?php else: ?>
                                    <ul class="mb-0 ps-3">
                                        <?php foreach ($slots as $slot): ?>
                                            <li>
                                                <?= lmyc_fmt_date(isset($slot['date']) ? $slot['date'] : '') ?>
                                                <strong>
                                                    <?= e(substr((string) (isset($slot['start']) ? $slot['start'] : ''), 0, 5)) ?>
                                                    —
                                                    <?= e(substr((string) (isset($slot['end']) ? $slot['end'] : ''), 0, 5)) ?>
                                                </strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($payload as $k => $v): ?>
                            <tr>
                                <th style="width:200px;"><?= e((string) $k) ?></th>
                                <td><?= is_array($v) ? e(json_encode($v, JSON_UNESCAPED_UNICODE)) : e((string) $v) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payload)): ?>
                            <tr><td><span class="lms-text-muted-sm">Không có dữ liệu đi kèm.</span></td></tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!$isPending): ?>
            <h6 class="mb-2"><i class="fa-solid fa-check-double me-1"></i> Thông tin xử lý</h6>
            <div class="table-responsive mb-4">
                <table class="lms-table">
                    <tbody>
                        <tr>
                            <th style="width:200px;">Người xử lý</th>
                            <td><?= !empty($row['processed_name']) ? e((string) $row['processed_name']) : '—' ?></td>
                        </tr>
                        <tr>
                            <th>Thời gian xử lý</th>
                            <td><?= lmyc_fmt_dt(isset($row['processed_at']) ? $row['processed_at'] : '') ?></td>
                        </tr>
                        <tr>
                            <th>Ghi chú admin</th>
                            <td><?= !empty($row['admin_note']) ? e((string) $row['admin_note']) : '<span class="lms-text-muted-sm">—</span>' ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($isPending && $canEdit): ?>
            <h6 class="mb-2"><i class="fa-solid fa-gavel me-1"></i> Xử lý yêu cầu</h6>
            <?php if (empty($row['can_approve'])): ?>
                <div class="alert alert-info mb-3">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Loại yêu cầu này chưa hỗ trợ duyệt tự động. Bạn có thể từ chối kèm lý do.
                </div>
            <?php else: ?>
                <form method="post" action="<?= e(lms_url($alias, 'duyet')) ?>" class="d-inline-block mb-3">
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= e((string) (int) $row['id']) ?>">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-check me-1"></i> Duyệt yêu cầu
                    </button>
                </form>
            <?php endif; ?>

            <form method="post" action="<?= e(lms_url($alias, 'tu-choi')) ?>" class="ms-3 d-inline-block">
                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= e((string) (int) $row['id']) ?>">
                <div class="mb-2">
                    <label class="form-label" for="admin_note">Lý do từ chối <span class="text-danger">*</span></label>
                    <textarea name="admin_note" id="admin_note" class="form-control" rows="2"
                              placeholder="Nhập lý do từ chối (tối thiểu 5 ký tự)..." required></textarea>
                </div>
                <button type="submit" class="btn btn-danger">
                    <i class="fa-solid fa-xmark me-1"></i> Từ chối yêu cầu
                </button>
            </form>
        <?php elseif ($isPending): ?>
            <div class="alert alert-secondary mb-0">
                <i class="fa-solid fa-lock me-1"></i> Bạn chỉ có quyền xem yêu cầu này.
            </div>
        <?php endif; ?>
    </div>
</div>
