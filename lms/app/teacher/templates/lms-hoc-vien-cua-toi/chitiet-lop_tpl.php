<?php
$csrf = lms_csrf_token();
$groupClass = $group['class'];
$students = $group['students'];
$weekdays = \Lms\UserService::WEEKDAYS;
$today = date('Y-m-d');

$topicNames = array();
$topicGroups = array();
foreach ($group['topics'] as $topic) {
    $topicNames[(int) $topic['topic_id']] = (string) $topic['name'];
    $groupName = isset($topic['subject_name']) && trim((string) $topic['subject_name']) !== ''
        ? (string) $topic['subject_name']
        : 'Khác';
    $topicGroups[$groupName][] = $topic;
}

?>
<!-- ===================== Danh sách học viên ===================== -->
<div class="lms-panel mb-4">
    <div class="lms-panel-head is-static">
        <div class="lms-panel-head-main">
            <i class="fa-solid fa-users"></i>
            <span class="lms-panel-title">Danh sách học viên</span>
        </div>
        <span class="lms-badge lms-badge-secondary"><?= count($students) ?> học viên</span>
    </div>

    <div class="lms-panel-body">
        <?php if (empty($students)): ?>
            <p class="text-muted mb-0">Lớp chưa có học viên.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 lms-table">
                    <thead class="table-light text-center">
                        <tr>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th style="width: 110px;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) $student['full_name']) ?></strong>
                                    <small class="text-muted d-block"><?= e((string) $student['student_code']) ?></small>
                                </td>
                                <td><?= e((string) (!empty($student['email']) ? $student['email'] : '—')) ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-link text-muted" disabled
                                            title="Chat với học viên đang được phát triển"
                                            aria-label="Chat với <?= e((string) $student['full_name']) ?>">
                                        <i class="fa-regular fa-comment-dots"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== Accordion: Lộ trình ===================== -->
<div class="lms-section-accordion mb-4">
    <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#lmsGroupRoadmapCollapse" aria-expanded="true">
        <h4 class="lms-section-title mb-0">Lộ trình</h4>
        <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
    </div>

    <div id="lmsGroupRoadmapCollapse" class="collapse show mt-3">
        <?php if (empty($topicGroups)): ?>
            <div class="text-muted p-3 bg-white rounded-3">Lớp chưa được gán chuyên đề.</div>
        <?php else: ?>
            <?php $groupIndex = 0; ?>
            <?php foreach ($topicGroups as $groupName => $groupTopics): ?>
                <?php
                $groupIndex++;
                $collapseCatId = 'lmsGroupRoadmapGroup' . $groupIndex;
                $isMathProblems = (strpos(strtolower($groupName), 'problem') !== false);
                ?>
                <div class="lms-category-group mb-4">
                    <div class="lms-category-header" data-bs-toggle="collapse"
                         data-bs-target="#<?= e($collapseCatId) ?>" aria-expanded="true">
                        <h5 class="lms-category-title mb-0"><?= e($groupName) ?></h5>
                        <i class="fa-solid fa-chevron-up lms-cat-arrow"></i>
                    </div>

                    <div id="<?= e($collapseCatId) ?>" class="collapse show mt-3">
                        <div class="row g-3">
                            <?php foreach ($groupTopics as $topic): ?>
                                <?php
                                $doneText = $topic['max_sessions'] === null
                                    ? 'Đã dạy: ' . (int) $topic['done_sessions'] . ' buổi'
                                    : 'Đã dạy: ' . (int) $topic['done_sessions'] . '/' . (int) $topic['max_sessions'] . ' buổi';
                                ?>
                                <div class="col-md-6">
                                    <div class="lms-topic-card-v2">
                                        <div class="lms-topic-card-left">
                                            <div class="lms-topic-icon-wrap<?= $isMathProblems ? ' icon-orange' : ' icon-blue' ?>">
                                                <?php if ($isMathProblems): ?>
                                                    <i class="fa-regular fa-file-lines"></i>
                                                <?php else: ?>
                                                    <i class="fa-solid fa-book-open-reader"></i>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="lms-topic-card-body">
                                            <div class="lms-topic-name-link"><?= e((string) $topic['name']) ?></div>
                                            <div class="lms-topic-meta-done mt-1"><?= e($doneText) ?></div>

                                            <div class="lms-topic-actions-wrap mt-3">
                                                <?php lms_component('button', array(
                                                    'type' => 'button',
                                                    'label' => 'Thêm buổi',
                                                    'variant' => 'outline-primary',
                                                    'size' => 'sm',
                                                    'class' => 'w-100 lms-group-add-session',
                                                    'attrs' => array('data-topic' => (string) $topic['name']),
                                                )); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== Accordion: Nội dung buổi học ===================== -->
<div class="lms-section-accordion mb-4">
    <div class="lms-section-header" data-bs-toggle="collapse" data-bs-target="#lmsGroupSessionsCollapse" aria-expanded="true">
        <h4 class="lms-section-title mb-0">Nội dung buổi học</h4>
        <i class="fa-solid fa-chevron-up lms-toggle-arrow"></i>
    </div>

    <div id="lmsGroupSessionsCollapse" class="collapse show mt-3">
        <?php if (empty($group['visible_sessions'])): ?>
            <div class="text-muted p-3 bg-white rounded-3">Chưa có buổi học trong khóa.</div>
        <?php else: ?>
            <div class="lms-panel">
                <div class="lms-panel-head is-static">
                    <div class="lms-panel-head-main">
                        <i class="fa-regular fa-calendar-days"></i>
                        <span class="lms-panel-title">Buổi học theo lịch</span>
                    </div>
                    <span class="lms-badge lms-badge-secondary"><?= count($group['visible_sessions']) ?> buổi hiển thị</span>
                </div>

                <div class="lms-panel-body">
                    <div class="row g-3">
                        <?php foreach ($group['visible_sessions'] as $session): ?>
                            <?php
                            $sessionDate = (string) $session['session_date'];
                            $ts = strtotime($sessionDate);
                            $dow = (int) date('N', $ts) + 1;
                            $dayLabel = isset($weekdays[$dow]) ? $weekdays[$dow] : 'Buổi học';
                            $hasSummary = trim((string) $session['lesson_summary']) !== '';
                            $hasHw = trim((string) $session['hw_content']) !== '';
                            $isFuture = strcmp($sessionDate, $today) > 0;
                            $totalStudents = (int) $session['student_count'];
                            $gradedCount = (int) $session['graded_count'];
                            $isComplete = $hasHw && $totalStudents > 0 && $gradedCount >= $totalStudents;
                            $gradingStudents = isset($session['grading_students']) ? $session['grading_students'] : array();

                            if ($isFuture && !$hasSummary && !$hasHw) {
                                $stateLabel = 'Chưa diễn ra';
                                $stateClass = 'text-danger fst-italic';
                            } elseif ($isComplete) {
                                $stateLabel = 'Đã hoàn thành';
                                $stateClass = 'text-success';
                            } elseif ($hasHw) {
                                $stateLabel = 'Chưa chấm BTVN';
                                $stateClass = 'text-danger';
                            } elseif ($hasSummary) {
                                $stateLabel = 'Chưa giao BTVN';
                                $stateClass = 'text-danger';
                            } else {
                                $stateLabel = 'Chưa cập nhật';
                                $stateClass = 'text-muted';
                            }

                            $sessionTopics = \Lms\UserService::decodeJsonArray(isset($session['topic_ids']) ? $session['topic_ids'] : array());
                            if (empty($sessionTopics) && !empty($session['topic_id'])) {
                                $sessionTopics = array((int) $session['topic_id']);
                            }
                            $topicLabels = array();
                            foreach ($sessionTopics as $topicId) {
                                $topicId = (int) $topicId;
                                $topicLabels[] = isset($topicNames[$topicId]) ? $topicNames[$topicId] : ('#' . $topicId);
                            }
                            $attachments = \Lms\UserService::decodeJsonArray(isset($session['hw_attached_files']) ? $session['hw_attached_files'] : array());
                            $gradingId = 'lmsGroupGrading' . (int) $session['id'];
                            $sessionPageUrl = lms_url('lms-lich-day', 'buoi', array('id' => (int) $session['id']));
                            ?>
                            <div class="col-lg-6" id="session-<?= (int) $session['id'] ?>">
                                <div class="lms-session-card-v2">
                                    <div class="lms-session-card-header d-flex align-items-center justify-content-between">
                                        <div class="fw-bold">
                                            <i class="fa-regular fa-calendar-days me-2"></i>Buổi học <?= e($dayLabel) ?> <?= e(date('d/m', $ts)) ?>
                                        </div>
                                    </div>

                                    <div class="lms-session-card-body mt-2">
                                        <?php if ($isFuture && !$hasSummary && !$hasHw): ?>
                                            <div class="lms-session-future-notice text-danger fst-italic py-4 text-center">
                                                Chưa diễn ra
                                            </div>
                                        <?php else: ?>
                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Chuyên đề:</div>
                                                    <div class="lms-pill-value">
                                                        <?= $topicLabels !== array() ? e(implode(', ', $topicLabels)) : 'Chưa chọn chuyên đề' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-comment-dots me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Nhận xét buổi học:</div>
                                                    <div class="lms-pill-value">
                                                        <?= $hasSummary ? nl2br(e((string) $session['lesson_summary'])) : 'Chưa có nhận xét' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-file-lines me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">BTVN:</div>
                                                    <div class="lms-pill-value">
                                                        <?= $hasHw ? nl2br(e((string) $session['hw_content'])) : 'Chưa có BTVN' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-regular fa-clock me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Hạn nộp:</div>
                                                    <div class="lms-pill-value">
                                                        <?= !empty($session['hw_deadline']) ? e(date('H:i d/m/Y', strtotime((string) $session['hw_deadline']))) : 'Chưa có hạn nộp' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="lms-session-pill">
                                                <i class="fa-solid fa-paperclip me-2 text-muted"></i>
                                                <div>
                                                    <div class="lms-pill-label">Tệp đính kèm:</div>
                                                    <div class="lms-pill-value">
                                                        <?php if (!empty($attachments)): ?>
                                                            <?php foreach ($attachments as $file): ?>
                                                                <a href="<?= e(lms_upload_url((string) $file)) ?>" target="_blank" rel="noopener" class="d-inline-block me-2">
                                                                    <i class="fa-solid fa-download me-1"></i><?= e(basename((string) $file)) ?>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <span class="text-muted">Trống</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="d-flex flex-wrap gap-2 mt-3">
                                                <?php lms_component('button', array(
                                                    'href' => $sessionPageUrl,
                                                    'label' => 'Cập nhật nội dung',
                                                    'icon' => 'fa-regular fa-pen-to-square',
                                                    'variant' => 'outline-primary',
                                                    'size' => 'sm',
                                                )); ?>
                                                <?php if ($hasHw && $gradingStudents !== array()): ?>
                                                    <?php lms_component('button', array(
                                                        'type' => 'button',
                                                        'label' => $isComplete ? 'Xem chi tiết' : 'Chấm BTVN',
                                                        'icon' => 'fa-solid fa-check-to-slot',
                                                        'variant' => 'primary',
                                                        'size' => 'sm',
                                                        'attrs' => array(
                                                            'data-bs-toggle' => 'collapse',
                                                            'data-bs-target' => '#' . $gradingId,
                                                            'aria-expanded' => 'false',
                                                            'aria-controls' => $gradingId,
                                                        ),
                                                    )); ?>
                                                <?php endif; ?>
                                            </div>

                                            <div class="text-center mt-2 small">
                                                <span class="<?= e($stateClass) ?> fw-medium"><?= e($stateLabel) ?></span>
                                            </div>

                                            <?php if ($gradingStudents !== array()): ?>
                                                <div id="<?= e($gradingId) ?>" class="collapse mt-3">
                                                    <?php foreach ($gradingStudents as $row): ?>
                                                        <?php
                                                        $submitted = !empty($row['hw_submitted_at']);
                                                        $graded = !empty($row['hw_graded_at']);
                                                        if ($graded) {
                                                            $subLabel = 'Đã chấm';
                                                            $subClass = 'lms-badge-success';
                                                        } elseif ($submitted) {
                                                            $subLabel = 'Đã nộp bài';
                                                            $subClass = 'lms-badge-success';
                                                        } else {
                                                            $subLabel = 'Chưa nộp bài';
                                                            $subClass = 'lms-badge-danger';
                                                        }
                                                        ?>
                                                        <div class="lms-hw-card mb-2">
                                                            <div class="lms-hw-card-head">
                                                                <span class="lms-hw-card-title">BTVN - <?= e((string) $row['full_name']) ?></span>
                                                                <span class="lms-badge <?= e($subClass) ?>"><?= e($subLabel) ?></span>
                                                                <?php if ($submitted || $graded): ?>
                                                                    <span class="ms-auto small <?= !empty($row['grade_overdue']) ? 'text-danger' : 'text-muted' ?>">
                                                                        <?= e((string) $row['grade_due_label']) ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="lms-hw-card-body">
                                                                <div class="fw-bold fs-6 mb-2">Bài nộp của học sinh</div>
                                                                <?php lms_component('file_grid', array(
                                                                    'files' => $row['hw_submission_files'],
                                                                    'empty' => 'Học sinh chưa nộp bài.',
                                                                )); ?>

                                                                <?php if ($graded): ?>
                                                                    <div class="fw-bold fs-6 mt-3 mb-2">Kết quả chấm</div>
                                                                    <p class="mb-1"><strong>Điểm:</strong> <?= e(isset($row['grade']) ? (string) $row['grade'] : '—') ?></p>
                                                                    <p class="mb-1"><?= nl2br(e((string) (isset($row['teacher_feedback_text']) ? $row['teacher_feedback_text'] : '—'))) ?></p>
                                                                    <?php if (!empty($row['teacher_feedback_files'])): ?>
                                                                        <?php lms_component('file_grid', array('files' => $row['teacher_feedback_files'])); ?>
                                                                    <?php endif; ?>
                                                                <?php elseif ($submitted): ?>
                                                                    <form method="post" enctype="multipart/form-data" class="mt-3"
                                                                          action="<?= e(lms_url('lms-hoc-vien-cua-toi', 'cham-btvn-lop')) ?>">
                                                                        <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                                                        <input type="hidden" name="class_id" value="<?= (int) $groupClass['id'] ?>">
                                                                        <input type="hidden" name="session_id" value="<?= (int) $session['id'] ?>">
                                                                        <input type="hidden" name="session_student_id" value="<?= (int) $row['id'] ?>">
                                                                        <div class="row g-2">
                                                                            <div class="col-md-4">
                                                                                <label class="form-label small">Điểm (0–20)</label>
                                                                                <?php lms_component('form/input', array(
                                                                                    'name' => 'grade',
                                                                                    'type' => 'number',
                                                                                    'min' => 0,
                                                                                    'max' => 20,
                                                                                    'step' => 0.25,
                                                                                    'class' => 'form-control form-control-sm',
                                                                                    'bare' => true,
                                                                                )); ?>
                                                                            </div>
                                                                            <div class="col-12">
                                                                                <label class="form-label small">Nhận xét</label>
                                                                                <?php lms_component('form/textarea', array(
                                                                                    'name' => 'teacher_feedback_text',
                                                                                    'rows' => 2,
                                                                                    'placeholder' => 'Nhập nội dung....',
                                                                                    'class' => 'form-control form-control-sm',
                                                                                    'bare' => true,
                                                                                )); ?>
                                                                            </div>
                                                                            <div class="col-12">
                                                                                <label class="form-label small">File đính kèm (tối đa 4)</label>
                                                                                <?php lms_component('form/file_upload', array(
                                                                                    'name' => 'teacher_feedback_files[]',
                                                                                    'id' => 'group-feedback-files-' . (int) $row['id'],
                                                                                    'max_files' => 4,
                                                                                    'browse_text' => 'chọn tệp nhận xét',
                                                                                )); ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="text-end mt-2">
                                                                            <?php lms_component('button', array(
                                                                                'type' => 'submit',
                                                                                'label' => 'Gửi',
                                                                                'variant' => 'primary',
                                                                                'size' => 'sm',
                                                                            )); ?>
                                                                        </div>
                                                                    </form>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal thêm buổi cho lớp -->

<div class="modal fade" id="lmsGroupAddSessionModal" tabindex="-1" aria-labelledby="lmsGroupAddSessionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(lms_url('lms-hoc-vien-cua-toi', 'them-buoi-lop')) ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="lmsGroupAddSessionLabel">Thêm buổi học</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                    <input type="hidden" name="class_id" value="<?= (int) $groupClass['id'] ?>">
                    <input type="hidden" name="topic_name" id="lmsGroupAddTopicInput" value="">
                    <div class="mb-2">
                        <label class="form-label">Chuyên đề</label>
                        <input type="text" class="form-control" id="lmsGroupAddTopicLabel" value="" readonly>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label">Ngày học <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="session_date" min="<?= e(date('Y-m-d')) ?>" required>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Bắt đầu <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="start_time" value="17:30" required>
                        </div>
                        <div class="col-sm-3">
                            <label class="form-label">Kết thúc <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="end_time" value="19:00" required>
                        </div>
                    </div>
                    <div class="mt-2">
                        <label class="form-label">Lý do <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="note" rows="3" required placeholder="Lý do thêm buổi..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <?php lms_component('button', array(
                        'type' => 'button',
                        'label' => 'Huỷ',
                        'variant' => 'outline-secondary',
                        'size' => 'md',
                        'attrs' => array('data-bs-dismiss' => 'modal'),
                    )); ?>
                    <?php lms_component('button', array(
                        'type' => 'submit',
                        'label' => 'Gửi yêu cầu',
                        'variant' => 'primary',
                        'size' => 'md',
                    )); ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var modalElement = document.getElementById('lmsGroupAddSessionModal');
        if (!modalElement || !window.bootstrap) {
            return;
        }
        var topicInput = document.getElementById('lmsGroupAddTopicInput');
        var topicLabel = document.getElementById('lmsGroupAddTopicLabel');
        var modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        document.querySelectorAll('.lms-group-add-session').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var topic = btn.getAttribute('data-topic') || '';
                if (topicInput) topicInput.value = topic;
                if (topicLabel) topicLabel.value = topic;
                modal.show();
            });
        });
    });
})();
</script>
