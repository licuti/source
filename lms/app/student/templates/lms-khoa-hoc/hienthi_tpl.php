<?php
/**
 * Student — Các khoá học của tôi (mockup student-ver2).
 * Dùng chung card .lmnx-card với trang Nhận xét tháng.
 * Data do source chuẩn bị: $classes, $alias.
 */
?>
<div class="lmnx-page-head">
    <h1 class="lms-page-title mb-0">Các khoá học của tôi</h1>
    <a class="lmnx-view-all" href="<?= e(lms_url($alias)) ?>">
        Xem tất cả <i class="fa-solid fa-chevron-right"></i>
    </a>
</div>

<?php if (empty($classes)): ?>
    <div class="lms-ov-empty">Bạn chưa được xếp vào lớp nào. Vui lòng liên hệ trung tâm để được sắp xếp lịch học.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($classes as $class): ?>
            <?php
            $cover = lms_subject_cover_url(isset($class['subject_name']) ? $class['subject_name'] : '');
            $grade = trim((string) $class['grade']);
            ?>
            <div class="col-lg-6">
                <a class="lms-card lmnx-card lmnx-card-link"
                   href="<?= e(lms_url($alias, 'lop', array('class_id' => (int) $class['id']))) ?>"
                   aria-label="Mở khoá học <?= e((string) $class['name']) ?>">
                    <div class="lmnx-card-head">
                        <?php if ($cover !== ''): ?>
                            <div class="lmnx-cover">
                                <img src="<?= e($cover) ?>" alt="<?= e((string) $class['subject_name']) ?>">
                            </div>
                        <?php else: ?>
                            <div class="lmnx-cover lmnx-cover-fallback">
                                <i class="fa-solid fa-book-open"></i>
                            </div>
                        <?php endif; ?>

                        <div class="lmnx-summary">
                            <div class="lmnx-class-name"><?= e((string) $class['name']) ?></div>
                            <?php if ($grade !== ''): ?>
                                <div class="lmnx-grade"><?= e($grade) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($class['schedule_rows'])): ?>
                                <div class="lmnx-schedules">
                                    <?php foreach ($class['schedule_rows'] as $slot): ?>
                                        <div class="lmnx-schedule">
                                            <i class="fa-regular fa-calendar"></i>
                                            <span class="lmnx-schedule-day"><?= e((string) $slot['day']) ?></span>
                                            <span class="lmnx-schedule-time"><?= e((string) $slot['time']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="lmnx-schedule-empty">Chưa có lịch cố định</div>
                            <?php endif; ?>

                            <div class="lmnx-card-status <?= e((string) $class['hw_class']) ?>">
                                <?= e((string) $class['hw_label']) ?>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
