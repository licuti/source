<?php
/**
 * Admin LMS — Quản lý vi phạm: view cấu hình ngưỡng (violation_thresholds, ADM-06).
 * Data do source chuẩn bị: $thresholds, $editRow, $errors, $alias.
 */
$isEdit = !empty($editRow) && is_array($editRow);
$post   = $_POST;

$row = $isEdit ? $editRow : array();

// Giá trị sticky: ưu tiên input vừa submit (khi validate lỗi), rồi record đang sửa.
function lvp_field($key, $default = '')
{
    global $post, $row;
    if (isset($post[$key])) {
        return (string) $post[$key];
    }
    if (isset($row[$key]) && $row[$key] !== null) {
        return (string) $row[$key];
    }
    return (string) $default;
}
?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="lms-card">
            <div class="lms-card-header">
                <i class="fa-solid fa-sliders me-1"></i> Danh sách ngưỡng vi phạm
            </div>
            <div class="lms-card-body">
                <?php if (empty($thresholds)): ?>
                    <p class="text-muted mb-0 py-3 text-center">
                        Chưa có ngưỡng nào. Tạo ngưỡng đầu tiên ở form bên phải.
                    </p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="lms-table">
                            <thead>
                                <tr>
                                    <th class="text-end">ID</th>
                                    <th>Số lớp</th>
                                    <th class="text-end">Miễn</th>
                                    <th class="text-end">Cảnh báo</th>
                                    <th class="text-end">Hạ phí</th>
                                    <th class="text-end">% phí sau hạ</th>
                                    <th>Hiệu lực</th>
                                    <th class="text-end">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($thresholds as $t): ?>
                                    <?php
                                    $now = date('Y-m-d');
                                    $effective = strcmp((string) $t['effective_from'], $now) <= 0
                                        && (empty($t['effective_to']) || strcmp((string) $t['effective_to'], $now) >= 0);
                                    ?>
                                    <tr>
                                        <td class="text-end"><?= e((string) (int) $t['id']) ?></td>
                                        <td>
                                            <?= e((string) (int) $t['min_classes']) ?>–
                                            <?= $t['max_classes'] === null ? '∞' : e((string) (int) $t['max_classes']) ?>
                                            lớp
                                            <?php if ($effective): ?>
                                                <span class="lms-badge lms-badge-success">đang áp dụng</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end"><?= e((string) (int) $t['ticks_exempted']) ?></td>
                                        <td class="text-end"><?= e((string) (int) $t['ticks_to_warn']) ?></td>
                                        <td class="text-end"><?= e((string) (int) $t['ticks_to_demote_fee']) ?></td>
                                        <td class="text-end"><?= e((string) $t['fee_percentage_after_demote']) ?>%</td>
                                        <td class="text-nowrap">
                                            <?= e((string) $t['effective_from']) ?>
                                            → <?= $t['effective_to'] === null ? '∞' : e((string) $t['effective_to']) ?>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <a href="<?= e(lms_url($alias, 'nguong', array('id' => (int) $t['id']))) ?>"
                                               class="btn btn-sm btn-outline-lms" title="Sửa">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <form method="post" action="<?= e(lms_url($alias, 'xoa-nguong')) ?>" class="d-inline"
                                                  onsubmit="return confirm('Xoá ngưỡng này?');">
                                                <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                                <input type="hidden" name="id" value="<?= e((string) (int) $t['id']) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Xoá">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="lms-card">
            <div class="lms-card-header">
                <i class="fa-solid fa-pen-to-square me-1"></i>
                <?= $isEdit ? 'Sửa ngưỡng #' . e((string) (int) $editRow['id']) : 'Thêm ngưỡng mới' ?>
            </div>
            <div class="lms-card-body">
                <form method="post" action="<?= e(lms_url($alias, 'luu-nguong')) ?>" class="lms-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                    <?php if ($isEdit): ?>
                        <input type="hidden" name="id" value="<?= e((string) (int) $editRow['id']) ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-min">Số lớp tối thiểu <span class="text-danger">*</span></label>
                            <input type="number" min="1" step="1" class="form-control" id="fld-min"
                                   name="min_classes" value="<?= e(lvp_field('min_classes', '1')) ?>" required>
                            <?= lms_field_error($errors, 'min_classes') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-max">Số lớp tối đa</label>
                            <input type="number" min="1" step="1" class="form-control" id="fld-max"
                                   name="max_classes" value="<?= e(lvp_field('max_classes')) ?>"
                                   placeholder="Để trống = không giới hạn">
                            <?= lms_field_error($errors, 'max_classes') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-exempt">Tick miễn <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="fld-exempt"
                                   name="ticks_exempted" value="<?= e(lvp_field('ticks_exempted', '0')) ?>" required>
                            <?= lms_field_error($errors, 'ticks_exempted') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-warn">Ngưỡng cảnh báo <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="fld-warn"
                                   name="ticks_to_warn" value="<?= e(lvp_field('ticks_to_warn', '0')) ?>" required>
                            <?= lms_field_error($errors, 'ticks_to_warn') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-demote">Ngưỡng hạ phí <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1" class="form-control" id="fld-demote"
                                   name="ticks_to_demote_fee" value="<?= e(lvp_field('ticks_to_demote_fee', '0')) ?>" required>
                            <?= lms_field_error($errors, 'ticks_to_demote_fee') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-fee">% phí sau hạ <span class="text-danger">*</span></label>
                            <input type="number" min="0" max="100" step="0.5" class="form-control" id="fld-fee"
                                   name="fee_percentage_after_demote"
                                    value="<?= e(lvp_field('fee_percentage_after_demote', '30')) ?>" required>
                            <?= lms_field_error($errors, 'fee_percentage_after_demote') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-from">Hiệu lực từ <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="fld-from" name="effective_from"
                                   value="<?= e(lvp_field('effective_from', date('Y-m-d'))) ?>" required>
                            <?= lms_field_error($errors, 'effective_from') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-to">Hết hiệu lực</label>
                            <input type="date" class="form-control" id="fld-to" name="effective_to"
                                   value="<?= e(lvp_field('effective_to')) ?>" placeholder="Để trống = vô thời hạn">
                            <?= lms_field_error($errors, 'effective_to') ?>
                        </div>
                    </div>

                    <div class="lms-text-muted-sm mb-3">
                        tick_thực_tế = tổng tick − tick miễn (theo khoảng số lớp GV đang dạy).
                        Hai khoảng số lớp không được trùng nhau khi cùng hiệu lực.
                    </div>

                    <div class="lms-form-actions">
                        <button type="submit" class="btn btn-lms px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> <?= $isEdit ? 'Lưu thay đổi' : 'Tạo ngưỡng' ?>
                        </button>
                        <?php if ($isEdit): ?>
                            <a href="<?= e(lms_url($alias, 'nguong')) ?>" class="btn btn-outline-secondary">Huỷ sửa</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
