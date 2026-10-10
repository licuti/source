<?php
/**
 * Admin LMS — Quản lý lớp học: view danh sách (ADM-03).
 * Data do source chuẩn bị: $items, $total, $totalPages, $page,
 * $filterQ, $filterTeacherId, $filterProgramIds, $filterStatus,
 * $filterClassType, $teachers, $programs, $canEdit, $canDelete.
 */
$classTypes   = \Lms\ClassService::CLASS_TYPES;
$classStatuses = \Lms\ClassService::CLASS_STATUSES;
$weekdays     = \Lms\UserService::WEEKDAYS;

$typeBadge = array(
    'one_on_one' => 'lms-badge-info',
    'group'      => 'lms-badge-warning',
);
$statusBadge = array(
    'active'    => 'lms-badge-success',
    'paused'    => 'lms-badge-secondary',
    'finished'  => 'lms-badge-info',
    'cancelled' => 'lms-badge-danger',
);
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-school me-1"></i> Danh sách lớp học
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?> lớp</span>
    </div>
    <div class="lms-card-body">
        <form method="get" action="<?= e(lms_url($alias)) ?>" class="lms-filter-bar row g-2 mb-3">
            <div class="col-sm-12 col-md-4">
                <input type="search" name="q" class="form-control" placeholder="Mã / tên lớp..."
                       value="<?= e($filterQ) ?>" aria-label="Tìm kiếm">
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="teacher_id" class="form-select" aria-label="Lọc theo giáo viên">
                    <option value="">Tất cả giáo viên</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= e((string) (int) $t['user_id']) ?>"<?= lms_selected($filterTeacherId, (int) $t['user_id']) ?>>
                            <?= e((string) ($t['teacher_code'] ?? '')) ?> — <?= e((string) ($t['full_name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="program_ids[]" class="form-select" multiple size="1" aria-label="Lọc theo chương trình">
                    <option value="">Tất cả chương trình</option>
                    <?php foreach ($programs as $p): ?>
                        <option value="<?= e((string) (int) $p['id']) ?>"<?= lms_in_selected((int) $p['id'], $filterProgramIds) ?>>
                            <?= e((string) ($p['name'] ?? '')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="status" class="form-select" aria-label="Lọc theo trạng thái">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($classStatuses as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterStatus, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3">
                <select name="class_type" class="form-select" aria-label="Lọc theo loại lớp">
                    <option value="">Tất cả loại lớp</option>
                    <?php foreach ($classTypes as $k => $label): ?>
                        <option value="<?= e($k) ?>"<?= lms_selected($filterClassType, $k) ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm
                </button>
                <?php if ($canEdit): ?>
                    <a href="<?= e(lms_url($alias, 'them')) ?>" class="btn btn-success">
                        <i class="fa-solid fa-plus me-1"></i> Thêm
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-inbox d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có lớp học nào phù hợp.
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Mã lớp</th>
                            <th>Chương trình</th>
                            <th>Loại lớp</th>
                            <th class="text-end">Sĩ số</th>
                            <th>Thời gian</th>
                            <th>Giáo viên</th>
                            <th class="text-end">Buổi</th>
                            <th>Trạng thái</th>
                            <?php if ($canEdit || $canDelete): ?>
                                <th class="text-end">Hành động</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $stt = isset($r['status']) ? (string) $r['status'] : '';
                            $ctype = isset($r['class_type']) ? (string) $r['class_type'] : '';
                            $times = array();
                            foreach ((isset($r['schedules']) ? $r['schedules'] : array()) as $sch) {
                                $dow = (int) $sch['day_of_week'];
                                $times[] = (isset($weekdays[$dow]) ? $weekdays[$dow] : '')
                                    . ' ' . substr((string) $sch['start_time'], 0, 5)
                                    . '-' . substr((string) $sch['end_time'], 0, 5);
                            }
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e((string) ($r['class_code'] ?? '')) ?></strong>
                                    <div class="lms-text-muted-sm"><?= e((string) ($r['name'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <?= e((string) ($r['program_name'] ?? '')) ?>
                                    <div class="lms-text-muted-sm"><?= e((string) ($r['subject_name'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <?php if (isset($classTypes[$ctype])): ?>
                                        <span class="lms-badge <?= e(isset($typeBadge[$ctype]) ? $typeBadge[$ctype] : 'lms-badge-secondary') ?>">
                                            <?= e($classTypes[$ctype]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= e((string) number_format((int) ($r['student_count'] ?? 0))) ?></td>
                                <td>
                                    <?php if ($times !== array()): ?>
                                        <?= e(implode(' / ', $times)) ?>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">Chưa có lịch</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= e((string) ($r['teacher_code'] ?? '')) ?> — <?= e((string) ($r['teacher_name'] ?? '')) ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <?= e((string) number_format((int) ($r['remaining_sessions'] ?? 0))) ?>
                                    <span class="lms-text-muted-sm">/ <?= e((string) number_format((int) ($r['total_sessions'] ?? 0))) ?></span>
                                </td>
                                <td>
                                    <?php if (isset($classStatuses[$stt])): ?>
                                        <span class="lms-badge <?= e(isset($statusBadge[$stt]) ? $statusBadge[$stt] : 'lms-badge-secondary') ?>">
                                            <?= e($classStatuses[$stt]) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($canEdit || $canDelete): ?>
                                    <td class="text-end text-nowrap">
                                        <?php if ($canEdit): ?>
                                            <a href="<?= e(lms_url($alias, 'sua', array('id' => (int) $r['id']))) ?>"
                                               class="btn btn-sm btn-outline-lms" title="Sửa">
                                                <i class="fa-solid fa-pen"></i>
                                            </a>
                                            <?php if ($stt === 'paused'): ?>
                                                <a href="<?= e(lms_url($alias, 'mo', array('id' => (int) $r['id']))) ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Tiếp tục lớp">
                                                    <i class="fa-solid fa-play"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= e(lms_url($alias, 'tam-dung', array('id' => (int) $r['id']))) ?>"
                                                   class="btn btn-sm btn-outline-secondary" title="Tạm dừng lớp">
                                                    <i class="fa-solid fa-pause"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($canDelete): ?>
                                            <a href="<?= e(lms_url($alias, 'xoa', array('id' => (int) $r['id']))) ?>"
                                               class="btn btn-sm btn-outline-danger lms-confirm-delete"
                                               title="Xoá lớp">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= lms_pagination(
                lms_url($alias),
                $page,
                $totalPages,
                array(
                    'q'            => $filterQ,
                    'teacher_id'   => $filterTeacherId,
                    'status'       => $filterStatus,
                    'class_type'   => $filterClassType,
                    'program_ids'  => $filterProgramIds,
                )
            ) ?>
        <?php endif; ?>
    </div>
</div>

<script>
    (function ($) {
        'use strict';
        $(function () {
            $('.lms-confirm-delete').on('click', function (e) {
                if (!window.confirm('Bạn có chắc muốn xoá lớp học này? Các buổi học đã hoàn thành sẽ được giữ lại làm lịch sử.')) {
                    e.preventDefault();
                }
            });
        });
    })(jQuery);
</script>
