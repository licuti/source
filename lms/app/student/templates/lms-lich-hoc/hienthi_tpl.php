<?php
/**
 * Student — Lịch học (view, mockup §9).
 * Data do source chuẩn bị: $weekMonday, $weekLabel, $prevWeek, $nextWeek, $calRows, $alias.
 */
?>
<div class="lms-card">
    <div class="lms-card-header lms-cal-header">
        <div class="lms-cal-nav">
            <a href="<?= e(lms_url($alias, '', lmlh_week_params($prevWeek))) ?>" class="btn btn-outline-lms btn-sm" aria-label="Tuần trước">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <strong>Tháng <?= e(date('n', strtotime($weekMonday))) ?> / <?= e(date('Y', strtotime($weekMonday))) ?></strong>
            <a href="<?= e(lms_url($alias, '', lmlh_week_params($nextWeek))) ?>" class="btn btn-outline-lms btn-sm" aria-label="Tuần sau">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>
    </div>
    <div class="lms-card-body">
        <div class="lms-cal-scroll">
            <table class="lms-cal">
                <thead>
                    <tr>
                        <th class="lms-cal-th lms-cal-th-hour">Giờ</th>
                        <?php for ($i = 0; $i < 7; $i++): ?>
                            <?php
                            $ts = strtotime('+' . $i . ' days', strtotime($weekMonday));
                            $dow = (int) date('N', $ts) + 1;
                            ?>
                            <th class="lms-cal-th">
                                <?= e(isset(\Lms\UserService::WEEKDAYS[$dow]) ? \Lms\UserService::WEEKDAYS[$dow] : '') ?>
                                <small><?= e((int) date('j', $ts) . '/' . (int) date('n', $ts)) ?></small>
                            </th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($calRows as $row): ?>
                        <tr>
                            <th class="lms-cal-th lms-cal-th-hour" scope="row"><?= e($row['label']) ?></th>
                            <?php foreach ($row['cells'] as $cell): ?>
                                <td class="lms-cal-cell<?php
                                    if (!empty($cell['sessions'])) {
                                        echo ' ' . lmlh_cell_class((string) $cell['sessions'][0]['status']);
                                    }
                                ?>">
                                    <?php foreach ($cell['sessions'] as $ses): ?>
                                        <a class="lms-cal-link" href="<?= e(lms_url($alias, 'buoi', array('id' => (int) $ses['id'], 'week' => $weekMonday))) ?>">
                                            <span class="lms-cal-code"><?= e((string) $ses['class_name']) ?></span>
                                            <span class="lms-cal-time"><?= e(substr((string) $ses['start_time'], 0, 5)) ?>–<?= e(substr((string) $ses['end_time'], 0, 5)) ?></span>
                                            <span class="lms-cal-view">Xem chi tiết</span>
                                        </a>
                                    <?php endforeach; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Chú thích (§9 L201-204) -->
        <ul class="lms-cal-legend">
            <li><span class="lms-cal-dot lms-cal-upcoming-dot"></span> Buổi học sắp tới</li>
            <li><span class="lms-cal-dot lms-cal-finished-dot"></span> Buổi học đã hoàn thành</li>
            <li><span class="lms-cal-dot lms-cal-cancelled-dot"></span> Buổi học đã nghỉ</li>
        </ul>
    </div>
</div>
