<?php
/**
 * Module Quản lý vận chuyển (admin)
 * - Cấu hình chung (#_thongtin)
 * - Mức phí theo tỉnh/huyện/xã (#_ship)
 */
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
        showdulieu();
        $template = 'quan-ly-van-chuyen/hienthi';
        break;
    case 'add':
    case 'edit':
        showdulieu();
        $template = 'quan-ly-van-chuyen/them';
        break;
    case 'save':
        luudulieu($id_module);
        break;
    case 'delete':
        xoadulieu($id_module);
        break;
    case 'delete_all':
        xoadulieu_mang($id_module);
        break;
    case 'save_settings':
        luu_settings_ship($id_module);
        break;
    default:
        $template = 'index';
}

function vc_flash($type, $message)
{
    $_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function vc_back_man()
{
    return 'index.php?p=quan-ly-van-chuyen&a=man';
}

/** Parse số tiền: bỏ dấu phân cách nghìn , */
function vc_parse_money($val)
{
    $s = preg_replace('/[^\d]/', '', (string) $val);
    return $s === '' ? 0.0 : (float) $s;
}

function showdulieu()
{
    global $d, $items, $item, $tinhs, $huyens, $xas, $shipping_settings;
    global $total_page, $limit, $total_records, $current_page, $per_page, $filter_tinh;

    $a = $_REQUEST['a'] ?? '';

    if ($a === 'add' || $a === 'edit') {
        $item = array();
        if ($a === 'edit' && !empty($_REQUEST['id'])) {
            $row = $d->simple_fetch('SELECT * FROM #_ship WHERE id = ' . (int) $_REQUEST['id']);
            $item = is_array($row) ? $row : array();
        }
        $tinhs = $d->getTinh('code, ten');
        if (!is_array($tinhs)) {
            $tinhs = array();
        }
        $huyens = array();
        $xas = array();
        if (!empty($item['id_tinh'])) {
            $huyens = $d->getHuyen($item['id_tinh'], 'code, ten');
            if (!is_array($huyens)) {
                $huyens = array();
            }
        }
        if (!empty($item['id_huyen'])) {
            $xas = $d->getXa($item['id_huyen'], 'code, ten');
            if (!is_array($xas)) {
                $xas = array();
            }
        }
        return;
    }

    // settings
    $shipping_settings = $d->simple_fetch(
        "SELECT free_ship_threshold, default_ship_phi, ship_base_weight, ship_rounding, box_phi"
        . " FROM #_thongtin WHERE lang = '" . $d->clear(LANG) . "' LIMIT 1"
    );
    if (!is_array($shipping_settings)) {
        $shipping_settings = array();
    }

    $tinhs = $d->getTinh('code, ten');
    if (!is_array($tinhs)) {
        $tinhs = array();
    }

    $where = array('1=1');
    $key = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
    $tinh = isset($_GET['tinh']) ? trim((string) $_GET['tinh']) : '';

    if ($tinh !== '') {
        $where[] = "s.id_tinh = '" . $d->clear($tinh) . "'";
        $filter_tinh = $tinh;
    } else {
        $filter_tinh = '';
    }

    if ($key !== '') {
        $k = $d->clear($key);
        $where[] = "(s.ghi_chu LIKE '%{$k}%' OR t.ten LIKE '%{$k}%' OR h.ten LIKE '%{$k}%' OR x.ten LIKE '%{$k}%')";
    }

    $whereSql = implode(' AND ', $where);
    $limit  = 14;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $from = ' FROM #_ship s'
        . ' LEFT JOIN #_thanhpho t ON s.id_tinh = t.code'
        . " LEFT JOIN #_huyen h ON (s.id_huyen = h.code AND s.id_tinh = h.code_tinh AND s.id_huyen <> '' AND s.id_huyen IS NOT NULL)"
        . " LEFT JOIN #_xa x ON (s.id_xa = x.code AND s.id_huyen = x.code_huyen AND s.id_xa <> '' AND s.id_xa IS NOT NULL)"
        . ' WHERE ' . $whereSql;

    $items = $d->o_fet(
        'SELECT s.*, t.ten AS ten_tinh, h.ten AS ten_huyen, x.ten AS ten_xa'
        . $from
        . ' ORDER BY s.so_thu_tu ASC, s.id DESC'
        . ' LIMIT ' . (int) $offset . ', ' . (int) $limit
    );
    if (!is_array($items)) {
        $items = array();
    }

    $countRow = $d->simple_fetch('SELECT COUNT(*) AS c' . $from);
    $total_records = (int) ($countRow['c'] ?? 0);
    $total_page    = (int) ceil($total_records / max(1, $limit));
    $current_page  = $page;
    $per_page      = $limit;
}
function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        vc_flash('error', 'Không có quyền chỉnh sửa.');
        $d->redirect(vc_back_man());
        return;
    }

    $id = (int) ($_REQUEST['id'] ?? 0);
    $id_tinh  = trim((string) ($_POST['id_tinh'] ?? ''));
    $id_huyen = trim((string) ($_POST['id_huyen'] ?? ''));
    $id_xa    = trim((string) ($_POST['id_xa'] ?? ''));

    $backForm = $id > 0
        ? 'index.php?p=quan-ly-van-chuyen&a=edit&id=' . $id
        : 'index.php?p=quan-ly-van-chuyen&a=add';

    if ($id_tinh === '') {
        vc_flash('error', 'Vui lòng chọn Tỉnh / Thành phố.');
        $d->redirect($backForm);
        return;
    }

    $data = array(
        'id_tinh'          => $d->clear($id_tinh),
        'id_huyen'         => $d->clear($id_huyen),
        'id_xa'            => $d->clear($id_xa),
        'phi_ship'         => vc_parse_money($_POST['phi_ship'] ?? 0),
        'phi_extra_kg'     => vc_parse_money($_POST['phi_extra_kg'] ?? 0),
        'free_ship_weight' => (float) ($_POST['free_ship_weight'] ?? 0),
        'ghi_chu'          => $d->clear(trim((string) ($_POST['ghi_chu'] ?? ''))),
        'hien_thi'         => isset($_POST['hien_thi']) ? 1 : 0,
        'so_thu_tu'        => (int) ($_POST['so_thu_tu'] ?? 0),
    );

    if ($data['phi_ship'] < 0) {
        vc_flash('error', 'Phí vận chuyển không hợp lệ.');
        $d->redirect($backForm);
        return;
    }

    // Cảnh báo trùng vùng (không chặn cứng nếu huyện/xã rỗng có chủ đích)
    $dupSql = "SELECT id FROM #_ship WHERE id_tinh = '" . $d->clear($id_tinh) . "'"
        . " AND IFNULL(id_huyen,'') = '" . $d->clear($id_huyen) . "'"
        . " AND IFNULL(id_xa,'') = '" . $d->clear($id_xa) . "'";
    if ($id > 0) {
        $dupSql .= ' AND id <> ' . $id;
    }
    $dup = $d->simple_fetch($dupSql);
    if (!empty($dup)) {
        vc_flash('error', 'Đã có mức phí cho địa bàn này. Hãy sửa bản ghi hiện có.');
        $d->redirect($backForm);
        return;
    }

    $capnhat = $_POST['capnhat'] ?? 'capnhat';
    $d->reset();
    $d->setTable('#_ship');

    if ($id > 0) {
        $d->setWhere('id', $id);
        if ($d->update($data)) {
            vc_flash('success', 'Đã cập nhật mức phí vận chuyển.');
            $d->redirect($capnhat === 'capnhat_sua' ? $backForm : vc_back_man());
        } else {
            vc_flash('error', 'Cập nhật thất bại.');
            $d->redirect($backForm);
        }
        return;
    }

    $newId = $d->insert($data);
    if ($newId) {
        vc_flash('success', 'Đã thêm mức phí vận chuyển.');
        $d->redirect($capnhat === 'capnhat_sua'
            ? 'index.php?p=quan-ly-van-chuyen&a=edit&id=' . (int) $newId
            : vc_back_man());
    } else {
        vc_flash('error', 'Thêm mới thất bại.');
        $d->redirect($backForm);
    }
}

function xoadulieu($id_module)
{
    global $d;
    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        vc_flash('error', 'Không có quyền xóa.');
        $d->redirect(vc_back_man());
        return;
    }
    $id = (int) $_GET['id'];
    if ($id <= 0) {
        $d->redirect(vc_back_man());
        return;
    }
    $d->reset();
    $d->setTable('#_ship');
    $d->setWhere('id', $id);
    $d->delete();
    vc_flash('success', 'Đã xóa mức phí.');
    $d->redirect(vc_back_man());
}

function xoadulieu_mang($id_module)
{
    global $d;
    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        vc_flash('error', 'Không có quyền xóa hoặc chưa chọn mục.');
        $d->redirect(vc_back_man());
        return;
    }
    $ids = array_values(array_filter(array_map('intval', (array) $_POST['chk_child'])));
    if (empty($ids)) {
        $d->redirect(vc_back_man());
        return;
    }
    $d->o_que('DELETE FROM #_ship WHERE id IN (' . implode(',', $ids) . ')');
    vc_flash('success', 'Đã xóa ' . count($ids) . ' mức phí.');
    $d->redirect(vc_back_man());
}

function luu_settings_ship($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        vc_flash('error', 'Không có quyền chỉnh sửa.');
        $d->redirect(vc_back_man());
        return;
    }

    $data = array(
        'free_ship_threshold' => vc_parse_money($_POST['free_ship_threshold'] ?? 0),
        'default_ship_phi'    => vc_parse_money($_POST['default_ship_phi'] ?? 0),
        'ship_base_weight'    => max(0, (float) ($_POST['ship_base_weight'] ?? 0)),
        'ship_rounding'       => ((int) ($_POST['ship_rounding'] ?? 0) === 1) ? 1 : 0,
        'box_phi'             => vc_parse_money($_POST['box_phi'] ?? 0),
    );

    $d->reset();
    $d->setTable('#_thongtin');
    $d->setWhere('lang', LANG);
    if ($d->update($data)) {
        vc_flash('success', 'Đã cập nhật cấu hình vận chuyển.');
    } else {
        vc_flash('error', 'Cập nhật cấu hình thất bại.');
    }
    $d->redirect(vc_back_man());
}
