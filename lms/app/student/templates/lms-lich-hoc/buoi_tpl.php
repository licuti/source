<?php
/**
 * Student — Chi tiết buổi học & Nộp BTVN.
 * Tham khảo thiết kế: Tai-lieu-phat-trien/student-ver2/Trang Chi tiết buổi học.png
 *
 * Data do source chuẩn bị: $session, $alias, $weekMonday.
 * Lưu ý:
 * - Card 1 (Lesson Summary & BTVN): chỉ hiển thị thông tin cho học viên (không có nút Cập nhật).
 * - Card 2 (Bài tập về nhà): học viên nộp BTVN; phần "Nhận xét của giáo viên" chỉ hiển thị
 *   khi Admin đã duyệt "Bài tập về nhà" ($session['feedback_approved'] === true).
 *
 * PHP 7.3 compatible.
 */
?>
<?php
$csrf = lms_csrf_token();
$isFuture = !empty($session['is_future']);
$hasSummary = !empty($session['lesson_summary']);
$hasHw = !empty($session['hw_content']);
$isGraded = !empty($session['graded']);
$isSubmitted = !empty($session['hw_submitted_at']);
$feedbackApproved = !empty($session['feedback_approved']);

$existingHwFiles = (!empty($session['hw_submission_files']) && is_array($session['hw_submission_files']))
    ? $session['hw_submission_files']
    : array();
$feedbackFiles = (!empty($session['teacher_feedback_files']) && is_array($session['teacher_feedback_files']))
    ? $session['teacher_feedback_files']
    : array();

$hwFull = count($existingHwFiles) >= 5;
$hwLocked = $isFuture || $hwFull || $isGraded;
$classUrl = lms_url('lms-khoa-hoc', 'lop', array('class_id' => (int) $session['class_id']));
?>

<div class="lms-topic-detail-view">
    <!-- Header: Tên khóa học + Link Xem tất cả -->
    <div class="lms-page-head">
        <h1 class="lms-page-title mb-0">
            <a href="<?= e($classUrl) ?>">
                <?= e((string) $session['class_name']) ?>
            </a>
        </h1>
        <a href="<?= e($classUrl) ?>" class="lms-view-all-link">
            Xem tất cả <i class="fa-solid fa-chevron-right ms-1"></i>
        </a>
    </div>

    <!-- ===================== Section 1: Lesson Summary & BTVN ===================== -->
    <div class="lms-section-accordion mb-4">
        <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#collapseStudentLessonSummary" aria-expanded="true">
            <h5 class="lms-section-title mb-0">
                <i class="fa-solid fa-chevron-up me-2 lms-toggle-arrow"></i>Lesson Summary &amp; BTVN
            </h5>
        </div>

        <div id="collapseStudentLessonSummary" class="collapse show mt-3">
            <div class="lms-session-card-v2">
                <div class="lms-session-card-header d-flex align-items-center justify-content-between">
                    <div class="fw-bold">
                        <i class="fa-regular fa-calendar-days me-2"></i>Buổi học <?= e(lmlh_session_title($session)) ?>
                    </div>
                    <i class="fa-solid fa-chevron-up text-muted"></i>
                </div>

                <div class="lms-session-card-body mt-2">
                    <?php if ($isFuture && !$hasSummary && !$hasHw): ?>
                        <div class="lms-session-future-notice text-danger fst-italic py-4 text-center">
                            Chưa diễn ra
                        </div>
                    <?php else: ?>
                        <!-- Pill 1: Chuyên đề -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                            <div>
                                <div class="lms-pill-label">Chọn các chuyên đề *</div>
                                <div class="lms-pill-value">
                                    <?= !empty($session['topic_names']) ? e(implode(', ', $session['topic_names'])) : 'Chưa chọn chuyên đề' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pill 2: BTVN -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                            <div>
                                <div class="lms-pill-label">BTVN:</div>
                                <div class="lms-pill-value">
                                    <?= $hasHw ? nl2br(e((string) $session['hw_content'])) : 'Chưa có BTVN' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pill 3: Hạn nộp -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-clock me-2 text-muted"></i>
                            <div>
                                <div class="lms-pill-label">Hạn nộp:</div>
                                <div class="lms-pill-value<?= !empty($session['deadline_overdue']) ? ' text-danger' : '' ?>">
                                    <?= !empty($session['deadline_label']) ? e($session['deadline_label']) : 'Chưa có hạn nộp' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pill 4: Nhận xét buổi học -->
                        <div class="lms-session-pill">
                            <i class="fa-regular fa-comment-dots me-2 text-muted"></i>
                            <div>
                                <div class="lms-pill-label">Nhận xét buổi học:</div>
                                <div class="lms-pill-value">
                                    <?= $hasSummary ? nl2br(e((string) $session['lesson_summary'])) : 'Chưa có nhận xét' ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pill 5: Tệp đính kèm -->
                        <div class="lms-session-pill mb-0">
                            <i class="fa-solid fa-paperclip me-2 text-muted"></i>
                            <div>
                                <div class="lms-pill-label">Tệp đính kèm:</div>
                                <div class="lms-pill-value">
                                    <?php if (!empty($session['hw_attached_files'])): ?>
                                        <?php foreach ($session['hw_attached_files'] as $f): ?>
                                            <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener" class="d-inline-block me-2">
                                                <i class="fa-solid fa-download me-1"></i><?= e(basename((string) $f)) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Trống</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== Section 2: Bài tập về nhà ===================== -->
    <div class="lms-panel lms-test-section mb-4">
        <div class="lms-panel-head" data-bs-toggle="collapse" data-bs-target="#collapseStudentHw" aria-expanded="true">
            <div class="lms-panel-head-main">
                <i class="fa-solid fa-chevron-up lms-test-chevron"></i>
                <h5 class="lms-panel-title mb-0">Bài tập về nhà</h5>
            </div>
            <div class="lms-test-header-status">
                <?php if ($isGraded): ?>
                    <span class="lms-badge lms-badge-success">Đã chấm</span>
                <?php elseif ($isSubmitted): ?>
                    <span class="lms-badge lms-badge-success">Đã nộp bài</span>
                <?php endif; ?>
            </div>
        </div>

        <div id="collapseStudentHw" class="collapse show">
            <div class="lms-panel-body">
                <h6 class="fw-bold fs-5 mb-3">Bài nộp của học sinh</h6>

                <?php if ($hwLocked): ?>
                    <?php if ($isFuture): ?>
                        <div class="text-danger fst-italic">Buổi học chưa diễn ra, chưa mở nộp bài tập về nhà.</div>
                    <?php else: ?>
                        <?php lms_component('file_grid', array(
                            'files' => $existingHwFiles,
                            'empty' => 'Chưa có tệp bài nộp.',
                        )); ?>
                        <?php if ($hwFull && !$isGraded): ?>
                            <div class="text-muted small mt-2">Đã đủ 5 tệp. Vui lòng liên hệ giáo viên nếu cần nộp lại.</div>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <form method="post" action="<?= e(lms_url('lms-khoa-hoc', 'nop-btvn')) ?>"
                          enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                        <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">
                        <input type="hidden" name="back" value="buoi">

                        <?php lms_component('form/file_upload', array(
                            'name' => 'hw_files[]',
                            'id' => 'hw_files_' . (int) $session['id'],
                            'existing' => $existingHwFiles,
                            'max_files' => 5 - count($existingHwFiles),
                            'required' => true,
                            'replace_hint' => !empty($existingHwFiles) ? 'Nộp bổ sung, tổng tối đa 5 tệp.' : '',
                            'browse_text' => 'chọn tệp bài làm',
                        )); ?>

                        <div class="lms-form-footer">
                            <div class="lms-form-note">
                                <?php if (!empty($session['deadline_text'])): ?>
                                    <span class="text-danger fst-italic"><?= e($session['deadline_text']) ?></span>
                                <?php elseif (!empty($session['deadline_label'])): ?>
                                    <span class="text-danger fst-italic">Hạn nộp: <?= e($session['deadline_label']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="lms-test-action">
                                <?php lms_component('button', array(
                                    'type' => 'submit',
                                    'label' => 'Nộp bài',
                                    'variant' => 'primary',
                                    'size' => 'md',
                                    'class' => 'px-4',
                                )); ?>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if ($feedbackApproved): ?>
                    <!-- Nhận xét của giáo viên (chỉ hiển thị khi Admin đã duyệt BTVN) -->
                    <h6 class="fw-bold fs-5 mt-4 mb-3">Nhận xét của giáo viên</h6>
                    <div class="lms-hw-compose-box p-3">
                        <div class="mb-3">
                            <?php if (!empty($session['teacher_feedback_text'])): ?>
                                <?= nl2br(e((string) $session['teacher_feedback_text'])) ?>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Không có nội dung nhận xét.</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($feedbackFiles)): ?>
                            <?php lms_component('file_grid', array('files' => $feedbackFiles)); ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
