<?php
/**
 * Module Mã khuyến mãi (admin)
 * - CRUD #_khuyenmai + bật/tắt hien_thi
 * - Lọc loại / trạng thái hiệu lực
 * - Lịch sử dùng #_khuyenmai_ls
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
    case 'add':
    case 'edit':
        showdulieu();
        $template = @$_REQUEST['p'] . '/them';
        break;
    case 'history':
        show_history();
        $template = @$_REQUEST['p'] . '/lichsu';
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
    default:
        $template = 'index';
}

function km_flash($type, $message)
{
    $_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function km_back_man($extra = '')
{
    return 'index.php?p=ma-khuyen-mai&a=man' . $extra;
}

function km_loai_label($loai)
{
    return ((int) $loai === 1) ? 'Giảm phí ship' : 'Giảm tổng đơn';
}

function km_format_giatri($row)
{
    $v = (float) ($row['gia_tri'] ?? 0);
    if ((int) ($row['don_vi'] ?? 0) === 1) {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.') . '%';
    }
    return number_format($v) . 'đ';
}

/**
 * Mã trạng thái hiệu lực:
 * off | soon | expired | full | active
 */
function km_status_code($row, $used = 0)
{
    if (isset($row['hien_thi']) && (int) $row['hien_thi'] === 0) {
        return 'off';
    }
    $today = date('Y-m-d');
    $from  = (string) ($row['tu_ngay'] ?? '');
    $to    = (string) ($row['den_ngay'] ?? '');
    $limit = (int) ($row['gioi_han'] ?? 0);

    if ($from !== '' && $from !== '0000-00-00' && $today < $from) {
        return 'soon';
    }
    if ($to !== '' && $to !== '0000-00-00' && $today > $to) {
        return 'expired';
    }
    if ($limit > 0 && (int) $used >= $limit) {
        return 'full';
    }
    return 'active';
}

function km_status_badge($row, $used = 0)
{
    $map = array(
        'off'     => array('Tạm tắt', 'text-bg-secondary'),
        'soon'    => array('Chưa bắt đầu', 'text-bg-secondary'),
        'expired' => array('Hết hạn', 'text-bg-dark'),
        'full'    => array('Hết lượt', 'text-bg-danger'),
        'active'  => array('Đang áp dụng', 'text-bg-success'),
    );
    $code = km_status_code($row, $used);
    $m = $map[$code] ?? array($code, 'text-bg-secondary');
    return '<span class="badge ' . $m[1] . '">' . htmlspecialchars($m[0], ENT_QUOTES, 'UTF-8') . '</span>';
}

/** Điều kiện SQL theo filter trạng thái (cần alias bảng km + used subquery). */
function km_status_sql($status, $today)
{
    $today = addslashes($today);
    switch ($status) {
        case 'off':
            return 'km.hien_thi = 0';
        case 'soon':
            return "km.hien_thi = 1 AND km.tu_ngay IS NOT NULL AND km.tu_ngay > '{$today}' AND km.tu_ngay <> '0000-00-00'";
        case 'expired':
            return "km.hien_thi = 1 AND km.den_ngay IS NOT NULL AND km.den_ngay <> '0000-00-00' AND km.den_ngay < '{$today}'";
        case 'full':
            return 'km.hien_thi = 1 AND km.gioi_han > 0 AND IFNULL(u.used,0) >= km.gioi_han';
        case 'active':
            return "km.hien_thi = 1"
                . " AND (km.tu_ngay IS NULL OR km.tu_ngay = '' OR km.tu_ngay = '0000-00-00' OR km.tu_ngay <= '{$today}')"
                . " AND (km.den_ngay IS NULL OR km.den_ngay = '' OR km.den_ngay = '0000-00-00' OR km.den_ngay >= '{$today}')"
                . ' AND (km.gioi_han = 0 OR IFNULL(u.used,0) < km.gioi_han)';
        default:
            return '';
    }
}
function showdulieu()
{
    global $d, $items, $item, $total_page, $limit, $total_records, $current_page, $per_page;
    global $filter_loai, $filter_status, $members, $used_count, $usage_logs;

    $a = $_REQUEST['a'] ?? '';

    if ($a === 'add' || $a === 'edit') {
        $item = array();
        $used_count = 0;
        $usage_logs = array();
        if ($a === 'edit' && !empty($_REQUEST['id'])) {
            $id  = (int) $_REQUEST['id'];
            $row = $d->simple_fetch('SELECT * FROM #_khuyenmai WHERE id = ' . $id);
            $item = is_array($row) ? $row : array();
            if (!empty($item['ma'])) {
                $ma = $d->clear($item['ma']);
                $uc = $d->simple_fetch(
                    "SELECT COUNT(*) AS c FROM #_khuyenmai_ls WHERE ma_km = '{$ma}'"
                );
                $used_count = (int) ($uc['c'] ?? 0);
                $usage_logs = $d->o_fet(
                    "SELECT * FROM #_khuyenmai_ls WHERE ma_km = '{$ma}' ORDER BY id DESC LIMIT 20"
                );
                if (!is_array($usage_logs)) {
                    $usage_logs = array();
                }
            }
        }
        // Chỉ load tối đa 300 TV gần nhất (form select2)
        $members = $d->o_fet(
            'SELECT id, ho_ten, email FROM #_thanhvien WHERE loai = 0 ORDER BY id DESC LIMIT 300'
        );
        if (!is_array($members)) {
            $members = array();
        }
        $items = $item;
        return;
    }

    // ---- list ----
    $where = array('1=1');
    $col  = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $key  = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
    $loai = isset($_GET['loai']) ? trim((string) $_GET['loai']) : '';
    $stt  = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

    if ($loai === '0' || $loai === '1') {
        $where[] = 'km.loai = ' . (int) $loai;
        $filter_loai = $loai;
    } else {
        $filter_loai = '';
    }

    $allowedStatus = array('off', 'soon', 'expired', 'full', 'active');
    if (in_array($stt, $allowedStatus, true)) {
        $filter_status = $stt;
        $stSql = km_status_sql($stt, date('Y-m-d'));
        if ($stSql !== '') {
            $where[] = $stSql;
        }
    } else {
        $filter_status = '';
    }

    $allowed = array('ten', 'ma');
    if ($key !== '') {
        $keySql = $d->clear($key);
        if ($col !== '' && in_array($col, $allowed, true)) {
            $where[] = 'km.' . $col . " LIKE '%" . $keySql . "%'";
        } else {
            $where[] = "(km.ten LIKE '%" . $keySql . "%' OR km.ma LIKE '%" . $keySql . "%')";
        }
    }

    $whereSql = implode(' AND ', $where);
    $limit  = 14;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $fromSql = ' FROM #_khuyenmai km'
        . ' LEFT JOIN ('
        . '   SELECT ma_km, COUNT(*) AS used FROM #_khuyenmai_ls GROUP BY ma_km'
        . ' ) u ON u.ma_km = km.ma'
        . ' WHERE ' . $whereSql;

    $items = $d->o_fet(
        'SELECT km.*, IFNULL(u.used,0) AS used_count' . $fromSql
        . ' ORDER BY km.id DESC LIMIT ' . (int) $offset . ', ' . (int) $limit
    );
    if (!is_array($items)) {
        $items = array();
    }

    $countRow = $d->simple_fetch('SELECT COUNT(*) AS c' . $fromSql);
    $total_records = (int) ($countRow['c'] ?? 0);
    $total_page    = (int) ceil($total_records / max(1, $limit));
    $current_page  = $page;
    $per_page      = $limit;
}

function show_history()
{
    global $d, $item, $usage_logs, $used_count, $total_page, $limit, $total_records, $current_page, $per_page;

    $id = (int) ($_GET['id'] ?? 0);
    $item = $id > 0
        ? $d->simple_fetch('SELECT * FROM #_khuyenmai WHERE id = ' . $id)
        : null;
    if (empty($item) || empty($item['ma'])) {
        $usage_logs = array();
        $used_count = 0;
        $total_records = 0;
        $total_page = 1;
        $current_page = 1;
        $per_page = 20;
        return;
    }

    $ma = $d->clear($item['ma']);
    $limit  = 20;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $usage_logs = $d->o_fet(
        "SELECT * FROM #_khuyenmai_ls WHERE ma_km = '{$ma}' ORDER BY id DESC"
        . ' LIMIT ' . (int) $offset . ', ' . (int) $limit
    );
    if (!is_array($usage_logs)) {
        $usage_logs = array();
    }

    $uc = $d->simple_fetch("SELECT COUNT(*) AS c FROM #_khuyenmai_ls WHERE ma_km = '{$ma}'");
    $used_count = (int) ($uc['c'] ?? 0);
    $total_records = $used_count;
    $total_page    = (int) ceil($total_records / max(1, $limit));
    $current_page  = $page;
    $per_page      = $limit;
}
function km_build_data()
{
    global $d;

    $ten = trim((string) ($_POST['ten'] ?? ''));
    $ma  = strtoupper(trim((string) ($_POST['ma'] ?? '')));
    $ma  = preg_replace('/[^A-Z0-9_-]/', '', $ma);

    $donVi = ((int) ($_POST['don_vi'] ?? 0) === 1) ? 1 : 0;
    $giaTri = (int) ($_POST['gia_tri'] ?? 0);
    if ($donVi === 1 && $giaTri > 100) {
        $giaTri = 100;
    }

    $tu  = trim((string) ($_POST['tu_ngay'] ?? ''));
    $den = trim((string) ($_POST['den_ngay'] ?? ''));

    $data = array(
        'ten'          => $d->clear($ten),
        'ma'           => $d->clear($ma),
        'gia_tri'      => max(0, $giaTri),
        'don_vi'       => $donVi,
        'loai'         => ((int) ($_POST['loai'] ?? 0) === 1) ? 1 : 0,
        'dieu_kien'    => max(0, (int) ($_POST['dieu_kien'] ?? 0)),
        'tu_ngay'      => $d->clear($tu),
        'den_ngay'     => $d->clear($den),
        'gia_tri_max'  => $donVi === 1 ? max(0, (int) ($_POST['gia_tri_max'] ?? 0)) : 0,
        'gioi_han'     => max(0, (int) ($_POST['gioi_han'] ?? 0)),
        'hien_thi'     => isset($_POST['hien_thi']) ? 1 : 0,
        'id_thanhvien' => '',
    );

    $tv = isset($_POST['id_thanhvien']) ? (array) $_POST['id_thanhvien'] : array();
    $tv = array_values(array_filter(array_map('intval', $tv)));
    if (!empty($tv)) {
        $data['id_thanhvien'] = ',' . implode(',', $tv) . ',';
    }

    return $data;
}

function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        km_flash('error', 'Không có quyền chỉnh sửa.');
        $d->redirect(km_back_man());
        return;
    }

    $id   = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    $data = km_build_data();
    $backForm = $id > 0
        ? 'index.php?p=ma-khuyen-mai&a=edit&id=' . $id
        : 'index.php?p=ma-khuyen-mai&a=add';

    if ($data['ten'] === '' || $data['ma'] === '') {
        km_flash('error', 'Vui lòng nhập tên chương trình và mã khuyến mãi hợp lệ.');
        $d->redirect($backForm);
        return;
    }

    if ($data['gia_tri'] <= 0) {
        km_flash('error', 'Giá trị khuyến mãi phải lớn hơn 0.');
        $d->redirect($backForm);
        return;
    }

    if ($data['don_vi'] === 1 && $data['gia_tri'] > 100) {
        km_flash('error', 'Giá trị phần trăm không được vượt quá 100%.');
        $d->redirect($backForm);
        return;
    }

    $tu  = $data['tu_ngay'];
    $den = $data['den_ngay'];
    if ($tu !== '' && $den !== '' && $den < $tu) {
        km_flash('error', 'Ngày kết thúc phải sau hoặc bằng ngày bắt đầu.');
        $d->redirect($backForm);
        return;
    }

    $dupSql = "SELECT id FROM #_khuyenmai WHERE ma = '" . $d->clear($data['ma']) . "'";
    if ($id > 0) {
        $dupSql .= ' AND id <> ' . $id;
    }
    $dup = $d->simple_fetch($dupSql);
    if (!empty($dup)) {
        km_flash('error', 'Mã khuyến mãi đã tồn tại.');
        $d->redirect($backForm);
        return;
    }

    $capnhat = $_POST['capnhat'] ?? 'capnhat';
    $d->reset();
    $d->setTable('#_khuyenmai');

    if ($id > 0) {
        $d->setWhere('id', $id);
        if ($d->update($data)) {
            km_flash('success', 'Đã cập nhật mã khuyến mãi.');
            $d->redirect($capnhat === 'capnhat_sua'
                ? 'index.php?p=ma-khuyen-mai&a=edit&id=' . $id
                : km_back_man());
        } else {
            km_flash('error', 'Cập nhật thất bại.');
            $d->redirect($backForm);
        }
        return;
    }

    $newId = $d->insert($data);
    if ($newId) {
        km_flash('success', 'Đã thêm mã khuyến mãi.');
        $d->redirect($capnhat === 'capnhat_sua'
            ? 'index.php?p=ma-khuyen-mai&a=edit&id=' . (int) $newId
            : km_back_man());
    } else {
        km_flash('error', 'Thêm mới thất bại (có thể do trùng mã).');
        $d->redirect($backForm);
    }
}

function xoadulieu($id_module)
{
    global $d;
    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        km_flash('error', 'Không có quyền xóa.');
        $d->redirect(km_back_man());
        return;
    }
    $id = (int) $_GET['id'];
    if ($id <= 0) {
        $d->redirect(km_back_man());
        return;
    }
    $d->reset();
    $d->setTable('#_khuyenmai');
    $d->setWhere('id', $id);
    $d->delete();
    km_flash('success', 'Đã xóa mã khuyến mãi.');
    $d->redirect(km_back_man());
}

function xoadulieu_mang($id_module)
{
    global $d;
    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        km_flash('error', 'Không có quyền xóa hoặc chưa chọn mục.');
        $d->redirect(km_back_man());
        return;
    }
    $ids = array_values(array_filter(array_map('intval', (array) $_POST['chk_child'])));
    if (empty($ids)) {
        $d->redirect(km_back_man());
        return;
    }
    $d->o_que('DELETE FROM #_khuyenmai WHERE id IN (' . implode(',', $ids) . ')');
    km_flash('success', 'Đã xóa ' . count($ids) . ' mã khuyến mãi.');
    $d->redirect(km_back_man());
}
