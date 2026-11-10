<?php
/**
 * Admin LMS — Quản lý Bài tập về nhà (BTVN).
 * Mockup: Tai-lieu-phat-trien/admin/Quản lý BTVN.png
 *
 * Data do source chuẩn bị: $items, $total, $totalPages, $page, $q, $dateFrom, $dateTo.
 */
$fmtTime = function ($date, $time) {
    $ts = strtotime(trim((string) $date . ' ' . (string) $time));
    if ($ts === false) {
        return '—';
    }
    $dow = (int) date('N', $ts) + 1; // 2..8
    $label = isset(\Lms\UserService::WEEKDAYS[$dow]) ? \Lms\UserService::WEEKDAYS[$dow] : '';
    return $label . ' ' . date('d-m-Y H:i', $ts);
};
?>
<!-- ===================== Bộ lọc ===================== -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
        <div style="min-width: 165px;">
            <label for="fld-date-from" class="visually-hidden">Từ ngày</label>
            <?php lms_component('form/input', array(
                'name'  => 'date_from',
                'type'  => 'date',
                'value' => $dateFrom,
                'bare'  => true,
                'class' => 'form-control',
            )); ?>
        </div>
        <div style="min-width: 165px;">
            <label for="fld-date-to" class="visually-hidden">Đến ngày</label>
            <?php lms_component('form/input', array(
                'name'  => 'date_to',
                'type'  => 'date',
                'value' => $dateTo,
                'bare'  => true,
                'class' => 'form-control',
            )); ?>
        </div>
        <div style="min-width: 260px;">
            <label for="fld-q" class="visually-hidden">Mã lớp, mã giáo viên</label>
            <?php lms_component('form/input', array(
                'name'        => 'q',
                'type'        => 'search',
                'value'       => $q,
                'placeholder' => 'Tìm theo mã lớp, mã giáo viên',
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

<div class="d-flex justify-content-between mb-2">
    <div class="fw-bold text-dark">
        Tổng: <?= e((string) number_format((int) $total)) ?> buổi học
    </div>
        <?php lms_component('pagination', array(
            'base_url'    => lms_url($alias),
            'page'        => $page,
            'total_pages' => $totalPages,
            'params'      => array_filter(array(
                'q'         => $q,
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
            ), function ($v) { return $v !== '' && $v !== null; }),
            'class'       => 'mt-0',
        )); ?>
</div>

<!-- ===================== Bảng BTVN ===================== -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0 text-nowrap lms-table lms-table-pin">
        <thead class="table-light text-center">
            <tr>
                <th style="min-width: 190px;" data-pin-left>Thời gian</th>
                <th data-pin-left>Mã GV</th>
                <th data-pin-left>Mã Lớp</th>
                <th style="min-width: 180px;">Chuyên đề</th>
                <th style="min-width: 220px;">BTVN</th>
                <th style="min-width: 220px;">Nhận xét buổi học</th>
                <th style="min-width: 190px;">Thời gian cập nhật</th>
                <th style="width: 100px;" data-pin-right>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Chưa có buổi học nào phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $r): ?>
                    <?php
                    $updatedAt = !empty($r['summary_submitted_at']) ? $r['summary_submitted_at'] : $r['updated_at'];
                    $updatedTs = strtotime((string) $updatedAt);
                    $updatedLabel = $updatedTs !== false
                        ? $fmtTime(date('Y-m-d', $updatedTs), date('H:i', $updatedTs))
                        : '—';
                    ?>
                    <tr>
                        <td class="text-center" data-pin-left>
                            <?= e($fmtTime($r['session_date'], $r['start_time'])) ?>
                        </td>
                        <td class="text-center" data-pin-left><?= e((string) $r['teacher_code']) ?></td>
                        <td class="text-center" data-pin-left><strong><?= e((string) $r['class_code']) ?></strong></td>
                        <td class="text-wrap" style="max-width: 220px;">
                            <?= trim((string) $r['topic_name']) !== '' ? e((string) $r['topic_name']) : '—' ?>
                        </td>
                        <td class="text-wrap" style="max-width: 260px;">
                            <?= trim((string) $r['hw_content']) !== ''
                                ? nl2br(e((string) $r['hw_content']))
                                : '<span class="text-danger fw-semibold">Chưa giao</span>' ?>
                        </td>
                        <td class="text-wrap" style="max-width: 260px;">
                            <?= trim((string) $r['lesson_summary']) !== ''
                                ? nl2br(e((string) $r['lesson_summary']))
                                : '-' ?>
                        </td>
                        <td class="text-center"><?= e($updatedLabel) ?></td>
                        <td class="text-center" data-pin-right>
                            <a class="lms-action-link"
                               href="<?= e(lms_url('lms-lop', 'buoi', array('id' => (int) $r['session_id']))) ?>">
                                Chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php lms_component('pagination', array(
    'base_url'    => lms_url($alias),
    'page'        => $page,
    'total_pages' => $totalPages,
    'params'      => array_filter(array(
        'q'         => $q,
        'date_from' => $dateFrom,
        'date_to'   => $dateTo,
    ), function ($v) { return $v !== '' && $v !== null; }),
)); ?>
