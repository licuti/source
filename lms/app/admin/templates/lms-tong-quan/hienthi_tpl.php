<?php
/**
 * Admin LMS — Tổng quan (view).
 * Data do source chuẩn bị: $stats, $programStats.
 */
?>
<div class="row g-3 mb-4">
    <?php foreach ($stats as $s): ?>
        <div class="col-6 col-lg-3">
            <div class="lms-stat-card">
                <span class="lms-stat-icon"><i class="<?= e($s['icon']) ?>"></i></span>
                <div class="lms-stat-value"><?= e((string) number_format((int) $s['value'])) ?></div>
                <div class="lms-stat-label"><?= e($s['label']) ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="lms-card">
    <div class="lms-card-header">
        <i class="fa-solid fa-layer-group me-1"></i> Thống kê nhóm chương trình
    </div>
    <div class="lms-card-body">
        <?php if (empty($programStats)): ?>
            <p class="text-muted mb-0">Chưa có chương trình nào.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="lms-table">
                    <thead>
                        <tr>
                            <th>Chương trình</th>
                            <th class="text-end">Số lớp</th>
                            <th class="text-end">Số học viên</th>
                            <th class="text-end">Số buổi học</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($programStats as $row): ?>
                            <tr>
                                <td><?= e((string) $row['name']) ?></td>
                                <td class="text-end"><?= e((string) number_format((int) $row['class_count'])) ?></td>
                                <td class="text-end"><?= e((string) number_format((int) $row['student_count'])) ?></td>
                                <td class="text-end"><?= e((string) number_format((int) $row['session_count'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
