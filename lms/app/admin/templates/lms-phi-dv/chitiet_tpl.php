<?php
/**
 * Admin LMS — Thống kê phí dịch vụ: view chi tiết 1 GV × tháng (ADM-07).
 * Data do source chuẩn bị: $teacher, $month, $summary, $transactions,
 * $errors, $canEdit, $alias, $teacherId.
 */
?>
<?php
$fmt = function ($v) {
    return number_format((float) $v, 0, ',', '.');
};

$nextStatus = '';
$nextLabel  = '';
if ($summary['status'] === 'draft') {
    $nextStatus = 'pending';
    $nextLabel  = 'Gửi duyệt';
} elseif ($summary['status'] === 'pending') {
    $nextStatus = 'paid';
    $nextLabel  = 'Duyệt thanh toán';
}
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= e(lms_url($alias)) ?>" class="btn btn-sm btn-outline-secondary me-1">
            <i class="fa-solid fa-arrow-left"></i>
        </a>
        <i class="fa-solid fa-money-bill-wave me-1"></i>
        Chi tiết phí dịch vụ — <?= e((string) $teacher['teacher_code']) ?> (<?= e((string) $teacher['full_name']) ?>)
        <span class="ms-auto lms-text-muted-sm">Tháng <?= e($month) ?></span>
    </div>
    <div class="lms-card-body">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $field => $msg): ?>
                        <li><?= e((string) $msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Tổng thu</div>
                    <div class="lms-stat-value"><?= e($fmt($summary['tong_thu'])) ?>đ</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Tổng phạt</div>
                    <div class="lms-stat-value text-danger"><?= e($fmt($summary['tong_phat'])) ?>đ</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Thưởng</div>
                    <div class="lms-stat-value text-success"><?= e($fmt($summary['tong_thuong'])) ?>đ</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Thuế TNCN</div>
                    <div class="lms-stat-value"><?= e($fmt($summary['thue'])) ?>đ</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Thực nhận</div>
                    <div class="lms-stat-value"><?= e($fmt($summary['thuc_nhan'])) ?>đ</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="lms-stat-card">
                    <div class="lms-stat-label">Trạng thái</div>
                    <div class="lms-stat-value">
                        <?php if ($summary['status'] !== ''): ?>
                            <span class="lms-badge <?= e(lpd_status_badge($summary['status'])) ?>">
                                <?= e(lpd_status_label($summary['status'])) ?>
                            </span>
                        <?php else: ?>
                            <span class="lms-text-muted-sm">—</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canEdit && $nextStatus !== ''): ?>
            <form method="post" action="<?= e(lms_url($alias, 'duyet')) ?>" class="mb-4 d-inline-block">
                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                <input type="hidden" name="teacher_id" value="<?= e((string) $teacherId) ?>">
                <input type="hidden" name="month" value="<?= e($month) ?>">
                <input type="hidden" name="status" value="<?= e($nextStatus) ?>">
                <button type="submit" class="btn btn-success"
                        onclick="return confirm('Chuyển sang <?= e($nextLabel) ?>? Hành động này không thể hoàn tác.');">
                    <i class="fa-solid fa-check me-1"></i> <?= e($nextLabel) ?>
                </button>
            </form>
        <?php endif; ?>

        <h2 class="h5 mb-3">Giao dịch</h2>
        <?php if (empty($transactions)): ?>
            <p class="text-muted mb-0 py-3 text-center">Chưa có giao dịch nào trong tháng này.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th class="text-end">ID</th>
                            <th>Ngày</th>
                            <th>Loại</th>
                            <th>Ghi chú</th>
                            <th class="text-end">Số tiền</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <?php $amount = (float) $t['amount']; ?>
                            <tr>
                                <td class="text-end"><?= e((string) (int) $t['id']) ?></td>
                                <td class="text-nowrap"><?= e((string) ($t['transaction_date'] ?? '—')) ?></td>
                                <td>
                                    <span class="lms-badge <?= $amount < 0 ? 'lms-badge-danger' : 'lms-badge-success' ?>">
                                        <?= e(lpd_type_label((string) $t['type'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($t['reason'])): ?>
                                        <span title="<?= e((string) $t['reason']) ?>">
                                            <?= e(mb_strimwidth((string) $t['reason'], 0, 60, '…')) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <strong class="<?= $amount < 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= $amount < 0 ? '−' : '+' ?><?= e($fmt(abs($amount))) ?>đ
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <?php if ($canEdit): ?>
            <h2 class="h5 mt-4 mb-3">Thêm điều chỉnh thủ công</h2>
            <form method="post" action="<?= e(lms_url($alias, 'them-dieu-chinh')) ?>" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                <input type="hidden" name="teacher_id" value="<?= e((string) $teacherId) ?>">
                <input type="hidden" name="month" value="<?= e($month) ?>">
                <div class="col-sm-6 col-md-3">
                    <select name="type" class="form-select" aria-label="Loại điều chỉnh" required>
                        <option value="penalty">Phạt (trừ tiền)</option>
                        <option value="bonus">Thưởng (cộng tiền)</option>
                        <option value="adjustment">Điều chỉnh (giữ dấu)</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-3">
                    <input type="text" class="form-control" name="amount" placeholder="Số tiền (VD 500000)"
                           inputmode="numeric" required>
                </div>
                <div class="col-sm-12 col-md-4">
                    <input type="text" class="form-control" name="note" placeholder="Ghi chú / lý do">
                </div>
                <div class="col-sm-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-lms">
                        <i class="fa-solid fa-plus me-1"></i> Thêm
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
