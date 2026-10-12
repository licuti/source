<?php
/** Admin library create/edit form. */
$types = \Lms\LibraryService::FILE_TYPES;
?>
<div class="lms-card">
    <div class="lms-card-header"><i class="fa-solid fa-file-arrow-up me-1"></i><?= e($title) ?></div>
    <div class="lms-card-body">
        <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?= e(lms_url($alias, $isEditMode ? 'sua' : 'them', $isEditMode ? array('id' => $formId) : array())) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
            <div class="row g-3">
                <?php if ($isEditMode): ?>
                    <div class="col-12"><label class="form-label">File hiện tại</label><div><?= e((string) $row['file_url']) ?></div></div>
                <?php else: ?>
                    <div class="col-md-8"><label class="form-label" for="library-file">File (tối đa 100 MB)</label><input class="form-control" id="library-file" type="file" name="library_file" accept=".pdf,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.mp3,.mp4,.zip" required><div class="form-text">PDF, Office, ảnh, audio, video hoặc ZIP.</div></div>
                <?php endif; ?>
                <div class="col-md-6"><label class="form-label" for="library-title">Tên tài liệu</label><input class="form-control" id="library-title" name="title" maxlength="200" value="<?= e((string) ($row['title'] ?? '')) ?>"></div>
                <div class="col-md-6"><label class="form-label" for="library-type">Loại</label><select class="form-select" id="library-type" name="file_type"><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>"<?= lms_selected((string) ($row['file_type'] ?? 'document'), $key) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="library-class">Lớp (tuỳ chọn)</label><select class="form-select" id="library-class" name="class_id"><option value="0">Tài liệu chung</option><?php foreach ($classes as $class): ?><option value="<?= e((string) $class['id']) ?>"<?= lms_selected((string) ($row['class_id'] ?? ''), (string) $class['id']) ?>><?= e($class['class_code'] . ' — ' . $class['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="library-subject">Môn học (tuỳ chọn)</label><select class="form-select" id="library-subject" name="subject_id"><option value="0">Không gắn môn</option><?php foreach ($subjects as $subject): ?><option value="<?= e((string) $subject['id']) ?>"<?= lms_selected((string) ($row['subject_id'] ?? ''), (string) $subject['id']) ?>><?= e($subject['program_name'] . ' — ' . $subject['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="library-topic">Chuyên đề (tuỳ chọn)</label><select class="form-select" id="library-topic" name="topic_id"><option value="0">Không gắn chuyên đề</option><?php foreach ($topics as $topic): ?><option value="<?= e((string) $topic['id']) ?>"<?= lms_selected((string) ($row['topic_id'] ?? ''), (string) $topic['id']) ?>><?= e($topic['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" id="library-downloadable" name="is_downloadable" value="1"<?= !isset($row['is_downloadable']) || (int) $row['is_downloadable'] === 1 ? ' checked' : '' ?>><label class="form-check-label" for="library-downloadable">Cho phép giáo viên tải tài liệu này</label></div></div>
                <div class="col-12"><label class="form-label" for="library-description">Mô tả</label><textarea class="form-control" id="library-description" name="description" rows="3"><?= e((string) ($row['description'] ?? '')) ?></textarea></div>
            </div>
            <div class="d-flex gap-2 mt-3"><button class="btn btn-lms" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Lưu</button><a class="btn btn-outline-secondary" href="<?= e(lms_url($alias)) ?>">Huỷ</a></div>
        </form>
    </div>
</div>
