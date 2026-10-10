<?php
/**
 * Student — Chi tiết chuyên đề: buổi học + nộp BTVN/Test (view, mockup §4 + §6/§8).
 * Data do source chuẩn bị: $class, $topic, $sessions, $tests, $alias.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<div class="lms-card lms-class-detail">
    <div class="lms-card-header">
        <a href="<?= e(lms_url($alias, 'lop', array('class_id' => (int) $class['id']))) ?>" class="lms-back-link">
            <i class="fa-solid fa-arrow-left me-1"></i> <?= e((string) $class['name']) ?>
        </a>
        <strong class="ms-2">Chuyên đề: <?= e((string) $topic['name']) ?></strong>
    </div>
    <div class="lms-card-body">
        <div class="lms-topic-card-status">
            <?php if ($topic['max_sessions'] === null): ?>
                Đã dạy: <?= e((string) number_format((int) $topic['done_sessions'])) ?> buổi
            <?php else: ?>
                Đã dạy: <?= e((string) number_format((int) $topic['done_sessions'])) ?>
                / <?= e((string) number_format((int) $topic['max_sessions'])) ?> buổi
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ===================== Nội dung buổi học (§4) ===================== -->
<div class="lms-card mt-3">
    <div class="lms-card-header">
        <i class="fa-solid fa-calendar-day me-1"></i> Nội dung buổi học
    </div>
    <div class="lms-card-body">
        <?php if (empty($sessions)): ?>
            <p class="text-muted mb-0">Chưa có buổi học nào cho chuyên đề này.</p>
        <?php else: ?>
            <?php foreach ($sessions as $s): ?>
                <div class="lms-hw-card">
                    <div class="lms-hw-card-head">
                        <span class="lms-hw-card-title">
                            <i class="fa-solid fa-calendar-day me-1"></i>
                            Buổi học <?= e(lmkh_session_title($s)) ?>
                        </span>
                        <?php if (!empty($s['graded'])): ?>
                            <span class="lms-badge lms-badge-success">Đã chấm: <?= e(number_format((float) $s['grade'], 2)) ?>/20</span>
                        <?php else: ?>
                            <span class="lms-badge <?= e($s['hw_label'][1]) ?>"><?= e($s['hw_label'][0]) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lms-hw-card-body">
                        <?php if (!empty($s['is_future'])): ?>
                            <p class="lms-hw-future"><i class="fa-solid fa-clock me-1"></i>Chưa diễn ra</p>
                            <p class="text-muted mb-0" style="font-size:.85rem;">
                                Chỉ khi tới ngày học thì các ô buổi học này mới được mở ra.
                            </p>
                        <?php else: ?>
                            <div class="lms-hw-row">
                                <span class="lms-hw-label">Chuyên đề:</span>
                                <span><?= e((string) $topic['name']) ?></span>
                            </div>
                            <div class="lms-hw-row">
                                <span class="lms-hw-label">BTVN:</span>
                                <span><?= !empty($s['hw_content']) ? nl2br(e((string) $s['hw_content'])) : '<em class="text-muted">Chưa có BTVN</em>' ?></span>
                            </div>
                            <div class="lms-hw-row">
                                <span class="lms-hw-label">Hạn nộp:</span>
                                <?php if (!empty($s['deadline_label'])): ?>
                                    <span class="lms-hw-due<?= $s['deadline_overdue'] ? ' lms-hw-due-over' : '' ?>">
                                        <?= e($s['deadline_label']) ?>
                                        <?php if (!empty($s['deadline_text'])): ?>
                                            <em>(<?= e($s['deadline_text']) ?>)</em>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <em class="text-muted">Chưa có hạn nộp</em>
                                <?php endif; ?>
                            </div>
                            <div class="lms-hw-row">
                                <span class="lms-hw-label">Nhận xét buổi học:</span>
                                <span><?= !empty($s['lesson_summary']) ? nl2br(e((string) $s['lesson_summary'])) : '<em class="text-muted">Chưa có nhận xét</em>' ?></span>
                            </div>
                            <?php if (!empty($s['teacher_feedback_text'])): ?>
                                <div class="lms-hw-row">
                                    <span class="lms-hw-label">Nhận xét GV:</span>
                                    <span><?= nl2br(e((string) $s['teacher_feedback_text'])) ?></span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($s['hw_attached_files'])): ?>
                                <div class="lms-hw-row">
                                    <span class="lms-hw-label">Tệp đính kèm:</span>
                                    <span class="lms-file-list">
                                        <?php foreach ($s['hw_attached_files'] as $f): ?>
                                            <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-paperclip me-1"></i><?= e(basename((string) $f)) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($s['hw_submission_files'])): ?>
                                <div class="lms-hw-row">
                                    <span class="lms-hw-label">Bài đã nộp:</span>
                                    <span class="lms-file-list">
                                        <?php foreach ($s['hw_submission_files'] as $f): ?>
                                            <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener">
                                                <i class="fa-solid fa-file me-1"></i><?= e(basename((string) $f)) ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </span>
                                </div>
                            <?php endif; ?>

                            <!-- Form nộp BTVN (mặc định mở dù GV chưa giao — §13 L249) -->
                            <form method="post" action="<?= e(lms_url($alias, 'nop-btvn')) ?>"
                                  class="lms-form lms-submit-form" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                                <input type="hidden" name="session_id" value="<?= e((int) $s['id']) ?>">
                                <input type="hidden" name="back" value="khoa-hoc">
                                <div class="lms-upload-slots">
                                    <?php for ($i = 0; $i < 4; $i++): ?>
                                        <input type="file" name="hw_files[]" class="form-control form-control-sm"
                                               accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.docx,.xlsx,.pptx,.mp3,.mp4,.zip">
                                    <?php endfor; ?>
                                </div>
                                <div class="lms-form-actions">
                                    <button type="submit" class="btn btn-lms btn-sm">
                                        <i class="fa-solid fa-file-upload me-1"></i> Nộp BTVN
                                    </button>
                                    <small class="text-muted">Tối đa 4 file (pdf, ảnh, office, zip).</small>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- ===================== Bài test + Minitest (§6 + §8) ===================== -->
<?php
$testsByType = array('test' => array(), 'minitest' => array());
foreach ($tests as $t) {
    $type = (string) $t['test_type'];
    if (!isset($testsByType[$type])) {
        $testsByType[$type] = array();
    }
    $testsByType[$type][] = $t;
}
?>

<div class="lms-card mt-3">
    <div class="lms-card-header">
        <i class="fa-solid fa-file-pen me-1"></i> Bài test chuyên đề
    </div>
    <div class="lms-card-body">
        <?php if (empty($testsByType['test'])): ?>
            <p class="text-muted mb-0">
                <span class="lms-badge lms-badge-secondary me-1">Chưa có bài test</span>
                Bài test sẽ được giáo viên/quản trị viên tạo và duyệt. Bạn vui lòng đợi nhé.
            </p>
        <?php else: ?>
            <?php foreach ($testsByType['test'] as $t): ?>
                <?php include __DIR__ . '/_test_section_tpl.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="lms-card mt-3">
    <div class="lms-card-header">
        <i class="fa-solid fa-clipboard-question me-1"></i> Minitest chuyên đề
    </div>
    <div class="lms-card-body">
        <?php if (empty($testsByType['minitest'])): ?>
            <p class="text-muted mb-0">
                <span class="lms-badge lms-badge-secondary me-1">Chưa có minitest</span>
                Minitest sẽ được quản trị viên duyệt trước khi bạn nộp bài.
            </p>
        <?php else: ?>
            <?php foreach ($testsByType['minitest'] as $t): ?>
                <?php include __DIR__ . '/_test_section_tpl.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
