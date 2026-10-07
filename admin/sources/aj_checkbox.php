<?php
/**
 * Endpoint AJAX: bật/tắt công tắc trạng thái và xoá ảnh.
 *
 * Tên bảng và tên cột đến từ người dùng nên KHÔNG thể bind tham số
 * (SQL không cho bind identifier). Vì vậy phải đối chiếu whitelist —
 * danh sách dưới đây lấy từ toàn bộ lời gọi thực tế trong admin.
 */

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../lib/bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

// Chỉ người đã đăng nhập mới được gọi
if (empty($_SESSION['user_hash']) || empty($_SESSION['id_user'])) {
    http_response_code(403);
    echo '0';
    exit;
}

// CSRF cho mọi POST (mutation)
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
        http_response_code(419);
        echo '0';
        exit;
    }
}

$ALLOWED_TABLES = array(
    '#_album', '#_binhluan', '#_category', '#_category_noidung', '#_content',
    '#_filter', '#_filter_content', '#_flash_sale', '#_module_admin',
    '#_khuyenmai', '#_ship', '#_sanpham', '#_sanpham_ctv', '#_thanhvien', '#_tintuc', '#_user', '#_video',
);

$ALLOWED_COLUMNS = array(
    'background', 'content', 'gia_goc', 'hien_thi', 'hinh_anh', 'noi_bat',
    'so_thu_tu', 'sp_hot', 'sp_moi', 'sp_sale', 'sp_top', 'tieu_bieu',
    'trang_thai', 'video',
);

/*
 * Các bảng dùng khoá 'id'; còn lại dùng 'id_code'.
 * Đã đối chiếu với schema thật (xem _backup_ui/check-keys.php).
 * Lưu ý: #_sanpham_ctv KHÔNG có cột id_code — bản cũ dùng id_code nên
 * câu update luôn lỗi mà không báo gì.
 */
$TABLES_KEYED_BY_ID = array(
    '#_module_admin', '#_thanhvien', '#_flash_sale', '#_binhluan', '#_khuyenmai', '#_ship',
    '#_category_noidung', '#_user', '#_sanpham_ctv',
);

/** Chuẩn hoá tên bảng về dạng '#_xxx' (một số nơi gọi '_xxx'). */
function normalizeTable($name)
{
    $name = (string) $name;
    if ($name === '') {
        return '';
    }
    if (strpos($name, '#_') === 0) {
        return $name;
    }
    return '#' . ltrim($name, '#');
}

$d  = new func_index($config['database']);
$do = isset($_REQUEST['do']) ? $_REQUEST['do'] : '';

$table = normalizeTable(isset($_POST['bang']) ? $_POST['bang'] : '');
$col   = isset($_POST['cot']) ? $_POST['cot'] : '';
$id    = isset($_POST['id']) ? (int) $_POST['id'] : 0;

if (!in_array($table, $ALLOWED_TABLES, true) || !in_array($col, $ALLOWED_COLUMNS, true) || $id <= 0) {
    http_response_code(400);
    echo '0';
    exit;
}

$key = in_array($table, $TABLES_KEYED_BY_ID, true) ? 'id' : 'id_code';

if ($do === 'checkbox') {
    $value = (int) (!empty($_POST['trangthai']));

    echo $d->safeExec("update $table set $col = ? where $key = ?", array($value, $id)) ? '1' : '0';

} elseif ($do === 'xoaimg') {
    $row = $d->safeFetchOne("select $col from $table where $key = ?", array($id));

    if (empty($row[$col])) {
        echo '0';
        $d->disconnect();
        exit;
    }

    // basename() chặn path traversal qua tên file lưu trong DB
    $file   = basename($row[$col]);
    $folder = isset($_POST['folder']) ? basename($_POST['folder']) : '';

    if ($folder !== '') {
        @unlink('../../uploads/images/' . $folder . '/' . $file);
        echo '1';
    } else {
        foreach (array('', 'largethumb/', 'smallthumb/', 'thumb/') as $sub) {
            @unlink('../../uploads/images/' . $sub . $file);
        }
        echo $d->safeExec("update $table set $col = '' where $key = ?", array($id)) ? '1' : '0';
    }

} else {
    http_response_code(400);
    echo '0';
}

$d->disconnect();
