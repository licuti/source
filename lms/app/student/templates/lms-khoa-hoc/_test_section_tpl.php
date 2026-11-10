<?php
/**
 * Partial: Card Test / Minitest của 1 chuyên đề (Student portal).
 * Tham khảo thiết kế: Tai-lieu-phat-trien/student-ver2/Trang Chi tiết Nộp test.png
 *
 * Variables: $t (test row or placeholder array), $topic, $csrf, $alias, $type ('test'|'minitest').
 * PHP 7.3 compatible.
 */

$testType = isset($type) ? $type : (!empty($t['test_type']) ? (string) $t['test_type'] : 'test');
$isMini = ($testType === 'minitest');
$sectionTitle = $isMini ? 'Minitest chuyên đề' : 'Bài test chuyên đề';
$collapseId = 'collapseTestSection_' . $testType;

$testFileUrl = '';
if (!empty($t['test_file_url'])) {
    $testFileUrl = (string) $t['test_file_url'];
} elseif ($isMini && !empty($topic['minitest_file_url'])) {
    $testFileUrl = (string) $topic['minitest_file_url'];
} elseif (!$isMini && !empty($topic['test_file_url'])) {
    $testFileUrl = (string) $topic['test_file_url'];
}

$isGraded = (!empty($t) && !empty($t['graded_at']));
$isSubmitted = (!empty($t) && !empty($t['submitted_at']));
$isPublished = (!empty($t) && isset($t['status']) && (string) $t['status'] === 'published');

$submittedFiles = (!empty($t) && !empty($t['submission_files']) && is_array($t['submission_files']))
    ? $t['submission_files']
    : array();

$feedbackFiles = (!empty($t) && !empty($t['teacher_feedback_files']) && is_array($t['teacher_feedback_files']))
    ? $t['teacher_feedback_files']
    : array();

$formId = 'formSubmitTest_' . $testType;
?>

<div class="lms-panel lms-test-section mb-4" id="section-<?= e($testType) ?>">
    <!-- Accordion Header -->
    <div class="lms-panel-head" data-bs-toggle="collapse"
         data-bs-target="#<?= e($collapseId) ?>" aria-expanded="true">
        <div class="lms-panel-head-main">
            <i class="fa-solid fa-chevron-up lms-test-chevron"></i>
            <h5 class="lms-panel-title mb-0"><?= e($sectionTitle) ?></h5>
            <?php if ($testFileUrl !== ''): ?>
                <a href="<?= e(lms_upload_url($testFileUrl)) ?>" target="_blank" rel="noopener"
                   class="lms-panel-file-link" onclick="event.stopPropagation();">
                    <span><?= e(basename($testFileUrl)) ?></span>
                    <i class="fa-solid fa-download ms-1"></i>
                </a>
            <?php endif; ?>
        </div>
        <div class="lms-test-header-status">
            <?php if ($isGraded): ?>
                <span class="lms-badge lms-badge-success">Đã chấm</span>
            <?php elseif ($isSubmitted): ?>
                <span class="lms-badge lms-badge-success">Đã nộp bài</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Accordion Body -->
    <div id="<?= e($collapseId) ?>" class="collapse show">
        <div class="lms-panel-body">
            <h6 class="fw-bold fs-5 mb-3">Bài nộp của học sinh</h6>

            <?php if ($isGraded): ?>
                <?php lms_component('file_grid', array(
                    'files' => $submittedFiles,
                    'empty' => 'Chưa có tệp bài nộp.',
                )); ?>
            <?php else: ?>
                <form id="<?= e($formId) ?>" method="post" action="<?= e(lms_url($alias, 'nop-test')) ?>"
                      enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <?php if (!empty($t['id'])): ?>
                        <input type="hidden" name="test_id" value="<?= e((int) $t['id']) ?>">
                    <?php endif; ?>

                    <?php lms_component('form/file_upload', array(
                        'name' => 'test_files[]',
                        'id' => 'test_files_' . $testType,
                        'existing' => $submittedFiles,
                        'max_files' => 5,
                        'required' => true,
                        'replace_hint' => $isSubmitted ? 'Nộp lại sẽ thay thế toàn bộ tệp đã nộp.' : '',
                        'browse_text' => 'chọn tệp bài làm',
                    )); ?>

                    <div class="lms-form-footer">
                        <div class="lms-form-note">
                            <?php if ($isSubmitted): ?>
                                <span class="text-success fw-semibold">Đã nộp bài.</span>
                                <?php if (!empty($t['deadline_label'])): ?>
                                    <span class="text-muted ms-1">Hạn nộp: <?= e($t['deadline_label']) ?></span>
                                <?php endif; ?>
                            <?php elseif (!$isPublished): ?>
                                <span class="text-danger fst-italic">
                                    *Admin không duyệt <?= $isMini ? 'minitest' : 'bài test' ?> thì học sinh không nộp bài được ở đây
                                </span>
                            <?php elseif (!empty($t['deadline_text'])): ?>
                                <span class="<?= !empty($t['deadline_overdue']) ? 'text-danger' : 'text-orange' ?> fst-italic">
                                    <?= e($t['deadline_text']) ?>
                                </span>
                            <?php elseif (!empty($t['deadline_label'])): ?>
                                <span class="text-muted">Hạn nộp: <?= e($t['deadline_label']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="lms-test-action">
                            <?php lms_component('button', array(
                                'type' => 'submit',
                                'label' => 'Nộp bài',
                                'variant' => 'primary',
                                'size' => 'md',
                                'class' => 'px-4' . (!$isPublished ? ' lms-btn-disabled' : ''),
                                'disabled' => !$isPublished,
                            )); ?>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Nhận xét của giáo viên -->
            <h6 class="fw-bold fs-5 mt-4 mb-3">Nhận xét của giáo viên</h6>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="border rounded-3 bg-white p-2">
                        <div class="small text-muted">Điểm số</div>
                        <div class="fw-bold">
                            <?= $isGraded ? (e(number_format((float) $t['score_x'], 1)) . '/' . e(number_format((float) $t['max_score'], 1))) : 'Chưa chấm' ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded-3 bg-white p-2">
                        <div class="small text-muted">Tỷ lệ</div>
                        <div class="fw-bold">
                            <?= $isGraded ? (e(number_format((float) $t['score_y_percent'], 1)) . '%') : '--%' ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($isGraded && !empty($feedbackFiles)): ?>
                <?php lms_component('file_grid', array('files' => $feedbackFiles)); ?>
            <?php else: ?>
                <div class="lms-file-grid-empty text-muted small">Chưa có file nhận xét đính kèm từ giáo viên.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
