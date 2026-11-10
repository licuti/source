<?php
/**
 * Admin LMS — Chi tiết kết quả test (mở từ tab Duyệt KQ test).
 * Mockup: Tai-lieu-phat-trien/admin-ver2/chi tiết kq test.png.
 * Tham khảo HTML hiển thị tệp & upload từ: buoi_tpl.php (lms/app/student/templates/lms-lich-hoc/buoi_tpl.php).
 *
 * Data do source chuẩn bị: $result (head, tests), $ctx, $canEdit, $canDelete, $alias.
 * PHP 7.3 compatible.
 */
$head = $result['head'];
$tests = $result['tests'];

$testLabels = \Lms\AdminRequestService::TEST_TYPES;

$done = (int) $head['done_sessions'];
$max  = $head['max_sessions'];
$progress = $max === null ? (string) $done : $done . '/' . $max;

$ctxParams = array(
    'class_id'   => (int) $ctx['class_id'],
    'student_id' => (int) $ctx['student_id'],
    'topic_id'   => (int) $ctx['topic_id'],
);

// Sắp xếp 'test' lên trước 'minitest' để khớp thứ tự logic
if (!empty($tests) && is_array($tests)) {
    usort($tests, function ($a, $b) {
        $order = array('test' => 1, 'minitest' => 2);
        $oa = isset($order[$a['test_type']]) ? $order[$a['test_type']] : 99;
        $ob = isset($order[$b['test_type']]) ? $order[$b['test_type']] : 99;
        if ($oa !== $ob) {
            return $oa - $ob;
        }
        return $a['id'] - $b['id'];
    });
}
?>

<!-- ===================== Box 1: Thông tin chuyên đề, Học sinh, Giáo viên ===================== -->
<div class="lms-panel mb-4">
    <div class="lms-panel-body py-3 px-4">
        <div class="row align-items-center">
            <div class="col-md-4">
                <h5 class="mb-1 fw-bold text-dark"><?= e((string) $head['topic_name']) ?></h5>
                <div class="text-danger fw-semibold">
                    Đã dạy: <?= e($progress) ?> buổi
                </div>
            </div>
            <div class="col-md-4 text-md-center mt-2 mt-md-0">
                <span class="fw-bold text-dark fs-5">
                    <?= e(trim((string) $head['student_code'] . ' - ' . (string) $head['student_name'], ' -')) ?>
                </span>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <span class="fw-bold text-dark fs-5">
                    <?= e(trim((string) $head['teacher_code'] . ' - ' . (string) $head['teacher_name'], ' -')) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<?php if (empty($tests)): ?>
    <div class="alert alert-info mb-0">Chưa có bài test/minitest cho chuyên đề này.</div>
<?php endif; ?>

<!-- ===================== Accordion Cards: Bài test & Minitest ===================== -->
<?php foreach ($tests as $t): ?>
    <?php
    $typeLabel    = isset($testLabels[$t['test_type']]) ? $testLabels[$t['test_type']] : (string) $t['test_type'];
    $secTitle     = ($t['test_type'] === 'minitest') ? 'Minitest chuyên đề' : 'Bài test chuyên đề';
    $hasSub       = !empty($t['submission_id']);
    $graded       = !empty($t['graded_at']);
    $maxScore     = !empty($t['max_score']) ? (float) $t['max_score'] : 20.0;
    $collapseId   = 'collapseTest_' . (int) $t['id'];
    $testFile     = !empty($t['test_file_url']) ? (string) $t['test_file_url'] : '';
    $templateFile = !empty($t['lesson_summary_url']) ? (string) $t['lesson_summary_url'] : $testFile;

    $submittedFiles = (!empty($t['submission_files']) && is_array($t['submission_files']))
        ? $t['submission_files']
        : array();

    $feedbackFiles = (!empty($t['teacher_feedback_files']) && is_array($t['teacher_feedback_files']))
        ? $t['teacher_feedback_files']
        : array();

    // Format score displays
    $scoreXDisplay = '';
    if ($t['score_x'] !== null && $t['score_x'] !== '') {
        $valX = (float) $t['score_x'];
        $valXStr = (floor($valX) == $valX) ? (string) (int) $valX : (string) $valX;
        $maxStr  = (floor($maxScore) == $maxScore) ? (string) (int) $maxScore : (string) $maxScore;
        $scoreXDisplay = $valXStr . '/' . $maxStr;
    }

    $scoreYDisplay = '';
    if ($t['score_y_percent'] !== null && $t['score_y_percent'] !== '') {
        $valY = (float) $t['score_y_percent'];
        $valYStr = (floor($valY) == $valY) ? (string) (int) $valY : (string) $valY;
        $scoreYDisplay = $valYStr . '%';
    }
    ?>
    <div class="lms-panel lms-test-section mb-4" id="section-test-<?= (int) $t['id'] ?>">
        <!-- Card Header (Accordion toggle) -->
        <div class="lms-panel-head" data-bs-toggle="collapse"
             data-bs-target="#<?= e($collapseId) ?>" aria-expanded="true">
            <div class="lms-panel-head-main">
                <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
                <h5 class="lms-panel-title mb-0"><?= e($secTitle) ?></h5>
                <?php if ($testFile !== ''): ?>
                    <a href="<?= e(lms_upload_url($testFile)) ?>" target="_blank" rel="noopener"
                       class="lms-panel-file-link" onclick="event.stopPropagation();">
                        <span><?= e(basename($testFile)) ?></span>
                        <i class="fa-solid fa-download ms-1"></i>
                    </a>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($hasSub && $canEdit && !$graded): ?>
                    <?php lms_component('button', array(
                        'label'   => 'Xác nhận',
                        'type'    => 'submit',
                        'variant' => 'secondary',
                        'size'    => 'md',
                        'class'   => 'px-4',
                        'attrs'   => array(
                            'form'       => 'kq-form-' . (int) $t['id'],
                            'formaction' => lms_url($alias, 'xac-nhan-kq'),
                            'onclick'    => 'event.stopPropagation();',
                        ),
                    )); ?>
                <?php elseif ($graded): ?>
                    <?php lms_component('badge', array(
                        'label'   => 'Đã xác nhận',
                        'variant' => 'success',
                        'type'    => 'lms',
                        'icon'    => 'fa-solid fa-check',
                    )); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Card Body -->
        <div id="<?= e($collapseId) ?>" class="collapse show">
            <div class="lms-panel-body">
                <!-- Phần 1: Bài nộp của học sinh (tham khảo buoi_tpl.php dùng file_grid) -->
                <h6 class="fw-bold fs-5 mb-3">Bài nộp của học sinh</h6>

                <?php lms_component('file_grid', array(
                    'files' => $submittedFiles,
                    'empty' => 'Chưa có tệp bài nộp.',
                    'class' => 'mb-4',
                )); ?>

                <!-- Phần 2: Chấm điểm & tải lên file chấm (tham khảo buoi_tpl.php / form/file_upload) -->
                <?php if ($canEdit && $hasSub): ?>
                    <form method="post" action="<?= e(lms_url($alias, 'cap-nhat-kq')) ?>"
                          enctype="multipart/form-data" id="kq-form-<?= (int) $t['id'] ?>" class="mt-4 pt-3 border-top">
                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                        <input type="hidden" name="submission_id" value="<?= e((string) (int) $t['submission_id']) ?>">
                        <input type="hidden" name="class_id" value="<?= e((string) $ctxParams['class_id']) ?>">
                        <input type="hidden" name="student_id" value="<?= e((string) $ctxParams['student_id']) ?>">
                        <input type="hidden" name="topic_id" value="<?= e((string) $ctxParams['topic_id']) ?>">

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h6 class="fw-bold fs-5 mb-0">Chấm điểm</h6>
                            <?php if (!empty($templateFile)): ?>
                                <a href="<?= e(lms_upload_url((string) $templateFile)) ?>" target="_blank" rel="noopener"
                                   class="lms-panel-file-link">
                                    <span><?= e(basename((string) $templateFile)) ?></span>
                                    <i class="fa-solid fa-download ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <!-- 2 ô nhập điểm dùng lms_component('form/input') -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted" for="score-x-<?= (int) $t['id'] ?>">Điểm số (ví dụ: 15/20 hoặc 15)</label>
                                <?php lms_component('form/input', array(
                                    'name'        => 'score_x',
                                    'id'          => 'score-x-' . (int) $t['id'],
                                    'placeholder' => '15/20',
                                    'value'       => $scoreXDisplay,
                                    'bare'        => true,
                                )); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted" for="score-y-<?= (int) $t['id'] ?>">Tỷ lệ % (ví dụ: 75%)</label>
                                <?php lms_component('form/input', array(
                                    'name'        => 'score_y_percent',
                                    'id'          => 'score-y-' . (int) $t['id'],
                                    'placeholder' => '75%',
                                    'value'       => $scoreYDisplay,
                                    'bare'        => true,
                                )); ?>
                            </div>
                        </div>

                        <!-- Upload file chấm dùng lms_component('form/file_upload') theo buoi_tpl.php -->
                        <?php lms_component('form/file_upload', array(
                            'name'           => 'graded_files[]',
                            'id'             => 'graded_files_' . (int) $t['id'],
                            'label'          => 'File chấm / nhận xét (tùy chọn)',
                            'existing'       => $feedbackFiles,
                            'existing_label' => 'Đã tải lên',
                            'max_files'      => 4,
                            'max_size_mb'    => 20,
                            'hint'           => 'Kéo thả hoặc chọn tối đa 4 tệp · ≤ 20MB/tệp',
                            'browse_text'    => 'chọn file chấm',
                        )); ?>

                        <!-- Nút Cập nhật -->
                        <div class="text-center mt-4">
                            <?php lms_component('button', array(
                                'type'    => 'submit',
                                'label'   => 'Cập nhật',
                                'variant' => 'primary',
                                'size'    => 'md',
                                'class'   => 'px-5',
                            )); ?>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0 mt-3">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        <?= !$hasSub ? 'Học sinh chưa nộp bài test này.' : 'Bạn chỉ có quyền xem kết quả test.' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endforeach; ?>
