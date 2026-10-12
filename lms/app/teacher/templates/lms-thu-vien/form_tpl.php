<?php
/** Teacher library upload form. */
$types = \Lms\LibraryService::FILE_TYPES;
?>
<div class="lms-card">
    <div class="lms-card-header"><i class="fa-solid fa-file-arrow-up me-1"></i>Tải tài liệu lên thư viện</div>
    <div class="lms-card-body">
        <?php if ($errors): ?><div class="alert alert-danger"><?php foreach ($errors as $message): ?><div><?= e($message) ?></div><?php endforeach; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?= e(lms_url($alias, 'them')) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label" for="library-file">File (tối đa 100 MB)</label><input class="form-control" id="library-file" type="file" name="library_file" accept=".pdf,.docx,.xlsx,.pptx,.jpg,.jpeg,.png,.mp3,.mp4,.zip" required></div>
                <div class="col-md-6"><label class="form-label" for="library-title">Tên tài liệu</label><input class="form-control" id="library-title" name="title" maxlength="200"></div>
                <div class="col-md-6"><label class="form-label" for="library-type">Loại</label><select class="form-select" id="library-type" name="file_type"><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>"<?= $key === 'document' ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="library-class">Lớp (tuỳ chọn)</label><select class="form-select" id="library-class" name="class_id"><option value="0">Tài liệu chung</option><?php foreach ($classes as $class): ?><option value="<?= e((string) $class['id']) ?>"><?= e($class['class_code'] . ' — ' . $class['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="library-subject">Môn học (tuỳ chọn)</label><select class="form-select" id="library-subject" name="subject_id"><option value="0">Không gắn môn</option><?php foreach ($subjects as $subject): ?><option value="<?= e((string) $subject['id']) ?>"><?= e($subject['program_name'] . ' — ' . $subject['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label" for="library-topic">Chuyên đề (tuỳ chọn)</label><select class="form-select" id="library-topic" name="topic_id"><option value="0">Không gắn chuyên đề</option><?php foreach ($topics as $topic): ?><option value="<?= e((string) $topic['id']) ?>"><?= e($topic['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><label class="form-label" for="library-description">Mô tả</label><textarea class="form-control" id="library-description" name="description" rows="3"></textarea></div>
            </div>
            <div class="d-flex gap-2 mt-3"><button class="btn btn-lms" type="submit"><i class="fa-solid fa-upload me-1"></i>Tải lên</button><a class="btn btn-outline-secondary" href="<?= e(lms_url($alias)) ?>">Huỷ</a></div>
        </form>
    </div>
</div>
