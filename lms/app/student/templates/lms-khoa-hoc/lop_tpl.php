<?php
/**
 * Student — Chi tiết lớp + lộ trình chuyên đề (view, mockup §5 + §3.2).
 * Data do source chuẩn bị: $class, $topics, $tests, $alias.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<div class="lms-card lms-class-detail">
    <div class="lms-card-header">
        <a href="<?= e(lms_url($alias)) ?>" class="lms-back-link">
            <i class="fa-solid fa-arrow-left me-1"></i> Các khoá học
        </a>
        <strong class="ms-2"><?= e((string) $class['name']) ?></strong>
        <span class="lms-badge lms-badge-primary ms-2">Lộ trình</span>
    </div>
    <div class="lms-card-body">
        <div class="lms-class-detail-meta">
            <span><i class="fa-solid fa-book me-1"></i>Môn <?= e((string) $class['subject_name']) ?></span>
            <span><i class="fa-solid fa-chalkboard-user me-1"></i>GV <?= e((string) $class['teacher_name']) ?></span>
            <span><i class="fa-solid fa-hourglass-half me-1"></i>Đã học
                <?= e((string) number_format((int) $class['done_sessions'])) ?>
                / <?= e((string) number_format((int) $class['total_sessions'])) ?> buổi</span>
        </div>
        <div class="lms-progress lms-progress-lg mb-2">
            <div class="lms-progress-bar" style="width: <?= e((int) $class['progress_percent']) ?>%"></div>
        </div>
        <div class="text-muted" style="font-size:.85rem;">
            Tiến độ hoàn thành: <strong><?= e((string) number_format((int) $class['progress_percent'])) ?>%</strong>
        </div>
    </div>
</div>

<div class="lms-card mt-3">
    <div class="lms-card-header">
        <i class="fa-solid fa-list-check me-1"></i> Danh sách chuyên đề
    </div>
    <div class="lms-card-body">
        <?php if (empty($topics)): ?>
            <p class="text-muted mb-0">Chưa có chuyên đề nào cho môn học này.</p>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($topics as $t): ?>
                    <?php
                    $max = $t['max_sessions'];
                    $doneText = $max === null
                        ? 'Đã dạy: ' . (int) $t['done_sessions'] . ' buổi'
                        : 'Đã dạy: ' . (int) $t['done_sessions'] . '/' . (int) $max . ' buổi';
                    $testScore = $t['best_test_score'];
                    $minitestScore = $t['best_minitest_score'];
                    ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="lms-topic-card">
                            <div class="lms-topic-card-head">
                                <a class="lms-topic-card-name" href="<?= e(lms_url($alias, 'chuyen-de', array('class_id' => (int) $class['id'], 'topic_id' => (int) $t['id']))) ?>">
                                    <?= e((string) $t['name']) ?>
                                </a>
                            </div>
                            <div class="lms-topic-card-status<?= ($max !== null && (int) $t['done_sessions'] >= (int) $max) ? ' lms-topic-done' : '' ?>">
                                <?= e($doneText) ?>
                            </div>
                            <div class="lms-topic-card-scores">
                                <span class="lms-badge lms-badge-secondary">Điểm test: <?= $testScore === null ? '-- %' : e(number_format($testScore, 1)) . ' %' ?></span>
                                <span class="lms-badge lms-badge-secondary">Minitest: <?= $minitestScore === null ? '-- %' : e(number_format($minitestScore, 1)) . ' %' ?></span>
                            </div>
                            <?php if (!empty($t['has_test']) || !empty($t['has_minitest'])): ?>
                                <div class="lms-topic-card-actions">
                                    <?php if ($t['test_ready']): ?>
                                        <a class="btn btn-lms btn-sm" href="<?= e(lms_url($alias, 'chuyen-de', array('class_id' => (int) $class['id'], 'topic_id' => (int) $t['id']))) ?>">
                                            <i class="fa-solid fa-file-upload me-1"></i> Nộp test
                                        </a>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-lms" disabled
                                                title="Chỉ nộp test khi đã đủ số buổi học của chuyên đề">
                                            <i class="fa-solid fa-file-upload me-1"></i> Nộp test
                                        </button>
                                        <small class="text-muted d-block mt-1" style="font-size:.75rem;">
                                            Chưa đủ số buổi để nộp test
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
