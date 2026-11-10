<?php
/**
 * Student — Tổng quan (view theo mockup Trang chủ Student).
 * Dùng lại class layout của Teacher Tổng quan (.lms-ov-*).
 * Data do source chuẩn bị: $classes, $upcoming, $notifications.
 */
$nextSession = !empty($upcoming) ? $upcoming[0] : null;
?>
<div class="row g-4">
    <div class="col-lg-8">
        <h2 class="lms-page-title">Buổi học sắp tới</h2>

        <?php if ($nextSession === null): ?>
            <div class="lms-ov-empty">Bạn không có buổi học sắp tới.</div>
        <?php else: ?>
            <a class="lms-ov-session"
               href="<?= e(lms_url('lms-lich-hoc', 'buoi', array('id' => (int) $nextSession['id']))) ?>">
                <span class="lms-ov-session-title"><?= e((string) $nextSession['class_name']) ?></span>
                <span class="lms-ov-session-meta">
                    <span>
                        <i class="fa-regular fa-calendar"></i>
                        <?= e(date('d/m/Y', strtotime((string) $nextSession['session_date']))) ?>
                    </span>
                    <span>
                        <i class="fa-regular fa-clock"></i>
                        <?= e(substr((string) $nextSession['start_time'], 0, 5)) ?> - <?= e(substr((string) $nextSession['end_time'], 0, 5)) ?>
                    </span>
                    <span class="lms-ov-session-status">Sắp diễn ra</span>
                </span>
            </a>
        <?php endif; ?>

        <h2 class="lms-page-title mt-4">Các khoá học của tôi</h2>

        <?php if (empty($classes)): ?>
            <div class="lms-ov-empty">Bạn chưa được xếp vào lớp nào.</div>
        <?php else: ?>
            <div class="lms-ov-student-grid">
                <?php foreach ($classes as $class): ?>
                    <?php
                    $scheduleLines = !empty($class['schedule_lines'])
                        ? explode('|', (string) $class['schedule_lines'])
                        : array();
                    ?>
                    <a class="lms-ov-student"
                       href="<?= e(lms_url('lms-khoa-hoc', 'lop', array('class_id' => (int) $class['id']))) ?>">
                        <span class="lms-ov-student-avatar">
                            <i class="fa-solid fa-user-graduate"></i>
                        </span>
                        <span class="lms-ov-student-body">
                            <span class="lms-ov-student-name"><?= e((string) $class['name']) ?></span>
                            <?php if ($scheduleLines === array()): ?>
                                <span class="lms-ov-student-slot text-muted">Chưa có lịch</span>
                            <?php else: ?>
                                <?php foreach ($scheduleLines as $line): ?>
                                    <span class="lms-ov-student-slot"><?= e((string) $line) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <h2 class="lms-page-title">Thông báo</h2>

        <div class="lms-ov-notice-list">
            <?php if (empty($notifications)): ?>
                <div class="lms-ov-empty">Chưa có thông báo.</div>
            <?php else: ?>
                <?php foreach ($notifications as $notification): ?>
                    <?php
                    $notifUrl = !empty($notification['link_url'])
                        ? (string) $notification['link_url']
                        : lms_url('lms-thong-bao');
                    ?>
                    <div class="lms-ov-notice">
                        <h3 class="lms-ov-notice-title"><?= e((string) $notification['title']) ?></h3>
                        <p class="lms-ov-notice-text"><?= nl2br(e((string) $notification['message'])) ?></p>
                        <?php lms_component('button', array(
                            'label'   => 'Xem chi tiết',
                            'href'    => $notifUrl,
                            'variant' => 'primary',
                            'size'    => 'md',
                        )); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>