<?php
/**
 * Module Bình luận - đánh giá (admin)
 * - Bảng chung: sản phẩm (id_sanpham) + tin tức (id_tin)
 * - Lọc loại / tìm kiếm / phân trang server-side
 * - Bật/tắt hiển thị (trang_thai), xem chi tiết, xóa
 */
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
        showdulieu();
        $template = @$_REQUEST['p'] . '/hienthi';
        break;
    case 'delete':
        xoadulieu($id_module);
        break;
    case 'delete_all':
        xoadulieu_mang($id_module);
        break;
    default:
        $template = 'index';
}

/** Danh sách + tìm + lọc loại + phân trang. */
function showdulieu()
{
    global $d, $items, $total_page, $limit, $total_records, $current_page, $per_page, $filter_type;

    $where = array('1=1');
    $col   = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $key   = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
    $type  = isset($_GET['type']) ? trim((string) $_GET['type']) : '';
    if (!in_array($type, array('sanpham', 'tintuc', ''), true)) {
        $type = '';
    }
    $filter_type = $type;

    if ($type === 'sanpham') {
        $where[] = 'id_sanpham > 0';
    } elseif ($type === 'tintuc') {
        $where[] = 'id_tin > 0';
    }

    $allowed = array('ho_ten', 'email', 'dien_thoai', 'noi_dung', 'tieu_de');
    if ($key !== '') {
        $keySql = $d->clear($key);
        if ($col !== '' && in_array($col, $allowed, true)) {
            $where[] = $col . " LIKE '%" . $keySql . "%'";
        } else {
            $where[] = "(ho_ten LIKE '%" . $keySql . "%' OR email LIKE '%" . $keySql . "%'"
                . " OR dien_thoai LIKE '%" . $keySql . "%' OR noi_dung LIKE '%" . $keySql . "%'"
                . " OR tieu_de LIKE '%" . $keySql . "%')";
        }
    }

    $whereSql = implode(' AND ', $where);

    $limit  = 14;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $base = 'SELECT * FROM #_binhluan WHERE ' . $whereSql . ' ORDER BY id DESC';

    $items = $d->o_fet($base . ' LIMIT ' . (int) $offset . ', ' . (int) $limit);
    if (!is_array($items)) {
        $items = array();
    }

    $countRow = $d->simple_fetch('SELECT COUNT(*) AS c FROM #_binhluan WHERE ' . $whereSql);
    $total_records = (int) ($countRow['c'] ?? 0);
    $total_page    = (int) ceil($total_records / max(1, $limit));
    $current_page  = $page;
    $per_page      = $limit;
}

function xoadulieu($id_module)
{
    global $d;
    $back = 'index.php?p=binh-luan&a=man';
    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        $d->redirect($back);
        return;
    }
    $id = (int) $_GET['id'];
    if ($id <= 0) {
        $d->redirect($back);
        return;
    }
    $d->reset();
    $d->setTable('#_binhluan');
    $d->setWhere('id', $id);
    $d->delete();
    $d->redirect($back);
}

function xoadulieu_mang($id_module)
{
    global $d;
    $back = 'index.php?p=binh-luan&a=man';
    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        $d->redirect($back);
        return;
    }
    $ids = array_values(array_filter(array_map('intval', (array) $_POST['chk_child'])));
    if (empty($ids)) {
        $d->redirect($back);
        return;
    }
    $d->o_que('DELETE FROM #_binhluan WHERE id IN (' . implode(',', $ids) . ')');
    $d->redirect($back);
}
