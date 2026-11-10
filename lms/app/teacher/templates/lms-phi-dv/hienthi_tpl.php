<?php
/**
 * Teacher LMS — Phí dịch vụ: giao dịch của mình (TCH-05, mockup Phí dịch vụ.png).
 * Data do source chuẩn bị: $dateFrom, $dateTo, $studentId, $students,
 * $transactions, $summary, $tickSummary, $demoteWarning, $alias.
 */
$fmtMoney = function ($v) {
    return lpdv_money($v);
};
?>
<?php if ($demoteWarning !== ''): ?>
    <div class="lms-alert lms-alert-warning" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Cảnh báo vi phạm:</strong> <?= e($demoteWarning) ?>
        <a href="<?= e(lms_url('lms-vi-pham')) ?>" class="alert-link ms-1">Xem chi tiết</a>
    </div>
<?php endif; ?>

<form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-pdv-form">
    <div class="lms-pdv-row">
        <label class="lms-pdv-label" for="lmld-pdv-total">Tổng tiền:</label>
        <div class="lms-pdv-control">
            <?php lms_component('form/addon_input', array(
                'name'     => '',
                'id'       => 'lmld-pdv-total',
                'value'    => $fmtMoney($summary['thuc_nhan']),
                'suffix'   => 'VND',
                'readonly' => true,
                'bare'     => true,
            )); ?>
            <div class="d-flex flex-wrap gap-3 mt-1 small">
                <span class="text-success fw-semibold">
                    Đã thanh toán: <?= e($fmtMoney(isset($summary['da_thanh_toan']) ? $summary['da_thanh_toan'] : 0)) ?> đ
                </span>
                <span class="text-warning-emphasis fw-semibold">
                    Chờ thanh toán: <?= e($fmtMoney(isset($summary['cho_thanh_toan']) ? $summary['cho_thanh_toan'] : 0)) ?> đ
                </span>
            </div>
        </div>
    </div>

    <div class="lms-pdv-row">
        <label class="lms-pdv-label" for="lmld-pdv-student">Chọn học viên</label>
        <div class="lms-pdv-control">
            <?php
            $studentOptions = array('' => 'Tất cả học viên');
            foreach ($students as $st) {
                $studentOptions[(string) (int) $st['user_id']] = (string) $st['full_name'];
            }
            lms_component('form/select', array(
                'name'    => 'student_id',
                'id'      => 'lmld-pdv-student',
                'options' => $studentOptions,
                'value'   => (string) $studentId,
                'bare'    => true,
                'class'   => 'form-select',
                'attrs'   => array('aria-label' => 'Chọn học viên'),
            ));
            ?>
        </div>
    </div>

    <div class="lms-pdv-row">
        <label class="lms-pdv-label">Chọn khoảng thời gian</label>
        <div class="lms-pdv-control">
            <div class="lms-pdv-dates">
                <?php lms_component('form/date_picker', array(
                    'name'     => 'date_from',
                    'id'       => 'lmld-pdv-from',
                    'value'    => $dateFrom,
                    'required' => true,
                    'bare'     => true,
                )); ?>
                <span class="lms-pdv-date-sep">-</span>
                <?php lms_component('form/date_picker', array(
                    'name'     => 'date_to',
                    'id'       => 'lmld-pdv-to',
                    'value'    => $dateTo,
                    'required' => true,
                    'bare'     => true,
                )); ?>
            </div>
        </div>
    </div>

    <div class="lms-pdv-actions">
        <?php lms_component('button', array(
            'label'   => 'Tìm kiếm',
            'type'    => 'submit',
            'variant' => 'primary',
            'icon'    => 'fa-solid fa-magnifying-glass',
        )); ?>
    </div>
</form>

<div class="table-responsive mt-4">
    <table class="table table-bordered align-middle text-center lms-table mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 18%;">Ngày</th>
                <th style="width: 18%;">Học viên</th>
                <th style="width: 15%;">Số tiền</th>
                <th>Nội dung</th>
                <th style="width: 16%;">Trạng thái</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        Chưa có giao dịch nào trong khoảng thời gian đã chọn.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $t): ?>
                    <?php
                    $amount   = (float) $t['amount'];
                    $txStatus = isset($t['status']) ? (string) $t['status'] : 'draft';
                    ?>
                    <tr>
                        <td class="text-nowrap"><?= e(lpdv_time_label($t)) ?></td>
                        <td><?= e(lpdv_student_label($t)) ?></td>
                        <td class="text-nowrap fw-semibold">
                            <?= $amount < 0 ? '- ' : '+' ?><?= e($fmtMoney(abs($amount))) ?>
                        </td>
                        <td class="text-wrap"><?= e(lpdv_description($t)) ?></td>
                        <td class="text-nowrap">
                            <?php if ($txStatus === 'paid'): ?>
                                <span class="badge bg-success">Đã thanh toán</span>
                                <?php if (!empty($t['paid_at'])): ?>
                                    <div class="small text-muted mt-1"><?= e(substr((string) $t['paid_at'], 0, 10)) ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Chờ thanh toán</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php lms_component('pagination', array(
    'base_url'    => lms_url($alias),
    'page'        => $page,
    'total_pages' => $totalPages,
    'params'      => array(
        'student_id' => $studentId > 0 ? (string) $studentId : '',
        'date_from'  => $dateFrom,
        'date_to'    => $dateTo,
    ),
)); ?>
