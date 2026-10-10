<?php
/**
 * Partial: section Test/Minitest của 1 chuyên đề (mockup §6 + §8).
 * Biến dùng: $t (row test + submission), $csrf, $alias.
 * Include từ chuyen-de_tpl.php trong scope foreach.
 */
$typeLabel = (string) $t['test_type'] === 'minitest' ? 'minitest' : 'test';
$isPublished = (string) $t['status'] === 'published';
?>
<div class="lms-test-card">
    <div class="lms-test-card-head">
        <strong><?= e((string) $t['title'] !== '' ? (string) $t['title'] : ('Bài ' . $typeLabel)) ?></strong>
        <span class="lms-badge <?= $isPublished ? 'lms-badge-success' : 'lms-badge-warning' ?>">
            <?= e(isset(\Lms\StudentCourseService::TEST_STATUS[(string) $t['status']]) ? \Lms\StudentCourseService::TEST_STATUS[(string) $t['status']] : (string) $t['status']) ?>
        </span>
    </div>
    <div class="lms-test-card-body">
        <?php if (!empty($t['test_file_url'])): ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Đề bài:</span>
                <a href="<?= e(lms_upload_url((string) $t['test_file_url'])) ?>" target="_blank" rel="noopener">
                    <i class="fa-solid fa-download me-1"></i><?= e(basename((string) $t['test_file_url'])) ?>
                </a>
            </div>
        <?php endif; ?>

        <?php if (!empty($t['deadline_label'])): ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Hạn nộp:</span>
                <span class="lms-hw-due<?= $t['deadline_overdue'] ? ' lms-hw-due-over' : '' ?>">
                    <?= e($t['deadline_label']) ?>
                    <?php if (!empty($t['deadline_text'])): ?>
                        <em>(<?= e($t['deadline_text']) ?>)</em>
                    <?php endif; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if (!empty($t['submission_files'])): ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Bài đã nộp:</span>
                <span class="lms-file-list">
                    <?php foreach ($t['submission_files'] as $f): ?>
                        <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener">
                            <i class="fa-solid fa-file me-1"></i><?= e(basename((string) $f)) ?>
                        </a>
                    <?php endforeach; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if (!empty($t['graded_at'])): ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Kết quả:</span>
                <span class="lms-badge lms-badge-success">
                    Đã chấm: <?= e(number_format((float) $t['score_x'], 2)) ?>/<?= e(number_format((float) $t['max_score'], 2)) ?>
                    (<?= e(number_format((float) $t['score_y_percent'], 1)) ?>%)
                </span>
            </div>
        <?php elseif (!empty($t['submitted_at'])): ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Trạng thái:</span>
                <span class="lms-badge <?= e($t['sub_status_label'][1]) ?>"><?= e($t['sub_status_label'][0]) ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($t['teacher_feedback_files'])): ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">File chấm của GV:</span>
                <span class="lms-file-list">
                    <?php foreach ($t['teacher_feedback_files'] as $f): ?>
                        <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener">
                            <i class="fa-solid fa-file-lines me-1"></i><?= e(basename((string) $f)) ?>
                        </a>
                    <?php endforeach; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($isPublished && empty($t['graded_at'])): ?>
            <form method="post" action="<?= e(lms_url($alias, 'nop-test')) ?>"
                  class="lms-form lms-submit-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="test_id" value="<?= e((int) $t['id']) ?>">
                <div class="lms-upload-slots">
                    <?php for ($i = 0; $i < 4; $i++): ?>
                        <input type="file" name="test_files[]" class="form-control form-control-sm"
                               accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.docx,.xlsx,.pptx,.mp3,.mp4,.zip">
                    <?php endfor; ?>
                </div>
                <div class="lms-form-actions">
                    <button type="submit" class="btn btn-lms btn-sm">
                        <i class="fa-solid fa-file-upload me-1"></i> Nộp bài
                    </button>
                    <small class="text-muted">Tối đa 4 file.</small>
                </div>
            </form>
        <?php elseif ((string) $t['test_type'] === 'minitest' && !$isPublished): ?>
            <p class="lms-hw-note text-danger mb-0" style="font-size:.85rem;">
                <em>* Admin chưa duyệt minitest này nên bạn chưa nộp bài được.</em>
            </p>
        <?php endif; ?>
    </div>
</div>
