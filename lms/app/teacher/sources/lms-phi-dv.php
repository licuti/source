<?php
/**
 * Teacher LMS — Phí dịch vụ / lương (TCH-05, Phase 2).
 *
 * SRS §TCH-05: GV xem lương + thuế của mình theo khoảng ngày/học viên;
 * banner cảnh báo khi tick vi phạm đạt ngưỡng hạ phí (BR-TICK-03).
 *
 * Action:
 *  - 'man' (GET) → bảng transactions của CHÍNH MÌNH (filter học viên + khoảng ngày)
 *    + tổng tiền theo công thức BR-PAY-04 (khớp admin lms-phi-dv).
 *
 * Row-level: teacher_id = user id hiện tại.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d         = lms_db();
$user      = lms_current_user();
$alias     = 'lms-phi-dv';
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    default:
        lpdv_list();
        break;
}

// ===================== Helpers =====================

/**
 * Khoảng ngày mặc định: tháng trước (tháng đã có dữ liệu chốt lương).
 *
 * @return array array('from' => 'Y-m-d', 'to' => 'Y-m-d')
 */
function lpdv_default_range()
{
    $first = strtotime('first day of previous month');
    if ($first === false) {
        return array('from' => date('Y-m-01'), 'to' => date('Y-m-t'));
    }
    return array(
        'from' => date('Y-m-01', $first),
        'to'   => date('Y-m-t', $first),
    );
}

/**
 * Format tiền kiểu admin/report: 1,234,567.
 *
 * @param float $value
 * @return string
 */
function lpdv_money($value)
{
    return number_format((float) $value, 0, ',', ',');
}

/**
 * Nhãn thời gian giao dịch: ngày + giờ buổi học (fallback giờ tạo).
 *
 * @param array $t
 * @return string
 */
function lpdv_time_label(array $t)
{
    $date = isset($t['transaction_date']) ? (string) $t['transaction_date'] : '';
    $time = !empty($t['start_time'])
        ? substr((string) $t['start_time'], 0, 5)
        : (!empty($t['created_at']) ? substr((string) $t['created_at'], 11, 5) : '');
    return trim($date . ' ' . $time);
}

/**
 * Tên học viên hiển thị trên bảng (salary 1-1 → tên HV; lớp nhóm → tên lớp).
 *
 * @param array $t
 * @return string
 */
function lpdv_student_label(array $t)
{
    if ((string) $t['type'] !== 'salary_session') {
        return '—';
    }
    $names = isset($t['student_names']) ? trim((string) $t['student_names']) : '';
    if ((string) ($t['class_type'] ?? '') === 'one_on_one' && $names !== '') {
        return $names;
    }
    $className = isset($t['class_name']) ? trim((string) $t['class_name']) : '';
    return $className !== '' ? $className : '—';
}

/**
 * Nội dung giao dịch (khớp mockup/admin).
 *
 * @param array $t
 * @return string
 */
function lpdv_description(array $t)
{
    $type = isset($t['type']) ? (string) $t['type'] : '';
    $note = isset($t['reason']) ? trim((string) $t['reason']) : '';

    if ($type === 'salary_session') {
        $className = isset($t['class_name']) ? trim((string) $t['class_name']) : '';
        if ($className === '') {
            $className = isset($t['class_code']) ? trim((string) $t['class_code']) : '';
        }
        if ($className !== '') {
            return 'Bạn đã nhận được ' . lpdv_money($t['amount']) . ' đ khi đã hoàn thành bài học cho ' . $className;
        }
    }

    if ($note !== '') {
        return $note;
    }

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

// ===================== Danh sách giao dịch của mình =====================

function lpdv_list()
{
    global $title, $template, $teacherId, $dateFrom, $dateTo, $studentId,
           $students, $transactions, $summary, $demoteWarning,
           $page, $totalPages, $d, $alias;

    if ($teacherId <= 0) {
        lms_flash('error', 'Vui lòng đăng nhập lại.');
        header('Location: ' . lms_url('', 'login'));
        exit;
    }

    $defaults = lpdv_default_range();
    $dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : $defaults['from'];
    $dateTo   = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : $defaults['to'];
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
        $dateFrom = $defaults['from'];
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
        $dateTo = $defaults['to'];
    }
    if (strcmp($dateFrom, $dateTo) > 0) {
        $tmp = $dateFrom;
        $dateFrom = $dateTo;
        $dateTo = $tmp;
    }
    $studentId = isset($_GET['student_id']) ? max(0, (int) $_GET['student_id']) : 0;

    // Danh sách HV đang học các lớp của GV (để lọc).
    $students = $d->safeFetch(
        'select distinct st.user_id, st.full_name, st.student_code'
        . ' from db_lms_class_students cs'
        . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
        . ' join db_lms_students st on st.user_id = cs.student_id'
        . ' where c.teacher_id = ? and c.status = ? and cs.status = ?'
        . ' order by st.full_name asc',
        array($teacherId, 'active', 'active')
    );

    // Nếu truyền student_id không thuộc GV → bỏ lọc để tránh lộ dữ liệu.
    if ($studentId > 0) {
        $valid = false;
        foreach ($students as $st) {
            if ((int) $st['user_id'] === $studentId) {
                $valid = true;
                break;
            }
        }
        if (!$valid) {
            $studentId = 0;
        }
    }

    $paySvc = new \Lms\PayrollService($d);
    $allTransactions = $paySvc->transactionsByRange($teacherId, $dateFrom, $dateTo, $studentId);

    // Cùng công thức với admin lms-phi-dv (BR-PAY-04) — tính trên toàn bộ filter,
    // không phải chỉ trang hiện tại.
    $teacherRow = $d->safeFetchOne(
        'select is_commit_08 from db_lms_teachers where user_id = ? limit 1',
        array($teacherId)
    );
    $isCommit08 = !empty($teacherRow['is_commit_08']);
    $summary = $paySvc->feeSummary($allTransactions, $isCommit08);

    // Phân trang hiển thị (dữ liệu lớn vẫn giữ tổng tiền đúng toàn khoảng lọc).
    $perPage = 20;
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $totalTransactions = count($allTransactions);
    $totalPages = max(1, (int) ceil($totalTransactions / $perPage));
    $page = min($page, $totalPages);
    $transactions = array_slice($allTransactions, ($page - 1) * $perPage, $perPage);

    // Banner cảnh báo tick (SRS TCH-05 + BR-TICK-03).
    $tickSvc      = new \Lms\TickService($d);
    $tickSummary  = $tickSvc->teacherTickSummary($teacherId);
    $demoteWarning = '';
    if (is_array($tickSummary['threshold'])) {
        $demoteAt = (int) $tickSummary['threshold']['ticks_to_demote_fee'];
        if ($tickSummary['real'] >= $demoteAt) {
            $demoteWarning = 'Bạn đang ở mức ' . $tickSummary['real'] . ' tick vi phạm —'
                . ' đã vượt ngưỡng ' . $demoteAt . ', % phí dịch vụ sẽ bị hạ tháng sau.'
                . ' Hãy chấm bài đúng hạn để giảm tick.';
        }
    }

    $title    = 'Phí dịch vụ';
    $template = 'lms-phi-dv/hienthi';
}
