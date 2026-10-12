<?php
/**
 * Admin LMS — Sắp xếp lớp: view danh sách HV chờ xếp + kết quả match GV (ADM-04).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page, $filterQ,
 * $filterSubject, $subjectOptions, $studentId, $student, $matched, $canEdit, $alias.
 */
?>
<?php
$weekdays = \Lms\UserService::WEEKDAYS;
$rankLabels = \Lms\UserService::TEACHER_RANKS;
$genderLabels = \Lms\UserService::GENDERS;

$slotText = function ($slots) {
    $out = array();
    $weekdays = \Lms\UserService::WEEKDAYS;
    foreach ($slots as $s) {
        $day = isset($weekdays[(int) $s['day']]) ? $weekdays[(int) $s['day']] : '?';
        $out[] = $day . ' ' . substr((string) $s['start'], 0, 5) . '-' . substr((string) $s['end'], 0, 5);
    }
    return implode('; ', $out);
};
?>

<div class="row g-3">
    <!-- Cột trái: danh sách HV chờ xếp -->
    <div class="col-lg-4">
        <div class="lms-card h-100">
            <div class="lms-card-header">
                <i class="fa-solid fa-users me-1"></i> Học viên chờ xếp
                <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?></span>
            </div>
            <div class="lms-card-body">
                <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
                    <div class="col-12">
                        <input type="text" class="form-control form-control-sm" name="q"
                               value="<?= e($filterQ) ?>" placeholder="Mã / tên / username học viên"
                               aria-label="Tìm học viên">
                    </div>
                    <div class="col-12">
                        <select class="form-select form-select-sm" name="subject_id" aria-label="Lọc theo môn">
                            <option value="">— Tất cả môn —</option>
                            <?php foreach ($subjectOptions as $sid => $s): ?>
                                <option value="<?= e((string) $sid) ?>"<?= lms_selected(
                                    (string) $filterSubject,
                                    (string) $sid
                                ) ?>><?= e($s['program']) ?> — <?= e($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm
                        </button>
                    </div>
                </form>

                <?php if (empty($items)): ?>
                    <p class="text-muted mb-0 py-4 text-center">
                        <i class="fa-solid fa-check d-block mb-2" style="font-size:1.6rem;"></i>
                        Không còn học viên nào chờ xếp lớp.
                    </p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($items as $r): ?>
                            <a href="<?= e(lms_url($alias, 'man', array(
                                'student_id' => (int) $r['user_id'],
                                'q' => $filterQ,
                                'subject_id' => $filterSubject,
                            ))) ?>"
                               class="list-group-item list-group-item-action <?= $studentId === (int) $r['user_id'] ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between align-items-start">
                                    <strong><?= e((string) $r['full_name']) ?></strong>
                                    <small class="lms-text-muted-sm"><?= e((string) ($r['student_code'] ?? '')) ?></small>
                                </div>
                                <div class="small">
                                    <?= e((string) ($r['grade'] ?? '—')) ?>
                                    · <?= e((string) ($r['subject_name'] ?? 'chưa chọn môn')) ?>
                                </div>
                                <div class="small lms-text-muted-sm">
                                    <?= $r['learning_type'] === 'online' ? 'Online' : e((string) ($r['district'] ?? '—')) ?>
                                    · <?= e((string) $r['slot_count']) ?> khung giờ rảnh
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <?= lms_pagination(
                        lms_url($alias, 'man', array('q' => $filterQ, 'subject_id' => $filterSubject)),
                        $page,
                        $totalPages
                    ) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cột phải: kết quả match -->
    <div class="col-lg-8">
        <?php if (empty($student)): ?>
            <div class="lms-card h-100">
                <div class="lms-card-body py-5 text-center text-muted">
                    <i class="fa-solid fa-shuffle d-block mb-2" style="font-size:1.8rem;"></i>
                    Chọn 1 học viên ở cột trái để xem danh sách giáo viên phù hợp.
                </div>
            </div>
        <?php else: ?>
            <div class="lms-card mb-3">
                <div class="lms-card-header">
                    <i class="fa-solid fa-user-graduate me-1"></i>
                    <?= e((string) $student['full_name']) ?>
                    <span class="lms-text-muted-sm">(<?= e((string) $student['student_code']) ?>)</span>
                </div>
                <div class="lms-card-body">
                    <div class="row g-2 small">
                        <div class="col-md-6">
                            <strong>Môn học:</strong> <?= e((string) ($student['subject_name'] ?? 'chưa chọn môn')) ?><br>
                            <strong>Lớp:</strong> <?= e((string) ($student['grade'] ?? '—')) ?>
                            · <strong>Hình thức:</strong> <?= e($student['learning_type'] === 'online' ? 'Online' : 'Offline') ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Quận:</strong> <?= e((string) ($student['district'] ?? '—')) ?><br>
                            <strong>Yêu cầu GV:</strong> <?= isset($genderLabels[$student['preferred_teacher_gender'] ?? ''])
                                ? e($genderLabels[$student['preferred_teacher_gender']])
                                : 'không yêu cầu' ?>
                        </div>
                        <div class="col-12">
                            <strong>Khung giờ rảnh:</strong>
                            <?php if (!empty($student['free_time_slots'])): ?>
                                <?= e($slotText($student['free_time_slots'])) ?>
                            <?php else: ?>
                                <span class="text-danger">chưa có khung giờ rảnh</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lms-card">
                <div class="lms-card-header">
                    <i class="fa-solid fa-chalkboard-user me-1"></i> Giáo viên phù hợp
                    <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format(count($matched))) ?></span>
                </div>
                <div class="lms-card-body">
                    <?php if (empty($student['free_time_slots'])): ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                            Học viên chưa có khung giờ rảnh nên không thể ghép lớp.
                            <a href="<?= e(lms_url('lms-hoc-vien', 'sua', array('id' => (int) $student['user_id']))) ?>">
                                Cập nhật hồ sơ học viên</a>.
                        </div>
                    <?php elseif (empty($matched)): ?>
                        <p class="text-muted mb-0 py-4 text-center">
                            <i class="fa-solid fa-circle-xmark d-block mb-2" style="font-size:1.6rem;"></i>
                            Không có giáo viên nào phù hợp với tiêu chí của học viên.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="lms-table align-middle">
                                <thead>
                                    <tr>
                                        <th>Giáo viên</th>
                                        <th>Hạng</th>
                                        <th>Khớp lịch</th>
                                        <th>Số lớp</th>
                                        <th class="text-end">Điểm</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($matched as $m): ?>
                                        <tr>
                                            <td>
                                                <strong><?= e((string) $m['teacher_code']) ?></strong>
                                                — <?= e((string) $m['full_name']) ?>
                                                <div class="small lms-text-muted-sm">
                                                    <?= implode(', ', array_map('e', $m['districts'] !== array() ? $m['districts'] : array('mọi quận'))) ?>
                                                    <?= $m['can_teach_online'] ? ' · dạy online' : '' ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="lms-badge <?= e(lms_xep_lop_rank_badge($m['rank'])) ?>">
                                                    <?= e(isset($rankLabels[$m['rank']]) ? $rankLabels[$m['rank']] : $m['rank']) ?>
                                                </span>
                                            </td>
                                            <td style="min-width: 120px;">
                                                <div class="lms-progress">
                                                    <div class="lms-progress-bar" style="width: <?= e((string) (int) $m['schedule_pct']) ?>%"></div>
                                                </div>
                                                <span class="small"><?= e((string) $m['schedule_pct']) ?>%</span>
                                            </td>
                                            <td><?= e((string) $m['current_classes']) ?> / <?= e((string) ($m['max_classes'] > 0 ? $m['max_classes'] : '∞')) ?></td>
                                            <td class="text-end"><strong><?= e((string) $m['score']) ?></strong></td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-1">
                                                    <a href="<?= e(lms_url($alias, 'chi-tiet-gv', array(
                                                        'teacher_id' => (int) $m['teacher_id'],
                                                        'student_id' => (int) $student['user_id'],
                                                    ))) ?>" class="btn btn-sm btn-outline-lms" title="Xem lịch tuần">
                                                        <i class="fa-solid fa-calendar-week"></i>
                                                    </a>
                                                    <?php if ($canEdit): ?>
                                                        <form method="post" action="<?= e(lms_url($alias, 'tao-lop')) ?>"
                                                              class="d-flex gap-1 align-items-center"
                                                              onsubmit="return confirm('Tạo lớp 1 kèm 1 với giáo viên này?');">
                                                            <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                                                            <input type="hidden" name="student_id" value="<?= e((string) $student['user_id']) ?>">
                                                            <input type="hidden" name="teacher_id" value="<?= e((string) $m['teacher_id']) ?>">
                                                            <input type="number" name="price_per_session" min="0" step="1000"
                                                                   class="form-control form-control-sm" style="width: 90px;"
                                                                   placeholder="Học phí" title="Học phí mỗi buổi (để trống = 0)" value="">
                                                            <input type="number" name="total_sessions" min="0" step="1"
                                                                   class="form-control form-control-sm" style="width: 70px;"
                                                                   placeholder="Số buổi" title="Tổng số buổi khoá (để trống = 0)" value="">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Tạo lớp 1-1">
                                                                <i class="fa-solid fa-plus me-1"></i> Tạo lớp
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="lms-text-muted-sm mt-2 mb-0">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Lớp được tạo với lịch = khung giờ giao nhau giữa lịch rảnh của học viên và lịch trống của giáo viên.
                            Học phí / số buổi / tên lớp có thể chỉnh lại trong Quản lý lớp học.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
