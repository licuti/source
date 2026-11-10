<?php
/**
 * Admin — Chi tiết buổi học & Duyệt bài tập về nhà.
 * Tham khảo thiết kế: Tai-lieu-phat-trien/admin-ver2/chi tiết buổi học.png
 *
 * Data: $detail ('session', 'students', 'topics', 'audit_logs').
 * PHP 7.3 compatible.
 */
?>
<?php
$session = $detail['session'];
$csrf = lms_csrf_token();
$attachedFiles = isset($session['hw_attached_files']) && is_array($session['hw_attached_files'])
    ? $session['hw_attached_files']
    : array();
$selectedTopicIds = isset($session['topic_ids']) && is_array($session['topic_ids'])
    ? array_map('intval', $session['topic_ids'])
    : array();
if (empty($selectedTopicIds) && !empty($session['topic_id'])) {
    $selectedTopicIds = array((int) $session['topic_id']);
}
$dl = isset($session['hw_deadline']) ? (string) $session['hw_deadline'] : '';
$dlValue = $dl !== '' ? date('Y-m-d\TH:i', strtotime($dl)) : '';

$ts = strtotime((string) $session['session_date']);
$dow = $ts !== false ? ((int) date('N', $ts) + 1) : 0;
$wd = isset(\Lms\UserService::WEEKDAYS[$dow]) ? \Lms\UserService::WEEKDAYS[$dow] : '';
$sessionTitle = $ts !== false ? ($wd . ' ' . (int) date('j', $ts) . '/' . (int) date('n', $ts)) : (string) $session['session_date'];
$auditLogs = isset($detail['audit_logs']) && is_array($detail['audit_logs']) ? $detail['audit_logs'] : array();
?>

<div class="lms-topic-detail-view">
    <!-- ===================== Card 1: Thông tin buổi học ===================== -->
    <div class="lms-session-card-v2 mb-4">
        <div class="lms-session-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="fw-bold">
                <i class="fa-regular fa-calendar-days me-2"></i>Buổi học <?= e($sessionTitle) ?>
            </div>
            <div class="fw-bold">
                <?= e((string) $session['class_code']) ?> [<?= e((string) $session['class_name']) ?>]
            </div>
            <div class="fw-bold d-flex align-items-center gap-2">
                <span><?= e((string) $session['teacher_code']) ?> [<?= e((string) $session['teacher_name']) ?>]</span>
                <i class="fa-solid fa-chevron-up text-muted"></i>
            </div>
        </div>

        <div class="lms-session-card-body mt-2">
            <form method="post" action="<?= e(lms_url('lms-lop', 'cap-nhat-buoi')) ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">

                <!-- Pill 1: Chọn các chuyên đề * -->
                <div class="lms-session-pill">
                    <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="lms-pill-label">Chọn các chuyên đề *</div>
                        <select name="topic_ids[]" class="form-select select2-multiple lms-pill-input" multiple data-placeholder="Chọn các chuyên đề *">
                            <?php foreach ($detail['topics'] as $topic): ?>
                                <option value="<?= e((int) $topic['id']) ?>"<?= lms_in_selected((int) $topic['id'], $selectedTopicIds) ?>>
                                    <?= e((string) $topic['name']) ?>
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
                        <textarea name="hw_content" class="form-control lms-pill-input" rows="2" placeholder="Nhập nội dung BTVN..."><?= e((string) $session['hw_content']) ?></textarea>
                    </div>
                </div>

                <!-- Pill 3: Hạn nộp -->
                <div class="lms-session-pill">
                    <i class="fa-regular fa-clock me-2 text-muted"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="lms-pill-label">Hạn nộp:</div>
                        <input type="datetime-local" name="hw_deadline" class="form-control lms-pill-input" value="<?= e($dlValue) ?>">
                    </div>
                </div>

                <!-- Pill 4: Nhận xét buổi học -->
                <div class="lms-session-pill">
                    <i class="fa-regular fa-comment-dots me-2 text-muted"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="lms-pill-label">Nhận xét buổi học:</div>
                        <textarea name="lesson_summary" class="form-control lms-pill-input" rows="3" placeholder="Nhập nhận xét buổi học..."><?= e((string) $session['lesson_summary']) ?></textarea>
                    </div>
                </div>

                <!-- Pill 5: Tệp đính kèm -->
                <div class="lms-session-pill mb-0">
                    <i class="fa-solid fa-paperclip me-2 text-muted"></i>
                    <div class="flex-grow-1 min-w-0">
                        <div class="lms-pill-label mb-1">Tệp đính kèm:</div>
                        <?php lms_component('form/file_upload', array(
                            'name' => 'hw_files[]',
                            'id' => 'admin-hw-files-' . (int) $session['id'],
                            'existing' => $attachedFiles,
                            'max_files' => max(1, 4 - count($attachedFiles)),
                            'browse_text' => 'chọn tệp đính kèm',
                        )); ?>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <?php lms_component('button', array(
                        'type' => 'submit',
                        'label' => 'Cập nhật',
                        'variant' => 'primary',
                        'size' => 'md',
                        'class' => 'px-4',
                    )); ?>
                    <button type="button" class="btn btn-lms px-4" data-bs-toggle="modal" data-bs-target="#modalSessionHistory">
                        Lịch sử chỉnh sửa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================== Card 2: Bài tập về nhà ===================== -->
    <div class="lms-panel lms-test-section mb-4">
        <div class="lms-panel-head" data-bs-toggle="collapse" data-bs-target="#collapseAdminHw" aria-expanded="true">
            <div class="lms-panel-head-main">
                <i class="fa-solid fa-chevron-up lms-test-chevron"></i>
                <h5 class="lms-panel-title mb-0">Bài tập về nhà</h5>
            </div>
        </div>

        <div id="collapseAdminHw" class="collapse show">
            <div class="lms-panel-body">
                <?php if (empty($detail['students'])): ?>
                    <p class="text-muted mb-0">Chưa có học viên trong buổi học này.</p>
                <?php else: ?>
                    <?php foreach ($detail['students'] as $idx => $student): ?>
                        <div class="<?= $idx > 0 ? 'mt-4 pt-4 border-top' : '' ?>">
                            <?php if (count($detail['students']) > 1): ?>
                                <div class="fw-bold text-orange mb-3">
                                    <i class="fa-solid fa-user-graduate me-1"></i><?= e((string) $student['full_name']) ?>
                                </div>
                            <?php endif; ?>

                            <h6 class="fw-bold fs-5 mb-3">Bài nộp của học sinh</h6>
                            <?php lms_component('file_grid', array(
                                'files' => $student['hw_submission_files'],
                                'empty' => 'Học sinh chưa nộp bài.',
                            )); ?>

                            <div class="d-flex align-items-center justify-content-between mt-4 mb-3">
                                <h6 class="fw-bold fs-5 mb-0">Nhận xét của giáo viên</h6>
                                <?php if ((string) $student['status'] === 'approved'): ?>
                                    <span class="lms-badge lms-badge-success">Đã duyệt</span>
                                <?php elseif ((string) $student['status'] === 'graded'): ?>
                                    <span class="lms-badge lms-badge-warning">Chờ duyệt</span>
                                <?php endif; ?>
                            </div>

                            <form method="post" action="<?= e(lms_url('lms-lop', 'cham-btvn')) ?>" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">
                                <input type="hidden" name="session_student_id" value="<?= e((int) $student['id']) ?>">
                                <input type="hidden" name="reason" value="Admin duyệt bài tập về nhà">

                                <div class="lms-hw-compose-box p-3">
                                    <textarea class="form-control border-0 shadow-none p-0 mb-3" name="teacher_feedback_text"
                                              rows="3" placeholder="Nhập nội dung...."><?= e((string) (isset($student['teacher_feedback_text']) ? $student['teacher_feedback_text'] : '')) ?></textarea>

                                    <?php lms_component('form/file_upload', array(
                                        'name' => 'teacher_feedback_files[]',
                                        'id' => 'admin-feedback-files-' . (int) $student['id'],
                                        'existing' => $student['teacher_feedback_files'],
                                        'max_files' => max(1, 4 - count($student['teacher_feedback_files'])),
                                        'browse_text' => 'chọn tệp nhận xét',
                                    )); ?>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <?php lms_component('button', array(
                                        'type' => 'submit',
                                        'label' => 'GỬI',
                                        'variant' => 'primary',
                                        'size' => 'md',
                                        'class' => 'px-4',
                                    )); ?>
                                </div>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lịch sử chỉnh sửa -->
<div class="modal fade" id="modalSessionHistory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Lịch sử chỉnh sửa buổi học</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <?php if (empty($auditLogs)): ?>
                    <p class="text-muted mb-0">Chưa có lịch sử chỉnh sửa cho buổi học này.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Thời gian</th>
                                    <th>Người thực hiện</th>
                                    <th>Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td><?= e((string) $log['created_at']) ?></td>
                                        <td><?= e((string) (!empty($log['username']) ? $log['username'] : $log['actor_type'])) ?></td>
                                        <td><?= e((string) $log['action']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
