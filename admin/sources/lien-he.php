<?php
/**
 * Module Liên hệ (admin)
 * - Danh sách tin nhắn form liên hệ (phân trang server-side)
 * - Xem chi tiết (AJAX) → đánh dấu đã xem
 * - Xóa 1 / xóa nhiều
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

/** Danh sách + tìm kiếm + phân trang. */
function showdulieu()
{
    global $d, $items, $total_page, $limit, $total_records, $current_page, $per_page, $unread_total;

    $where = array('1=1');
    $col   = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $key   = isset($_GET['key']) ? trim((string) $_GET['key']) : '';

    $allowed = array('ho_ten', 'email', 'so_dien_thoai', 'tieu_de', 'noi_dung', 'dia_chi');
    if ($key !== '') {
        $keySql = $d->clear($key);
        if ($col !== '' && in_array($col, $allowed, true)) {
            $where[] = $col . " LIKE '%" . $keySql . "%'";
        } else {
            $where[] = "(ho_ten LIKE '%" . $keySql . "%' OR email LIKE '%" . $keySql . "%'"
                . " OR so_dien_thoai LIKE '%" . $keySql . "%' OR tieu_de LIKE '%" . $keySql . "%'"
                . " OR noi_dung LIKE '%" . $keySql . "%' OR dia_chi LIKE '%" . $keySql . "%')";
        }
    }

    $whereSql = implode(' AND ', $where);

    // Số chưa xem (toàn bộ, không theo trang)
    $rowUnread = $d->simple_fetch("SELECT COUNT(*) AS c FROM #_lienhe WHERE da_xem = 0");
    $unread_total = (int) ($rowUnread['c'] ?? 0);

    $limit  = 10;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $base = 'SELECT * FROM #_lienhe WHERE ' . $whereSql
        . ' ORDER BY da_xem ASC, id DESC';

    $items = $d->o_fet($base . ' LIMIT ' . (int) $offset . ', ' . (int) $limit);
    if (!is_array($items)) {
        $items = array();
    }

    $countRow = $d->simple_fetch(
        'SELECT COUNT(*) AS c FROM #_lienhe WHERE ' . $whereSql
    );
    $total_records = (int) ($countRow['c'] ?? 0);
    $total_page    = (int) ceil($total_records / max(1, $limit));
    $current_page  = $page;
    $per_page      = $limit;
}

/** Xóa 1 liên hệ (?id=). */
function xoadulieu($id_module)
{
    global $d;

    $back = 'index.php?p=lien-he&a=man';
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
    $d->setTable('#_lienhe');
    $d->setWhere('id', $id);
    $d->delete();
    $d->redirect($back);
}

/** Xóa nhiều (chk_child[]). */
function xoadulieu_mang($id_module)
{
    global $d;

    $back = 'index.php?p=lien-he&a=man';
    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        $d->redirect($back);
        return;
    }

    $ids = array_values(array_filter(array_map('intval', (array) $_POST['chk_child'])));
    if (empty($ids)) {
        $d->redirect($back);
        return;
    }

    $chuoi = implode(',', $ids);
    $d->o_que('DELETE FROM #_lienhe WHERE id IN (' . $chuoi . ')');
    $d->redirect($back);
}
