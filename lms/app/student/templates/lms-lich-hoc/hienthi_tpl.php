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
        <?php
        $calLanes = lms_cal_lanes_for_rows($calRows);
        lms_component('calendar_week', array(
            'monday'     => $weekMonday,
            'weekdays'   => \Lms\UserService::WEEKDAYS,
            'rows'       => $calRows,
            'hour_label' => 'Giờ',
            'cell_class' => function (array $cell) {
                if (empty($cell['sessions'])) {
                    return '';
                }
                $first = $cell['sessions'][0];
                return 'lms-cal-cell--' . lms_cal_variant((string) $first['status']);
            },
            'cell_renderer' => function (array $cell) use ($alias, $weekMonday, $calLanes) {
                $html = '';
                foreach ($cell['sessions'] as $ses) {
                    $lane = lms_cal_lane_of($calLanes, (string) $cell['date'], 's' . (int) $ses['id']);
                    $html .= lms_component_render('calendar_week_event', array(
                        'variant' => lms_cal_variant((string) $ses['status']),
                        'title'   => (string) $ses['class_name'],
                        'start'   => (string) $ses['start_time'],
                        'end'     => (string) $ses['end_time'],
                        'lane'    => $lane['lane'],
                        'lanes'   => $lane['lanes'],
                        'href'    => lms_url($alias, 'buoi', array('id' => (int) $ses['id'], 'week' => $weekMonday)),
                        'footer'  => 'Xem chi tiết',
                    ));
                }
                return $html;
            },
        ));
        ?>

        <!-- Chú thích (§9 L201-204) -->
        <ul class="lms-cal-legend">
            <li><span class="lms-cal-dot lms-cal-upcoming-dot"></span> Buổi học sắp tới</li>
            <li><span class="lms-cal-dot lms-cal-finished-dot"></span> Buổi học đã hoàn thành</li>
            <li><span class="lms-cal-dot lms-cal-cancelled-dot"></span> Buổi học đã nghỉ</li>
        </ul>
    </div>
</div>
