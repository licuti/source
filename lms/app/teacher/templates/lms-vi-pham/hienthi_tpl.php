<?php
/**
 * Teacher LMS — Vi phạm chất lượng dịch vụ: view tick của mình (mockup Vi phạm nội quy.png).
 * Data do source chuẩn bị: $items, $total, $page, $totalPages, $tickSummary,
 * $warnMessage, $alias.
 */
$reasonLabels = \Lms\TickService::ALL_REASONS;
$displayLabels = array(
    'late_lesson_summary' => 'Không ghi báo cáo buổi học',
    'late_hw_grading'     => 'Chưa chấm BTVN',
    'late_test_grading'   => 'Chấm test trễ',
    'manual_penalty'      => 'Phạt thủ công',
    'manual_exempt'       => 'Miễn giảm tick',
);

$exempted = (int) $tickSummary['exempted'];
$raw      = (int) $tickSummary['raw'];
$real     = (int) $tickSummary['real'];
$demoteAt = is_array($tickSummary['threshold']) ? (int) $tickSummary['threshold']['ticks_to_demote_fee'] : 0;

$fmtTime = function ($row) {
    $date = isset($row['violation_date']) ? (string) $row['violation_date'] : '';
    $time = !empty($row['start_time'])
        ? substr((string) $row['start_time'], 0, 5)
        : (!empty($row['created_at']) ? substr((string) $row['created_at'], 11, 5) : '');
    return trim($date . ' ' . $time);
};

$studentLabel = function ($row) {
    $names = isset($row['student_names']) ? trim((string) $row['student_names']) : '';
    if ((string) ($row['class_type'] ?? '') === 'one_on_one' && $names !== '') {
        return $names;
    }
    $className = isset($row['class_name']) ? trim((string) $row['class_name']) : '';
    return $className !== '' ? $className : '—';
};

$contentLabel = function ($row) use ($reasonLabels, $displayLabels) {
    $reason = isset($row['reason']) ? (string) $row['reason'] : '';
    if (isset($displayLabels[$reason])) {
        return $displayLabels[$reason];
    }
    if (isset($reasonLabels[$reason])) {
        return $reasonLabels[$reason];
    }
    $note = isset($row['note']) ? trim((string) $row['note']) : '';
    return $note !== '' ? $note : $reason;
};
?>
<?php if ($warnMessage !== ''): ?>
    <div class="lms-alert lms-alert-warning" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <strong>Cảnh báo:</strong> <?= e($warnMessage) ?>
    </div>
<?php endif; ?>

<div class="lms-vp-stats">
    <div class="lms-vp-stat">
        <span class="lms-vp-label">Số tick đã vi phạm:</span>
        <span class="lms-vp-box"><?= e((string) $raw) ?></span>
    </div>
    <div class="lms-vp-stat">
        <span class="lms-vp-label">Số tick được miễn:</span>
        <span class="lms-vp-box"><?= e((string) $exempted) ?></span>
        <i class="fa-regular fa-circle-question lms-vp-info"
           title="Số tick được miễn theo chính sách miễn giảm dựa trên số lớp đang dạy."></i>
    </div>
    <div class="lms-vp-stat">
        <span class="lms-vp-label">Tổng tick thực tế:</span>
        <span class="lms-vp-box lms-vp-box-real"><?= e((string) $real) ?></span>
    </div>
    <div class="lms-vp-stat lms-vp-threshold">
        <span class="lms-vp-label">Ngưỡng hạ mức phí dịch vụ:</span>
        <span class="lms-vp-box"><?= $demoteAt > 0 ? e((string) $demoteAt) : '—' ?></span>
    </div>
</div>

<div class="lms-vp-note">** Có chính sách miễn giảm tick</div>

<div class="table-responsive">
    <table class="table table-bordered align-middle text-center lms-table mb-0">
        <thead class="table-light">
            <tr>
                <th style="width: 20%;">Ngày</th>
                <th style="width: 22%;">Học viên</th>
                <th style="width: 18%;">Số lượng tick</th>
                <th>Nội dung vi phạm</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="4" class="text-center py-4 text-muted">
                        Không có vi phạm nào. Tốt lắm!
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $r): ?>
                    <?php $tick = (int) $r['tick_amount']; ?>
                    <tr>
                        <td class="text-nowrap"><?= e($fmtTime($r)) ?></td>
                        <td><?= e($studentLabel($r)) ?></td>
                        <td class="text-nowrap fw-semibold">
                            <?= $tick > 0 ? '+' . $tick : e((string) $tick) ?>
                        </td>
                        <td class="text-wrap"><?= e($contentLabel($r)) ?></td>
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
)); ?>
