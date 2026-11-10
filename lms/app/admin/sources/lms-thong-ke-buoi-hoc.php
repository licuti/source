<?php
/**
 * Admin LMS — Thống kê số buổi học (ADM-15).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/Thống kê số buổi học.png
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§15. Thống kê số buổi học)
 *
 * Action:
 *  - 'man'  (GET) → Form tra cứu theo Mã học viên + Khoảng thời gian, Card thông tin HV, Tổng số buổi học & Bảng chi tiết.
 *  - 'xuat' (GET) → Xuất file CSV số buổi học của học viên.
 *
 * PHP 7.3 compatible.
 */
if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-thong-ke-buoi-hoc';

lms_form_guard($d, $user, $alias, 'view', 'admin');

$svc = new \Lms\AdminReportService($d);
$studentCode = isset($_GET['student_code']) ? trim((string) $_GET['student_code']) : '';
$dateFrom = isset($_GET['date_from']) ? trim((string) $_GET['date_from']) : '';
$dateTo = isset($_GET['date_to']) ? trim((string) $_GET['date_to']) : '';

if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
    $tmp = $dateFrom;
    $dateFrom = $dateTo;
    $dateTo = $tmp;
}

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$perPage = 20;

if (isset($a) && $a === 'xuat') {
    $data = $svc->lessonStats($studentCode, $dateFrom, $dateTo, 1, 10000);
    if (empty($data['student'])) {
        lms_flash('error', 'Không tìm thấy học viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }
    $safeCode = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $data['student']['student_code']);
    if ($safeCode === '') {
        $safeCode = 'hoc-vien';
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="so-buoi-hoc-' . $safeCode . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Thời Gian', 'Thứ', 'Số buổi học', 'Mã giáo viên', 'Ghi chú'));

    $weekdays = \Lms\UserService::WEEKDAYS;
    foreach ($data['items'] as $r) {
        $timeStr = trim((string) $r['session_date'] . ' ' . substr((string) $r['start_time'], 0, 5));
        $dow = (int) date('N', strtotime((string) $r['session_date']));
        $dowLabel = isset($weekdays[$dow + 1]) ? $weekdays[$dow + 1] : '';
        $row = array(
            $timeStr,
            $dowLabel,
            1,
            isset($r['teacher_code']) ? (string) $r['teacher_code'] : '',
            isset($r['lesson_summary']) ? (string) $r['lesson_summary'] : '',
        );
        foreach ($row as $i => $cell) {
            if (is_string($cell) && preg_match('/^[\s]*[=+@-]/', $cell)) {
                $row[$i] = "'" . $cell;
            }
        }
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

$data = $svc->lessonStats($studentCode, $dateFrom, $dateTo, $page, $perPage);
$student = $data['student'];
$items = $data['items'];
$total = (int) $data['total'];
$finishedCount = (int) $data['finished_count'];
$totalPages = max(1, (int) ceil($total / $perPage));

$title = 'Thống kê số buổi học';
$template = 'lms-thong-ke-buoi-hoc/hienthi';
