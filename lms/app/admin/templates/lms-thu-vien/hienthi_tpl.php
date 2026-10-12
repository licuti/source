<?php
/** Admin library card grid. */
$types = \Lms\LibraryService::FILE_TYPES;
?>
<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-book-open"></i> Thư viện tài liệu
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) $total) ?></span>
        <?php if ($canEdit): ?><a href="<?= e(lms_url($alias, 'them')) ?>" class="btn btn-sm btn-lms"><i class="fa-solid fa-upload me-1"></i>Tải tài liệu</a><?php endif; ?>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="row g-2 mb-3">
            <div class="col-md-5"><input class="form-control" name="q" value="<?= e($filterQ) ?>" placeholder="Tìm tên hoặc mô tả tài liệu"></div>
            <div class="col-md-3"><select class="form-select" name="file_type"><option value="">Tất cả loại</option><?php foreach ($types as $key => $label): ?><option value="<?= e($key) ?>"<?= lms_selected($filterType, $key) ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-3"><select class="form-select" name="subject_id"><option value="0">Tất cả môn</option><?php foreach ($subjects as $subject): ?><option value="<?= e((string) $subject['id']) ?>"<?= lms_selected((string) $filterSubject, (string) $subject['id']) ?>><?= e($subject['program_name'] . ' — ' . $subject['name']) ?></option><?php endforeach; ?></select></div>
            <div class="col-md-1"><button class="btn btn-primary w-100" type="submit" aria-label="Tìm"><i class="fa-solid fa-magnifying-glass"></i></button></div>
        </form>
        <?php if (!$items): ?>
            <p class="text-muted text-center py-5 mb-0">Chưa có tài liệu phù hợp.</p>
        <?php else: ?>
            <div class="row g-3 lms-library-grid">
                <?php foreach ($items as $file): ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <article class="lms-card lms-library-card h-100">
                            <div class="lms-card-body">
                                <div class="lms-library-icon"><i class="fa-solid <?= e(lms_library_icon($file['file_type'])) ?>"></i></div>
                                <span class="lms-badge lms-badge-primary"><?= e(isset($types[$file['file_type']]) ? $types[$file['file_type']] : $file['file_type']) ?></span>
                                <h3 class="h6 mt-2 mb-1"><?= e((string) $file['title']) ?></h3>
                                <div class="small text-muted"><?= e((string) ($file['subject_name'] ?: 'Tài liệu chung')) ?><?= !empty($file['class_code']) ? ' · ' . e($file['class_code']) : '' ?></div>
                                <div class="small text-muted mt-2"><?= e(\Lms\LibraryService::formatSize($file['file_size_bytes'])) ?> · <?= e((string) $file['download_count']) ?> lượt tải</div>
                                <?php if (empty($file['is_downloadable'])): ?><span class="lms-badge lms-badge-warning mt-2">Đã tắt tải xuống cho GV</span><?php endif; ?>
                                <?php if (!empty($file['description'])): ?><p class="small mt-2 mb-0"><?= e((string) $file['description']) ?></p><?php endif; ?>
                            </div>
                            <div class="lms-card-body pt-0 d-flex gap-2">
                                <a class="btn btn-sm btn-outline-lms" href="<?= e(lms_url($alias, 'tai', array('id' => (int) $file['id']))) ?>"><i class="fa-solid fa-download me-1"></i>Tải</a>
                                <?php if ($canEdit): ?><a class="btn btn-sm btn-outline-secondary" href="<?= e(lms_url($alias, 'sua', array('id' => (int) $file['id']))) ?>" aria-label="Sửa"><i class="fa-solid fa-pen"></i></a><?php endif; ?>
                                <?php if ($canDelete): ?><form method="post" action="<?= e(lms_url($alias, 'xoa')) ?>" onsubmit="return confirm('Xoá tài liệu này?');"><input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>"><input type="hidden" name="id" value="<?= e((string) $file['id']) ?>"><button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Xoá"><i class="fa-solid fa-trash"></i></button></form><?php endif; ?>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
            <?= lms_pagination(lms_url($alias), $page, $totalPages, array('q' => $filterQ, 'file_type' => $filterType, 'subject_id' => $filterSubject)) ?>
        <?php endif; ?>
    </div>
</div>
<?php
function lms_library_icon($type)
{
    $icons = array('slide' => 'fa-person-chalkboard', 'exam' => 'fa-file-circle-question', 'video' => 'fa-film', 'audio' => 'fa-headphones', 'document' => 'fa-file-lines', 'other' => 'fa-paperclip');
    return isset($icons[$type]) ? $icons[$type] : $icons['other'];
}
?>
