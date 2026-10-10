<?php
/**
 * Teacher — Tổng quan (view).
 * Data do source chuẩn bị: $warnMessage, $weekSessions, $weekdays, $statusLabels.
 */
?>
<?php if ($warnMessage !== ''): ?>
    <div class="lms-alert-banner">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span><?= $warnMessage /* đã escape số liệu, không có HTML user */ ?></span>
        <button type="button" class="btn-close-lms" aria-label="Đóng">&times;</button>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="lms-stat-card">
            <span class="lms-stat-icon"><i class="fa-solid fa-school"></i></span>
            <div class="lms-stat-value"><?= e((string) number_format($classCount)) ?></div>
            <div class="lms-stat-label">Lớp đang dạy</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="lms-stat-card">
            <span class="lms-stat-icon"><i class="fa-solid fa-triangle-exclamation"></i></span>
            <div class="lms-stat-value"><?= e((string) number_format($tickCount)) ?></div>
            <div class="lms-stat-label">Tick trong tháng</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="lms-stat-card">
            <span class="lms-stat-icon"><i class="fa-solid fa-calendar-days"></i></span>
            <div class="lms-stat-value"><?= e((string) number_format(count($weekSessions))) ?></div>
            <div class="lms-stat-label">Buổi dạy trong tuần</div>
        </div>
    </div>
</div>

<div class="lms-card">
    <div class="lms-card-header">
        <i class="fa-solid fa-calendar-days me-1"></i> Lịch dạy tuần này
        <small class="text-muted fw-normal ms-2">(<?= e($weekStart) ?> → <?= e($weekEnd) ?>)</small>
    </div>
    <div class="lms-card-body">
        <?php if (empty($weekSessions)): ?>
            <p class="text-muted mb-0">Bạn không có buổi dạy nào trong tuần này.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Ngày</th>
                            <th>Thứ</th>
                            <th>Giờ</th>
                            <th>Lớp</th>
                            <th>Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($weekSessions as $s): ?>
                            <?php
                            $dow = (int) date('N', strtotime((string) $s['session_date'])) + 1;
                            $st = isset($s['status']) ? (string) $s['status'] : 'upcoming';
                            $label = isset($statusLabels[$st]) ? $statusLabels[$st] : array($st, 'lms-badge-secondary');
                            ?>
                            <tr>
                                <td><?= e(date('d/m/Y', strtotime((string) $s['session_date']))) ?></td>
                                <td><?= e(isset($weekdays[$dow]) ? $weekdays[$dow] : '') ?></td>
                                <td><?= e(substr((string) $s['start_time'], 0, 5)) ?>–<?= e(substr((string) $s['end_time'], 0, 5)) ?></td>
                                <td><?= e((string) $s['class_name']) ?>
                                    <?php if ((string) $s['class_type'] === 'group'): ?>
                                        <span class="lms-badge lms-badge-primary ms-1">Nhóm</span>
                                    <?php else: ?>
                                        <span class="lms-badge lms-badge-secondary ms-1">1-1</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="lms-badge <?= e($label[1]) ?>"><?= e($label[0]) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
