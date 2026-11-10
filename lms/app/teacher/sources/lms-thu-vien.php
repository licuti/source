<?php
/**
 * Teacher LMS — Thư viện tài liệu (TCH-07).
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/teacher/Thư viện.png
 *  - Tai-lieu-phat-trien/teacher/Tài liệu teacher.md (§10. Thư viện)
 * PHP 7.3 compatible.
 */
if (!defined('_source')) { die('Error'); }

$d = lms_db();
$user = lms_current_user();
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
$alias = 'lms-thu-vien';
$service = new \Lms\LibraryService($d);
$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'tai':
        lms_form_guard($d, $user, $alias, 'view', 'teacher');
        lms_library_teacher_download($service, $teacherId, $alias);
        break;

    default:
        lms_form_guard($d, $user, $alias, 'view', 'teacher');
        lms_library_teacher_list($service, $teacherId);
        break;
}

function lms_library_teacher_list(\Lms\LibraryService $service, $teacherId)
{
    global $title, $template, $items, $total, $totalPages, $page, $filterQ;

    $filterQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 24;

    $res = $service->listFilesForTeacher($teacherId, array('q' => $filterQ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));

    $title    = 'Thư viện';
    $template = 'lms-thu-vien/hienthi';
}

function lms_library_teacher_download(\Lms\LibraryService $service, $teacherId, $alias)
{
    lms_prepare_private_upload_dir('library');
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if (!$service->canTeacherDownloadFile($id, $teacherId)) {
        lms_flash('error', 'Tài liệu không tồn tại hoặc Admin chưa cho phép tải xuống file này.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $file = $service->getFile($id);
    if (empty($file)) {
        lms_flash('error', 'Tài liệu không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $fileUrl = (string) $file['file_url'];
    if (preg_match('#^https?://#i', $fileUrl)) {
        $service->incrementDownload($id);
        header('Location: ' . $fileUrl);
        exit;
    }

    $path = realpath(_LMS_DIR . '/public/uploads/' . ltrim($fileUrl, '/'));
    $base = realpath(_LMS_DIR . '/public/uploads');
    if (!$path || !$base || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        lms_flash('error', 'File không còn tồn tại trên máy chủ.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $service->incrementDownload($id);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . rawurlencode(basename($path)) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
