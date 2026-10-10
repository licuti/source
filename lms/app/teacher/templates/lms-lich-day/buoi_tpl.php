<?php
/**
 * Teacher — Chi tiết buổi học (view, mockup §7).
 * Data do source chuẩn bị: $session, $students, $summaryLocked, $deadlineLabel,
 * $weekMonday, $canEdit.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<div class="lms-card">
    <div class="lms-card-header">
        <a href="<?= e(lms_url($alias, '', lmld_week_params($weekMonday))) ?>" class="btn btn-outline-lms btn-sm me-2">
            <i class="fa-solid fa-arrow-left"></i> Lịch
        </a>
        <i class="fa-solid fa-calendar-day me-1"></i> <?= e($title) ?>
        <span class="lms-badge lms-badge-secondary ms-1"><?= e((string) $session['class_code']) ?></span>
        <?php if (!empty($session['summary_submitted_at'])): ?>
            <span class="lms-badge lms-badge-success ms-1">Đã giao BTVN</span>
        <?php else: ?>
            <span class="lms-badge lms-badge-danger ms-1">Chưa giao BTVN</span>
        <?php endif; ?>
    </div>
    <div class="lms-card-body">

        <!-- ===================== Lesson summary & BTVN (§7.1) ===================== -->
        <h2 class="lms-section-title">Lesson summary &amp; BTVN</h2>

        <?php if ($summaryLocked): ?>
            <div class="lms-alert lms-alert-danger">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                Chỉ được sửa trước 12h trưa ngày kế tiếp buổi học (hạn: <?= e($deadlineLabel) ?>).
            </div>
            <div class="lms-readonly-block">
                <h3>Nội dung buổi học</h3>
                <p><?= nl2br(e((string) (isset($session['lesson_summary']) ? $session['lesson_summary'] : '—'))) ?></p>
                <h3>Bài tập về nhà</h3>
                <p><?= nl2br(e((string) (isset($session['hw_content']) ? $session['hw_content'] : '—'))) ?></p>
                <h3>Các file đính kèm</h3>
                <?php $files = \Lms\UserService::decodeJsonArray(isset($session['hw_attached_files']) ? $session['hw_attached_files'] : array()); ?>
                <?php if (empty($files)): ?>
                    <p class="text-muted">Không có file đính kèm.</p>
                <?php else: ?>
                    <ul class="lms-file-list">
                        <?php foreach ($files as $f): ?>
                            <li><a href="<?= e(lms_upload_url($f)) ?>" target="_blank"><i class="fa-solid fa-paperclip me-1"></i><?= e(basename($f)) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php elseif (!$canEdit): ?>
            <div class="lms-alert lms-alert-warning">Bạn không có quyền chỉnh sửa buổi học này.</div>
        <?php else: ?>
            <form method="post" action="<?= e(lms_url($alias, 'luu-buoi', array('id' => (int) $session['id'], 'week' => $weekMonday))) ?>"
                  class="lms-form" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="lms-form-label">Chuyên đề</label>
                        <select class="form-select" name="topic_ids[]" multiple size="4">
                            <?php foreach ($session['topics'] as $t): ?>
                                <option value="<?= e((int) $t['id']) ?>"<?= lms_in_selected((int) $t['id'], array_map('intval', $session['topic_ids'])) ?>><?= e((string) $t['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="lms-text-muted-sm">Giữ Ctrl để chọn nhiều chuyên đề.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="lms-form-label" for="lmld-hw-deadline">Hạn nộp BTVN</label>
                        <?php
                        $dl = isset($session['hw_deadline']) ? (string) $session['hw_deadline'] : '';
                        $dlValue = $dl !== '' ? date('Y-m-d\TH:i', strtotime($dl)) : '';
                        ?>
                        <input type="datetime-local" class="form-control" id="lmld-hw-deadline" name="hw_deadline" value="<?= e($dlValue) ?>">
                        <small class="lms-text-muted-sm">Để trống để dùng mặc định (12:00 trưa ngày kế tiếp).</small>
                    </div>
                    <div class="col-12">
                        <label class="lms-form-label" for="lmld-summary">Nội dung buổi học</label>
                        <textarea class="form-control" id="lmld-summary" name="lesson_summary" rows="3" placeholder="Nội dung đã dạy trong buổi..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="lms-form-label" for="lmld-hw">Bài tập về nhà</label>
                        <textarea class="form-control" id="lmld-hw" name="hw_content" rows="3" placeholder="Nội dung BTVN..."></textarea>
                    </div>
                    <div class="col-12">
                        <label class="lms-form-label">File đính kèm (tối đa 4)</label>
                        <div class="lms-file-pickers">
                            <?php for ($i = 0; $i < 4; $i++): ?>
                                <input type="file" class="form-control" name="hw_files[]" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.docx,.xlsx,.pptx,.mp3,.mp4,.zip">
                            <?php endfor; ?>
                        </div>
                        <div class="form-check mt-1">
                            <input type="checkbox" class="form-check-input" id="lmld-replace-files" name="replace_files" value="1">
                            <label class="form-check-label" for="lmld-replace-files">Thay thế toàn bộ file đính kèm cũ</label>
                        </div>
                    </div>
                </div>
                <div class="lms-form-actions">
                    <button type="submit" class="btn btn-lms">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Cập nhật
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <!-- ===================== Bài tập về nhà (§7.2) ===================== -->
        <h2 class="lms-section-title mt-4">Bài tập về nhà</h2>

        <?php if (empty($students)): ?>
            <p class="text-muted">Chưa có học viên trong buổi học này.</p>
        <?php else: ?>
            <?php foreach ($students as $st): ?>
                <?php $graded = (string) $st['status'] === 'graded'; ?>
                <div class="lms-hw-card">
                    <div class="lms-hw-card-head">
                        <strong><i class="fa-solid fa-user-graduate me-1"></i> <?= e((string) $st['full_name']) ?></strong>
                        <?php $subStatus = (string) $st['hw_submission_status']; ?>
                        <?php if ($subStatus === 'submitted_on_time'): ?>
                            <span class="lms-badge lms-badge-success ms-1">Nộp đúng hạn</span>
                        <?php elseif ($subStatus === 'submitted_late'): ?>
                            <span class="lms-badge lms-badge-danger ms-1">Nộp trễ</span>
                        <?php else: ?>
                            <span class="lms-badge lms-badge-secondary ms-1">Chưa nộp</span>
                        <?php endif; ?>
                        <?php if ($graded): ?>
                            <span class="lms-badge lms-badge-primary ms-1">Điểm: <?= e(isset($st['grade']) ? (string) $st['grade'] : '—') ?></span>
                            <?php if (!empty($st['is_graded_late'])): ?>
                                <span class="lms-badge lms-badge-danger ms-1">Chấm trễ</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <span class="lms-hw-due<?= !empty($st['grade_overdue']) ? ' lms-hw-due-over' : '' ?>">
                            <?= e($st['grade_due_label']) ?>
                        </span>
                    </div>
                    <div class="lms-hw-card-body">
                        <h4>Bài nộp của học sinh</h4>
                        <?php if (empty($st['hw_submission_files'])): ?>
                            <p class="text-muted lms-text-muted-sm">Học sinh chưa nộp bài.</p>
                        <?php else: ?>
                            <ul class="lms-file-list">
                                <?php foreach ($st['hw_submission_files'] as $f): ?>
                                    <li><a href="<?= e(lms_upload_url($f)) ?>" target="_blank"><i class="fa-solid fa-paperclip me-1"></i><?= e(basename($f)) ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if ($graded): ?>
                            <h4>Nhận xét của giáo viên</h4>
                            <p><?= nl2br(e((string) (isset($st['teacher_feedback_text']) ? $st['teacher_feedback_text'] : '—'))) ?></p>
                            <?php $fbFiles = \Lms\UserService::decodeJsonArray(isset($st['teacher_feedback_files']) ? $st['teacher_feedback_files'] : array()); ?>
                            <?php if (!empty($fbFiles)): ?>
                                <ul class="lms-file-list">
                                    <?php foreach ($fbFiles as $f): ?>
                                        <li><a href="<?= e(lms_upload_url($f)) ?>" target="_blank"><i class="fa-solid fa-paperclip me-1"></i><?= e(basename($f)) ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        <?php elseif ($canEdit && !empty($st['hw_submitted_at'])): ?>
                            <form method="post" action="<?= e(lms_url($alias, 'cham-btvn', array('id' => (int) $session['id'], 'week' => $weekMonday))) ?>"
                                  class="lms-form mt-2" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">
                                <input type="hidden" name="session_student_id" value="<?= e((int) $st['id']) ?>">
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <label class="lms-form-label">Điểm (0–20)</label>
                                        <input type="number" class="form-control" name="grade" min="0" max="20" step="0.25">
                                    </div>
                                    <div class="col-12">
                                        <label class="lms-form-label">Nhận xét</label>
                                        <textarea class="form-control" name="teacher_feedback_text" rows="2" placeholder="Nhập nội dung...."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="lms-form-label">File đính kèm (tối đa 4)</label>
                                        <div class="lms-file-pickers">
                                            <?php for ($i = 0; $i < 4; $i++): ?>
                                                <input type="file" class="form-control" name="teacher_feedback_files[]" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.docx,.xlsx,.pptx,.mp3,.mp4,.zip">
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="lms-form-actions">
                                    <button type="submit" class="btn btn-lms">Gửi</button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
