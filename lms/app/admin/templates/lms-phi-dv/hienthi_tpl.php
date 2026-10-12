<?php
/**
 * Admin LMS — Thống kê phí dịch vụ: view danh sách GV × tháng (ADM-07).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page, $filterMonth,
 * $canEdit, $alias.
 */
?>
<?php
$fmt = function ($v) {
    return number_format((float) $v, 0, ',', '.');
};
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-money-bill-wave me-1"></i> Thống kê phí dịch vụ tháng <?= e($filterMonth) ?>
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?> giáo viên</span>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-6 col-md-3">
                <input type="month" class="form-control" name="month" value="<?= e($filterMonth) ?>"
                       aria-label="Chọn tháng">
            </div>
            <div class="col-sm-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Xem
                </button>
                <a href="<?= e(lms_url($alias, 'xuat', array('month' => $filterMonth))) ?>"
                   class="btn btn-success">
                    <i class="fa-solid fa-file-excel me-1"></i> Xuất Excel
                </a>
            </div>
            <div class="col-12">
                <p class="lms-text-muted-sm mb-0">
                    <i class="fa-solid fa-circle-info me-1"></i>
                    Chốt lương tháng bằng cron <code>lms_payroll_cron.php</code> (ngày 1 hàng tháng).
                    Chỉ tính buổi đã kết thúc và không còn bài tập chưa chấm.
                </p>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-money-bill-wave d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có dữ liệu phí dịch vụ tháng <?= e($filterMonth) ?>.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Giáo viên</th>
                            <th class="text-end">Tổng thu</th>
                            <th class="text-end">Tổng phạt</th>
                            <th class="text-end">Thưởng</th>
                            <th class="text-end">Thuế TNCN</th>
                            <th class="text-end">Thực nhận</th>
                            <th>Trạng thái</th>
                            <th class="text-end">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) ($r['teacher_code'] ?? '')) ?></strong>
                                    <?= !empty($r['full_name']) ? '— ' . e((string) $r['full_name']) : '' ?>
                                </td>
                                <td class="text-end"><?= e($fmt($r['tong_thu'])) ?>đ</td>
                                <td class="text-end text-danger"><?= e($fmt($r['tong_phat'])) ?>đ</td>
                                <td class="text-end text-success"><?= e($fmt($r['tong_thuong'])) ?>đ</td>
                                <td class="text-end"><?= e($fmt($r['thue'])) ?>đ</td>
                                <td class="text-end"><strong><?= e($fmt($r['thuc_nhan'])) ?>đ</strong></td>
                                <td>
                                    <?php if ($r['status'] !== ''): ?>
                                        <span class="lms-badge <?= e(lpd_status_badge($r['status'])) ?>">
                                            <?= e(lpd_status_label($r['status'])) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(lms_url($alias, 'chi-tiet', array('teacher_id' => (int) $r['teacher_id'], 'month' => $filterMonth))) ?>"
                                       class="btn btn-sm btn-outline-lms">
                                        <i class="fa-solid fa-eye me-1"></i> Chi tiết
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
                array('month' => $filterMonth)
            ) ?>
        <?php endif; ?>
    </div>
</div>
