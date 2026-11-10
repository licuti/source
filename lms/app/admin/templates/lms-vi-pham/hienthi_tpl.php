<?php
/**
 * Admin LMS — Quản lý vi phạm: danh sách + Modal Điều chỉnh vi phạm + Modal Chỉnh sửa quy chế.
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/Quản lý vi phạm.png
 *  - Tai-lieu-phat-trien/admin/+_- vi phạm.png
 *  - Tai-lieu-phat-trien/admin/Miễn giảm.png
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components:
 *  - form/input, form/select, form/textarea, form/addon_input, form/field_row
 *  - button, pagination
 *
 * Data từ source lms-vi-pham.php:
 *  $items, $total, $page, $perPage, $totalPages,
 *  $filterQ, $filterTeacher, $filterReason, $filterMonth, $filterAuto,
 *  $teachers, $classes, $thresholds, $demoteThreshold, $scanRules,
 *  $canEdit, $alias.
 */

$oneOnOneRules = isset($scanRules['one_on_one']) && is_array($scanRules['one_on_one'])
    ? $scanRules['one_on_one']
    : array();
$groupRules = isset($scanRules['group']) && is_array($scanRules['group'])
    ? $scanRules['group']
    : array();

// Chuẩn bị options cho component form/select trong Modal Điều chỉnh vi phạm
$classSelectOptions = array();
if (!empty($classes) && is_array($classes)) {
    foreach ($classes as $cls) {
        $cid = (int) $cls['id'];
        $classSelectOptions[$cid] = array(
            'label' => $cls['class_code'] . ($cls['name'] !== '' ? ' — ' . $cls['name'] : ''),
            'attrs' => array(
                'data-teacher-id' => (int) ($cls['teacher_id'] ?? 0),
            ),
        );
    }
}

$teacherSelectOptions = array();
if (!empty($teachers) && is_array($teachers)) {
    foreach ($teachers as $t) {
        $tid = (int) $t['user_id'];
        $teacherSelectOptions[$tid] = $t['teacher_code'] . ' — ' . $t['full_name'];
    }
}

if (!function_exists('lvp_render_scan_rule_row')) {
    /**
     * Render 1 dòng cấu hình quét thời hạn (Toggle switch + Input giờ/số giờ + Addon tick)
     * sử dụng hoàn toàn các component form/input, form/addon_input và form/field_row.
     *
     * @param array $cfg
     * @return void
     */
    function lvp_render_scan_rule_row(array $cfg)
    {
        $label       = isset($cfg['label']) ? (string) $cfg['label'] : '';
        $switchName  = isset($cfg['switch_name']) ? (string) $cfg['switch_name'] : '';
        $switchVal   = !empty($cfg['switch_checked']);
        $inputType   = isset($cfg['input_type']) ? (string) $cfg['input_type'] : 'number';
        $inputName   = isset($cfg['input_name']) ? (string) $cfg['input_name'] : '';
        $inputVal    = isset($cfg['input_value']) ? $cfg['input_value'] : '';
        $unitText    = isset($cfg['unit_text']) ? (string) $cfg['unit_text'] : '';
        $tickName    = isset($cfg['tick_name']) ? (string) $cfg['tick_name'] : '';
        $tickVal     = isset($cfg['tick_value']) ? (int) $cfg['tick_value'] : 1;
        $hint        = isset($cfg['hint']) ? (string) $cfg['hint'] : '';
        $idPrefix    = isset($cfg['id_prefix']) ? (string) $cfg['id_prefix'] : 'scan';

        ob_start();
        ?>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="form-check form-switch mb-0 me-1">
                <input class="form-check-input"
                       type="checkbox"
                       id="<?= e($idPrefix . '-' . $switchName) ?>"
                       name="<?= e($switchName) ?>"
                       value="1"
                       <?= $switchVal ? 'checked' : '' ?>
                       >
            </div>

            <div style="width: <?= $inputType === 'time' ? '135px' : '85px' ?>;">
                <?php
                $inpProps = array(
                    'name'  => $inputName,
                    'id'    => $idPrefix . '-' . $inputName,
                    'type'  => $inputType,
                    'value' => $inputVal,
                    'bare'  => true,
                    'class' => 'form-control text-center',
                );
                if ($inputType === 'number') {
                    $inpProps['min'] = 0;
                    $inpProps['max'] = 720;
                }
                lms_component('form/input', $inpProps);
                ?>
            </div>

            <span class="text-dark small"><?= e($unitText) ?></span>

            <div class="ms-sm-auto" style="width: 150px;">
                <?php lms_component('form/addon_input', array(
                    'name'   => $tickName,
                    'id'     => $idPrefix . '-' . $tickName,
                    'type'   => 'number',
                    'value'  => $tickVal,
                    'min'    => 0,
                    'max'    => 20,
                    'suffix' => 'tick',
                    'bare'   => true,
                )); ?>
            </div>
        </div>
        <?php
        $controlHtml = ob_get_clean();

        lms_component('form/field_row', array(
            'label'   => $label,
            'id'      => $idPrefix . '-' . $inputName,
            'control' => $controlHtml,
            'hint'    => $hint,
        ));
    }
}
?>

<!-- Thanh tìm kiếm & các nút chức năng (Mockup Quản lý vi phạm.png) -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
    <form method="get" action="<?= e(lms_url($alias)) ?>" class="d-flex flex-wrap align-items-center gap-2">
        <div style="min-width: 240px;">
            <?php lms_component('form/input', array(
                'name'        => 'q',
                'value'       => $filterQ,
                'placeholder' => 'Mã lớp, GV',
                'bare'        => true,
                'class'       => 'form-control',
            )); ?>
        </div>
        <?php lms_component('button', array(
            'label'   => 'Tìm',
            'type'    => 'submit',
            'variant' => 'outline-secondary',
            'class'   => '',
        )); ?>
        <?php if ($filterQ !== '' || $filterTeacher > 0 || $filterReason !== '' || $filterMonth !== ''): ?>
            <?php lms_component('button', array(
                'label'   => 'Xoá lọc',
                'href'    => lms_url($alias),
                'variant' => 'outline-secondary',
                'class'   => '',
            )); ?>
        <?php endif; ?>
    </form>

    <?php if ($canEdit): ?>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <?php lms_component('button', array(
                'label'   => 'Chỉnh sửa quy chế',
                'type'    => 'button',
                'variant' => 'primary',
                'size'    => 'md',
                'attrs'   => array(
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#lmsRegulationModal',
                ),
            )); ?>
            <?php lms_component('button', array(
                'label'   => 'Điều chỉnh',
                'type'    => 'button',
                'variant' => 'primary',
                'size'    => 'md',
                'attrs'   => array(
                    'data-bs-toggle' => 'modal',
                    'data-bs-target' => '#lmsAdjustViolationModal',
                ),
            )); ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bảng danh sách vi phạm (7 cột theo Mockup Quản lý vi phạm.png) -->
<div class="table-responsive">
    <table class="table table-bordered table-hover align-middle mb-0 lms-table">
        <thead class="text-center">
            <tr>
                <th style="width: 110px;">Mã lớp</th>
                <th style="width: 100px;">Mã GV</th>
                <th style="width: 160px;">Thời gian vi phạm</th>
                <th style="width: 130px;">Số tick mới nhất</th>
                <th>Nội dung vi phạm</th>
                <th style="width: 140px;">Số tick tháng này</th>
                <?php if ($canEdit): ?>
                    <th style="width: 100px;">Hành động</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)): ?>
                <tr>
                    <td colspan="<?= $canEdit ? 7 : 6 ?>" class="text-center py-4 text-muted">
                        Chưa có bản ghi vi phạm nào phù hợp.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($items as $r): ?>
                    <?php
                    $vid         = (int) ($r['id'] ?? 0);
                    $classCode   = !empty($r['class_code']) ? (string) $r['class_code'] : (!empty($r['class_name']) ? (string) $r['class_name'] : '—');
                    $teacherCode = !empty($r['teacher_code']) ? (string) $r['teacher_code'] : ('GV#' . (int) ($r['teacher_id'] ?? 0));

                    $rawTime = !empty($r['created_at']) ? (string) $r['created_at'] : (isset($r['violation_date']) ? (string) $r['violation_date'] : '');
                    $timeDisplay = '—';
                    if ($rawTime !== '' && strtotime($rawTime) !== false) {
                        $timeDisplay = strlen($rawTime) > 10
                            ? date('d-m-Y H:i', strtotime($rawTime))
                            : date('d-m-Y', strtotime($rawTime)) . ' 00:00';
                    }

                    $tickAmt     = (int) ($r['tick_amount'] ?? 0);
                    $tickDisplay = $tickAmt > 0 ? '+' . $tickAmt : (string) $tickAmt;

                    $contentDisplay = \Lms\TickService::formatViolationContent($r);
                    $monthTicks     = (int) ($r['month_ticks'] ?? 0);
                    ?>
                    <tr>
                        <!-- 1. Mã lớp -->
                        <td class="text-center fw-medium">
                            <?= e($classCode) ?>
                        </td>

                        <!-- 2. Mã GV -->
                        <td class="text-center fw-semibold" title="<?= e((string) ($r['teacher_name'] ?? '')) ?>">
                            <?= e($teacherCode) ?>
                        </td>

                        <!-- 3. Thời gian vi phạm -->
                        <td class="text-center text-nowrap">
                            <?= e($timeDisplay) ?>
                        </td>

                        <!-- 4. Số tick mới nhất -->
                        <td class="text-center fw-bold <?= $tickAmt < 0 ? 'text-success' : 'text-dark' ?>">
                            <?= e($tickDisplay) ?>
                        </td>

                        <!-- 5. Nội dung vi phạm -->
                        <td>
                            <?= e($contentDisplay) ?>
                        </td>

                        <!-- 6. Số tick tháng này -->
                        <td class="text-center fw-semibold">
                            <?= $monthTicks ?>
                        </td>

                        <!-- 7. Hành động -->
                        <?php if ($canEdit): ?>
                            <td class="text-center text-nowrap">
                                <a href="#"
                                   class="lms-action-link js-delete-violation"
                                   data-id="<?= $vid ?>">Xoá</a>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Phân trang chuẩn theo mockup (Component pagination) -->
<?php lms_component('pagination', array(
    'page'        => $page,
    'total_pages' => $totalPages,
    'base_url'    => lms_url($alias, 'man'),
    'params'      => array_filter(array(
        'q'          => $filterQ,
        'teacher_id' => $filterTeacher,
        'reason'     => $filterReason,
        'month'      => $filterMonth,
        'auto'       => $filterAuto,
    ), function ($v) { return $v !== '' && $v !== 0 && $v !== null; }),
)); ?>

<?php if ($canEdit): ?>
    <!-- Form ẩn dùng để xoá bản ghi vi phạm (bảo vệ CSRF) -->
    <form method="post" action="<?= e(lms_url($alias, 'xoa')) ?>" id="lms-delete-violation-form" class="d-none">
        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
        <?php lms_component('form/input', array(
            'name'  => 'id',
            'id'    => 'lms-delete-violation-id',
            'type'  => 'hidden',
            'value' => '',
            'bare'  => true,
        )); ?>
    </form>

    <!-- ===================== MODAL 1: ĐIỀU CHỈNH VI PHẠM (+_- vi phạm.png) ===================== -->
    <div class="modal fade" id="lmsAdjustViolationModal" tabindex="-1" aria-labelledby="lmsAdjustViolationLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark fs-5" id="lmsAdjustViolationLabel">
                        Điều chỉnh vi phạm
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <form method="post" action="<?= e(lms_url($alias, 'dieu-chinh')) ?>" id="form-adjust-violation">
                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

                        <!-- 1. Mã lớp (Component form/select) -->
                        <?php lms_component('form/select', array(
                            'name'        => 'class_id',
                            'id'          => 'fld-adjust-class',
                            'label'       => 'Mã lớp',
                            'options'     => $classSelectOptions,
                            'empty_label' => '— Tìm hoặc chọn mã lớp (không bắt buộc) —',
                            'value'       => '',
                            'class'       => 'form-select',
                        )); ?>

                        <!-- 2. Giáo viên * (Component form/select) -->
                        <?php lms_component('form/select', array(
                            'name'        => 'teacher_id',
                            'id'          => 'fld-adjust-teacher',
                            'label'       => 'Giáo viên',
                            'options'     => $teacherSelectOptions,
                            'empty_label' => '— Tìm hoặc chọn giáo viên —',
                            'value'       => '',
                            'required'    => true,
                            'class'       => 'form-select',
                        )); ?>

                        <!-- 3. Số tick * (Component form/input) -->
                        <?php lms_component('form/input', array(
                            'name'        => 'tick_amount',
                            'id'          => 'fld-adjust-ticks',
                            'label'       => 'Số tick',
                            'placeholder' => '+1, -1, +2, -2',
                            'value'       => '',
                            'required'    => true,
                        )); ?>

                        <!-- 4. Nội dung (Component form/textarea) -->
                        <?php lms_component('form/textarea', array(
                            'name'        => 'note',
                            'id'          => 'fld-adjust-note',
                            'label'       => 'Nội dung',
                            'rows'        => 3,
                            'placeholder' => 'Ghi nội dung điều chỉnh số tick ở đây',
                            'value'       => '',
                        )); ?>

                        <!-- Nút Thêm (Component button) -->
                        <div class="text-center mt-3">
                            <?php lms_component('button', array(
                                'label'   => 'Thêm',
                                'type'    => 'submit',
                                'variant' => 'secondary',
                                'size'    => 'md',
                                'class'   => '',
                            )); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== MODAL 2: CHỈNH SỬA QUY CHẾ MIỄN GIẢM & QUÉT THỜI HẠN (Miễn giảm.png) ===================== -->
    <div class="modal fade" id="lmsRegulationModal" tabindex="-1" aria-labelledby="lmsRegulationLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark fs-5" id="lmsRegulationLabel">
                        Chỉnh sửa quy chế miễn giảm và ngưỡng hạ phí dịch vụ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- PHẦN 1: Bảng miễn giảm & ngưỡng hạ phí dịch vụ -->
                    <form method="post" action="<?= e(lms_url($alias, 'luu-quy-che-mien-giam')) ?>" class="mb-4 pb-4 border-bottom">
                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

                        <div class="table-responsive mb-3">
                            <table class="table table-bordered align-middle mb-2 text-center" id="lms-exemption-table">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50%;">Số lớp</th>
                                        <th style="width: 38%;">Số tick miễn giảm</th>
                                        <th style="width: 12%;"></th>
                                    </tr>
                                </thead>
                                <tbody id="lms-exemption-tbody">
                                    <?php foreach ($thresholds as $idx => $th): ?>
                                        <tr>
                                            <td class="p-1">
                                                <?php lms_component('form/input', array(
                                                    'name'        => 'class_range[]',
                                                    'id'          => 'fld-class-range-' . $idx,
                                                    'value'       => (string) $th['class_range'],
                                                    'placeholder' => 'VD: Từ 6 trở lên, 5, 1-2',
                                                    'required'    => true,
                                                    'bare'        => true,
                                                    'class'       => 'form-control form-control-sm text-center',
                                                )); ?>
                                            </td>
                                            <td class="p-1">
                                                <?php lms_component('form/input', array(
                                                    'name'     => 'ticks_exempted[]',
                                                    'id'       => 'fld-ticks-exempted-' . $idx,
                                                    'type'     => 'number',
                                                    'value'    => (int) $th['ticks_exempted'],
                                                    'min'      => 0,
                                                    'max'      => 100,
                                                    'required' => true,
                                                    'bare'     => true,
                                                    'class'    => 'form-control form-control-sm text-center',
                                                )); ?>
                                            </td>
                                            <td class="p-1">
                                                <?php lms_component('button', array(
                                                    'label'   => '×',
                                                    'type'    => 'button',
                                                    'variant' => 'outline-danger',
                                                    'size'    => 'sm',
                                                    'class'   => 'py-0 px-2 js-remove-threshold-row',
                                                    'title'   => 'Xoá dòng',
                                                )); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>

                            <div class="text-end">
                                <?php lms_component('button', array(
                                    'label'   => 'Thêm dòng',
                                    'type'    => 'button',
                                    'variant' => 'outline-secondary',
                                    'size'    => 'sm',
                                    'icon'    => 'fa-solid fa-plus',
                                    'id'      => 'btn-add-threshold-row',
                                )); ?>
                            </div>
                        </div>

                        <!-- Ngưỡng tick hạ phí dịch vụ (Component form/addon_input) -->
                        <div>
                            <?php lms_component('form/addon_input', array(
                                'name'     => 'ticks_to_demote_fee',
                                'id'       => 'fld-demote-ticks',
                                'label'    => 'Ngưỡng tick hạ phí dịch vụ',
                                'type'     => 'number',
                                'value'    => (int) $demoteThreshold,
                                'min'      => 1,
                                'max'      => 100,
                                'suffix'   => 'tick',
                                'required' => true,
                            )); ?>
                        </div>

                        <div class="text-center mt-3">
                            <?php lms_component('button', array(
                                'label'   => 'Cập nhật',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'size'    => 'md',
                                'class'   => '',
                            )); ?>
                        </div>
                    </form>

                    <!-- PHẦN 2: Chỉnh sửa quy chế quét thời hạn lớp 1-1 -->
                    <form method="post" action="<?= e(lms_url($alias, 'luu-quy-che-quet')) ?>" class="mb-4 pb-4 border-bottom">
                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                        <?php lms_component('form/input', array(
                            'name'  => 'scope',
                            'type'  => 'hidden',
                            'value' => 'one_on_one',
                            'bare'  => true,
                        )); ?>

                        <h6 class="fw-bold text-dark mb-3">
                            Chỉnh sửa quy chế quét thời hạn lớp 1-1
                        </h6>

                        <?php
                        lvp_render_scan_rule_row(array(
                            'id_prefix'      => 's1',
                            'label'          => 'Hạn giao BTVN',
                            'switch_name'    => 'hw_deadline_enabled',
                            'switch_checked' => !empty($oneOnOneRules['hw_deadline_enabled']),
                            'input_type'     => 'time',
                            'input_name'     => 'hw_deadline_time',
                            'input_value'    => isset($oneOnOneRules['hw_deadline_time']) ? (string) $oneOnOneRules['hw_deadline_time'] : '12:00',
                            'unit_text'      => 'của ngày hôm sau',
                            'tick_name'      => 'hw_deadline_ticks',
                            'tick_value'     => (int) ($oneOnOneRules['hw_deadline_ticks'] ?? 1),
                        ));

                        lvp_render_scan_rule_row(array(
                            'id_prefix'      => 's1',
                            'label'          => 'Hạn check BTVN',
                            'switch_name'    => 'hw_check_enabled',
                            'switch_checked' => !empty($oneOnOneRules['hw_check_enabled']),
                            'input_type'     => 'number',
                            'input_name'     => 'hw_check_hours',
                            'input_value'    => (int) ($oneOnOneRules['hw_check_hours'] ?? 0),
                            'unit_text'      => 'giờ trước buổi tiếp theo',
                            'tick_name'      => 'hw_check_ticks',
                            'tick_value'     => (int) ($oneOnOneRules['hw_check_ticks'] ?? 1),
                            'hint'           => '*Chỉ áp dụng nếu học sinh có nộp BTVN trước deadline GV giao.',
                        ));

                        lvp_render_scan_rule_row(array(
                            'id_prefix'      => 's1',
                            'label'          => 'Hạn chấm test',
                            'switch_name'    => 'test_grace_enabled',
                            'switch_checked' => !empty($oneOnOneRules['test_grace_enabled']),
                            'input_type'     => 'number',
                            'input_name'     => 'test_grace_hours',
                            'input_value'    => (int) ($oneOnOneRules['test_grace_hours'] ?? 72),
                            'unit_text'      => 'giờ kể từ khi hs nộp bài',
                            'tick_name'      => 'test_grace_ticks',
                            'tick_value'     => (int) ($oneOnOneRules['test_grace_ticks'] ?? 1),
                        ));
                        ?>

                        <div class="text-center mt-3">
                            <?php lms_component('button', array(
                                'label'   => 'Cập nhật',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'size'    => 'md',
                                'class'   => '',
                            )); ?>
                        </div>
                    </form>

                    <!-- PHẦN 3: Chỉnh sửa quy chế quét thời hạn lớp đông người -->
                    <form method="post" action="<?= e(lms_url($alias, 'luu-quy-che-quet')) ?>">
                        <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
                        <?php lms_component('form/input', array(
                            'name'  => 'scope',
                            'type'  => 'hidden',
                            'value' => 'group',
                            'bare'  => true,
                        )); ?>

                        <h6 class="fw-bold text-dark mb-3">
                            Chỉnh sửa quy chế quét thời hạn lớp đông người
                        </h6>

                        <?php
                        lvp_render_scan_rule_row(array(
                            'id_prefix'      => 's2',
                            'label'          => 'Hạn giao BTVN',
                            'switch_name'    => 'hw_deadline_enabled',
                            'switch_checked' => !empty($groupRules['hw_deadline_enabled']),
                            'input_type'     => 'time',
                            'input_name'     => 'hw_deadline_time',
                            'input_value'    => isset($groupRules['hw_deadline_time']) ? (string) $groupRules['hw_deadline_time'] : '12:00',
                            'unit_text'      => 'của ngày hôm sau',
                            'tick_name'      => 'hw_deadline_ticks',
                            'tick_value'     => (int) ($groupRules['hw_deadline_ticks'] ?? 1),
                        ));

                        lvp_render_scan_rule_row(array(
                            'id_prefix'      => 's2',
                            'label'          => 'Hạn check BTVN',
                            'switch_name'    => 'hw_check_enabled',
                            'switch_checked' => !empty($groupRules['hw_check_enabled']),
                            'input_type'     => 'number',
                            'input_name'     => 'hw_check_hours',
                            'input_value'    => (int) ($groupRules['hw_check_hours'] ?? 24),
                            'unit_text'      => 'giờ trước buổi tiếp theo',
                            'tick_name'      => 'hw_check_ticks',
                            'tick_value'     => (int) ($groupRules['hw_check_ticks'] ?? 1),
                            'hint'           => '*Chỉ áp dụng nếu học sinh có nộp BTVN trước deadline GV giao.',
                        ));
                        ?>

                        <div class="text-center mt-3">
                            <?php lms_component('button', array(
                                'label'   => 'Cập nhật',
                                'type'    => 'submit',
                                'variant' => 'primary',
                                'size'    => 'md',
                                'class'   => '',
                            )); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Template dòng mẫu (dùng component) để JS nhân bản khi bấm Thêm dòng -->
    <template id="tpl-threshold-row">
        <tr>
            <td class="p-1">
                <?php lms_component('form/input', array(
                    'name'        => 'class_range[]',
                    'value'       => '',
                    'placeholder' => 'VD: 1-2, 3, Từ 6 trở lên',
                    'required'    => true,
                    'bare'        => true,
                    'class'       => 'form-control form-control-sm text-center',
                )); ?>
            </td>
            <td class="p-1">
                <?php lms_component('form/input', array(
                    'name'     => 'ticks_exempted[]',
                    'type'     => 'number',
                    'value'    => 1,
                    'min'      => 0,
                    'max'      => 100,
                    'required' => true,
                    'bare'     => true,
                    'class'    => 'form-control form-control-sm text-center',
                )); ?>
            </td>
            <td class="p-1">
                <?php lms_component('button', array(
                    'label'   => '×',
                    'type'    => 'button',
                    'variant' => 'outline-danger',
                    'size'    => 'sm',
                    'class'   => 'py-0 px-2 js-remove-threshold-row',
                    'title'   => 'Xoá dòng',
                )); ?>
            </td>
        </tr>
    </template>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Xoá bản ghi vi phạm
        var delForm = document.getElementById('lms-delete-violation-form');
        var delInput = document.getElementById('lms-delete-violation-id');
        document.querySelectorAll('.js-delete-violation').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                var id = link.getAttribute('data-id');
                if (!id) return;
                if (confirm('Bạn chắc chắn muốn xoá bản ghi vi phạm này?')) {
                    delInput.value = id;
                    delForm.submit();
                }
            });
        });

        // 2. Thêm / Xoá dòng trong bảng Quy chế miễn giảm (nhân bản từ <template> đã render bằng component)
        var tbody = document.getElementById('lms-exemption-tbody');
        var addBtn = document.getElementById('btn-add-threshold-row');
        var rowTpl = document.getElementById('tpl-threshold-row');
        if (tbody && addBtn && rowTpl) {
            addBtn.addEventListener('click', function () {
                var clone = rowTpl.content.cloneNode(true);
                tbody.appendChild(clone);
            });

            tbody.addEventListener('click', function (e) {
                var btn = e.target.closest('.js-remove-threshold-row');
                if (!btn) return;
                if (tbody.querySelectorAll('tr').length <= 1) {
                    alert('Bảng quy chế phải có ít nhất 1 dòng.');
                    return;
                }
                btn.closest('tr').remove();
            });
        }

        // 3. Khởi tạo Select2 cho Modal Điều chỉnh vi phạm (search & chọn Mã lớp + Giáo viên)
        if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
            var $ = window.jQuery;
            var $modal = $('#lmsAdjustViolationModal');
            var $classSel = $('#fld-adjust-class');
            var $teacherSel = $('#fld-adjust-teacher');

            $classSel.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $modal,
                placeholder: '— Tìm hoặc chọn mã lớp —'
            });

            $teacherSel.select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $modal,
                placeholder: '— Tìm hoặc chọn giáo viên —'
            });

            // Khi chọn Mã lớp, tự động gợi ý Giáo viên dạy lớp đó nếu chưa chọn
            $classSel.on('change', function () {
                var tid = $(this).find('option:selected').data('teacher-id');
                if (tid && parseInt(tid, 10) > 0 && !$teacherSel.val()) {
                    $teacherSel.val(String(tid)).trigger('change.select2');
                }
            });
        }
    });
    </script>
<?php endif; ?>
