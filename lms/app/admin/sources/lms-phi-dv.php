<?php
/**
 * Admin LMS — Thống kê phí dịch vụ (ADM-07).
 *
 * Mockup: Tai-lieu-phat-trien/admin/Thống kê phí dịch vụ*.png.
 *  - Tra cứu theo mã GV + khoảng thời gian (mặc định tháng hiện tại).
 *  - Hiển thị ký quỹ / vi phạm / phí cộng thêm / thuế + bảng giao dịch.
 *  - Xuất CSV: tất cả GV, phí dịch vụ 1 GV, số buổi dạy 1 GV.
 *
 * Action:
 *  - ''             (GET)  → form tra cứu + kết quả 1 GV.
 *  - 'xuat-tat-ca'  (GET)  → CSV tổng hợp phí DV mọi GV trong khoảng ngày.
 *  - 'xuat-phi-dv'  (GET)  → CSV giao dịch phí DV của 1 GV.
 *  - 'xuat-buoi-day' (GET) → CSV số buổi dạy của 1 GV.
 *
 * Router đã check auth gate + quyền view; action POST thêm guard edit (§3.4).
 * KHÔNG echo HTML (PRG). PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d       = lms_db();
$user    = lms_current_user();
$alias   = 'lms-phi-dv';
$paySvc  = new \Lms\PayrollService($d);

$action = (isset($a) && $a !== '') ? (string) $a : (isset($_GET['a']) ? (string) $_GET['a'] : '');

switch ($action) {
    case 'chi-tiet':
    case 'them-dieu-chinh':
    case 'duyet':
        header('Location: ' . lms_url($alias));
        exit;

    case 'xuat-tat-ca':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lpd_export_all($paySvc);
        break;

    case 'xuat-phi-dv':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lpd_export_teacher($paySvc);
        break;

    case 'xuat-buoi-day':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lpd_export_sessions($paySvc);
        break;

    default:
        lpd_report($paySvc);
        break;
}

// ===================== Helpers =====================

/**
 * Khoảng ngày mặc định = tháng hiện tại.
 *
 * @return array array('Y-m-01', 'Y-m-t')
 */
function lpd_default_range()
{
    return array(date('Y-m-01'), date('Y-m-t'));
}

/**
 * @param string $date
 * @return bool
 */
function lpd_valid_date($date)
{
    $date = (string) $date;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
}

/**
 * Khoảng ngày hợp lệ từ query (tự đảo nếu nhập ngược), mặc định tháng hiện tại.
 *
 * @return array array($dateFrom, $dateTo)
 */
function lpd_range_from_query()
{
    list($defaultFrom, $defaultTo) = lpd_default_range();

    $from = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
    $to   = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

    if (!lpd_valid_date($from)) {
        $from = $defaultFrom;
    }
    if (!lpd_valid_date($to)) {
        $to = $defaultTo;
    }
    if ($from > $to) {
        $tmp  = $from;
        $from = $to;
        $to   = $tmp;
    }

    return array($from, $to);
}

/**
 * Mã GV từ query.
 *
 * @return string
 */
function lpd_teacher_code_from_query()
{
    return isset($_GET['teacher_code']) ? trim((string) $_GET['teacher_code']) : '';
}

/**
 * @param float|int $value
 * @return string 243000 → "243,000"
 */
function lpd_money($value)
{
    return number_format((float) $value, 0, ',', ',');
}

/**
 * Nhãn hiển thị cho status.
 *
 * @param string $status
 * @return string
 */
function lpd_status_label($status)
{
    switch ((string) $status) {
        case 'draft':
            return 'Nháp';
        case 'pending':
            return 'Chờ duyệt';
        case 'paid':
            return 'Đã thanh toán';
        case 'mixed':
            return 'Thanh toán một phần';
        default:
            return '—';
    }
}

/**
 * Nhãn tiếng Việt cho type transaction.
 *
 * @param string $type
 * @return string
 */
function lpd_type_label($type)
{
    $labels = array(
        'salary_session' => 'Lương buổi',
        'penalty'        => 'Phạt',
        'bonus'          => 'Thưởng',
        'tax'            => 'Thuế TNCN',
        'deposit_deduct' => 'Trừ cọc',
        'adjustment'     => 'Điều chỉnh',
    );
    return isset($labels[$type]) ? $labels[$type] : $type;
}

/**
 * Thời gian giao dịch: ngày + giờ buổi học (nếu có).
 *
 * @param array $t
 * @return string
 */
function lpd_time_label(array $t)
{
    $date = isset($t['transaction_date']) ? (string) $t['transaction_date'] : '';
    $time = !empty($t['start_time']) ? substr((string) $t['start_time'], 0, 5) : '';
    return trim($date . ' ' . $time);
}

/**
 * Nội dung giao dịch hiển thị (khớp mockup).
 *
 * @param array $t
 * @return string
 */
function lpd_description(array $t)
{
    $type = isset($t['type']) ? (string) $t['type'] : '';
    $note = isset($t['reason']) ? trim((string) $t['reason']) : '';

    if ($type === 'salary_session') {
        $classCode = isset($t['class_code']) ? trim((string) $t['class_code']) : '';
        if ($classCode !== '') {
            return 'Bạn đã nhận được ' . lpd_money($t['amount']) . ' đ khi đã hoàn thành bài học cho ' . $classCode;
        }
    }

    if ($note !== '') {
        return $note;
    }

    return lpd_type_label($type);
}

/**
 * Chống CSV injection cho các ô dạng chuỗi.
 *
 * @param array $row
 * @return array
 */
function lpd_csv_row(array $row)
{
    foreach ($row as $i => $cell) {
        if (is_string($cell) && preg_match('/^[\s]*[=+@-]/', $cell)) {
            $row[$i] = "'" . $cell;
        }
    }
    return $row;
}

/**
 * Mở output CSV (UTF-8 BOM) và trả về stream.
 *
 * @param string $filename
 * @return resource|null
 */
function lpd_csv_open($filename)
{
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    // UTF-8 BOM để Excel mở tiếng Việt không lỗi.
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    return $out === false ? null : $out;
}

/**
 * Tên file an toàn từ mã GV.
 *
 * @param string $value
 * @return string
 */
function lpd_file_slug($value)
{
    return preg_replace('/[^A-Za-z0-9_-]/', '', (string) $value);
}

// ===================== Tra cứu + kết quả =====================

function lpd_report(\Lms\PayrollService $paySvc)
{
    global $title, $template, $teacherCode, $dateFrom, $dateTo, $teacher,
           $transactions, $report, $canEdit, $d, $user, $alias;

    list($dateFrom, $dateTo) = lpd_range_from_query();
    $teacherCode = lpd_teacher_code_from_query();

    $teacher      = $teacherCode !== '' ? $paySvc->findTeacherByCode($teacherCode) : array();
    $transactions = array();
    $report       = array(
        'count'         => 0,
        'tong_thu'      => 0.0,
        'ky_quy'        => 0.0,
        'vi_pham'       => 0.0,
        'phi_cong_them' => 0.0,
        'thue'          => 0.0,
        'thue_rate'     => 0.0,
        'thuc_nhan'     => 0.0,
    );

    if (!empty($teacher)) {
        $transactions = $paySvc->transactionsByRange($teacher['user_id'], $dateFrom, $dateTo);
        $report       = $paySvc->feeSummary(
            $transactions,
            (int) $teacher['is_commit_08'] === 1
        );
    }

    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');

    $title    = 'Thống kê phí dịch vụ';
    $template = 'lms-phi-dv/hienthi';
}

// ===================== Xuất CSV =====================

function lpd_export_all(\Lms\PayrollService $paySvc)
{
    list($dateFrom, $dateTo) = lpd_range_from_query();
    $items = $paySvc->allFeeSummaries($dateFrom, $dateTo);

    $out = lpd_csv_open('phi-dich-vu-tat-ca-' . $dateFrom . '-den-' . $dateTo . '.csv');
    if ($out === null) {
        exit;
    }

    fputcsv($out, array(
        'Mã GV',
        'Tên giáo viên',
        'Số điện thoại',
        'Email',
        'Tổng thu',
        'Ký quỹ',
        'Vi phạm',
        'Phí DV cộng thêm',
        'Thuế',
        'Thực nhận',
    ));

    foreach ($items as $r) {
        fputcsv($out, lpd_csv_row(array(
            $r['teacher_code'],
            $r['full_name'],
            $r['phone'],
            $r['email'],
            $r['tong_thu'],
            $r['ky_quy'],
            $r['vi_pham'],
            $r['phi_cong_them'],
            $r['thue'],
            $r['thuc_nhan'],
        )));
    }

    fclose($out);
    exit;
}

function lpd_export_teacher(\Lms\PayrollService $paySvc)
{
    global $alias;

    list($dateFrom, $dateTo) = lpd_range_from_query();
    $teacherCode = lpd_teacher_code_from_query();

    $teacher = $paySvc->findTeacherByCode($teacherCode);
    if (empty($teacher)) {
        lms_flash('error', 'Không tìm thấy giáo viên "' . $teacherCode . '".');
        header('Location: ' . lms_url($alias, '', array(
            'teacher_code' => $teacherCode,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        )));
        exit;
    }

    $items = $paySvc->transactionsByRange($teacher['user_id'], $dateFrom, $dateTo);

    $out = lpd_csv_open('phi-dich-vu-' . lpd_file_slug($teacher['teacher_code']) . '.csv');
    if ($out === null) {
        exit;
    }

    fputcsv($out, array('Thời gian', 'Loại', 'Số tiền', 'Nội dung', 'Trạng thái'));

    foreach ($items as $t) {
        fputcsv($out, lpd_csv_row(array(
            lpd_time_label($t),
            lpd_type_label($t['type']),
            (float) $t['amount'],
            lpd_description($t),
            lpd_status_label($t['status']),
        )));
    }

    fclose($out);
    exit;
}

function lpd_export_sessions(\Lms\PayrollService $paySvc)
{
    global $alias;

    list($dateFrom, $dateTo) = lpd_range_from_query();
    $teacherCode = lpd_teacher_code_from_query();

    $teacher = $paySvc->findTeacherByCode($teacherCode);
    if (empty($teacher)) {
        lms_flash('error', 'Không tìm thấy giáo viên "' . $teacherCode . '".');
        header('Location: ' . lms_url($alias, '', array(
            'teacher_code' => $teacherCode,
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
        )));
        exit;
    }

    $items = $paySvc->sessionsByRange($teacher['user_id'], $dateFrom, $dateTo);

    $out = lpd_csv_open('so-buoi-day-' . lpd_file_slug($teacher['teacher_code']) . '.csv');
    if ($out === null) {
        exit;
    }

    fputcsv($out, array('Ngày', 'Giờ bắt đầu', 'Giờ kết thúc', 'Mã lớp', 'Tên lớp', 'Trạng thái', 'Ghi chú'));

    foreach ($items as $s) {
        fputcsv($out, lpd_csv_row(array(
            $s['session_date'],
            substr((string) $s['start_time'], 0, 5),
            substr((string) $s['end_time'], 0, 5),
            $s['class_code'],
            $s['class_name'],
            $s['status'],
            (string) $s['lesson_summary'],
        )));
    }

    fclose($out);
    exit;
}
