<?php
/**
 * Student — Tổng quan (view).
 * Data do source chuẩn bị: $classes, $upcoming, $notifications, $hwLabels.
 */
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="lms-stat-card">
            <span class="lms-stat-icon"><i class="fa-solid fa-school"></i></span>
            <div class="lms-stat-value"><?= e((string) number_format(count($classes))) ?></div>
            <div class="lms-stat-label">Lớp đang học</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="lms-stat-card">
            <span class="lms-stat-icon"><i class="fa-solid fa-calendar-days"></i></span>
            <div class="lms-stat-value"><?= e((string) number_format(count($upcoming))) ?></div>
            <div class="lms-stat-label">Buổi học sắp tới</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="lms-card">
            <div class="lms-card-header">
                <i class="fa-solid fa-calendar-days me-1"></i> Buổi học sắp tới
            </div>
            <div class="lms-card-body">
                <?php if (empty($upcoming)): ?>
                    <p class="text-muted mb-0">Bạn không có buổi học sắp tới.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="lms-table">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Giờ</th>
                                    <th>Lớp</th>
                                    <th>BTVN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $s): ?>
                                    <?php
                                    $hw = isset($s['hw_status']) ? (string) $s['hw_status'] : 'not_submitted';
                                    $hwLabel = isset($hwLabels[$hw]) ? $hwLabels[$hw] : array($hw, 'lms-badge-secondary');
                                    ?>
                                    <tr>
                                        <td><?= e(date('d/m/Y', strtotime((string) $s['session_date']))) ?></td>
                                        <td><?= e(substr((string) $s['start_time'], 0, 5)) ?>–<?= e(substr((string) $s['end_time'], 0, 5)) ?></td>
                                        <td><?= e((string) $s['class_name']) ?></td>
                                        <td><span class="lms-badge <?= e($hwLabel[1]) ?>"><?= e($hwLabel[0]) ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="lms-card mb-3">
            <div class="lms-card-header">
                <i class="fa-solid fa-bell me-1"></i> Thông báo
            </div>
            <div class="lms-card-body">
                <?php if (empty($notifications)): ?>
                    <p class="text-muted mb-0">Chưa có thông báo.</p>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="d-flex gap-2 py-2 border-bottom">
                            <i class="fa-solid fa-circle-info text-secondary mt-1"></i>
                            <div>
                                <div class="fw-semibold" style="font-size:.9rem;"><?= e((string) $n['title']) ?></div>
                                <div class="text-muted" style="font-size:.82rem;"><?= e((string) $n['message']) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="lms-card">
            <div class="lms-card-header">
                <i class="fa-solid fa-book-open me-1"></i> Các khoá học đang học
            </div>
            <div class="lms-card-body">
                <?php if (empty($classes)): ?>
                    <p class="text-muted mb-0">Bạn chưa được xếp vào lớp nào.</p>
                <?php else: ?>
                    <?php foreach ($classes as $c): ?>
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <div class="fw-semibold" style="font-size:.92rem;"><?= e((string) $c['name']) ?></div>
                                <div class="text-muted" style="font-size:.8rem;">
                                    <?= e((string) $c['subject_name']) ?> · GV <?= e((string) $c['teacher_name']) ?>
                                </div>
                            </div>
                            <span class="lms-badge lms-badge-primary">
                                <?= e((string) number_format((int) $c['remaining_sessions'])) ?>
                                / <?= e((string) number_format((int) $c['total_sessions'])) ?> buổi
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
