<?php
/**
 * Teacher — Chi tiết buổi học (Lesson Summary & BTVN + Chấm bài tập về nhà).
 * Tham khảo thiết kế: Tai-lieu-phat-trien/teacher-ver2/Trang Chi tiết buổi học.png
 *
 * Data do source chuẩn bị: $session, $students, $summaryLocked, $deadlineLabel,
 * $weekMonday, $canEdit, $headerTitle, $backUrl.
 *
 * PHP 7.3 compatible.
 */
?>
<?php
$csrf = lms_csrf_token();
$attachedFiles = \Lms\UserService::decodeJsonArray(
    isset($session['hw_attached_files']) ? $session['hw_attached_files'] : array()
);
$dl = isset($session['hw_deadline']) ? (string) $session['hw_deadline'] : '';
$dlValue = $dl !== '' ? date('Y-m-d\TH:i', strtotime($dl)) : '';
$readonlySummary = ($summaryLocked || !$canEdit);
$selectedTopicIds = isset($session['topic_ids']) && is_array($session['topic_ids'])
    ? array_map('intval', $session['topic_ids'])
    : array();
if (empty($selectedTopicIds) && !empty($session['topic_id'])) {
    $selectedTopicIds = array((int) $session['topic_id']);
}
?>

<div class="lms-topic-detail-view">
    <!-- Header: Tên học sinh / lớp + Link Xem tất cả -->
    <div class="lms-page-head">
        <h1 class="lms-page-title mb-0">
            <a href="<?= e($backUrl) ?>">
                <?= e($headerTitle) ?>
            </a>
        </h1>
        <a href="<?= e($backUrl) ?>" class="lms-view-all-link">
            Xem tất cả <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </div>

    <!-- ===================== Section 1: Lesson Summary & BTVN ===================== -->
    <div class="lms-section-accordion mb-4">
        <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#collapseTeacherSummary" aria-expanded="true">
            <h5 class="lms-section-title mb-0">
                <i class="fa-solid fa-chevron-up me-2 lms-toggle-arrow"></i>Lesson Summary &amp; BTVN
            </h5>
        </div>

        <div id="collapseTeacherSummary" class="collapse show mt-3">
            <div class="lms-session-card-v2">
                <div class="lms-session-card-header d-flex align-items-center justify-content-between">
                    <div class="fw-bold">
                        <i class="fa-regular fa-calendar-days me-2"></i>Buổi học <?= e(lmld_session_title($session)) ?>
                    </div>
                    <i class="fa-solid fa-chevron-up text-muted"></i>
                </div>

                <div class="lms-session-card-body mt-2">
                    <form method="post" action="<?= e(lms_url($alias, 'luu-buoi', array('id' => (int) $session['id'], 'week' => $weekMonday))) ?>"
                          enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">

                        <!-- Pill 1: Chọn các chuyên đề * -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="lms-pill-label">Chọn các chuyên đề *</div>
                                <select class="form-select select2-multiple lms-pill-input" name="topic_ids[]" multiple
                                        data-placeholder="Chọn các chuyên đề *" <?= $readonlySummary ? 'disabled' : '' ?>>
                                    <?php foreach ($session['topics'] as $t): ?>
                                        <option value="<?= e((int) $t['id']) ?>"<?= lms_in_selected((int) $t['id'], $selectedTopicIds) ?>>
                                            <?= e((string) $t['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Pill 2: BTVN -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="lms-pill-label">BTVN:</div>
                                <textarea class="form-control lms-pill-input" name="hw_content" rows="2"
                                          placeholder="Nhập nội dung BTVN..." <?= $readonlySummary ? 'readonly' : '' ?>><?= e((string) (isset($session['hw_content']) ? $session['hw_content'] : '')) ?></textarea>
                            </div>
                        </div>

                        <!-- Pill 3: Hạn nộp -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-clock me-2 text-muted"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="lms-pill-label">Hạn nộp:</div>
                                <input type="datetime-local" class="form-control lms-pill-input" name="hw_deadline"
                                       value="<?= e($dlValue) ?>" <?= $readonlySummary ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <!-- Pill 4: Nhận xét buổi học -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-comment-dots me-2 text-muted"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="lms-pill-label">Nhận xét buổi học:</div>
                                <textarea class="form-control lms-pill-input" name="lesson_summary" rows="3"
                                          placeholder="Nhập nhận xét buổi học..." <?= $readonlySummary ? 'readonly' : '' ?>><?= e((string) (isset($session['lesson_summary']) ? $session['lesson_summary'] : '')) ?></textarea>
                            </div>
                        </div>

                        <!-- Pill 5: Tệp đính kèm -->
                        <div class="lms-session-pill mb-0">
                            <i class="fa-solid fa-paperclip me-2 text-muted"></i>
                            <div class="flex-grow-1 min-w-0">
                                <div class="lms-pill-label mb-1">Tệp đính kèm:</div>
                                <?php if ($readonlySummary): ?>
                                    <div class="lms-pill-value">
                                        <?php if (!empty($attachedFiles)): ?>
                                            <?php foreach ($attachedFiles as $f): ?>
                                                <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener" class="d-inline-block me-2">
                                                    <i class="fa-solid fa-download me-1"></i><?= e(basename((string) $f)) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Trống</span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <?php lms_component('form/file_upload', array(
                                        'name' => 'hw_files[]',
                                        'id' => 'lmld-hw-files',
                                        'existing' => $attachedFiles,
                                        'max_files' => max(1, 4 - count($attachedFiles)),
                                        'browse_text' => 'chọn tệp đính kèm',
                                    )); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="lms-form-footer mt-3">
                            <div class="lms-form-note text-danger fst-italic">
                                ** Chỉ được sửa trc 12h trưa ngày kế tiếp
                            </div>
                            <?php if ($canEdit): ?>
                                <div class="lms-test-action">
                                    <?php lms_component('button', array(
                                        'type' => 'submit',
                                        'label' => 'Cập nhật',
                                        'variant' => 'primary',
                                        'size' => 'md',
                                        'class' => 'px-4' . ($summaryLocked ? ' lms-btn-disabled' : ''),
                                        'disabled' => $summaryLocked,
                                    )); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== Section 2: Bài tập về nhà ===================== -->
    <div class="lms-panel lms-test-section mb-4">
        <div class="lms-panel-head" data-bs-toggle="collapse" data-bs-target="#collapseTeacherHw" aria-expanded="true">
            <div class="lms-panel-head-main">
                <i class="fa-solid fa-chevron-up lms-test-chevron"></i>
                <h5 class="lms-panel-title mb-0">Bài tập về nhà</h5>
            </div>
        </div>

        <div id="collapseTeacherHw" class="collapse show">
            <div class="lms-panel-body">
                <?php if (empty($students)): ?>
                    <p class="text-muted mb-0">Chưa có học viên trong buổi học này.</p>
                <?php else: ?>
                    <?php foreach ($students as $idx => $st): ?>
                        <?php
                        $graded = in_array((string) $st['status'], array('graded', 'approved'), true);
                        $canGradeStudent = $canEdit && !empty($st['hw_submitted_at']) && !$graded;
                        ?>
                        <div class="<?= $idx > 0 ? 'mt-4 pt-4 border-top' : '' ?>">
                            <?php if (count($students) > 1): ?>
                                <div class="fw-bold text-orange mb-3">
                                    <i class="fa-solid fa-user-graduate me-1"></i><?= e((string) $st['full_name']) ?>
                                </div>
                            <?php endif; ?>

                            <h6 class="fw-bold fs-5 mb-3">Bài nộp của học sinh</h6>
                            <?php lms_component('file_grid', array(
                                'files' => $st['hw_submission_files'],
                                'empty' => 'Học sinh chưa nộp bài.',
                            )); ?>

                            <h6 class="fw-bold fs-5 mt-4 mb-3">Chấm bài</h6>
                            <?php if ($graded): ?>
                                <div class="lms-hw-compose-box p-3">
                                    <div class="mb-3">
                                        <?= !empty($st['teacher_feedback_text']) ? nl2br(e((string) $st['teacher_feedback_text'])) : '<span class="text-muted fst-italic">Đã chấm (không có nội dung nhận xét).</span>' ?>
                                    </div>
                                    <?php if (!empty($st['teacher_feedback_files'])): ?>
                                        <?php lms_component('file_grid', array('files' => $st['teacher_feedback_files'])); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="lms-form-footer mt-3">
                                    <div class="lms-form-note text-success fw-semibold">
                                        Đã gửi nhận xét chấm bài.
                                    </div>
                                    <div class="lms-test-action">
                                        <?php lms_component('button', array(
                                            'type' => 'button',
                                            'label' => 'GỬI',
                                            'variant' => 'primary',
                                            'size' => 'md',
                                            'class' => 'px-4 lms-btn-disabled',
                                            'disabled' => true,
                                        )); ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <form method="post" action="<?= e(lms_url($alias, 'cham-btvn', array('id' => (int) $session['id'], 'week' => $weekMonday))) ?>"
                                      enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">
                                    <input type="hidden" name="session_student_id" value="<?= e((int) $st['id']) ?>">

                                    <div class="lms-hw-compose-box p-3">
                                        <textarea class="form-control border-0 shadow-none p-0 mb-3" name="teacher_feedback_text"
                                                  rows="3" placeholder="Nhập nội dung...." <?= !$canGradeStudent ? 'readonly' : '' ?>><?= e((string) (isset($st['teacher_feedback_text']) ? $st['teacher_feedback_text'] : '')) ?></textarea>

                                        <?php lms_component('form/file_upload', array(
                                            'name' => 'teacher_feedback_files[]',
                                            'id' => 'lmld-feedback-files-' . (int) $st['id'],
                                            'existing' => $st['teacher_feedback_files'],
                                            'max_files' => max(1, 4 - count($st['teacher_feedback_files'])),
                                            'browse_text' => 'chọn tệp nhận xét',
                                            'disabled' => !$canGradeStudent,
                                        )); ?>
                                    </div>

                                    <div class="lms-form-footer mt-3">
                                        <div class="lms-form-note text-danger fst-italic">
                                            <?= e($st['grade_due_label']) ?>
                                        </div>
                                        <?php if ($canEdit): ?>
                                            <div class="lms-test-action">
                                                <?php lms_component('button', array(
                                                    'type' => 'submit',
                                                    'label' => 'GỬI',
                                                    'variant' => 'primary',
                                                    'size' => 'md',
                                                    'class' => 'px-4' . (!$canGradeStudent ? ' lms-btn-disabled' : ''),
                                                    'disabled' => !$canGradeStudent,
                                                )); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
