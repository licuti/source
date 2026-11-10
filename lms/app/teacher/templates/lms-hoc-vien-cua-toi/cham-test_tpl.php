<?php
/**
 * Teacher — Trang Chi tiết Chấm Test / Minitest.
 * Tham khảo thiết kế: Tai-lieu-phat-trien/teacher-ver2/Chi tiết Chấm Test.png
 *
 * Variables: $detail, $topic, $testsForTopic, $classId, $studentId, $cycleId, $topicId.
 * PHP 7.3 compatible.
 */
$csrf = lms_csrf_token();
$detailStudent = $detail['student'];
$detailClass = $detail['class'];

$testItem = isset($testsForTopic['test']) ? $testsForTopic['test'] : null;
$miniItem = isset($testsForTopic['minitest']) ? $testsForTopic['minitest'] : null;

if ($testItem === null) {
    $testItem = array(
        'test_type' => 'test',
        'status' => 'draft',
        'test_file_url' => isset($topic['test_file_url']) ? $topic['test_file_url'] : '',
        'submission_files' => array(),
    );
}
if ($miniItem === null) {
    $miniItem = array(
        'test_type' => 'minitest',
        'status' => 'draft',
        'test_file_url' => isset($topic['minitest_file_url']) ? $topic['minitest_file_url'] : '',
        'submission_files' => array(),
    );
}

$backUrl = lms_url('lms-hoc-vien-cua-toi', 'chi-tiet', array(
    'class_id' => $classId,
    'student_id' => $studentId,
    'cycle_id' => $cycleId,
));
?>

<div class="lms-teacher-test-grade-view">
    <!-- Header: Tên học viên + Link Xem tất cả -->
    <div class="lms-page-head">
        <h1 class="lms-page-title mb-0">
            <a href="<?= e($backUrl) ?>"><?= e((string) $detailStudent['full_name']) ?></a>
        </h1>
        <a href="<?= e($backUrl) ?>" class="lms-view-all-link">
            Xem tất cả <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </div>

    <!-- Breadcrumb danh mục -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item">Lộ trình</li>
            <li class="breadcrumb-item active" aria-current="page">
                <?= !empty($topic['subject_name']) ? e($topic['subject_name']) : 'Chuyên đề' ?>
            </li>
        </ol>
    </nav>

    <!-- ===================== Card 1: Thông tin chuyên đề & Mục tiêu then chốt ===================== -->
    <div class="lms-panel mb-4">
        <div class="lms-panel-body">
            <h4 class="lms-topic-headline fw-bold mb-1"><?= e((string) $topic['name']) ?></h4>
            <div class="lms-topic-sessions-count text-danger fw-semibold mb-3">
                <?php if ($topic['max_sessions'] === null): ?>
                    Đã dạy: <?= e((string) number_format((int) $topic['done_sessions'])) ?> buổi
                <?php else: ?>
                    Đã dạy: <?= e((string) number_format((int) $topic['done_sessions'])) ?> / <?= e((string) number_format((int) $topic['max_sessions'])) ?> buổi
                <?php endif; ?>
            </div>

            <div class="lms-topic-objectives-section">
                <div class="fw-bold mb-2">Mục tiêu then chốt</div>
                <div class="lms-objectives-box p-3">
                    <?php if (!empty($topic['content'])): ?>
                        <div class="lms-objectives-content"><?= nl2br(e((string) $topic['content'])) ?></div>
                    <?php else: ?>
                        <span class="text-muted fst-italic">Nội dung có sẵn.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php
    $sections = array(
        array('title' => 'Bài test chuyên đề', 'item' => $testItem, 'type' => 'test'),
        array('title' => 'Minitest chuyên đề', 'item' => $miniItem, 'type' => 'minitest'),
    );
    ?>

    <?php foreach ($sections as $sec): ?>
        <?php
        $secTitle = $sec['title'];
        $te = $sec['item'];
        $secType = $sec['type'];
        $isMini = ($secType === 'minitest');
        $collapseId = 'collapseTeacherGrade_' . $secType;

        $testFile = '';
        if (!empty($te['test_file_url'])) {
            $testFile = (string) $te['test_file_url'];
        } elseif ($isMini && !empty($topic['minitest_file_url'])) {
            $testFile = (string) $topic['minitest_file_url'];
        } elseif (!$isMini && !empty($topic['test_file_url'])) {
            $testFile = (string) $topic['test_file_url'];
        }

        $submittedFiles = (!empty($te['submission_files']) && is_array($te['submission_files']))
            ? $te['submission_files']
            : array();

        $feedbackFiles = (!empty($te['teacher_feedback_files']) && is_array($te['teacher_feedback_files']))
            ? $te['teacher_feedback_files']
            : array();

        $isGraded = !empty($te['graded_at']);
        $isTeacherGraded = isset($te['score_x']) && $te['score_x'] !== null && $te['score_x'] !== '';
        $hasTestId = !empty($te['id']);

        // Score values
        $scoreXVal = isset($te['score_x']) && $te['score_x'] !== null ? number_format((float) $te['score_x'], 1) : '';
        $maxScoreVal = !empty($te['max_score']) ? (int) $te['max_score'] : 20;
        $scoreDisplay = ($scoreXVal !== '') ? ($scoreXVal . '/' . $maxScoreVal) : '';
        $scoreYVal = isset($te['score_y_percent']) && $te['score_y_percent'] !== null
            ? (number_format((float) $te['score_y_percent'], 1) . '%')
            : '';

        // Hạn chấm label (chỉ hiện khi GV chưa chấm)
        $gradeDueLabel = '';
        $gradeOverdue = false;
        if (!$isGraded && !$isTeacherGraded && !empty($te['submitted_at'])) {
            $checkHours = 24;
            $dueTs = strtotime((string) $te['submitted_at'] . ' +' . $checkHours . ' hours');
            $diff = $dueTs - time();
            if ($diff < 0) {
                $gradeOverdue = true;
                $mins = (int) round(abs($diff) / 60);
                $gradeDueLabel = $mins >= 60
                    ? 'Hạn chấm: bạn đã chấm trễ ' . (int) round($mins / 60) . ' giờ'
                    : 'Hạn chấm: bạn đã chấm trễ ' . $mins . ' phút';
            } else {
                $hours = (int) round($diff / 3600);
                $gradeDueLabel = $hours >= 1
                    ? 'Hạn chấm: bạn còn ' . $hours . ' giờ để chấm'
                    : 'Hạn chấm: bạn còn ' . (int) round($diff / 60) . ' phút để chấm';
            }
        }
        ?>
        <div class="lms-panel lms-test-section mb-4" id="section-teacher-<?= e($secType) ?>">
            <!-- Accordion Header -->
            <div class="lms-panel-head" data-bs-toggle="collapse"
                 data-bs-target="#<?= e($collapseId) ?>" aria-expanded="true">
                <div class="lms-panel-head-main">
                    <i class="fa-solid fa-chevron-up lms-test-chevron"></i>
                    <h5 class="lms-panel-title mb-0"><?= e($secTitle) ?></h5>
                    <?php if ($testFile !== ''): ?>
                        <a href="<?= e(lms_upload_url($testFile)) ?>" target="_blank" rel="noopener"
                           class="lms-panel-file-link" onclick="event.stopPropagation();">
                            <span><?= e(basename($testFile)) ?></span>
                            <i class="fa-solid fa-download ms-1"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="lms-test-header-status">
                    <?php if ($isGraded): ?>
                        <span class="lms-badge lms-badge-success">Đã chấm điểm</span>
                    <?php elseif ($isTeacherGraded): ?>
                        <span class="lms-badge lms-badge-info">Đã chấm - Chờ Admin duyệt</span>
                    <?php elseif (!empty($te['submitted_at'])): ?>
                        <span class="lms-badge lms-badge-warning">Học sinh đã nộp</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accordion Body -->
            <div id="<?= e($collapseId) ?>" class="collapse show">
                <div class="lms-panel-body">
                    <!-- Phần 1: Bài nộp của học sinh -->
                    <h6 class="fw-bold fs-5 mb-3">Bài nộp của học sinh</h6>

                    <?php lms_component('file_grid', array(
                        'files' => $submittedFiles,
                        'empty' => 'Học sinh chưa nộp bài.',
                        'class' => 'mb-4',
                    )); ?>

                    <!-- Phần 2: Chấm điểm (Form GV chấm) -->
                    <form method="post" action="<?= e(lms_url('lms-hoc-vien-cua-toi', 'luu-cham-test')) ?>"
                          enctype="multipart/form-data" class="lms-teacher-grading-form mt-4 pt-3 border-top">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="class_id" value="<?= e((string) $classId) ?>">
                        <input type="hidden" name="student_id" value="<?= e((string) $studentId) ?>">
                        <input type="hidden" name="cycle_id" value="<?= e((string) $cycleId) ?>">
                        <input type="hidden" name="topic_id" value="<?= e((string) $topicId) ?>">
                        <?php if ($hasTestId): ?>
                            <input type="hidden" name="test_id" value="<?= e((int) $te['id']) ?>">
                        <?php endif; ?>

                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h6 class="fw-bold fs-5 mb-0">Chấm điểm</h6>
                            <?php if ($testFile !== ''): ?>
                                <a href="<?= e(lms_upload_url($testFile)) ?>" target="_blank" rel="noopener"
                                   class="lms-panel-file-link">
                                    <span><?= e(basename($testFile)) ?></span>
                                    <i class="fa-solid fa-download ms-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small text-muted" for="score_x_<?= e($secType) ?>">Điểm số (ví dụ: 15/20 hoặc 15)</label>
                                <?php lms_component('form/input', array(
                                    'name' => 'score_x',
                                    'id' => 'score_x_' . $secType,
                                    'placeholder' => '15/20',
                                    'value' => $scoreDisplay,
                                    'bare' => true,
                                )); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small text-muted" for="score_y_<?= e($secType) ?>">Tỷ lệ % (ví dụ: 75%)</label>
                                <?php lms_component('form/input', array(
                                    'name' => 'score_y_percent',
                                    'id' => 'score_y_' . $secType,
                                    'placeholder' => '75%',
                                    'value' => $scoreYVal,
                                    'bare' => true,
                                )); ?>
                            </div>
                        </div>

                        <!-- File nhận xét của GV -->
                        <?php lms_component('form/file_upload', array(
                            'name' => 'teacher_feedback_files[]',
                            'id' => 'teacher_feedback_' . $secType,
                            'label' => 'File chấm / nhận xét của giáo viên (tùy chọn)',
                            'existing' => $feedbackFiles,
                            'existing_label' => 'Đã gửi',
                            'max_files' => 4,
                            'max_size_mb' => 20,
                            'hint' => 'Kéo thả hoặc chọn tối đa 4 tệp · ≤ 20MB/tệp',
                            'browse_text' => 'chọn tệp nhận xét',
                        )); ?>

                        <!-- Bottom row: Hạn chấm + Nút GỬI -->
                        <div class="lms-form-footer pt-2">
                            <div class="lms-grade-due-notice">
                                <?php if ($isGraded): ?>
                                    <span class="text-success fw-medium">Đã chấm điểm hoàn tất (Admin đã xác nhận)</span>
                                <?php elseif ($isTeacherGraded): ?>
                                    <span class="text-primary fw-medium">Đã gửi kết quả chấm — Chờ Admin xác nhận</span>
                                <?php elseif (!empty($gradeDueLabel)): ?>
                                    <span class="text-danger fst-italic fw-medium"><?= e($gradeDueLabel) ?></span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php lms_component('button', array(
                                    'type' => 'submit',
                                    'label' => 'GỬI',
                                    'variant' => 'primary',
                                    'size' => 'md',
                                    'class' => 'px-5',
                                )); ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
