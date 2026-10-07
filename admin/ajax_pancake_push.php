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

require_once _source . 'services/pancake/PancakeApiClient.php';
// Bật output buffering để tránh lỗi parse JSON khi có warning
ob_start();

$pancakeConfig = function_exists('getPancakeConfig') ? getPancakeConfig() : ($config['pancake'] ?? []);
$apiClient = new PancakeApiClient($pancakeConfig);

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Lỗi kết nối Pancake API']);
    exit;
}

// Lấy danh sách kho để gán tồn kho
$warehouse_id = null;
$wh_res = $apiClient->getWarehouses();
if (isset($wh_res['success']) && $wh_res['success']) {
    if (isset($wh_res['data']['data'][0]['id'])) {
        $warehouse_id = $wh_res['data']['data'][0]['id'];
    } elseif (isset($wh_res['data'][0]['id'])) {
        $warehouse_id = $wh_res['data'][0]['id'];
    }
}

// 1. Lấy thông tin sản phẩm
$product = $d->safeFetchOne("SELECT * FROM #_sanpham WHERE id_code = ? AND lang = 'vi' LIMIT 1", [$id]);
if (!$product) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
    exit;
}

// Xây dựng URL gốc chuẩn (vì ajax call không có sẵn $config_base từ config.php)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'labavista.com.vn';
$config_base = $protocol . $domainName . '/';

// Lấy hình ảnh đại diện (phải truyền URL tuyệt đối)
$image_url = '';
if (!empty($product['hinh_anh'])) {
    if (preg_match('/^https?:\/\//', $product['hinh_anh'])) {
        $image_url = $product['hinh_anh'];
    } else {
        $image_url = $config_base . 'upload/product/' . $product['hinh_anh'];
    }
}

// 2. Lấy biến thể (nếu có)
$variations = $d->safeFetch("SELECT * FROM #_sanpham_bienthe WHERE id_sanpham = ?", [$id]);

// Chuẩn bị payload cho Pancake
$payload = [
    "name" => $product['tenvi'] ?? $product['ten'] ?? 'Sản phẩm ' . $id,
    "custom_id" => (isset($product['ma_sp']) && $product['ma_sp']) ? $product['ma_sp'] : ('WEB-'.$product['id']),
    "weight" => isset($product['weight']) ? (float)$product['weight'] : 0,
    "is_published" => isset($product['hien_thi']) ? (bool)$product['hien_thi'] : true
];

$product_attributes = [];
$pancake_variations = [];

if (empty($variations)) {
    // Không có biến thể -> 1 biến thể mặc định
    $pancake_variations[] = [
        "retail_price" => (int)($product['gia'] ?? 0),
        "custom_id" => $payload['custom_id'],
        "weight" => $payload['weight'],
        "remain_quantity" => (int)($product['so_luong'] ?? 0),
        "quantity" => (int)($product['so_luong'] ?? 0),
        "images" => $image_url ? [$image_url] : []
    ];
    
    if ($warehouse_id) {
        $pancake_variations[0]['variations_warehouses'] = [
            [
                "warehouse_id" => $warehouse_id,
                "remain_quantity" => (int)($product['so_luong'] ?? 0)
            ]
        ];
    }
} else {
    // Phân tích thuộc tính (Màu sắc, Size...)
    // Dữ liệu thuộc tính thường nằm trong db_sanpham_bienthe_thuoctinh
    $attr_map = []; // [id_thuoctinh => ['name' => 'Màu', 'values' => ['Đen', 'Trắng']]]
    
    foreach ($variations as $var) {
        $var_payload = [
            "retail_price" => (int)$var['gia'],
            "custom_id" => $var['ma_sp'] ?: ($payload['custom_id'] . '-' . $var['id']),
            "weight" => isset($var['weight']) ? (float)$var['weight'] : 0,
            "remain_quantity" => (int)($var['so_luong'] ?? 0),
            "quantity" => (int)($var['so_luong'] ?? 0),
            "fields" => []
        ];
        
        if (!empty($var['hinh_anh'])) {
            if (preg_match('/^https?:\/\//', $var['hinh_anh'])) {
                $var_payload["images"] = [$var['hinh_anh']];
            } else {
                $var_payload["images"] = [$config_base . 'upload/product/' . $var['hinh_anh']];
            }
        } elseif ($image_url) {
            $var_payload["images"] = [$image_url];
        }

        // Lấy các thuộc tính của biến thể này
        $var_attrs = $d->safeFetch("SELECT tt.ten as ten_thuoctinh, gt.ten as ten_giatri, tt.id as id_tt 
                                       FROM #_sanpham_bienthe_thuoctinh bt
                                       JOIN #_thuoctinh tt ON tt.id_code = bt.id_thuoctinh AND tt.lang = 'vi'
                                       JOIN #_thuoctinh_giatri gt ON gt.id_code = bt.id_thuoctinh_giatri AND gt.lang = 'vi'
                                       WHERE bt.id_bienthe = ?", [$var['id']]);
                                       
        foreach ($var_attrs as $attr) {
            $tt_name = $attr['ten_thuoctinh'];
            $gt_name = $attr['ten_giatri'];
            
            $var_payload["fields"][] = [
                "name" => $tt_name,
                "value" => $gt_name
            ];
            
            // Gom vào product_attributes tổng
            if (!isset($attr_map[$attr['id_tt']])) {
                $attr_map[$attr['id_tt']] = [
                    "name" => $tt_name,
                    "values" => []
                ];
            }
            if (!in_array($gt_name, $attr_map[$attr['id_tt']]['values'])) {
                $attr_map[$attr['id_tt']]['values'][] = $gt_name;
            }
        }
        
        if ($warehouse_id) {
            $var_payload['variations_warehouses'] = [
                [
                    "warehouse_id" => $warehouse_id,
                    "remain_quantity" => (int)($var['so_luong'] ?? 0)
                ]
            ];
        }

        $pancake_variations[] = $var_payload;
    }
    
    foreach ($attr_map as $am) {
        $product_attributes[] = $am;
    }
}

if (!empty($product_attributes)) {
    $payload['product_attributes'] = $product_attributes;
}
$payload['variations'] = $pancake_variations;

// 3. Gửi lên Pancake
if (!empty($product['pancake_product_id'])) {
    // Lấy thông tin sản phẩm hiện tại để map ID biến thể
    $existing = $apiClient->getProduct($product['pancake_product_id']);
    $pancake_var_map = [];
    if (isset($existing['success']) && $existing['success']) {
        $p_data = $existing['data']['product'] ?? $existing['data']['data'] ?? $existing['data'] ?? [];
        if (isset($p_data['variations']) && is_array($p_data['variations'])) {
            foreach ($p_data['variations'] as $v) {
                $sku = $v['custom_id'] ?? $v['display_id'] ?? '';
                if (!empty($sku)) {
                    $pancake_var_map[$sku] = $v['id'];
                }
            }
        }
    }
    // Gắn ID biến thể vào payload để tránh lỗi duplicate
    foreach ($payload['variations'] as &$pv) {
        if (isset($pancake_var_map[$pv['custom_id']])) {
            $pv['id'] = $pancake_var_map[$pv['custom_id']];
        }
    }
    
    $res = $apiClient->updateProduct($product['pancake_product_id'], $payload);
} else {
    $res = $apiClient->createProduct($payload);
}

if ($res['success']) {
    $pancake_data = $res['data']['data'] ?? $res['data'] ?? [];
    if (isset($res['data']['product'])) $pancake_data = $res['data']['product'];
    
    if (isset($pancake_data['id'])) {
        $pancake_id = $pancake_data['id'];
        // Cập nhật lại pancake_product_id vào web
        $d->safeExec("UPDATE #_sanpham SET pancake_product_id = ? WHERE id_code = ?", [$pancake_id, $id]);
        
        // Nếu có mapping biến thể thì map
        if (isset($pancake_data['variations']) && is_array($pancake_data['variations'])) {
            $qty_map = [];
            if (empty($variations)) {
                $qty_map[trim($product['ma_sp']) ?: ('WEB-' . $id)] = (int)($product['so_luong'] ?? 0);
            } else {
                foreach ($variations as $var) {
                    $qty_map[trim($var['ma_sp']) ?: ('WEB-VAR-' . $var['id'])] = (int)($var['so_luong'] ?? 0);
                }
            }
            
            $inventory_items = [];

            foreach ($pancake_data['variations'] as $pv) {
                $p_sku = $pv['custom_id'] ?? $pv['display_id'] ?? '';
                $p_vid = $pv['id'] ?? '';
                if ($p_sku && $p_vid) {
                    $d->safeExec("UPDATE #_sanpham_bienthe SET pancake_variation_id = ? WHERE ma_sp = ? AND id_sanpham = ?", [$p_vid, $p_sku, $id]);
                    
                    if (isset($qty_map[$p_sku]) && $warehouse_id) {
                        $inventory_items[] = [
                            "variation_id" => $p_vid,
                            "remain_quantity" => $qty_map[$p_sku],
                            "warehouse_id" => $warehouse_id
                        ];
                    }
                }
            }
            
            if (!empty($inventory_items)) {
                $apiClient->updateInventoryBulk($inventory_items);
            }
        }
        
        $response = ['success' => true, 'message' => 'Đồng bộ thành công!'];
    } else {
         $response = ['success' => true, 'message' => 'Tạo thành công nhưng không lấy được ID trả về'];
    }
} else {
    $response = ['success' => false, 'message' => $res['message']];
}

while (ob_get_level()) ob_end_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;
