<?php
/**
 * Teacher — Tổng quan (bố cục theo mockup Tổng quan.png, Tổng quan - Thông báo.png).
 * Data do source chuẩn bị: $feeDemoted, $feeAfterDemote, $nextSessions,
 * $statusLabels, $myStudents, $summaryTask, $summaryTaskUrl, $gradingTask,
 * $ungradedTotal, $gradingTaskUrl, $recentNotifs.
 * Banner cảnh báo tick do layout render qua $layoutAlert (full-width dưới header).
 *
 * Chat widget là placeholder toàn cục trong layout (TODO: chưa build logic).
 */
$hasNotice = !empty($summaryTask) || !empty($gradingTask) || $feeDemoted || !empty($recentNotifs);
?>
<div class="row g-4">
    <!-- ===================== Cột giữa ===================== -->
    <div class="col-lg-8">
        <h1 class="lms-page-title">Buổi học sắp tới</h1>

        <?php if (empty($nextSessions)): ?>
            <div class="lms-ov-empty">Bạn không có buổi học nào sắp tới.</div>
        <?php else: ?>
            <?php foreach ($nextSessions as $s): ?>
                <?php
                $st = isset($s['status']) ? (string) $s['status'] : 'upcoming';
                $statusLabel = isset($statusLabels[$st]) ? $statusLabels[$st] : $st;
                ?>
                <a class="lms-ov-session" href="<?= e(lms_url('lms-lich-day', 'buoi', array('id' => (int) $s['id']))) ?>">
                    <span class="lms-ov-session-title"><?= e((string) $s['class_name']) ?></span>
                    <span class="lms-ov-session-meta">
                        <span>
                            <i class="fa-regular fa-calendar"></i>
                            <?= e(date('d/m/Y', strtotime((string) $s['session_date']))) ?>
                        </span>
                        <span>
                            <i class="fa-regular fa-clock"></i>
                            <?= e(substr((string) $s['start_time'], 0, 5)) ?> - <?= e(substr((string) $s['end_time'], 0, 5)) ?>
                        </span>
                        <span class="lms-ov-session-status"><?= e($statusLabel) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2 class="lms-page-title mt-4">Các học viên hiện tại</h2>

        <?php if (empty($myStudents)): ?>
            <div class="lms-ov-empty">Bạn chưa có học viên nào đang học.</div>
        <?php else: ?>
            <div class="lms-ov-student-grid">
                <?php foreach ($myStudents as $stu): ?>
                    <a class="lms-ov-student" href="<?= e(lms_url('lms-hoc-vien-cua-toi')) ?>">
                        <span class="lms-ov-student-avatar">
                            <i class="fa-solid fa-user-graduate"></i>
                        </span>
                        <span class="lms-ov-student-body">
                            <span class="lms-ov-student-name"><?= e((string) $stu['full_name']) ?></span>
                            <?php if (!empty($stu['schedules'])): ?>
                                <?php foreach ($stu['schedules'] as $slotLabel): ?>
                                    <span class="lms-ov-student-slot"><?= e($slotLabel) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="lms-ov-student-slot text-muted">Chưa có lịch</span>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===================== Cột phải: Thông báo ===================== -->
    <div class="col-lg-4">
        <h2 class="lms-page-title">Thông báo</h2>

        <div class="lms-ov-notice-list">
            <?php if (!$hasNotice): ?>
                <div class="lms-ov-empty">Chưa có thông báo.</div>
            <?php else: ?>
                <?php if (!empty($summaryTask) && is_array($summaryTask)): ?>
                    <div class="lms-ov-notice">
                        <h3 class="lms-ov-notice-title">Admin</h3>
                        <p class="lms-ov-notice-text">
                            Bạn chưa ghi báo cáo buổi học lớp <strong><?= e((string) $summaryTask['class_name']) ?></strong>
                            ngày <?= e(date('d/m/Y', strtotime((string) $summaryTask['session_date']))) ?>.
                            Hãy cập nhật để tránh bị tick.
                        </p>
                        <?php lms_component('button', array(
                            'label'   => 'Ghi báo cáo buổi học',
                            'href'    => $summaryTaskUrl,
                            'variant' => 'primary',
                            'size'    => 'md',
                        )); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($gradingTask) && is_array($gradingTask) && $ungradedTotal > 0): ?>
                    <div class="lms-ov-notice">
                        <h3 class="lms-ov-notice-title"><?= e((string) $gradingTask['class_name']) ?></h3>
                        <p class="lms-ov-notice-text">
                            Đã nộp BTVN - Chấm bài ngay!
                            <span class="d-block small text-muted"><?= e((string) $ungradedTotal) ?> bài đang chờ chấm</span>
                        </p>
                        <?php lms_component('button', array(
                            'label'   => 'Chấm bài',
                            'href'    => $gradingTaskUrl,
                            'variant' => 'primary',
                            'size'    => 'md',
                        )); ?>
                    </div>
                <?php endif; ?>

                <?php if ($feeDemoted): ?>
                    <div class="lms-ov-notice">
                        <h3 class="lms-ov-notice-title">Admin</h3>
                        <p class="lms-ov-notice-text">
                            Bạn đã bị hạ phí dịch vụ<?php if ($feeAfterDemote > 0): ?> (áp dụng mức
                                <?= e(rtrim(rtrim(number_format($feeAfterDemote, 2, ',', '.'), '0'), ',')) ?>%
                                từ tháng chốt lương tiếp theo)<?php endif; ?>.
                        </p>
                    </div>
                <?php endif; ?>

                <?php foreach ($recentNotifs as $n): ?>
                    <div class="lms-ov-notice">
                        <h3 class="lms-ov-notice-title"><?= e((string) $n['title']) ?></h3>
                        <?php if (!empty($n['message'])): ?>
                            <p class="lms-ov-notice-text"><?= e((string) $n['message']) ?></p>
                        <?php endif; ?>
                        <span class="lms-ov-notice-meta">
                            <?= e(date('H:i d/m/Y', strtotime((string) $n['created_at']))) ?>
                            · <a href="<?= e(lms_url('lms-thong-bao')) ?>">Xem thông báo</a>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
