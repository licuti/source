<?php
/**
 * Admin LMS — Quản lý vi phạm: view form thêm tick thủ công (ADM-06).
 * Data do source chuẩn bị: $errors, $teachers, $tickSummary, $alias.
 */
$reasonLabels = \Lms\TickService::MANUAL_REASONS;

$post = $_POST;
?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="lms-card">
            <div class="lms-card-header">
                <i class="fa-solid fa-triangle-exclamation me-1"></i> Thêm tick thủ công
            </div>
            <div class="lms-card-body">
                <form method="post" action="<?= e(lms_url($alias, 'them')) ?>" class="lms-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-teacher">Giáo viên <span class="text-danger">*</span></label>
                        <select class="form-select" id="fld-teacher" name="teacher_id" required
                                onchange="lvpPickTeacher(this.value)">
                            <option value="">— Chọn giáo viên —</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?= e((string) (int) $t['user_id']) ?>"<?= lms_selected(
                                    lms_form_old($post, array(), 'teacher_id'),
                                    (int) $t['user_id']
                                ) ?>>
                                    <?= e((string) ($t['teacher_code'] ?? '')) ?> — <?= e((string) ($t['full_name'] ?? '')) ?>
                                    <?= (string) ($t['status'] ?? '') !== 'active' ? ' (đã nghỉ)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= lms_field_error($errors, 'teacher_id') ?>
                        <div class="lms-text-muted-sm mt-1">Chọn giáo viên để xem tình trạng tick hiện tại.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-reason">Lý do <span class="text-danger">*</span></label>
                            <select class="form-select" id="fld-reason" name="reason" required>
                                <option value="">— Chọn lý do —</option>
                                <?php foreach ($reasonLabels as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(lms_form_old($post, array(), 'reason'), $k) ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'reason') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-tick">Số tick <span class="text-danger">*</span></label>
                            <select class="form-select" id="fld-tick" name="tick_amount" required>
                                <option value="">— Chọn —</option>
                                <option value="1"<?= lms_selected(lms_form_old($post, array(), 'tick_amount'), '1') ?>>+1 (phạt)</option>
                                <option value="-1"<?= lms_selected(lms_form_old($post, array(), 'tick_amount'), '-1') ?>>-1 (miễn giảm)</option>
                            </select>
                            <div class="lms-text-muted-sm mt-1">
                                Phạt thủ công đi kèm +1, miễn giảm đi kèm -1.
                            </div>
                            <?= lms_field_error($errors, 'tick_amount') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-note">Ghi chú</label>
                        <textarea class="form-control" id="fld-note" name="note" rows="3"
                                  placeholder="Lý do thêm tick (hiển thị trong lịch sử đối soát)"><?= e(lms_form_old($post, array(), 'note')) ?></textarea>
                        <?= lms_field_error($errors, 'note') ?>
                    </div>

                    <div class="lms-form-actions">
                        <button type="submit" class="btn btn-lms px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Thêm tick
                        </button>
                        <a href="<?= e(lms_url($alias)) ?>" class="btn btn-outline-secondary">Huỷ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="lms-card">
            <div class="lms-card-header"><i class="fa-solid fa-circle-info me-1"></i> Tình trạng tick</div>
            <div class="lms-card-body">
                <?php if (!empty($tickSummary) && is_array($tickSummary)): ?>
                    <?php $th = isset($tickSummary['threshold']) ? $tickSummary['threshold'] : null; ?>
                    <table class="lms-table">
                        <tbody>
                            <tr>
                                <td>Tổng tick (gồm trừ miễn giảm)</td>
                                <td class="text-end"><strong><?= e((string) (int) $tickSummary['raw']) ?></strong></td>
                            </tr>
                            <tr>
                                <td>Miễn giảm theo số lớp</td>
                                <td class="text-end">-<?= e((string) (int) $tickSummary['exempted']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Tick thực tế</strong></td>
                                <td class="text-end">
                                    <strong class="<?= (int) $tickSummary['real'] > 0 ? 'text-danger' : 'text-success' ?>">
                                        <?= e((string) (int) $tickSummary['real']) ?>
                                    </strong>
                                </td>
                            </tr>
                            <?php if (is_array($th)): ?>
                                <tr>
                                    <td>Ngưỡng cảnh báo</td>
                                    <td class="text-end"><?= e((string) (int) $th['ticks_to_warn']) ?> tick</td>
                                </tr>
                                <tr>
                                    <td>Ngưỡng hạ phí dịch vụ</td>
                                    <td class="text-end"><?= e((string) (int) $th['ticks_to_demote_fee']) ?> tick</td>
                                </tr>
                                <tr>
                                    <td>Khoảng số lớp đang áp dụng</td>
                                    <td class="text-end">
                                        <?= e((string) (int) $th['min_classes']) ?>–
                                        <?= $th['max_classes'] === null ? '∞' : e((string) (int) $th['max_classes']) ?>
                                        lớp
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <div class="lms-text-muted-sm mt-2">
                        Tick thực tế = tổng tick − số miễn (theo số lớp đang dạy). Vượt ngưỡng cảnh báo
                        → hệ thống gửi thông báo; vượt ngưỡng hạ phí → tháng sau giảm % phí dịch vụ.
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">Chọn giáo viên để xem tổng tick và ngưỡng áp dụng.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function lvpPickTeacher(teacherId) {
        var base = <?= json_encode(lms_url($alias, 'them')) ?>;
        window.location.href = teacherId !== '' ? base + '?teacher_id=' + encodeURIComponent(teacherId) : base;
    }
</script>
