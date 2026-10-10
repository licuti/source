<?php
/**
 * Admin LMS — Quản lý vi phạm: view danh sách tick (ADM-06).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page,
 * $filterTeacherId, $filterReason, $filterMonth, $filterAuto,
 * $teachers, $canEdit, $canDelete, $alias.
 */
$reasonLabels = \Lms\TickService::ALL_REASONS;

$reasonBadge = array(
    'late_lesson_summary' => 'lms-badge-danger',
    'late_hw_grading'     => 'lms-badge-danger',
    'late_test_grading'   => 'lms-badge-danger',
    'manual_penalty'      => 'lms-badge-warning',
    'manual_exempt'       => 'lms-badge-success',
);
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Danh sách tick vi phạm
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?> tick</span>
    </div>
    <div class="lms-card-body">
        <?php if ($canEdit): ?>
            <div class="mb-3 d-flex gap-2 flex-wrap">
                <a href="<?= e(lms_url($alias, 'them')) ?>" class="btn btn-lms btn-sm">
                    <i class="fa-solid fa-plus me-1"></i> Thêm tick thủ công
                </a>
                <a href="<?= e(lms_url($alias, 'nguong')) ?>" class="btn btn-outline-lms btn-sm">
                    <i class="fa-solid fa-sliders me-1"></i> Cấu hình ngưỡng
                </a>
            </div>
        <?php endif; ?>

        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
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
            <div class="col-sm-6 col-md-3">
                <select name="reason" class="form-select" aria-label="Lọc theo lý do">
                    <option value="">Tất cả lý do</option>
                    <?php foreach ($reasonLabels as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterReason, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-2">
                <input type="month" class="form-control" name="month" value="<?= e($filterMonth) ?>"
                       aria-label="Lọc theo tháng vi phạm">
            </div>
            <div class="col-sm-6 col-md-2">
                <select name="auto" class="form-select" aria-label="Lọc theo nguồn">
                    <option value="">Mọi nguồn</option>
                    <option value="1"<?= lms_selected($filterAuto, '1') ?>>Tự động (cron)</option>
                    <option value="0"<?= lms_selected($filterAuto, '0') ?>>Thủ công</option>
                </select>
            </div>
            <div class="col-sm-6 col-md-2 d-flex gap-2">
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
                <i class="fa-solid fa-triangle-exclamation d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có tick vi phạm nào phù hợp.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th class="text-end">ID</th>
                            <th>Giáo viên</th>
                            <th>Lý do</th>
                            <th>Buổi liên quan</th>
                            <th class="text-end">Tick</th>
                            <th>Nguồn</th>
                            <th>Ngày vi phạm</th>
                            <th>Người tạo</th>
                            <th>Ghi chú</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $reason  = (string) ($r['reason'] ?? '');
                            $auto    = (int) ($r['auto_generated'] ?? 1) === 1;
                            $created = isset($r['created_at']) ? date('d/m/Y H:i', strtotime((string) $r['created_at'])) : '—';
                            ?>
                            <tr>
                                <td class="text-end"><?= e((string) (int) $r['id']) ?></td>
                                <td>
                                    <?= e((string) ($r['teacher_code'] ?? '')) ?>
                                    <?= !empty($r['teacher_name']) ? '— ' . e((string) $r['teacher_name']) : '' ?>
                                </td>
                                <td>
                                    <span class="lms-badge <?= e(isset($reasonBadge[$reason]) ? $reasonBadge[$reason] : 'lms-badge-secondary') ?>">
                                        <?= e(isset($reasonLabels[$reason]) ? $reasonLabels[$reason] : $reason) ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <?php if (!empty($r['session_date'])): ?>
                                        <?= e(date('d/m/Y', strtotime((string) $r['session_date']))) ?>
                                        <?php if (!empty($r['start_time'])): ?>
                                            <span class="lms-text-muted-sm"><?= e(substr((string) $r['start_time'], 0, 5)) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['class_code'])): ?>
                                            <div class="lms-text-muted-sm"><?= e((string) $r['class_code']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong class="<?= (int) $r['tick_amount'] < 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= e((int) $r['tick_amount'] > 0 ? '+' . (int) $r['tick_amount'] : (string) (int) $r['tick_amount']) ?>
                                    </strong>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($auto): ?>
                                        <span class="lms-badge lms-badge-secondary">Cron</span>
                                    <?php else: ?>
                                        <span class="lms-badge lms-badge-primary">Thủ công</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap"><?= e((string) ($r['violation_date'] ?? '—')) ?></td>
                                <td class="text-nowrap"><?= e((string) ($r['created_by_name'] ?? ($auto ? 'system' : '—'))) ?></td>
                                <td>
                                    <?php if (!empty($r['note'])): ?>
                                        <span title="<?= e((string) $r['note']) ?>"><?= e(mb_strimwidth((string) $r['note'], 0, 40, '…')) ?></span>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <?php if ($canDelete && !$auto): ?>
                                        <form method="post" action="<?= e(lms_url($alias, 'xoa')) ?>" class="d-inline"
                                              onsubmit="return confirm('Xoá tick thủ công này?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                            <input type="hidden" name="id" value="<?= e((string) (int) $r['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá tick">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm" title="Tick do cron tạo — dùng miễn giảm thủ công để bù trừ">—</span>
                                    <?php endif; ?>
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
                    'teacher_id' => $filterTeacherId,
                    'reason'     => $filterReason,
                    'month'      => $filterMonth,
                    'auto'       => $filterAuto,
                )
            ) ?>
        <?php endif; ?>
    </div>
</div>
