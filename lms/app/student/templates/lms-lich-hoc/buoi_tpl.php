<?php
/**
 * Student — Chi tiết buổi học (view, mockup §4).
 * Data do source chuẩn bị: $session, $alias, $weekMonday.
 */
?>
<?php $csrf = lms_csrf_token(); ?>

<div class="lms-card lms-class-detail">
    <div class="lms-card-header">
        <a href="<?= e(lms_url($alias, '', lmlh_week_params($weekMonday))) ?>" class="lms-back-link">
            <i class="fa-solid fa-arrow-left me-1"></i> Lịch học
        </a>
        <strong class="ms-2">Buổi học <?= e(lmlh_session_title($session)) ?></strong>
    </div>
    <div class="lms-card-body">
        <div class="lms-class-detail-meta">
            <span><i class="fa-solid fa-school me-1"></i>Lớp <?= e((string) $session['class_name']) ?></span>
            <span><i class="fa-solid fa-chalkboard-user me-1"></i>GV <?= e((string) $session['teacher_name']) ?></span>
            <span><i class="fa-solid fa-clock me-1"></i><?= e(substr((string) $session['start_time'], 0, 5)) ?>–<?= e(substr((string) $session['end_time'], 0, 5)) ?></span>
        </div>

        <?php if (!empty($session['is_future'])): ?>
            <p class="lms-hw-future"><i class="fa-solid fa-clock me-1"></i>Chưa diễn ra</p>
            <p class="text-muted" style="font-size:.85rem;">
                Chỉ khi tới ngày học thì các ô buổi học này mới được mở ra.
            </p>
        <?php else: ?>
            <div class="lms-hw-row">
                <span class="lms-hw-label">BTVN:</span>
                <span><?= !empty($session['hw_content']) ? nl2br(e((string) $session['hw_content'])) : '<em class="text-muted">Chưa có BTVN</em>' ?></span>
            </div>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Hạn nộp:</span>
                <?php if (!empty($session['deadline_label'])): ?>
                    <span class="lms-hw-due<?= $session['deadline_overdue'] ? ' lms-hw-due-over' : '' ?>">
                        <?= e($session['deadline_label']) ?>
                        <?php if (!empty($session['deadline_text'])): ?>
                            <em>(<?= e($session['deadline_text']) ?>)</em>
                        <?php endif; ?>
                    </span>
                <?php else: ?>
                    <em class="text-muted">Chưa có hạn nộp</em>
                <?php endif; ?>
            </div>
            <div class="lms-hw-row">
                <span class="lms-hw-label">Nhận xét buổi học:</span>
                <span><?= !empty($session['lesson_summary']) ? nl2br(e((string) $session['lesson_summary'])) : '<em class="text-muted">Chưa có nhận xét</em>' ?></span>
            </div>
            <?php if (!empty($session['hw_attached_files'])): ?>
                <div class="lms-hw-row">
                    <span class="lms-hw-label">Tệp đính kèm:</span>
                    <span class="lms-file-list">
                        <?php foreach ($session['hw_attached_files'] as $f): ?>
                            <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener">
                                <i class="fa-solid fa-paperclip me-1"></i><?= e(basename((string) $f)) ?>
                            </a>
                        <?php endforeach; ?>
                    </span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($session['is_future'])): ?>
<div class="lms-card mt-3">
    <div class="lms-card-header">
        <i class="fa-solid fa-file-pen me-1"></i> Nộp bài tập về nhà
    </div>
    <div class="lms-card-body">
        <?php if (!empty($session['graded'])): ?>
            <span class="lms-badge lms-badge-success">Đã chấm: <?= e(number_format((float) $session['grade'], 2)) ?>/20</span>
        <?php else: ?>
            <span class="lms-badge <?= e($session['hw_label'][1]) ?>"><?= e($session['hw_label'][0]) ?></span>
        <?php endif; ?>

        <?php if (!empty($session['hw_submission_files'])): ?>
            <div class="lms-hw-row mt-2">
                <span class="lms-hw-label">Bài đã nộp:</span>
                <span class="lms-file-list">
                    <?php foreach ($session['hw_submission_files'] as $f): ?>
                        <a href="<?= e(lms_upload_url((string) $f)) ?>" target="_blank" rel="noopener">
                            <i class="fa-solid fa-file me-1"></i><?= e(basename((string) $f)) ?>
                        </a>
                    <?php endforeach; ?>
                </span>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= e(lms_url('lms-khoa-hoc', 'nop-btvn')) ?>"
              class="lms-form lms-submit-form mt-2" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="session_id" value="<?= e((int) $session['id']) ?>">
            <input type="hidden" name="back" value="buoi">
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
    </div>
</div>
<?php endif; ?>
