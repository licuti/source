<?php
/**
 * Admin LMS — Sắp xếp lớp: view chi tiết lịch tuần 1 GV (mockup §4).
 * Data do source chuẩn bị: $detail (array('teacher', 'days', 'schedules', 'free_slots')),
 * $studentId, $alias.
 */
?>
<?php
$teacher    = $detail['teacher'];
$days       = $detail['days'];
$schedules  = $detail['schedules'];
$freeSlots  = $detail['free_slots'];

$rankLabels = \Lms\UserService::TEACHER_RANKS;
$genderLabels = \Lms\UserService::GENDERS;

// Lịch cố định theo ngày (mockup: ô hồng — mã HV/tên HV + khung giờ)
$byDay = array();
foreach ($schedules as $s) {
    $byDay[(int) $s['day_of_week']][] = $s;
}
// Lịch trống theo ngày (mockup: ô xanh — còn nhận thêm lớp)
$freeByDay = array();
foreach ($freeSlots as $f) {
    $freeByDay[(int) $f['day_of_week']][] = $f;
}

$fmtTime = function ($t) {
    return substr((string) $t, 0, 5);
};
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-calendar-week me-1"></i>
        Chi tiết lịch giáo viên - <?= e((string) $teacher['teacher_code']) ?> - <?= e((string) $teacher['full_name']) ?>
        <span class="ms-auto">
            <a href="<?= e(lms_url($alias, 'man', $studentId > 0 ? array('student_id' => $studentId) : array())) ?>"
               class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại Sắp xếp lớp
            </a>
        </span>
    </div>
    <div class="lms-card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="small">
                    <strong>Địa chỉ hiện tại:</strong> <?= e((string) ($teacher['current_address'] ?? '—')) ?><br>
                    <strong>Quận có thể dạy:</strong>
                    <?= $teacher['teachable_districts'] !== array()
                        ? implode(', ', array_map('e', $teacher['teachable_districts']))
                        : 'mọi quận' ?><br>
                    <strong>Dạy online:</strong> <?= !empty($teacher['can_teach_online']) ? 'Có' : 'Không' ?>
                </div>
            </div>
            <div class="col-md-6">
                <div class="small">
                    <strong>Hạng:</strong>
                    <span class="lms-badge <?= e(lms_xep_lop_rank_badge($teacher['rank'])) ?>">
                        <?= e(isset($rankLabels[$teacher['rank']]) ? $rankLabels[$teacher['rank']] : $teacher['rank']) ?>
                    </span><br>
                    <strong>Số lớp đang dạy:</strong> <?= e((string) $teacher['current_classes']) ?>
                    / <?= e((string) ($teacher['max_classes'] > 0 ? $teacher['max_classes'] : '∞')) ?><br>
                    <strong>Trạng thái:</strong> <?= e($teacher['status'] === 'active' ? 'Đang dạy' : 'Tạm ngưng') ?>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <?php foreach ($days as $dow => $label): ?>
                <div class="col">
                    <div class="text-center small fw-bold pb-1 border-bottom"><?= e($label) ?></div>
                    <div class="d-flex flex-column gap-1 mt-1">
                        <?php if (isset($byDay[$dow])): ?>
                            <?php foreach ($byDay[$dow] as $s): ?>
                                <div class="lms-schedule-block lms-schedule-busy" title="Lịch cố định lớp đang dạy">
                                    <strong><?= e($fmtTime($s['start_time'])) ?>–<?= e($fmtTime($s['end_time'])) ?></strong>
                                    <div class="small"><?= e((string) ($s['class_name'] ?? $s['class_code'])) ?></div>
                                    <?php if (!empty($s['student_names'])): ?>
                                        <div class="small lms-text-muted-sm"><?= e((string) $s['student_names']) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (isset($freeByDay[$dow])): ?>
                            <?php foreach ($freeByDay[$dow] as $f): ?>
                                <div class="lms-schedule-block lms-schedule-free" title="Lịch trống — còn nhận thêm lớp">
                                    <strong><?= e($fmtTime($f['start_time'])) ?>–<?= e($fmtTime($f['end_time'])) ?></strong>
                                    <div class="small">Còn trống</div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!isset($byDay[$dow]) && !isset($freeByDay[$dow])): ?>
                            <div class="lms-schedule-block lms-schedule-none small">—</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-3 small">
            <span class="lms-badge lms-badge-danger">Lịch cố định</span> lớp đang dạy
            <span class="lms-badge lms-badge-success mx-1">Còn trống</span> lịch rống GV đăng ký nhận thêm lớp
        </div>
    </div>
</div>
