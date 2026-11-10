<?php
/**
 * LMS Shared Component: calendar_week
 * Khung lưới lịch tuần dùng chung (Admin "Lịch học" + Teacher "Quản lý lịch").
 *
 * Component chỉ render khung: tiêu đề 7 ngày + các hàng giờ + ô.
 * Nội dung từng ô do template truyền qua callback để giữ nghiệp vụ riêng.
 *
 * Props:
 * @var string   $monday         Thứ 2 của tuần 'Y-m-d' (dùng khi không truyền $days)
 * @var array    $weekdays       map dow (2..8) => label
 * @var array    $days           [['date','dow','label','day','is_today']] (tuỳ chọn)
 * @var array    $rows           [['label','hour','cells' => [cell,...]]]
 * @var callable $cell_renderer  function (array $cell, array $row): string — HTML trong <td>
 * @var callable $cell_class     function (array $cell, array $row): string — class thêm cho <td>
 * @var callable $cell_attrs     function (array $cell, array $row): array  — attr thêm cho <td>
 * @var callable $row_class      function (array $row): string
 * @var callable $row_attrs      function (array $row): array
 * @var callable $head_renderer  function (array $day): string — HTML trong <th>
 * @var callable $head_class     function (array $day): string
 * @var string   $hour_label     Nhãn cột giờ (mặc định: 'Giờ')
 * @var string   $hour_style     Style inline cho cột giờ
 * @var string   $hour_class     Class thêm cho ô giờ
 * @var string   $head_style     Style inline cho ô ngày
 * @var string   $table_id
 * @var string   $class          Class thêm cho <table>
 *
 * PHP 7.3 compatible.
 */

$monday       = isset($monday) ? (string) $monday : date('Y-m-d');
$weekdays     = isset($weekdays) && is_array($weekdays) ? $weekdays : array();
$days         = isset($days) && is_array($days) ? $days : array();
$rows         = isset($rows) && is_array($rows) ? $rows : array();
$cellRenderer = isset($cell_renderer) && is_callable($cell_renderer) ? $cell_renderer : null;
$cellClass    = isset($cell_class) && is_callable($cell_class) ? $cell_class : null;
$cellAttrs    = isset($cell_attrs) && is_callable($cell_attrs) ? $cell_attrs : null;
$rowClass     = isset($row_class) && is_callable($row_class) ? $row_class : null;
$rowAttrs     = isset($row_attrs) && is_callable($row_attrs) ? $row_attrs : null;
$headRenderer = isset($head_renderer) && is_callable($head_renderer) ? $head_renderer : null;
$headClass    = isset($head_class) && is_callable($head_class) ? $head_class : null;
$hourLabel    = isset($hour_label) && $hour_label !== '' ? (string) $hour_label : 'Giờ';
$hourStyle    = isset($hour_style) ? (string) $hour_style : '';
$hourClass    = isset($hour_class) ? (string) $hour_class : '';
$headStyle    = isset($head_style) ? (string) $head_style : '';
$tableId      = isset($table_id) ? (string) $table_id : '';
$tableClass   = isset($class) ? (string) $class : '';

if ($days === array()) {
    $startTs = strtotime($monday);
    if ($startTs !== false) {
        for ($i = 0; $i < 7; $i++) {
            $ts = strtotime('+' . $i . ' days', $startTs);
            $dow = (int) date('N', $ts) + 1;
            $days[] = array(
                'date'     => date('Y-m-d', $ts),
                'dow'      => $dow,
                'label'    => isset($weekdays[$dow]) ? $weekdays[$dow] : '',
                'day'      => (int) date('j', $ts),
                'is_today' => date('Y-m-d', $ts) === date('Y-m-d'),
            );
        }
    }
}

$attrHtml = function (array $attrs) {
    $out = '';
    foreach ($attrs as $k => $v) {
        if ($v === true) {
            $out .= ' ' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8');
        } elseif ($v !== false && $v !== null && $v !== '') {
            $out .= ' ' . htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8')
                . '="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
        }
    }
    return $out;
};
?>
<div class="lms-cal-scroll">
    <table class="lms-cal<?= $tableClass !== '' ? ' ' . e($tableClass) : '' ?>"<?= $tableId !== '' ? ' id="' . e($tableId) . '"' : '' ?>>
        <thead>
            <tr>
                <th class="lms-cal-th lms-cal-th-hour<?= $hourClass !== '' ? ' ' . e($hourClass) : '' ?>"<?= $hourStyle !== '' ? ' style="' . e($hourStyle) . '"' : '' ?>><?= e($hourLabel) ?></th>
                <?php foreach ($days as $dayInfo): ?>
                    <?php $hClass = $headClass ? (string) $headClass($dayInfo) : ''; ?>
                    <th class="lms-cal-th<?= $hClass !== '' ? ' ' . e($hClass) : '' ?>"<?= $headStyle !== '' ? ' style="' . e($headStyle) . '"' : '' ?>>
                        <?php if ($headRenderer): ?>
                            <?= $headRenderer($dayInfo) ?>
                        <?php else: ?>
                            <span class="d-block"><?= e((string) $dayInfo['label']) ?></span>
                            <small><?= (int) date('j', strtotime((string) $dayInfo['date'])) ?>/<?= (int) date('n', strtotime((string) $dayInfo['date'])) ?></small>
                        <?php endif; ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
                <?php
                $rClass = $rowClass ? (string) $rowClass($r) : '';
                $rAttrs = $rowAttrs ? (array) $rowAttrs($r) : array();
                ?>
                <tr class="lms-cal-row<?= $rClass !== '' ? ' ' . e($rClass) : '' ?>"<?= $attrHtml($rAttrs) ?>>
                    <th class="lms-cal-th lms-cal-th-hour<?= $hourClass !== '' ? ' ' . e($hourClass) : '' ?>" scope="row"<?= $hourStyle !== '' ? ' style="' . e($hourStyle) . '"' : '' ?>><?= e((string) $r['label']) ?></th>
                    <?php foreach ($r['cells'] as $cell): ?>
                        <?php
                        $cClass = $cellClass ? (string) $cellClass($cell, $r) : '';
                        $cAttrs = $cellAttrs ? (array) $cellAttrs($cell, $r) : array();
                        ?>
                        <td class="lms-cal-cell<?= $cClass !== '' ? ' ' . e($cClass) : '' ?>"<?= $attrHtml($cAttrs) ?>>
                            <?= $cellRenderer ? $cellRenderer($cell, $r) : '' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
