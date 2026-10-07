<?php
if (!isset($_SESSION)) {
    session_set_cookie_params(['SameSite' => 'None']);
    session_start();
}
ob_start();
error_reporting(0);
header('Content-Type: application/json');

    define('_source_lib', '../lib/');
    require_once __DIR__ . '/../../admin/lib/bootstrap.php';
    $d = new func_index($config['database']);
include_once _source_lib . 'function.php';

// ============================================================
// CẤU HÌNH UPLOAD — chỉnh ở đây
// ============================================================
$UPLOAD_CONFIG = [
    'max_files'      => 10,          // Số file tối đa mỗi lần
    'max_image_mb'   => 5,           // MB tối đa / ảnh
    'max_video_mb'   => 50,          // MB tối đa / video
    'allowed_image'  => ['jpg','jpeg','png','webp','gif'],
    'allowed_video'  => ['mp4','mov','webm','avi'],
    'save_dir'       => realpath(__DIR__ . '/../../media') . '/review/',
    'url_prefix'     => 'media/review/',
];

if (!is_dir($UPLOAD_CONFIG['save_dir'])) {
    mkdir($UPLOAD_CONFIG['save_dir'], 0755, true);
}

function json_out($success, $data = []) {
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Chỉ xử lý POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, ['message' => 'Method not allowed']);
}

// Validate token session cơ bản
// if (empty($_SESSION['token'])) json_out(false, ['message' => 'Unauthorized']);

if (empty($_FILES['media'])) {
    json_out(false, ['message' => 'Không có file nào được gửi lên']);
}

$files_raw = $_FILES['media'];

// Normalise thành mảng nếu chỉ upload 1 file
if (!is_array($files_raw['name'])) {
    $files_raw = array_map(function($v) { return [$v]; }, $files_raw);
}

$count = count($files_raw['name']);
if ($count > $UPLOAD_CONFIG['max_files']) {
    json_out(false, ['message' => 'Tối đa ' . $UPLOAD_CONFIG['max_files'] . ' file mỗi lần']);
}

$saved  = [];
$errors = [];

for ($i = 0; $i < $count; $i++) {
    if ($files_raw['error'][$i] !== UPLOAD_ERR_OK) {
        $errors[] = 'File ' . ($i+1) . ': lỗi upload';
        continue;
    }

    $orig_name = $files_raw['name'][$i];
    $tmp       = $files_raw['tmp_name'][$i];
    $size      = $files_raw['size'][$i];
    $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

    // Xác định loại
    if (in_array($ext, $UPLOAD_CONFIG['allowed_image'])) {
        $type    = 'image';
        $max_bytes = $UPLOAD_CONFIG['max_image_mb'] * 1024 * 1024;
    } elseif (in_array($ext, $UPLOAD_CONFIG['allowed_video'])) {
        $type    = 'video';
        $max_bytes = $UPLOAD_CONFIG['max_video_mb'] * 1024 * 1024;
    } else {
        $errors[] = '"' . htmlspecialchars($orig_name) . '": định dạng không hỗ trợ';
        continue;
    }

    if ($size > $max_bytes) {
        $limit = $type === 'image' ? $UPLOAD_CONFIG['max_image_mb'] : $UPLOAD_CONFIG['max_video_mb'];
        $errors[] = '"' . htmlspecialchars($orig_name) . '": vượt quá ' . $limit . 'MB';
        continue;
    }

    // Tạo tên file an toàn
    $new_name = date('YmdHis') . '_' . substr(md5(uniqid('', true)), 0, 8) . '.' . $ext;
    $dest     = $UPLOAD_CONFIG['save_dir'] . $new_name;

    if (!move_uploaded_file($tmp, $dest)) {
        $errors[] = '"' . htmlspecialchars($orig_name) . '": không thể lưu file';
        continue;
    }

    $saved[] = [
        'name' => $new_name,
        'url'  => URLPATH . $UPLOAD_CONFIG['url_prefix'] . $new_name,
        'type' => $type,
    ];
}

json_out(count($saved) > 0, [
    'files'   => $saved,
    'errors'  => $errors,
    'message' => count($errors) > 0 ? implode('; ', $errors) : 'Upload thành công',
]);
