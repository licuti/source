<?php
/** Teacher LMS — Thư viện tài liệu lớp học (TCH-07). */
if (!defined('_source')) { die('Error'); }

$d = lms_db();
$user = lms_current_user();
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
$alias = 'lms-thu-vien';
$service = new \Lms\LibraryService($d);
$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        lms_library_teacher_form($service, $teacherId);
        break;
    case 'xoa':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ.');
            header('Location: ' . lms_url($alias)); exit;
        }
        if ($service->deleteFile(isset($_POST['id']) ? (int) $_POST['id'] : 0, $teacherId)) {
            lms_flash('success', 'Đã xoá tài liệu của bạn.');
        } else {
            $errors = $service->errors();
            lms_flash('error', $errors ? reset($errors) : 'Không thể xoá tài liệu.');
        }
        header('Location: ' . lms_url($alias)); exit;
    case 'tai':
        lms_form_guard($d, $user, $alias, 'view', 'teacher');
        lms_library_teacher_download($service, $teacherId);
        break;
    default:
        lms_form_guard($d, $user, $alias, 'view', 'teacher');
        lms_library_teacher_list($service, $teacherId);
}

function lms_library_teacher_list(\Lms\LibraryService $service, $teacherId)
{
    global $title, $template, $items, $total, $totalPages, $page, $filterQ, $filterType, $classes, $alias, $d;
    $filterQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterType = isset($_GET['file_type']) ? (string) $_GET['file_type'] : '';
    $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 24;
    $res = $service->listFilesForTeacher($teacherId, array('q' => $filterQ, 'file_type' => $filterType, 'class_id' => $classId), $page, $perPage);
    $items = $res['items'];
    $total = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));
    $classes = $d->safeFetch('select id, class_code, name from db_lms_classes where teacher_id = ? and deleted_at is null order by created_at desc', array($teacherId));
    $title = 'Thư viện';
    $template = 'lms-thu-vien/hienthi';
}

function lms_library_teacher_form(\Lms\LibraryService $service, $teacherId)
{
    global $title, $template, $classes, $subjects, $topics, $errors, $alias, $d;
    $errors = array();
    $classes = $d->safeFetch('select id, class_code, name from db_lms_classes where teacher_id = ? and deleted_at is null order by created_at desc', array($teacherId));
    $subjects = $d->safeFetch('select s.id, s.name, p.name as program_name from db_lms_subjects s join db_lms_programs p on p.id=s.program_id order by p.name,s.name');
    $topics = $d->safeFetch('select id, name, subject_id from db_lms_topics order by subject_id, order_index, name');
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!lms_csrf_verify()) { lms_flash('error', 'Phiên làm việc không hợp lệ.'); header('Location: ' . lms_url($alias)); exit; }
        $upload = isset($_FILES['library_file']) ? $_FILES['library_file'] : array();
        if (lms_prepare_private_upload_dir('library') === null) {
            $saved = array('ok' => false, 'paths' => array(), 'error' => 'Không thể chuẩn bị thư mục tài liệu riêng tư.');
        } else {
            $saved = lms_save_files($upload, 'library/' . date('Ym'), array(
            'maxFiles' => 1,
            'maxSize' => \Lms\LibraryService::MAX_FILE_SIZE,
            'mimeWhitelist' => \Lms\LibraryService::ALLOWED_EXTENSIONS,
            ));
        }
        if (!$saved['ok']) {
            $errors = array('file' => $saved['error']);
        } elseif (empty($saved['paths'])) {
            $errors = array('file' => 'Chọn một file để tải lên.');
        } else {
            $data = $_POST;
            $data['file_url'] = $saved['paths'][0];
            $data['file_size_bytes'] = isset($upload['size']) ? (int) $upload['size'] : null;
            $data['is_downloadable'] = 1;
            if (empty($data['title'])) { $data['title'] = basename($upload['name']); }
            $id = $service->createFile($data, $teacherId, $teacherId);
            if ($id !== null) { lms_flash('success', 'Đã tải tài liệu lên thư viện.'); header('Location: ' . lms_url($alias)); exit; }
            $errors = $service->errors();
            $orphan = _LMS_DIR . '/public/uploads/' . $saved['paths'][0];
            if (is_file($orphan)) { @unlink($orphan); }
        }
    }
    $title = 'Tải tài liệu lên';
    $template = 'lms-thu-vien/form';
}

function lms_library_teacher_download(\Lms\LibraryService $service, $teacherId)
{
    global $alias;
    lms_prepare_private_upload_dir('library');
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if (!$service->canTeacherDownloadFile($id, $teacherId)) {
        http_response_code(404);
        lms_flash('error', 'Tài liệu không tồn tại hoặc admin chưa cho phép tải file này.');
        header('Location: ' . lms_url($alias)); exit;
    }
    $file = $service->getFile($id);
    $path = realpath(_LMS_DIR . '/public/uploads/' . ltrim((string) $file['file_url'], '/'));
    $base = realpath(_LMS_DIR . '/public/uploads');
    if (!$path || !$base || strpos($path, $base . DIRECTORY_SEPARATOR) !== 0 || !is_file($path)) {
        lms_flash('error', 'File không còn tồn tại trên máy chủ.'); header('Location: ' . lms_url($alias)); exit;
    }
    $service->incrementDownload($id);
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . rawurlencode(basename($path)) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}
