<?php
/** Admin LMS — Thư viện (TCH-07 / quản trị tài liệu). */
if (!defined('_source')) { die('Error'); }

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-thu-vien';
$service = new \Lms\LibraryService($d);
$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lms_library_admin_form($service, false);
        break;
    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lms_library_admin_form($service, true);
        break;
    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($service->deleteFile($id)) {
            lms_flash('success', 'Đã xoá tài liệu khỏi thư viện.');
        } else {
            $errors = $service->errors();
            lms_flash('error', $errors ? reset($errors) : 'Không thể xoá tài liệu.');
        }
        header('Location: ' . lms_url($alias));
        exit;
    case 'tai':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lms_library_admin_download($service);
        break;
    default:
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lms_library_admin_list($service);
}

function lms_library_admin_list(\Lms\LibraryService $service)
{
    global $title, $template, $items, $total, $totalPages, $page, $filterQ, $filterType,
           $filterSubject, $subjects, $canEdit, $canDelete, $alias, $d, $user;
    $filterQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterType = isset($_GET['file_type']) ? (string) $_GET['file_type'] : '';
    $filterSubject = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 24;
    $res = $service->listFiles(array('q' => $filterQ, 'file_type' => $filterType, 'subject_id' => $filterSubject), $page, $perPage);
    $items = $res['items'];
    $total = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));
    $subjects = $d->safeFetch('select s.id, s.name, p.name as program_name from db_lms_subjects s join db_lms_programs p on p.id=s.program_id order by p.name,s.name');
    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');
    $title = 'Thư viện';
    $template = 'lms-thu-vien/hienthi';
}

function lms_library_admin_form(\Lms\LibraryService $service, $isEdit)
{
    global $title, $template, $row, $errors, $subjects, $classes, $topics, $isEditMode, $formId, $alias, $d;
    $isEditMode = (bool) $isEdit;
    $errors = array();
    $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $row = $isEdit ? $service->getFile($formId) : array();
    if ($isEdit && !$row) {
        lms_flash('error', 'Tài liệu không tồn tại.');
        header('Location: ' . lms_url($alias)); exit;
    }
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ.');
            header('Location: ' . lms_url($alias)); exit;
        }
        $data = $_POST;
        $data['is_downloadable'] = isset($data['is_downloadable']) ? 1 : 0;
        if ($isEdit) {
            $ok = $service->updateFile($formId, $data);
            $id = $formId;
        } else {
            $ok = false;
            $id = null;
            $upload = isset($_FILES['library_file']) ? $_FILES['library_file'] : array();
            if (lms_prepare_private_upload_dir('library') === null) {
                $errors = array('file' => 'Không thể chuẩn bị thư mục tài liệu riêng tư.');
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
                $data['file_url'] = $saved['paths'][0];
            $data['file_size_bytes'] = isset($upload['size']) ? (int) $upload['size'] : null;
                $data['title'] = trim((string) (isset($data['title']) ? $data['title'] : ''));
                if ($data['title'] === '') { $data['title'] = basename($upload['name']); }
                $id = $service->createFile($data, (int) $GLOBALS['user']['id']);
                $ok = $id !== null;
                if (!$ok) {
                    $orphan = _LMS_DIR . '/public/uploads/' . $saved['paths'][0];
                    if (is_file($orphan)) { @unlink($orphan); }
                }
            }
        }
        if ($ok) {
            lms_flash('success', $isEdit ? 'Đã cập nhật tài liệu.' : 'Đã tải tài liệu lên thư viện.');
            header('Location: ' . lms_url($alias)); exit;
        }
        if (!$errors) { $errors = $service->errors(); }
        $row = array_merge($row, $data);
    }
    $subjects = $d->safeFetch('select s.id, s.name, p.name as program_name from db_lms_subjects s join db_lms_programs p on p.id=s.program_id order by p.name,s.name');
    $classes = $d->safeFetch('select id, class_code, name from db_lms_classes where deleted_at is null order by created_at desc');
    $topics = $d->safeFetch('select id, name, subject_id from db_lms_topics order by subject_id, order_index, name');
    $title = $isEdit ? 'Sửa tài liệu' : 'Tải tài liệu lên';
    $template = 'lms-thu-vien/form';
}

function lms_library_admin_download(\Lms\LibraryService $service)
{
    global $alias;
    lms_prepare_private_upload_dir('library');
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $file = $service->getFile($id);
    if (!$file) { lms_flash('error', 'Tài liệu không tồn tại.'); header('Location: ' . lms_url($alias)); exit; }
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
