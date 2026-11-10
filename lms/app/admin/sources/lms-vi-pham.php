<?php
/**
 * Admin LMS — Quản lý vi phạm (tick) (Phase 2 — ADM-06).
 *
 * Đối chiếu chuẩn thiết kế:
 *  - Tai-lieu-phat-trien/admin/Quản lý vi phạm.png (Màn danh sách)
 *  - Tai-lieu-phat-trien/admin/+_- vi phạm.png (Modal Điều chỉnh vi phạm)
 *  - Tai-lieu-phat-trien/admin/Miễn giảm.png (Modal Chỉnh sửa quy chế miễn giảm & quét thời hạn)
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§8. Quản lý vi phạm)
 *
 * Actions:
 *   - 'man'                   (GET)  → danh sách vi phạm + 2 modal (Điều chỉnh & Chỉnh sửa quy chế)
 *   - 'dieu-chinh'            (POST) → thêm bản ghi điều chỉnh tick (+/-1, +/-2...) kèm mã lớp
 *   - 'luu-quy-che-mien-giam' (POST) → cập nhật bảng miễn giảm & ngưỡng hạ phí dịch vụ
 *   - 'luu-quy-che-quet'      (POST) → cập nhật quy chế quét thời hạn lớp 1-1 / lớp đông người
 *   - 'xoa'                   (POST) → xoá bản ghi vi phạm
 *   - 'them' / 'nguong' / 'luu-nguong' / 'xoa-nguong' → giữ tương thích ngược
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d       = lms_db();
$user    = lms_current_user();
$alias   = 'lms-vi-pham';
$tickSvc = new \Lms\TickService($d);

$canView = is_array($user) && lms_check_permission($d, $user, $alias, 'view');
$canEdit = is_array($user) && lms_check_permission($d, $user, $alias, 'edit');

if (!$canView) {
    lms_flash('error', 'Bạn không có quyền truy cập mục Quản lý vi phạm.');
    header('Location: ' . lms_url(''));
    exit;
}

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'dieu-chinh':
        lvp_adjust_tick($tickSvc);
        break;
    case 'luu-quy-che-mien-giam':
        lvp_save_regulation_thresholds($tickSvc);
        break;
    case 'luu-quy-che-quet':
        lvp_save_scan_rules($tickSvc);
        break;
    case 'them':
        lvp_add_form($tickSvc);
        break;
    case 'xoa':
        lvp_delete_tick($tickSvc);
        break;
    case 'nguong':
        lvp_thresholds($tickSvc);
        break;
    case 'luu-nguong':
        lvp_save_threshold($tickSvc);
        break;
    case 'xoa-nguong':
        lvp_delete_threshold($tickSvc);
        break;
    default:
        lvp_list($tickSvc);
        break;
}

// ===================== Helpers =====================

function lvp_teachers_dropdown(\LmsDb $d)
{
    return $d->safeFetch(
        'select user_id, teacher_code, full_name from db_lms_teachers order by teacher_code asc, full_name asc'
    );
}

function lvp_classes_dropdown(\LmsDb $d)
{
    return $d->safeFetch(
        'select id, class_code, name, teacher_id from db_lms_classes where deleted_at is null order by class_code asc, name asc'
    );
}

function lvp_require_edit($alias)
{
    global $canEdit;
    if (!$canEdit) {
        lms_flash('error', 'Bạn không có quyền chỉnh sửa mục này.');
        header('Location: ' . lms_url($alias));
        exit;
    }
}

function lvp_require_csrf($alias)
{
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ (CSRF). Vui lòng thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }
}

// ===================== Danh sách tick + Modals =====================

function lvp_list(\Lms\TickService $tickSvc)
{
    global $title, $template, $items, $total, $page, $perPage, $totalPages,
           $filterQ, $filterTeacher, $filterReason, $filterMonth, $filterAuto,
           $teachers, $classes, $thresholds, $demoteThreshold, $scanRules,
           $canEdit, $d, $alias;

    $filterQ       = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterTeacher = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $filterReason  = isset($_GET['reason']) ? (string) $_GET['reason'] : '';
    $filterMonth   = isset($_GET['month']) ? (string) $_GET['month'] : '';
    $filterAuto    = isset($_GET['auto']) ? (string) $_GET['auto'] : '';
    $page          = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage       = 20;

    $res = $tickSvc->listViolations(array(
        'q'          => $filterQ,
        'teacher_id' => $filterTeacher,
        'reason'     => $filterReason,
        'month'      => $filterMonth,
        'auto'       => $filterAuto,
    ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));

    $teachers = lvp_teachers_dropdown($d);
    $classes  = lvp_classes_dropdown($d);

    // Lấy danh sách quy chế miễn giảm sắp xếp từ lớp cao xuống thấp như mockup Miễn giảm.png
    $rawThresholds = $d->safeFetch(
        'select * from db_lms_violation_thresholds order by min_classes desc'
    );
    $thresholds = array();
    $demoteThreshold = 8;
    if (!empty($rawThresholds)) {
        $demoteThreshold = isset($rawThresholds[0]['ticks_to_demote_fee'])
            ? (int) $rawThresholds[0]['ticks_to_demote_fee']
            : 8;
        foreach ($rawThresholds as $rt) {
            $thresholds[] = array(
                'id'             => (int) $rt['id'],
                'min_classes'    => (int) $rt['min_classes'],
                'max_classes'    => $rt['max_classes'],
                'class_range'    => \Lms\TickService::formatClassRange($rt['min_classes'], $rt['max_classes']),
                'ticks_exempted' => (int) $rt['ticks_exempted'],
            );
        }
    } else {
        // Fallback mặc định theo mockup Miễn giảm.png nếu DB trống
        $thresholds = array(
            array('class_range' => 'Từ 6 trở lên', 'ticks_exempted' => 10),
            array('class_range' => '5',            'ticks_exempted' => 9),
            array('class_range' => '4',            'ticks_exempted' => 7),
            array('class_range' => '3',            'ticks_exempted' => 5),
            array('class_range' => '1-2',          'ticks_exempted' => 3),
        );
    }

    $scanRules = $tickSvc->getScanRules();

    $title    = 'Quản lý vi phạm';
    $template = 'lms-vi-pham/hienthi';
}

// ===================== Modal 1: Điều chỉnh vi phạm (+_- vi phạm.png) =====================

function lvp_adjust_tick(\Lms\TickService $tickSvc)
{
    global $user, $alias;

    lvp_require_edit($alias);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias));
        exit;
    }

    lvp_require_csrf($alias);

    $classId    = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $teacherId  = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $tickRaw    = isset($_POST['tick_amount']) ? trim((string) $_POST['tick_amount']) : '';
    $note       = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
    $reason     = isset($_POST['reason']) ? trim((string) $_POST['reason']) : '';
    $actorId    = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

    if ($tickRaw === '' || !preg_match('/^[+-]?\d+$/', $tickRaw)) {
        lms_flash('error', 'Số tick điều chỉnh không hợp lệ (VD hợp lệ: +1, -1, +2, -2).');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $tickAmount = (int) $tickRaw;
    $ok = $tickSvc->addManualTick($teacherId, $reason, $tickAmount, $note, $actorId, $classId > 0 ? $classId : null);

    if ($ok) {
        lms_flash('success', 'Đã thêm điều chỉnh vi phạm (' . ($tickAmount > 0 ? '+' . $tickAmount : (string) $tickAmount) . ' tick).');
    } else {
        lms_flash('error', $tickSvc->firstError() ?: 'Không thể thêm điều chỉnh vi phạm.');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Modal 2: Lưu quy chế miễn giảm & quét thời hạn (Miễn giảm.png) =====================

function lvp_save_regulation_thresholds(\Lms\TickService $tickSvc)
{
    global $user, $alias;

    lvp_require_edit($alias);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias));
        exit;
    }

    lvp_require_csrf($alias);

    $ranges     = isset($_POST['class_range']) && is_array($_POST['class_range']) ? $_POST['class_range'] : array();
    $exempteds  = isset($_POST['ticks_exempted']) && is_array($_POST['ticks_exempted']) ? $_POST['ticks_exempted'] : array();
    $demoteTick = isset($_POST['ticks_to_demote_fee']) ? (int) $_POST['ticks_to_demote_fee'] : 8;
    $actorId    = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

    $rows = array();
    $maxLen = max(count($ranges), count($exempteds));
    for ($i = 0; $i < $maxLen; $i++) {
        $rows[] = array(
            'class_range'    => isset($ranges[$i]) ? (string) $ranges[$i] : '',
            'ticks_exempted' => isset($exempteds[$i]) ? (string) $exempteds[$i] : '',
        );
    }

    if ($tickSvc->saveRegulationThresholds($rows, $demoteTick, $actorId)) {
        lms_flash('success', 'Đã cập nhật quy chế miễn giảm và ngưỡng hạ phí dịch vụ.');
    } else {
        lms_flash('error', $tickSvc->firstError() ?: 'Không thể cập nhật quy chế miễn giảm.');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

function lvp_save_scan_rules(\Lms\TickService $tickSvc)
{
    global $user, $alias;

    lvp_require_edit($alias);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias));
        exit;
    }

    lvp_require_csrf($alias);

    $scope   = isset($_POST['scope']) ? trim((string) $_POST['scope']) : '';
    $actorId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

    if ($tickSvc->saveScanRules($scope, $_POST, $actorId)) {
        $scopeLabel = ($scope === 'group') ? 'lớp đông người' : 'lớp 1-1';
        lms_flash('success', 'Đã cập nhật quy chế quét thời hạn cho ' . $scopeLabel . '.');
    } else {
        lms_flash('error', $tickSvc->firstError() ?: 'Không thể lưu quy chế quét thời hạn.');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Thêm tick thủ công (Trang rời tương thích ngược) =====================

function lvp_add_form(\Lms\TickService $tickSvc)
{
    global $title, $template, $errors, $old, $teachers, $user, $d, $alias;

    lvp_require_edit($alias);

    $errors = array();
    $old    = array(
        'teacher_id'  => isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0,
        'reason'      => \Lms\TickService::REASON_MANUAL_PENALTY,
        'tick_amount' => 1,
        'note'        => '',
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        lvp_require_csrf($alias);

        $old = array(
            'teacher_id'  => isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0,
            'reason'      => isset($_POST['reason']) ? (string) $_POST['reason'] : '',
            'tick_amount' => isset($_POST['tick_amount']) ? (int) $_POST['tick_amount'] : 0,
            'note'        => isset($_POST['note']) ? trim((string) $_POST['note']) : '',
        );

        $actorId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
        $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

        $ok = $tickSvc->addManualTick(
            $old['teacher_id'],
            $old['reason'],
            $old['tick_amount'],
            $old['note'],
            $actorId,
            $classId > 0 ? $classId : null
        );

        if ($ok) {
            lms_flash('success', 'Đã thêm tick thành công.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $errors = $tickSvc->errors();
    }

    $teachers = lvp_teachers_dropdown($d);
    $title    = 'Thêm tick thủ công';
    $template = 'lms-vi-pham/them';
}

// ===================== Xoá tick =====================

function lvp_delete_tick(\Lms\TickService $tickSvc)
{
    global $alias;

    lvp_require_edit($alias);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias));
        exit;
    }

    lvp_require_csrf($alias);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($tickSvc->deleteTick($id, true)) {
        lms_flash('success', 'Đã xoá bản ghi vi phạm.');
    } else {
        lms_flash('error', $tickSvc->firstError() ?: 'Không thể xoá bản ghi vi phạm.');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Cấu hình ngưỡng (Trang rời tương thích ngược) =====================

function lvp_thresholds(\Lms\TickService $tickSvc)
{
    global $title, $template, $thresholds, $editing, $errors, $canEdit;

    $thresholds = $tickSvc->listThresholds();
    $errors     = array();
    $editId     = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
    $editing    = array(
        'id'                          => 0,
        'min_classes'                 => 1,
        'max_classes'                 => '',
        'ticks_to_warn'               => 5,
        'ticks_to_demote_fee'         => 8,
        'fee_percentage_after_demote' => '30.00',
        'ticks_exempted'              => 2,
        'effective_from'              => date('Y-m-01'),
        'effective_to'                => '',
    );

    if ($editId > 0) {
        $found = $tickSvc->getThreshold($editId);
        if (!empty($found) && is_array($found)) {
            $editing = $found;
        }
    }

    $title    = 'Cấu hình ngưỡng vi phạm';
    $template = 'lms-vi-pham/nguong';
}

function lvp_save_threshold(\Lms\TickService $tickSvc)
{
    global $title, $template, $thresholds, $editing, $errors, $canEdit, $alias;

    lvp_require_edit($alias);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    lvp_require_csrf($alias);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $in = array(
        'min_classes'                 => isset($_POST['min_classes']) ? $_POST['min_classes'] : '',
        'max_classes'                 => isset($_POST['max_classes']) ? $_POST['max_classes'] : '',
        'ticks_to_warn'               => isset($_POST['ticks_to_warn']) ? $_POST['ticks_to_warn'] : '',
        'ticks_to_demote_fee'         => isset($_POST['ticks_to_demote_fee']) ? $_POST['ticks_to_demote_fee'] : '',
        'fee_percentage_after_demote' => isset($_POST['fee_percentage_after_demote']) ? $_POST['fee_percentage_after_demote'] : '',
        'ticks_exempted'              => isset($_POST['ticks_exempted']) ? $_POST['ticks_exempted'] : '',
        'effective_from'              => isset($_POST['effective_from']) ? $_POST['effective_from'] : '',
        'effective_to'                => isset($_POST['effective_to']) ? $_POST['effective_to'] : '',
    );

    if ($tickSvc->saveThreshold($in, $id > 0 ? $id : null)) {
        lms_flash('success', $id > 0 ? 'Đã cập nhật rule.' : 'Đã thêm rule mới.');
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    $errors     = $tickSvc->errors();
    $thresholds = $tickSvc->listThresholds();
    $editing    = array_merge(array('id' => $id), $in);
    $title      = 'Cấu hình ngưỡng vi phạm';
    $template   = 'lms-vi-pham/nguong';
}

function lvp_delete_threshold(\Lms\TickService $tickSvc)
{
    global $alias;

    lvp_require_edit($alias);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    lvp_require_csrf($alias);

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    if ($tickSvc->deleteThreshold($id)) {
        lms_flash('success', 'Đã xoá rule.');
    } else {
        lms_flash('error', $tickSvc->firstError() ?: 'Không thể xoá rule.');
    }

    header('Location: ' . lms_url($alias, 'nguong'));
    exit;
}
