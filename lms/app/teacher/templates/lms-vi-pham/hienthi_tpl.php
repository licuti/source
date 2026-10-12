<?php
/**
 * Teacher LMS — Vi phạm nội quy: view tick của mình.
 * Data do source chuẩn bị: $month, $filterReason, $items, $total,
 * $tickSummary, $warnMessage, $alias.
 */
?>
<?php
$reasonLabels = \Lms\TickService::ALL_REASONS;

$reasonBadge = array(
    'late_lesson_summary' => 'lms-badge-danger',
    'late_hw_grading'     => 'lms-badge-danger',
    'late_test_grading'   => 'lms-badge-danger',
    'manual_penalty'      => 'lms-badge-warning',
    'manual_exempt'       => 'lms-badge-success',
);

$exempted = (int) $tickSummary['exempted'];
$raw      = (int) $tickSummary['raw'];
$real     = (int) $tickSummary['real'];
$demoteAt = is_array($tickSummary['threshold']) ? (int) $tickSummary['threshold']['ticks_to_demote_fee'] : 0;
?>

<?php if ($warnMessage !== ''): ?>
    <div class="lms-alert lms-alert-warning" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Cảnh báo:</strong> <?= e($warnMessage) ?>
    </div>
<?php endif; ?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-triangle-exclamation me-1"></i> Vi phạm nội quy tháng <?= e($month) ?>
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format($total)) ?> tick</span>
    </div>
    <div class="lms-card-body">
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Tổng tick</div>
                    <div class="lms-stat-value"><?= e((string) $raw) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Được miễn</div>
                    <div class="lms-stat-value text-success">−<?= e((string) $exempted) ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Tick thực tế</div>
                    <div class="lms-stat-value <?= $demoteAt > 0 && $real >= $demoteAt ? 'text-danger' : '' ?>">
                        <?= e((string) $real) ?>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Ngưỡng hạ phí</div>
                    <div class="lms-stat-value"><?= e((string) $demoteAt) ?></div>
                </div>
            </div>
        </div>

        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-6 col-md-3">
                <input type="month" class="form-control" name="month" value="<?= e($month) ?>"
                       aria-label="Chọn tháng">
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="reason" class="form-select" aria-label="Lọc theo lý do">
                    <option value="">Tất cả lý do</option>
                    <?php foreach ($reasonLabels as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterReason, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Lọc
                </button>
                <a href="<?= e(lms_url($alias)) ?>" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-rotate-left me-1"></i>
                </a>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-circle-check d-block mb-2" style="font-size:1.6rem;"></i>
                Không có vi phạm nào trong tháng này. Tốt lắm!
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Ngày vi phạm</th>
                            <th>Lý do</th>
                            <th>Buổi liên quan</th>
                            <th class="text-end">Tick</th>
                            <th>Nguồn</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $reason = (string) ($r['reason'] ?? '');
                            $auto   = (int) ($r['auto_generated'] ?? 1) === 1;
                            ?>
                            <tr>
                                <td class="text-nowrap"><?= e((string) ($r['violation_date'] ?? '—')) ?></td>
                                <td>
                                    <span class="lms-badge <?= e(isset($reasonBadge[$reason]) ? $reasonBadge[$reason] : 'lms-badge-secondary') ?>">
                                        <?= e(isset($reasonLabels[$reason]) ? $reasonLabels[$reason] : $reason) ?>
                                    </span>
                                </td>
                                <td class="text-nowrap">
                                    <?php if (!empty($r['session_date'])): ?>
                                        <?= e(date('d/m/Y', strtotime((string) $r['session_date']))) ?>
                                        <?php if (!empty($r['class_code'])): ?>
                                            <div class="lms-text-muted-sm"><?= e((string) $r['class_code']) ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong class="<?= (int) $r['tick_amount'] < 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= (int) $r['tick_amount'] > 0 ? '+' . (int) $r['tick_amount'] : e((string) (int) $r['tick_amount']) ?>
                                    </strong>
                                </td>
                                <td class="text-nowrap">
                                    <?php if ($auto): ?>
                                        <span class="lms-badge lms-badge-secondary">Tự động</span>
                                    <?php else: ?>
                                        <span class="lms-badge lms-badge-primary">Thủ công</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($r['note'])): ?>
                                        <span title="<?= e((string) $r['note']) ?>"><?= e(mb_strimwidth((string) $r['note'], 0, 40, '…')) ?></span>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
