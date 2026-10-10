<?php
/**
 * Admin LMS — Quản lý học viên: view danh sách (ADM-01).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page,
 * $filterQ, $filterStatus, $canEdit, $canDelete.
 */
$statuses = \Lms\UserService::STUDENT_STATUSES;

$badgeClass = array(
    'studying'        => 'lms-badge-success',
    'waiting_fee'     => 'lms-badge-warning',
    'pending_arrange' => 'lms-badge-info',
    'paused'          => 'lms-badge-secondary',
    'quit'            => 'lms-badge-danger',
);
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-user-graduate me-1"></i> Danh sách học viên
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?> học viên</span>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-12 col-md-5">
                <input type="search" name="q" class="form-control" placeholder="Mã / tên / username / SĐT..."
                       value="<?= e($filterQ) ?>" aria-label="Tìm kiếm">
            </div>
            <div class="col-sm-8 col-md-4">
                <select name="status" class="form-select" aria-label="Lọc theo trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($statuses as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterStatus, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-4 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm
                </button>
                <?php if ($canEdit): ?>
                    <a href="<?= e(lms_url($alias, 'them')) ?>" class="btn btn-success">
                        <i class="fa-solid fa-plus me-1"></i> Thêm
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-inbox d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có học viên nào phù hợp.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Mã học viên</th>
                            <th>Họ tên</th>
                            <th>Username</th>
                            <th>SĐT phụ huynh</th>
                            <th>Lớp đang theo</th>
                            <th>GV 1-1</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Ưu tiên</th>
                            <?php if ($canEdit || $canDelete): ?>
                                <th class="text-end">Hành động</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $stt = isset($r['student_status']) ? (string) $r['student_status'] : '';
                            $locked = (isset($r['user_status']) ? (string) $r['user_status'] : '') === 'locked';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) ($r['student_code'] ?? '')) ?></strong>
                                    <div class="lms-text-muted-sm"><?= e(isset($r['grade']) ? (string) $r['grade'] : '') ?></div>
                                </td>
                                <td><?= e((string) ($r['full_name'] ?? '')) ?></td>
                                <td><?= e((string) ($r['username'] ?? '')) ?></td>
                                <td><?= e((string) ($r['parent_phone'] ?? ($r['phone'] ?? ''))) ?></td>
                                <td>
                                    <?php $cn = trim((string) ($r['class_names'] ?? '')); ?>
                                    <?= $cn !== '' ? e($cn) : '<span class="lms-text-muted-sm">Chưa có lớp</span>' ?>
                                </td>
                                <td>
                                    <?php $t1 = trim((string) ($r['teacher_1on1'] ?? '')); ?>
                                    <?= $t1 !== '' ? e($t1) : '<span class="lms-text-muted-sm">—</span>' ?>
                                </td>
                                <td>
                                    <?php if (isset($statuses[$stt])): ?>
                                        <span class="lms-badge <?= e(isset($badgeClass[$stt]) ? $badgeClass[$stt] : 'lms-badge-secondary') ?>">
                                            <?= e($statuses[$stt]) ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($locked): ?>
                                        <span class="lms-badge lms-badge-danger ms-1">Đã khoá</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= e((string) number_format((int) ($r['priority_number'] ?? 0))) ?></td>
                                <?php if ($canEdit || $canDelete): ?>
                                    <td class="text-end text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <a href="<?= e(lms_url($alias, 'sua', array('id' => (int) $r['id']))) ?>"
                                               class="btn btn-sm btn-outline-lms" title="Sửa">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <?php if ($locked): ?>
                                                <a href="<?= e(lms_url($alias, 'mo', array('id' => (int) $r['id']))) ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Mở khoá tài khoản">
                                                    <i class="fa-solid fa-lock-open"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= e(lms_url($alias, 'khoa', array('id' => (int) $r['id']))) ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Khoá tài khoản">
                                                    <i class="fa-solid fa-lock"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <a href="<?= e(lms_url($alias, 'xoa', array('id' => (int) $r['id']))) ?>"
                                               class="btn btn-sm btn-outline-danger lms-confirm-delete"
                                               title="Xoá học viên">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= lms_pagination(
                lms_url($alias),
                $page,
                $totalPages,
                array('q' => $filterQ, 'status' => $filterStatus)
            ) ?>
        <?php endif; ?>
    </div>
</div>

<script>
    (function ($) {
        'use strict';
        $(function () {
            $('.lms-confirm-delete').on('click', function (e) {
                if (!window.confirm('Bạn có chắc muốn xoá học viên này? Hành động không thể hoàn tác.')) {
                    e.preventDefault();
                }
            });
        });
    })(jQuery);
</script>
