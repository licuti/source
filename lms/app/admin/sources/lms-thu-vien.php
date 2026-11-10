<?php
/**
 * Admin LMS — Quản lý thư viện tài liệu (ADM-12 / TCH-07).
 * Đối chiếu mockup:
 *  - Tai-lieu-phat-trien/admin/Quản lý thư viện.png
 *  - Tai-lieu-phat-trien/admin/Quản lý thư viện - Thêm.png
 * PHP 7.3 compatible.
 */
if (!defined('_source')) { die('Error'); }

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-thu-vien';
$service = new \Lms\LibraryService($d);
$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lms_library_admin_add($service, $alias);
        break;

    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lms_library_admin_edit($service, $alias);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        lms_library_admin_delete($service, $alias);
        break;

    case 'tai':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lms_library_admin_download($service, $alias);
        break;

    case 'chi-tiet':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lms_library_admin_ajax_detail($service);
        break;

    default:
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lms_library_admin_list($service, $d, $alias, $user);
        break;
}

/**
 * Hiển thị danh sách tài liệu dạng lưới Card Grid
 */
function lms_library_admin_list(\Lms\LibraryService $service, LmsDb $d, $alias, $user)
{
    global $title, $template, $items, $total, $totalPages, $page, $filterQ, $canEdit, $canDelete;

    $filterQ = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 24;

    $res = $service->listFiles(array('q' => $filterQ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý thư viện';
    $template = 'lms-thu-vien/hienthi';
}

/**
 * Thêm tài liệu mới (Từ Modal Popup)
 */
function lms_library_admin_add(\Lms\LibraryService $service, $alias)
{
    global $user;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias));
        exit;
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $fileLink       = trim((string) (isset($_POST['file_link']) ? $_POST['file_link'] : ''));
    $title          = trim((string) (isset($_POST['title']) ? $_POST['title'] : ''));
    $categoryPrefix = lms_enum_normalize('library_categories', isset($_POST['category_prefix']) ? $_POST['category_prefix'] : 'none', 'none');
    $isDownloadable = isset($_POST['is_downloadable']) ? ((string) $_POST['is_downloadable'] === '0' ? 0 : 1) : 0;

    $finalFileUrl  = '';
    $finalFileSize = null;
    $hasUpload     = isset($_FILES['library_file']) && !empty($_FILES['library_file']['name']);

    if ($hasUpload) {
        if (lms_prepare_private_upload_dir('library') === null) {
            lms_flash('error', 'Không thể khởi tạo thư mục lưu trữ tài liệu.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $saved = lms_save_files($_FILES['library_file'], 'library/' . date('Ym'), array(
            'maxFiles'      => 1,
            'maxSize'       => \Lms\LibraryService::MAX_FILE_SIZE,
            'mimeWhitelist' => \Lms\LibraryService::ALLOWED_EXTENSIONS,
        ));

        if (!$saved['ok'] || empty($saved['paths'])) {
            lms_flash('error', !empty($saved['error']) ? $saved['error'] : 'Lỗi khi tải file lên máy chủ.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $finalFileUrl  = $saved['paths'][0];
        $finalFileSize = isset($_FILES['library_file']['size']) ? (int) $_FILES['library_file']['size'] : null;

        if ($title === '') {
            $title = pathinfo($_FILES['library_file']['name'], PATHINFO_FILENAME);
        }
    } elseif ($fileLink !== '') {
        $finalFileUrl = $fileLink;
        if ($title === '') {
            $parsedHost = parse_url($fileLink, PHP_URL_HOST);
            $parsedPath = parse_url($fileLink, PHP_URL_PATH);
            $title = $parsedPath ? basename($parsedPath) : ($parsedHost ? $parsedHost : 'Tài liệu liên kết');
        }
    } else {
        lms_flash('error', 'Vui lòng nhập liên kết tài liệu hoặc chọn file tải lên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    // Chuẩn hoá tiền tố phân loại
    $cleanTitle = trim((string) preg_replace('/^(\[Sách\]|\[Review\]|\[Test\]|\[Nội bộ\])\s*/u', '', $title));
    if ($cleanTitle === '') {
        $cleanTitle = $title;
    }
    $finalTitle = ($categoryPrefix !== '' && $categoryPrefix !== 'none')
        ? ($categoryPrefix . ' ' . $cleanTitle)
        : $cleanTitle;

    $detectedType = \Lms\LibraryService::detectFileType($finalFileUrl, $categoryPrefix);

    $fileId = $service->createFile(array(
        'file_url'        => $finalFileUrl,
        'title'           => $finalTitle,
        'file_type'       => $detectedType,
        'file_size_bytes' => $finalFileSize,
        'is_downloadable' => $isDownloadable,
    ), (int) $user['id']);

    if ($fileId) {
        lms_flash('success', 'Đã thêm tài liệu mới vào thư viện thành công.');
    } else {
        $errors = $service->errors();
        lms_flash('error', $errors ? reset($errors) : 'Không thể lưu tài liệu. Vui lòng thử lại.');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

/**
 * Cập nhật tài liệu (Từ Modal Sửa)
 */
function lms_library_admin_edit(\Lms\LibraryService $service, $alias)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location: ' . lms_url($alias));
        exit;
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $existing = $service->getFile($id);
    if (!$existing) {
        lms_flash('error', 'Tài liệu không tồn tại hoặc đã bị xoá.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $title          = trim((string) (isset($_POST['title']) ? $_POST['title'] : ''));
    $categoryPrefix = lms_enum_normalize('library_categories', isset($_POST['category_prefix']) ? $_POST['category_prefix'] : 'none', 'none');
    $fileLink       = trim((string) (isset($_POST['file_link']) ? $_POST['file_link'] : ''));
    $isDownloadable = isset($_POST['is_downloadable']) ? ((string) $_POST['is_downloadable'] === '0' ? 0 : 1) : 0;

    if ($title === '') {
        $title = (string) $existing['title'];
    }

    $cleanTitle = trim((string) preg_replace('/^(\[Sách\]|\[Review\]|\[Test\]|\[Nội bộ\])\s*/u', '', $title));
    if ($cleanTitle === '') {
        $cleanTitle = $title;
    }
    $finalTitle = ($categoryPrefix !== '' && $categoryPrefix !== 'none')
        ? ($categoryPrefix . ' ' . $cleanTitle)
        : $cleanTitle;

    $finalFileUrl  = $fileLink !== '' ? $fileLink : (string) $existing['file_url'];
    $finalFileSize = $existing['file_size_bytes'] !== null ? (int) $existing['file_size_bytes'] : null;

    if (isset($_FILES['library_file']) && !empty($_FILES['library_file']['name'])) {
        if (lms_prepare_private_upload_dir('library') === null) {
            lms_flash('error', 'Không thể khởi tạo thư mục lưu trữ tài liệu.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $saved = lms_save_files($_FILES['library_file'], 'library/' . date('Ym'), array(
            'maxFiles'      => 1,
            'maxSize'       => \Lms\LibraryService::MAX_FILE_SIZE,
            'mimeWhitelist' => \Lms\LibraryService::ALLOWED_EXTENSIONS,
        ));
        if (!$saved['ok'] || empty($saved['paths'])) {
            lms_flash('error', !empty($saved['error']) ? $saved['error'] : 'Lỗi khi tải file mới lên.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $finalFileUrl  = $saved['paths'][0];
        $finalFileSize = isset($_FILES['library_file']['size']) ? (int) $_FILES['library_file']['size'] : null;
    }

    $updateData = array(
        'title'           => $finalTitle,
        'file_url'        => $finalFileUrl,
        'file_size_bytes' => $finalFileSize,
        'file_type'       => \Lms\LibraryService::detectFileType($finalFileUrl, $categoryPrefix),
        'is_downloadable' => $isDownloadable,
    );

    if ($service->updateFile($id, $updateData)) {
        lms_flash('success', 'Đã cập nhật tài liệu thành công.');
    } else {
        $errors = $service->errors();
        lms_flash('error', $errors ? reset($errors) : 'Không thể cập nhật tài liệu.');
    }

    header('Location: ' . lms_url($alias));
    exit;
}

/**
 * Xoá tài liệu khỏi thư viện
 */
function lms_library_admin_delete(\Lms\LibraryService $service, $alias)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !lms_csrf_verify()) {
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
}

/**
 * Tải file hoặc mở link liên kết
 */
function lms_library_admin_download(\Lms\LibraryService $service, $alias)
{
    lms_prepare_private_upload_dir('library');
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $file = $service->getFile($id);

    if (!$file) {
        lms_flash('error', 'Tài liệu không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $fileUrl = (string) $file['file_url'];

    // Nếu là liên kết ngoài (URL) -> Tăng lượt tải và Redirect trực tiếp
    if (preg_match('#^https?://#i', $fileUrl)) {
        $service->incrementDownload($id);
        header('Location: ' . $fileUrl);
        exit;
    }

    // Nếu là file tải lên cục bộ
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

/**
 * API trả về JSON chi tiết file để mở Modal Sửa
 */
function lms_library_admin_ajax_detail(\Lms\LibraryService $service)
{
    header('Content-Type: application/json; charset=UTF-8');
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $file = $service->getFile($id);

    if (!$file) {
        echo json_encode(array('success' => false, 'message' => 'Tài liệu không tồn tại.'), JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Phân tích tiền tố từ title
    $categoryPrefix = 'none';
    $title = (string) $file['title'];
    if (preg_match('/^(\[Sách\]|\[Review\]|\[Test\]|\[Nội bộ\])/u', $title, $m)) {
        $categoryPrefix = $m[1];
        $title = trim((string) preg_replace('/^(\[Sách\]|\[Review\]|\[Test\]|\[Nội bộ\])\s*/u', '', $title));
    }

    echo json_encode(array(
        'success'         => true,
        'id'              => (int) $file['id'],
        'title'           => $title,
        'category_prefix' => $categoryPrefix,
        'file_url'        => (string) $file['file_url'],
        'file_type'       => (string) $file['file_type'],
        'is_downloadable' => (int) $file['is_downloadable'],
    ), JSON_UNESCAPED_UNICODE);
    exit;
}
