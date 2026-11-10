<?php
/**
 * LMS Form Component: form/class_schedule_slots
 * Cụm "Lịch cố định" của lớp học: mỗi dòng gồm Thứ + giờ bắt đầu/kết thúc.
 *
 * Dùng chung cho màn hình Admin:
 *  - lms-lop/lich-hoc  (xem/chỉnh nhanh lịch cố định)
 *  - lms-lop/sua       (form thêm/sửa lớp)
 *
 * Props:
 * @var array  $rows       array(array('day' => 2..8, 'start' => 'HH:MM', 'end' => 'HH:MM'))
 * @var string $id         ID gốc của component
 * @var bool   $can_edit
 * @var int    $default_day
 * @var string $default_start
 * @var string $default_end
 *
 * PHP 7.3 compatible.
 */

$rows = isset($rows) && is_array($rows) ? $rows : array();
$compId = isset($id) && $id !== '' ? (string) $id : 'lms-class-schedule';
$canEdit = !empty($can_edit);
$defaultDay = isset($default_day) ? (int) $default_day : 2;
$defaultStart = isset($default_start) ? (string) $default_start : '17:30';
$defaultEnd = isset($default_end) ? (string) $default_end : '19:00';
$weekdays = \Lms\UserService::WEEKDAYS;

$normRows = array();
foreach ($rows as $row) {
    if (!is_array($row)) {
        continue;
    }
    $day = isset($row['day']) ? (int) $row['day'] : 0;
    if (!isset($weekdays[$day])) {
        $day = $defaultDay;
    }
    $start = isset($row['start']) ? substr(trim((string) $row['start']), 0, 5) : $defaultStart;
    $end = isset($row['end']) ? substr(trim((string) $row['end']), 0, 5) : $defaultEnd;
    $normRows[] = array('day' => $day, 'start' => $start, 'end' => $end);
}
if ($normRows === array()) {
    $normRows[] = array('day' => $defaultDay, 'start' => $defaultStart, 'end' => $defaultEnd);
}
?>
<div class="lms-class-schedule" id="<?= e($compId) ?>"
     data-class-schedule data-can-edit="<?= $canEdit ? '1' : '0' ?>">
    <div class="lms-class-schedule-list">
        <?php foreach ($normRows as $row): ?>
            <div class="lms-class-schedule-row">
                <div class="input-group lms-class-schedule-day">
                    <span class="input-group-text bg-white text-muted px-2"><i class="fa-regular fa-calendar"></i></span>
                    <select class="form-select" name="sch_day[]"<?= $canEdit ? '' : ' disabled' ?>>
                        <?php foreach ($weekdays as $k => $label): ?>
                            <option value="<?= (int) $k ?>"<?= (int) $row['day'] === (int) $k ? ' selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="input-group lms-class-schedule-time">
                    <span class="input-group-text bg-white text-muted px-2"><i class="fa-regular fa-clock"></i></span>
                    <input type="time" class="form-control text-center px-1" name="sch_start[]"
                           value="<?= e((string) $row['start']) ?>"<?= $canEdit ? '' : ' disabled' ?>>
                    <span class="input-group-text bg-light text-muted px-1">-</span>
                    <input type="time" class="form-control text-center px-1" name="sch_end[]"
                           value="<?= e((string) $row['end']) ?>"<?= $canEdit ? '' : ' disabled' ?>>
                </div>
                <?php if ($canEdit): ?>
                    <button type="button" class="btn btn-link text-danger p-0 ms-1" data-schedule-remove
                            style="font-size: .85rem; text-decoration: none;">Xoá</button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php if ($canEdit): ?>
<script>
(function () {
    var root = document.getElementById(<?= json_encode($compId) ?>);
    if (!root || root.dataset.scheduleInited === '1') {
        return;
    }
    root.dataset.scheduleInited = '1';

    var list = root.querySelector('.lms-class-schedule-list');
    var weekdays = <?= json_encode($weekdays, JSON_UNESCAPED_UNICODE) ?>;
    var defaults = <?= json_encode(array('day' => $defaultDay, 'start' => $defaultStart, 'end' => $defaultEnd)) ?>;

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function dayOptions(selected) {
        var html = '';
        Object.keys(weekdays).forEach(function (key) {
            html += '<option value="' + esc(key) + '"' + (String(key) === String(selected) ? ' selected' : '') + '>'
                + esc(weekdays[key]) + '</option>';
        });
        return html;
    }

    function rowHtml(day, start, end) {
        return '<div class="lms-class-schedule-row">'
            + '<div class="input-group lms-class-schedule-day">'
            + '<span class="input-group-text bg-white text-muted px-2"><i class="fa-regular fa-calendar"></i></span>'
            + '<select class="form-select" name="sch_day[]">' + dayOptions(day) + '</select>'
            + '</div>'
            + '<div class="input-group lms-class-schedule-time">'
            + '<span class="input-group-text bg-white text-muted px-2"><i class="fa-regular fa-clock"></i></span>'
            + '<input type="time" class="form-control text-center px-1" name="sch_start[]" value="' + esc(start) + '">'
            + '<span class="input-group-text bg-light text-muted px-1">-</span>'
            + '<input type="time" class="form-control text-center px-1" name="sch_end[]" value="' + esc(end) + '">'
            + '</div>'
            + '<button type="button" class="btn btn-link text-danger p-0 ms-1" data-schedule-remove style="font-size: .85rem; text-decoration: none;">Xoá</button>'
            + '</div>';
    }

    function syncAddButton() {
        var rows = list.querySelectorAll('.lms-class-schedule-row');
        for (var i = 0; i < rows.length; i++) {
            var old = rows[i].querySelector('[data-schedule-add]');
            if (old) {
                old.remove();
            }
        }
        if (!rows.length) {
            return;
        }
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link text-primary p-0 ms-2';
        button.setAttribute('data-schedule-add', '1');
        button.style.fontSize = '.85rem';
        button.style.textDecoration = 'none';
        button.textContent = 'Thêm';
        rows[rows.length - 1].appendChild(button);
    }

    root.addEventListener('click', function (event) {
        var add = event.target.closest('[data-schedule-add]');
        if (add) {
            event.preventDefault();
            list.insertAdjacentHTML('beforeend', rowHtml(defaults.day, defaults.start, defaults.end));
            syncAddButton();
            return;
        }

        var remove = event.target.closest('[data-schedule-remove]');
        if (remove) {
            event.preventDefault();
            if (list.querySelectorAll('.lms-class-schedule-row').length > 1) {
                remove.closest('.lms-class-schedule-row').remove();
                syncAddButton();
            } else {
                window.alert('Lớp học cần tối thiểu 1 buổi trong lịch cố định.');
            }
        }
    });

    syncAddButton();
})();
</script>
<?php endif; ?>
