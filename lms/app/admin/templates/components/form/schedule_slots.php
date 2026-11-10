<?php
/**
 * LMS Form Component: form/schedule_slots
 * Cụm khung giờ lặp theo thứ (pill UI): chọn thứ + nhiều khung giờ, có
 * "Thêm giờ" / "Thêm ngày" và nút xoá từng khung giờ / cả ngày.
 *
 * Dùng cho "Thời gian rảnh" (ADM-01) và "Tìm theo lịch trống" (ADM-04).
 *
 * Props:
 * @var string $id           ID gốc của component (mặc định suy ra từ $day_name)
 * @var string $day_name     Tên field thứ, gửi dạng day_name[<row>]  (VD: 'slot_day')
 * @var string $start_name   Tên field giờ bắt đầu, dạng start_name[<row>][] (VD: 'slot_start')
 * @var string $end_name     Tên field giờ kết thúc (VD: 'slot_end')
 * @var array  $rows         array(array('day' => 2..8, 'ranges' => array(array('start','end'), ...)))
 * @var int    $default_day  Thứ mặc định cho dòng mới
 * @var string $default_start Giờ mặc định cho khung giờ đầu của dòng trống
 * @var string $default_end
 * @var string $new_start    Giờ mặc định khi bấm "Thêm giờ"
 * @var string $new_end
 * @var int    $add_day_value Thứ mặc định khi bấm "Thêm ngày"
 *
 * PHP 7.3 compatible.
 */

$dayName      = isset($day_name) && $day_name !== '' ? (string) $day_name : 'slot_day';
$startName    = isset($start_name) && $start_name !== '' ? (string) $start_name : 'slot_start';
$endName      = isset($end_name) && $end_name !== '' ? (string) $end_name : 'slot_end';
$rows         = isset($rows) && is_array($rows) ? $rows : array();
$defaultDay   = isset($default_day) ? (int) $default_day : 2;
$defaultStart = isset($default_start) ? (string) $default_start : '';
$defaultEnd   = isset($default_end) ? (string) $default_end : '';
$newStart     = isset($new_start) && $new_start !== '' ? (string) $new_start : '08:30';
$newEnd       = isset($new_end) && $new_end !== '' ? (string) $new_end : '10:00';
$addDayValue  = isset($add_day_value) ? (int) $add_day_value : 6;

$weekdays = \Lms\UserService::WEEKDAYS;
if (!isset($weekdays[$defaultDay])) {
    $defaultDay = 2;
}
if (!isset($weekdays[$addDayValue])) {
    $addDayValue = $defaultDay;
}

$compId = isset($id) && $id !== ''
    ? (string) $id
    : 'lms-slots-' . preg_replace('/[^a-z0-9]+/i', '-', $dayName);

// Chuẩn hoá dòng: thứ hợp lệ + luôn có ít nhất 1 khung giờ để hiển thị.
$normRows = array();
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $day = isset($row['day']) ? (int) $row['day'] : 0;
    if (!isset($weekdays[$day])) {
        $day = $defaultDay;
    }

    $ranges = array();
    if (isset($row['ranges']) && is_array($row['ranges'])) {
        foreach ($row['ranges'] as $range) {
            if (!is_array($range)) {
                continue;
            }
            $ranges[] = array(
                'start' => isset($range['start']) ? substr(trim((string) $range['start']), 0, 5) : '',
                'end'   => isset($range['end']) ? substr(trim((string) $range['end']), 0, 5) : '',
            );
        }
    }
    if ($ranges === array()) {
        $ranges[] = array('start' => $defaultStart, 'end' => $defaultEnd);
    }

    $normRows[] = array('day' => $day, 'ranges' => $ranges);
}
if ($normRows === array()) {
    $normRows[] = array(
        'day'    => $defaultDay,
        'ranges' => array(array('start' => $defaultStart, 'end' => $defaultEnd)),
    );
}

$slotConfig = array(
    'rootId'      => $compId,
    'dayName'     => $dayName,
    'startName'   => $startName,
    'endName'     => $endName,
    'newStart'    => $newStart,
    'newEnd'      => $newEnd,
    'addDayValue' => $addDayValue,
    'nextIndex'   => count($normRows),
    'weekdays'    => $weekdays,
);
?>
<div class="lms-schedule-slots" id="<?= htmlspecialchars($compId, ENT_QUOTES, 'UTF-8') ?>">
    <div class="d-flex flex-column gap-2" data-slots-list>
        <?php foreach ($normRows as $rowIndex => $slotRow): ?>
            <div class="lms-slot-day-row" data-index="<?= (int) $rowIndex ?>">
                <div class="lms-slot-pill-box lms-slot-pill-day">
                    <i class="fa-regular fa-calendar text-muted"></i>
                    <select class="lms-slot-day-select"
                            name="<?= htmlspecialchars($dayName . '[' . (int) $rowIndex . ']', ENT_QUOTES, 'UTF-8') ?>"
                            aria-label="Chọn thứ">
                        <?php foreach ($weekdays as $wVal => $wLabel): ?>
                            <option value="<?= (int) $wVal ?>"<?= (int) $wVal === (int) $slotRow['day'] ? ' selected' : '' ?>><?= htmlspecialchars($wLabel, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="lms-slot-pill-close" data-remove-day title="Xoá ngày">&times;</button>
                </div>
                <div class="lms-slot-times-box d-flex flex-wrap align-items-center gap-2">
                    <?php foreach ($slotRow['ranges'] as $range): ?>
                        <div class="lms-slot-pill-box lms-slot-pill-time">
                            <button type="button" class="lms-slot-time-toggle" data-time-toggle
                                    title="Chọn giờ" aria-label="Chọn giờ">
                                <i class="fa-regular fa-clock text-muted"></i>
                            </button>
                            <input type="time" class="lms-slot-time-input"
                                   name="<?= htmlspecialchars($startName . '[' . (int) $rowIndex . '][]', ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= htmlspecialchars($range['start'], ENT_QUOTES, 'UTF-8') ?>">
                            <span>-</span>
                            <input type="time" class="lms-slot-time-input"
                                   name="<?= htmlspecialchars($endName . '[' . (int) $rowIndex . '][]', ENT_QUOTES, 'UTF-8') ?>"
                                   value="<?= htmlspecialchars($range['end'], ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="lms-slot-pill-close" data-remove-time title="Xoá khung giờ">&times;</button>
                        </div>
                    <?php endforeach; ?>
                    <button type="button" class="lms-slot-link-action" data-add-time>Thêm giờ</button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div>
        <button type="button" class="lms-slot-link-action" data-add-day>Thêm ngày</button>
    </div>
</div>

<script>
(function () {
    var cfg = <?= json_encode($slotConfig, JSON_UNESCAPED_UNICODE) ?>;
    var root = document.getElementById(cfg.rootId);
    if (!root || root.dataset.inited) {
        return;
    }
    root.dataset.inited = '1';

    var list = root.querySelector('[data-slots-list]');
    var addDayButton = root.querySelector('[data-add-day]');
    if (!list || !addDayButton) {
        return;
    }

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function openPicker(input) {
        if (typeof input.showPicker === 'function') {
            try { input.showPicker(); } catch (err) { input.focus(); }
        } else {
            input.focus();
        }
    }

    function timePillHtml(index, start, end) {
        return '<div class="lms-slot-pill-box lms-slot-pill-time">'
            + '<button type="button" class="lms-slot-time-toggle" data-time-toggle title="Chọn giờ" aria-label="Chọn giờ">'
            + '<i class="fa-regular fa-clock text-muted"></i></button>'
            + '<input type="time" class="lms-slot-time-input" name="' + cfg.startName + '[' + index + '][]" value="' + esc(start) + '">'
            + '<span>-</span>'
            + '<input type="time" class="lms-slot-time-input" name="' + cfg.endName + '[' + index + '][]" value="' + esc(end) + '">'
            + '<button type="button" class="lms-slot-pill-close" data-remove-time title="Xoá khung giờ">&times;</button>'
            + '</div>';
    }

    function dayOptions(selected) {
        var html = '';
        Object.keys(cfg.weekdays).forEach(function (key) {
            html += '<option value="' + esc(key) + '"' + (String(key) === String(selected) ? ' selected' : '') + '>'
                + esc(cfg.weekdays[key]) + '</option>';
        });
        return html;
    }

    function dayRowHtml(index, day) {
        return '<div class="lms-slot-day-row" data-index="' + index + '">'
            + '<div class="lms-slot-pill-box lms-slot-pill-day">'
            + '<i class="fa-regular fa-calendar text-muted"></i>'
            + '<select class="lms-slot-day-select" name="' + cfg.dayName + '[' + index + ']" aria-label="Chọn thứ">'
            + dayOptions(day) + '</select>'
            + '<button type="button" class="lms-slot-pill-close" data-remove-day title="Xoá ngày">&times;</button>'
            + '</div>'
            + '<div class="lms-slot-times-box d-flex flex-wrap align-items-center gap-2">'
            + timePillHtml(index, cfg.newStart, cfg.newEnd)
            + '<button type="button" class="lms-slot-link-action" data-add-time>Thêm giờ</button>'
            + '</div>'
            + '</div>';
    }

    addDayButton.addEventListener('click', function () {
        list.insertAdjacentHTML('beforeend', dayRowHtml(cfg.nextIndex, cfg.addDayValue));
        cfg.nextIndex++;
    });

    root.addEventListener('click', function (event) {
        var timeInput = event.target.closest('.lms-slot-time-input');
        if (timeInput) {
            openPicker(timeInput);
            return;
        }

        var toggle = event.target.closest('[data-time-toggle]');
        if (toggle) {
            var pill = toggle.closest('.lms-slot-pill-time');
            var input = pill ? pill.querySelector('input[type="time"]') : null;
            if (input) {
                openPicker(input);
            }
            return;
        }

        var addTime = event.target.closest('[data-add-time]');
        if (addTime) {
            var dayRow = addTime.closest('.lms-slot-day-row');
            if (dayRow) {
                addTime.insertAdjacentHTML('beforebegin', timePillHtml(dayRow.dataset.index, cfg.newStart, cfg.newEnd));
            }
            return;
        }

        var removeTime = event.target.closest('[data-remove-time]');
        if (removeTime) {
            var timePill = removeTime.closest('.lms-slot-pill-time');
            if (timePill) {
                timePill.remove();
            }
            return;
        }

        var removeDay = event.target.closest('[data-remove-day]');
        if (removeDay) {
            var row = removeDay.closest('.lms-slot-day-row');
            if (row) {
                row.remove();
            }
        }
    });
})();
</script>
