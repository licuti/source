<?php
/**
 * Teacher LMS — Phí dịch vụ: view giao dịch của mình (TCH-05).
 * Data do source chuẩn bị: $month, $filterType, $transactions, $summary,
 * $tickSummary, $demoteWarning, $alias.
 */
?>
<?php
$fmt = function ($v) {
    return number_format((float) $v, 0, ',', '.');
};

$typeFilters = array(
    ''               => 'Tất cả loại',
    'salary_session' => 'Lương buổi',
    'penalty'        => 'Phạt',
    'bonus'          => 'Thưởng',
    'tax'            => 'Thuế TNCN',
    'adjustment'     => 'Điều chỉnh',
);
?>

<?php if ($demoteWarning !== ''): ?>
    <div class="lms-alert lms-alert-warning" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Cảnh báo vi phạm:</strong> <?= e($demoteWarning) ?>
        <a href="<?= e(lms_url('lms-vi-pham')) ?>" class="alert-link ms-1">Xem chi tiết</a>
    </div>
<?php endif; ?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-money-bill-wave me-1"></i> Phí dịch vụ tháng <?= e($month) ?>
    </div>
    <div class="lms-card-body">
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
        </div>

        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-6 col-md-3">
                <input type="month" class="form-control" name="month" value="<?= e($month) ?>"
                       aria-label="Chọn tháng">
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="type" class="form-select" aria-label="Lọc theo loại giao dịch">
                    <?php foreach ($typeFilters as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterType, $k) ?>><?= e($label) ?></option>
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

        <?php if (empty($transactions)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-money-bill-wave d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có giao dịch nào tháng <?= e($month) ?>.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
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
                                <td class="text-nowrap"><?= e((string) ($t['transaction_date'] ?? '—')) ?></td>
                                <td>
                                    <span class="lms-badge <?= $amount < 0 ? 'lms-badge-danger' : 'lms-badge-success' ?>">
                                        <?= e(lpdv_type_label((string) $t['type'])) ?>
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
    </div>
</div>
