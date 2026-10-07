<?php
/**
 * Partial MENU - ham menu_save_items + create/delete/save menu.
 * KHONG goi truc tiep; phai include tu sources/ajax.php.
 */
if (!isset($d) || !is_object($d) || !isset($do)) { http_response_code(500); exit; }

/**
 * Lưu đệ quy menu items.
 * $allowed_types: whitelist object_type.
 */
if (!function_exists('menu_save_items')) {
    function menu_save_items($d, $items, $menu_id, $parent_id, $allowed_types = [])
    {
        if (!is_array($items) || empty($items)) {
            return;
        }

        $sort = 0;
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $object_type = isset($item['object_type']) ? (string)$item['object_type'] : 'custom';
            if (!in_array($object_type, $allowed_types, true)) {
                $object_type = 'custom';
            }

            $row = [
                'menu_id'     => (int)$menu_id,
                'parent_id'   => (int)$parent_id,
                'sort_order'  => $sort,
                'label'       => $d->clear($item['label'] ?? ''),
                'url'         => $d->clear($item['url'] ?? '#'),
                'class'       => $d->clear($item['class'] ?? ''),
                'style'       => $d->clear($item['style'] ?? 'default'),
                'block'       => $d->clear($item['block'] ?? ''),
                'target'      => $d->clear($item['target'] ?? '_self'),
                'image'       => $d->clear($item['image'] ?? ''),
                'type'        => $d->clear($item['type'] ?? ''),
                'object_id'   => (int)($item['object_id'] ?? 0),
                'object_type' => $d->clear($object_type),
                'sub_label'   => $d->clear($item['sub_label'] ?? ''),
            ];

            $d->reset();
            $d->setTable('#_menu_items');
            $new_id = $d->insert($row);

            if ($new_id && !empty($item['children']) && is_array($item['children'])) {
                menu_save_items($d, $item['children'], $menu_id, $new_id, $allowed_types);
            }

            $sort++;
        }
    }
}

if ($do == 'create_menu') {
    $name = trim($_POST['name'] ?? '');
    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Tên menu không hợp lệ']);
        exit;
    }

    $d->reset();
    $d->setTable('#_menus');
    $id = $d->insert(['name' => $d->clear($name)]);

    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Không tạo được menu']);
        exit;
    }

    echo json_encode(['status' => 'success', 'menu_id' => $id, 'message' => 'Đã tạo menu']);
    exit;
}

if ($do == 'delete_menu') {
    $menu_id = (int)($_POST['menu_id'] ?? 0);
    if ($menu_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID menu không hợp lệ']);
        exit;
    }

    // Gỡ gán vị trí trước khi xóa menu
    $d->reset();
    $d->setTable('#_menu_locations');
    $d->setWhere('menu_id', $menu_id);
    $d->update(['menu_id' => 0]);

    $d->reset();
    $d->query("DELETE FROM #_menu_items WHERE menu_id = {$menu_id}");

    $d->reset();
    $d->query("DELETE FROM #_menus WHERE id = {$menu_id}");

    echo json_encode(['status' => 'success', 'message' => 'Đã xóa menu']);
    exit;
}

if ($do == 'save_menu') {
    $json_string = isset($_POST['json_data']) ? $_POST['json_data'] : '';
    $data = json_decode($json_string, true);

    if (empty($data) || !isset($data['id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
        exit;
    }

    $menu_id = (int)$data['id'];
    if ($menu_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'ID menu không hợp lệ']);
        exit;
    }

    // Kiểm tra menu tồn tại
    $exists = $d->simple_fetch('SELECT id FROM #_menus WHERE id = ' . $menu_id);
    if (empty($exists)) {
        echo json_encode(['status' => 'error', 'message' => 'Menu không tồn tại']);
        exit;
    }

    $allowed_object_types = ['category', 'sanpham', 'tintuc', 'custom', ''];

    try {
        $menu_name = $d->clear($data['name'] ?? '');

        // 1. Tên menu
        $d->reset();
        $d->setTable('#_menus');
        $d->setWhere('id', $menu_id);
        $d->update(['name' => $menu_name]);

        // 2. Vị trí: value dạng "locationName__lang" (2 gạch dưới)
        $locations_from_js = isset($data['locations']) ? (array)$data['locations'] : [];

        $d->reset();
        $d->setTable('#_menu_locations');
        $d->setWhere('menu_id', $menu_id);
        $d->update(['menu_id' => 0]);

        foreach ($locations_from_js as $loc_string) {
            $loc_string = trim((string)$loc_string);
            if ($loc_string === '' || strpos($loc_string, '__') === false) {
                continue;
            }
            // Tách an toàn: lang = phần sau "__" cuối cùng
            $pos = strrpos($loc_string, '__');
            $location_name = substr($loc_string, 0, $pos);
            $lang_code = substr($loc_string, $pos + 2);
            if ($location_name === '' || $lang_code === '') {
                continue;
            }

            $d->reset();
            $d->setTable('#_menu_locations');
            $d->setWhere('location_name', $location_name);
            $d->setWhere('lang', $lang_code);
            $d->update(['menu_id' => $menu_id]);
        }

        // 3. Items: xóa cũ, insert lại theo cây (đơn giản, dễ hiểu)
        $d->reset();
        $d->setTable('#_menu_items');
        $d->setWhere('menu_id', $menu_id);
        $d->delete();

        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
        menu_save_items($d, $items, $menu_id, 0, $allowed_object_types);

        echo json_encode(['status' => 'success', 'message' => 'Menu đã được lưu thành công!']);
        exit;
    } catch (Throwable $e) {
        // Không trả chi tiết lỗi cho client — chỉ ghi log nội bộ
        if (function_exists('error_log')) {
            error_log('ajax save_menu: ' . $e->getMessage());
        }
        echo json_encode(['status' => 'error', 'message' => 'Lỗi máy chủ, vui lòng thử lại.']);
        exit;
    }
}