<?php
/**
 * Admin LMS — Quản lý yêu cầu: view danh sách (ADM-05).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page,
 * $filterStatus, $filterType, $filterTeacherId, $teachers, $canEdit,
 * $pendingCount, $alias.
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

if (!function_exists('lmyc_buoi_text')) {
    /**
     * Text mô tả buổi liên quan (theo loại request + payload).
     *
     * @param array $r row từ listRequestsForAdmin
     * @return string
     */
    function lmyc_buoi_text(array $r)
    {
        $payload = json_decode((string) (isset($r['payload']) ? $r['payload'] : ''), true);
        if (!is_array($payload)) {
            $payload = array();
        }

        if (!empty($r['session_date'])) {
            $text = date('d/m/Y', strtotime((string) $r['session_date']));
            if (!empty($r['start_time'])) {
                $text .= ' ' . substr((string) $r['start_time'], 0, 5);
            }
            if (!empty($r['end_time'])) {
                $text .= '-' . substr((string) $r['end_time'], 0, 5);
            }
            return $text;
        }
        if (!empty($payload['session_date'])) {
            $text = date('d/m/Y', strtotime((string) $payload['session_date']));
            if (!empty($payload['start_time'])) {
                $text .= ' ' . substr((string) $payload['start_time'], 0, 5);
            }
            if (!empty($payload['end_time'])) {
                $text .= '-' . substr((string) $payload['end_time'], 0, 5);
            }
            return $text;
        }
        if (!empty($payload['slots']) && is_array($payload['slots'])) {
            return count($payload['slots']) . ' buổi mới';
        }
        return '—';
    }
}
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-inbox me-1"></i> Danh sách yêu cầu
        <span class="ms-auto lms-text-muted-sm">
            Tổng: <?= e((string) number_format((int) $total)) ?> yêu cầu
            <?php if ((int) $pendingCount > 0): ?>
                · <strong><?= e((string) number_format((int) $pendingCount)) ?> chờ duyệt</strong>
            <?php endif; ?>
        </span>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-6 col-md-3">
                <select name="status" class="form-select" aria-label="Lọc theo trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($statusLabels as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterStatus, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="type" class="form-select" aria-label="Lọc theo loại yêu cầu">
                    <option value="">Tất cả loại</option>
                    <?php foreach ($typeLabels as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterType, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="teacher_id" class="form-select" aria-label="Lọc theo giáo viên">
                    <option value="">Tất cả giáo viên</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= e((string) (int) $t['user_id']) ?>"<?= lms_selected($filterTeacherId, (int) $t['user_id']) ?>>
                            <?= e((string) ($t['teacher_code'] ?? '')) ?> — <?= e((string) ($t['full_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Lọc
                </button>
                <a href="<?= e(lms_url($alias)) ?>" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-rotate-left me-1"></i> Xoá lọc
                </a>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-inbox d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có yêu cầu nào phù hợp.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th class="text-end">ID</th>
                            <th>Loại</th>
                            <th>Tiêu đề</th>
                            <th>Lớp</th>
                            <th>Giáo viên</th>
                            <th>Học viên</th>
                            <th>Buổi liên quan</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $rtype  = (string) ($r['request_type'] ?? '');
                            $rstt   = (string) ($r['status'] ?? '');
                            $created = isset($r['created_at']) ? date('d/m/Y H:i', strtotime((string) $r['created_at'])) : '—';
                            ?>
                            <tr>
                                <td class="text-end"><?= e((string) (int) $r['id']) ?></td>
                                <td>
                                    <span class="lms-badge <?= e(isset($typeBadge[$rtype]) ? $typeBadge[$rtype] : 'lms-badge-secondary') ?>">
                                        <?= e(isset($typeLabels[$rtype]) ? $typeLabels[$rtype] : $rtype) ?>
                                    </span>
                                </td>
                                <td><strong><?= e((string) ($r['title'] ?? '')) ?></strong></td>
                                <td>
                                    <?php if (!empty($r['class_code'])): ?>
                                        <strong><?= e((string) $r['class_code']) ?></strong>
                                        <div class="lms-text-muted-sm"><?= e((string) ($r['class_name'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e((string) ($r['teacher_code'] ?? '')) ?>
                                    <?= !empty($r['teacher_name']) ? '— ' . e((string) $r['teacher_name']) : '' ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['student_name'])): ?>
                                        <?= e((string) $r['student_name']) ?>
                                        <div class="lms-text-muted-sm"><?= e((string) ($r['student_code'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= e(lmyc_buoi_text($r)) ?></td>
                                <td class="text-nowrap"><?= e($created) ?></td>
                                <td>
                                    <?php if (isset($statusLabels[$rstt])): ?>
                                        <span class="lms-badge <?= e(isset($statusBadge[$rstt]) ? $statusBadge[$rstt] : 'lms-badge-secondary') ?>">
                                            <?= e($statusLabels[$rstt]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= e(lms_url($alias, 'xem', array('id' => (int) $r['id']))) ?>"
                                       class="btn btn-sm btn-outline-lms" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye me-1"></i> Xem
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= lms_pagination(
                lms_url($alias),
                $page,
                $totalPages,
                array(
                    'status'     => $filterStatus,
                    'type'       => $filterType,
                    'teacher_id' => $filterTeacherId,
                )
            ) ?>
        <?php endif; ?>
    </div>
</div>
