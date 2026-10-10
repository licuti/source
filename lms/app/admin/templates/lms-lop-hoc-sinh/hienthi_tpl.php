<?php
/**
 * Admin LMS — Học sinh - Lớp học: view ma trận (ADM-04).
 * Data do source chuẩn bị: $items, $programs, $total, $totalPages, $page,
 * $filterClassType, $filterQStudent, $filterQClass, $canEdit, $canDelete.
 */
$tabLabels = array(
    'group'      => 'Lớp đông người',
    'one_on_one' => 'Lớp 1 kèm 1',
);
?>

<div class="lms-card">
    <div class="lms-card-header d-flex align-items-center gap-2 flex-wrap">
        <i class="fa-solid fa-users me-1"></i> Học sinh - Lớp học
        <span class="ms-auto lms-text-muted-sm">Tổng: <?= e((string) number_format((int) $total)) ?> học viên</span>
    </div>
    <div class="lms-card-body">

        <!-- ===================== Tabs ===================== -->
        <ul class="lms-tabs" role="tablist">
            <?php foreach ($tabLabels as $type => $label): ?>
                <li role="presentation">
                    <a href="<?= e(lms_url($alias, 'man', array('type' => $type))) ?>"
                       class="<?= $filterClassType === $type ? 'active' : '' ?>"
                       role="tab"
                       aria-selected="<?= $filterClassType === $type ? 'true' : 'false' ?>">
                        <?= e($label) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- ===================== Bộ lọc ===================== -->
        <form method="get" action="<?= e(lms_url($alias, 'man')) ?>" class="lms-filter-bar row g-2 mb-3">
            <input type="hidden" name="type" value="<?= e($filterClassType) ?>">
            <div class="col-sm-12 col-md-5">
                <input type="search" name="q_student" class="form-control" placeholder="Tìm theo HV (tên / mã / username)..."
                       value="<?= e($filterQStudent) ?>" aria-label="Tìm theo học viên">
            </div>
            <div class="col-sm-12 col-md-5">
                <input type="search" name="q_class" class="form-control" placeholder="Tìm theo lớp (mã / tên lớp)..."
                       value="<?= e($filterQClass) ?>" aria-label="Tìm theo lớp">
            </div>
            <div class="col-sm-12 col-md-2 d-flex">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm
                </button>
            </div>
        </form>

        <?php if (empty($items)): ?>
            <p class="text-muted mb-0 py-4 text-center">
                <i class="fa-solid fa-inbox d-block mb-2" style="font-size:1.6rem;"></i>
                Chưa có học viên nào trong lớp <?= e($tabLabels[$filterClassType]) ?>.
            </p>
        <?php else: ?>
            <div class="table-responsive lms-matrix-scroll">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>STT</th>
                            <th>Username</th>
                            <th>Họ tên</th>
                            <th>Khối</th>
                            <?php foreach ($programs as $p): ?>
                                <th><?= e((string) ($p['name'] ?? '')) ?></th>
                            <?php endforeach; ?>
                            <?php if ($canEdit): ?>
                                <th class="text-end">Hành động</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stt = ($page - 1) * max(1, (int) $perPage);
                        foreach ($items as $r):
                            $stt++;
                            $classes = isset($r['classes']) && is_array($r['classes']) ? $r['classes'] : array();
                        ?>
                            <tr>
                                <td><?= e((string) $stt) ?></td>
                                <td><strong><?= e((string) ($r['username'] ?? '')) ?></strong></td>
                                <td>
                                    <?= e((string) ($r['full_name'] ?? '')) ?>
                                    <div class="lms-text-muted-sm"><?= e((string) ($r['student_code'] ?? '')) ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($r['grade'])): ?>
                                        <?= e((string) $r['grade']) ?>
                                    <?php else: ?>
                                        <span class="lms-text-muted-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <?php foreach ($programs as $p): ?>
                                    <?php $pid = (int) $p['id']; ?>
                                    <td>
                                        <?php if (isset($classes[$pid]) && $classes[$pid] !== array()): ?>
                                            <?php foreach ($classes[$pid] as $cls): ?>
                                                <div class="lms-matrix-code">
                                                    <a href="<?= e(lms_url('lms-lop', 'sua', array('id' => (int) $cls['class_id']))) ?>"
                                                       class="lms-link"
                                                       title="<?= e((string) ($cls['schedules_text'] ?? '')) ?>">
                                                        <?= e((string) ($cls['class_code'] ?? '')) ?>
                                                    </a>
                                                    <?php if (!empty($cls['schedules_text'])): ?>
                                                        <span class="lms-matrix-sched"
                                                              title="<?= e((string) $cls['schedules_text']) ?>">
                                                            <i class="fa-regular fa-clock"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($canEdit): ?>
                                                        <a href="<?= e(lms_url($alias, 'chuyen', array(
                                                            'student_id' => (int) $r['user_id'],
                                                            'from_class_id' => (int) $cls['class_id'],
                                                        ))) ?>" class="btn btn-sm btn-outline-secondary" title="Chuyển lớp">
                                                            <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($canDelete): ?>
                                                        <a href="<?= e(lms_url($alias, 'xoa', array(
                                                            'student_id' => (int) $r['user_id'],
                                                            'class_id' => (int) $cls['class_id'],
                                                            'type' => $filterClassType,
                                                        ))) ?>"
                                                           class="btn btn-sm btn-outline-danger lms-confirm-delete"
                                                           title="Xoá khỏi lớp">
                                                            <i class="fa-solid fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="lms-text-muted-sm">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if ($canEdit): ?>
                                    <td class="text-end text-nowrap">
                                        <a href="<?= e(lms_url($alias, 'them', array(
                                            'student_id' => (int) $r['user_id'],
                                            'type' => $filterClassType,
                                        ))) ?>" class="btn btn-sm btn-outline-lms" title="Thêm lớp">
                                            <i class="fa-solid fa-plus me-1"></i> Thêm lớp
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?= lms_pagination(
                lms_url($alias, 'man'),
                $page,
                $totalPages,
                array(
                    'type'      => $filterClassType,
                    'q_student' => $filterQStudent,
                    'q_class'   => $filterQClass,
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
                if (!window.confirm('Bạn có chắc muốn xoá học viên khỏi lớp này? Các buổi học đã có dữ liệu sẽ được giữ lại.')) {
                    e.preventDefault();
                }
            });
        });
    })(jQuery);
</script>
