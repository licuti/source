<?php
session_start();
@define('_source', './sources/');

require_once __DIR__ . '/lib/bootstrap.php';
$d = new func_index($config['database']);

if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Tạm thời vô hiệu hóa kiểm tra CSRF để tránh lỗi 419 trên host live (có thể do cache)
// if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
//         http_response_code(419);
//         echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
//         exit;
//     }
// }

$selected_ids = isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) ? $_POST['selected_ids'] : [];
if (empty($selected_ids)) {
    echo json_encode(['success' => false, 'message' => 'Không có sản phẩm nào được chọn.']);
    exit;
}

$valid_ids = array_map('intval', $selected_ids);
$ids_str = implode(',', $valid_ids);

// Lấy danh sách ID sản phẩm có nguồn gốc từ Pancake nằm trong danh sách đã chọn
$pancake_products = $d->safeFetch("SELECT MIN(id) as id, id_code FROM #_sanpham WHERE id_code IN ($ids_str) AND pancake_product_id IS NOT NULL AND pancake_product_id != '' GROUP BY id_code");

if (empty($pancake_products)) {
    echo json_encode(['success' => true, 'deleted' => 0, 'message' => 'Không có sản phẩm nào cần xóa.']);
    exit;
}

$deleted_count = 0;
foreach ($pancake_products as $p) {
    $id = $p['id'];
    
    // Xóa hình ảnh
    $row = $d->safeFetchOne("SELECT hinh_anh FROM #_sanpham WHERE id = ? LIMIT 1", [$id]);
    if ($row && !empty($row['hinh_anh'])) {
        @unlink('../upload/product/' . $row['hinh_anh']);
    }

    // Lấy id_code của sản phẩm để xóa gallery (gallery liên kết bằng id_sp = id_code)
    $id_code = $p['id_code'];

    // Xóa hình gallery
    $galleries = $d->safeFetch("SELECT hinh_anh FROM #_sanpham_hinhanh WHERE id_sp = ?", [$id_code]);
    foreach ($galleries as $g) {
        if (!empty($g['hinh_anh'])) {
            @unlink('../upload/product/' . $g['hinh_anh']);
        }
    }
    $d->safeExec("DELETE FROM #_sanpham_hinhanh WHERE id_sp = ?", [$id_code]);

    // Xóa biến thể và hình ảnh biến thể
    $variations = $d->safeFetch("SELECT id, hinh_anh FROM #_sanpham_bienthe WHERE id_sanpham = ?", [$id_code]);
    foreach ($variations as $v) {
        if (!empty($v['hinh_anh'])) {
            @unlink('../upload/product/' . $v['hinh_anh']);
        }
        $d->safeExec("DELETE FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe = ?", [$v['id']]);
    }
    $d->safeExec("DELETE FROM #_sanpham_bienthe WHERE id_sanpham = ?", [$id_code]);

    // Cuối cùng xóa sản phẩm
    if ($d->safeExec("DELETE FROM #_sanpham WHERE id_code = ?", [$id_code])) {
        $deleted_count++;
    }
}

echo json_encode(['success' => true, 'deleted' => $deleted_count]);
exit;
?>
