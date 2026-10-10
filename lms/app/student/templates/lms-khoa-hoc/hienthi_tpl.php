<?php
/**
 * Student — Các khoá học (view, mockup §3.1).
 * Data do source chuẩn bị: $groups (map subject => array(class rows)).
 */
?>
<?php if (empty($groups)): ?>
    <div class="lms-card">
        <div class="lms-card-body text-center py-5">
            <i class="fa-solid fa-book-open" style="font-size:2.4rem;color:#F26B2A;"></i>
            <p class="text-muted mt-3 mb-0">Bạn chưa được xếp vào lớp nào. Vui lòng liên hệ trung tâm để được sắp xếp lịch học.</p>
        </div>
    </div>
<?php else: ?>
    <?php foreach ($groups as $subject => $classes): ?>
        <div class="lms-card lms-subject-group mb-3">
            <div class="lms-card-header lms-subject-header" data-bs-toggle="collapse"
                 href="#lmkh-group-<?= e(md5($subject)) ?>" role="button" aria-expanded="true">
                <i class="fa-solid fa-folder-open me-1"></i> <?= e($subject) ?>
                <span class="lms-subject-count"><?= e((string) count($classes)) ?> lớp</span>
                <i class="fa-solid fa-chevron-down lms-subject-chevron"></i>
            </div>
            <div class="collapse show" id="lmkh-group-<?= e(md5($subject)) ?>">
                <div class="lms-card-body">
                    <div class="row g-3">
                        <?php foreach ($classes as $c): ?>
                            <div class="col-md-6 col-xl-4">
                                <a class="lms-class-card" href="<?= e(lms_url($alias, 'lop', array('class_id' => (int) $c['id']))) ?>">
                                    <div class="lms-class-card-head">
                                        <span class="lms-class-card-name"><?= e((string) $c['name']) ?></span>
                                        <span class="lms-badge lms-badge-primary"><?= e((string) $c['class_code']) ?></span>
                                    </div>
                                    <div class="lms-class-card-meta">
                                        <span><i class="fa-solid fa-book me-1"></i><?= e((string) $c['subject_name']) ?></span>
                                        <span><i class="fa-solid fa-chalkboard-user me-1"></i>GV <?= e((string) $c['teacher_name']) ?></span>
                                    </div>
                                    <div class="lms-class-card-meta">
                                        <span>Đã học <?= e((string) number_format((int) $c['done_sessions'])) ?>
                                            / <?= e((string) number_format((int) $c['total_sessions'])) ?> buổi</span>
                                    </div>
                                    <div class="lms-progress">
                                        <div class="lms-progress-bar" style="width: <?= e((int) $c['progress_percent']) ?>%"></div>
                                    </div>
                                    <div class="lms-class-card-foot">
                                        <span class="lms-progress-percent"><?= e((string) number_format((int) $c['progress_percent'])) ?>% hoàn thành</span>
                                        <span class="lms-class-card-go">Xem lộ trình <i class="fa-solid fa-arrow-right ms-1"></i></span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
