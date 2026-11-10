<?php
/**
 * Admin LMS — Học sinh - Lớp học: ma trận HV × chương trình (ADM-03).
 * Mockup: Tai-lieu-phat-trien/admin/Học sinh - Lớp học - Lớp đông người.png
 *         Tai-lieu-phat-trien/admin/Học sinh - Lớp học - Lớp 1 kèm 1.png
 *
 * Cột chương trình là động: mỗi chương trình có ≥1 lớp active của tab hiện tại
 * là một cột (thêm chương trình mới không cần sửa template).
 *
 * Data do source chuẩn bị: $items, $programs, $total, $totalPages, $page,
 * $filterClassType, $filterQStudent, $filterQClass, $canEdit, $canDelete.
 */
$tabLabels = array(
    'group'      => 'Lớp đông người',
    'one_on_one' => 'Lớp 1 kèm 1',
);
$programCount = is_array($programs) ? count($programs) : 0;
$colCount     = 3 + max(1, $programCount) + ($canEdit ? 1 : 0);

$tabItems = array();
foreach ($tabLabels as $type => $label) {
    $tabItems[] = array(
        'label'  => $label,
        'href'   => lms_url($alias, 'man', array('type' => $type)),
        'active' => $filterClassType === $type,
    );
}
?>

<?php lms_component('tabs', array(
    'aria_label' => 'Loại lớp học',
    'items'      => $tabItems,
)); ?>

<!-- ===================== Bộ lọc ===================== -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <form method="get" action="<?= e(lms_url($alias, 'man')) ?>" class="d-flex flex-wrap align-items-center gap-2">
        <input type="hidden" name="type" value="<?= e($filterClassType) ?>">
        <div style="min-width: 170px;">
            <?php lms_component('form/input', array(
                'name'        => 'q_student',
                'type'        => 'search',
                'value'       => $filterQStudent,
                'placeholder' => 'Tìm theo HV',
                'bare'        => true,
                'class'       => 'form-control',
            )); ?>
        </div>
        <div style="min-width: 170px;">
            <?php lms_component('form/input', array(
                'name'        => 'q_class',
                'type'        => 'search',
                'value'       => $filterQClass,
                'placeholder' => 'Tìm theo lớp',
                'bare'        => true,
                'class'       => 'form-control',
            )); ?>
        </div>
        <?php lms_component('button', array(
            'label'   => 'Tìm',
            'type'    => 'submit',
            'variant' => 'outline-secondary',
            'class'   => 'px-3',
        )); ?>
    </form>
</div>

<div class="mb-2 fw-bold text-dark">
    Tổng: <?= e((string) number_format((int) $total)) ?> học viên
</div>

<!-- ===================== Ma trận HV × chương trình ===================== -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table">
        <thead class="table-light text-center">
            <tr>
                <th rowspan="2">Username</th>
                <th rowspan="2">Họ tên</th>
                <th rowspan="2">Khối</th>
                <?php if ($programCount > 0): ?>
                    <th colspan="<?= (int) $programCount ?>">Mã lớp (chia theo chương trình học)</th>
                <?php else: ?>
                    <th rowspan="2">Mã lớp</th>
                <?php endif; ?>
                <?php if ($canEdit): ?>
                    <th rowspan="2" style="width: 130px;">Hành động</th>
                <?php endif; ?>
            </tr>
            <?php if ($programCount > 0): ?>
                <tr>
                    <?php foreach ($programs as $p): ?>
                        <th><?= e((string) ($p['name'] ?? '')) ?></th>
                    <?php endforeach; ?>
                </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?= (int) $colCount ?>" class="text-center py-4 text-muted">
                        Chưa có học viên nào phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $r): ?>
                    <?php
                    $classes = isset($r['classes']) && is_array($r['classes']) ? $r['classes'] : array();

                    $gradeText = trim((string) ($r['grade'] ?? ''));
                    if ($gradeText !== '' && mb_stripos($gradeText, 'Lớp ') === 0) {
                        $gradeText = trim(mb_substr($gradeText, 4));
                    }
                    ?>
                    <tr>
                        <td><strong><?= e((string) ($r['username'] ?? '')) ?></strong></td>
                        <td><?= e((string) ($r['full_name'] ?? '')) ?></td>
                        <td class="text-center">
                            <?= $gradeText !== '' ? e($gradeText) : '<span class="text-muted">—</span>' ?>
                        </td>
                        <?php if ($programCount === 0): ?>
                            <td class="text-center text-muted">—</td>
                        <?php endif; ?>
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
                                                <span class="lms-matrix-sched" title="<?= e((string) $cls['schedules_text']) ?>">
                                                    <i class="fa-regular fa-clock"></i>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($canEdit): ?>
                                                <?php lms_component('button', array(
                                                    'href'    => lms_url($alias, 'chuyen', array(
                                                        'student_id'    => (int) $r['user_id'],
                                                        'from_class_id' => (int) $cls['class_id'],
                                                    )),
                                                    'variant' => 'outline-secondary',
                                                    'size'    => 'sm',
                                                    'icon'    => 'fa-solid fa-arrow-right-arrow-left',
                                                    'attrs'   => array('title' => 'Chuyển lớp'),
                                                )); ?>
                                            <?php endif; ?>
                                            <?php if ($canDelete): ?>
                                                <?php lms_component('button', array(
                                                    'href'    => lms_url($alias, 'xoa', array(
                                                        'student_id' => (int) $r['user_id'],
                                                        'class_id'   => (int) $cls['class_id'],
                                                        'type'       => $filterClassType,
                                                    )),
                                                    'variant' => 'outline-danger',
                                                    'size'    => 'sm',
                                                    'icon'    => 'fa-solid fa-trash',
                                                    'confirm' => 'Bạn có chắc muốn xoá học viên khỏi lớp này? Các buổi học đã có dữ liệu sẽ được giữ lại.',
                                                    'attrs'   => array('title' => 'Xoá khỏi lớp'),
                                                )); ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <?php if ($canEdit): ?>
                            <td class="text-center">
                                <?php lms_component('button', array(
                                    'label'   => 'Thêm lớp',
                                    'href'    => lms_url($alias, 'them', array(
                                        'student_id' => (int) $r['user_id'],
                                        'type'       => $filterClassType,
                                    )),
                                    'variant' => 'outline-primary',
                                    'size'    => 'sm',
                                    'icon'    => 'fa-solid fa-plus',
                                )); ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php lms_component('pagination', array(
    'base_url'    => lms_url($alias, 'man'),
    'page'        => $page,
    'total_pages' => $totalPages,
    'params'      => array(
        'type'      => $filterClassType,
        'q_student' => $filterQStudent,
        'q_class'   => $filterQClass,
    ),
)); ?>
