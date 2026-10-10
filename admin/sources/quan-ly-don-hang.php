<?php
/**
 * Module Quản lý đơn hàng (admin)
 *
 * - Danh sách: tìm / lọc trạng thái / TT / ngày / phân trang
 * - Chi tiết: xem SP, KH, cập nhật xử lý + thanh toán (tách riêng)
 * - Xóa 1 / xóa nhiều (+ chi tiết + lịch sử)
 *
 * Toàn bộ ghi DB nằm ở source (không còn logic POST trong template).
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
    case 'edit':
        showdulieu();
        $template = @$_REQUEST['p'] . '/them';
        break;
    case 'save':
        luu_xuly($id_module);
        break;
    case 'accept':
        tiepnhan_don($id_module);
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

/* ------------------------------------------------------------------ */
/* Helpers                                                            */
/* ------------------------------------------------------------------ */

function donhang_flash($type, $message)
{
    $_SESSION['admin_flash'] = array(
        'type'    => $type,
        'message' => $message,
    );
}

function donhang_back_man()
{
    return 'index.php?p=quan-ly-don-hang&a=man';
}

function donhang_back_edit($id)
{
    return 'index.php?p=quan-ly-don-hang&a=edit&id=' . (int) $id;
}

/** Map trạng thái xử lý (pipeline giao hàng). */
function donhang_trangthai_map()
{
    return array(
        0 => array('label' => 'Đơn mới',    'class' => 'text-bg-danger'),
        1 => array('label' => 'Đang xử lý', 'class' => 'text-bg-warning'),
        2 => array('label' => 'Đang giao',  'class' => 'text-bg-info'),
        3 => array('label' => 'Đã giao',    'class' => 'text-bg-success'),
        4 => array('label' => 'Trả hàng',   'class' => 'text-bg-secondary'),
    );
}

function donhang_trangthai_badge($code)
{
    $map  = donhang_trangthai_map();
    $code = (int) $code;
    if (!isset($map[$code])) {
        return '<span class="badge text-bg-secondary"># ' . $code . '</span>';
    }
    $m = $map[$code];
    return '<span class="badge ' . $m['class'] . '">'
        . htmlspecialchars($m['label'], ENT_QUOTES, 'UTF-8') . '</span>';
}

function donhang_thanhtoan_badge($paid)
{
    if ((int) $paid === 1) {
        return '<span class="badge text-bg-success">Đã thanh toán</span>';
    }
    return '<span class="badge text-bg-danger">Chưa thanh toán</span>';
}

/**
 * Tổng thanh toán 1 đơn = hàng + ship + thùng − giảm.
 * $subtotal = SUM(gia_ban * so_luong) từ chi tiết.
 */
function donhang_grand_total($order, $subtotal)
{
    $ship = (float) ($order['phi_vanchuyen'] ?? 0);
    $box  = ((int) ($order['has_box'] ?? 0) === 1)
        ? (float) ($order['phi_thung'] ?? 0) : 0;
    $giam = (float) ($order['so_tien_giam'] ?? 0);
    return (float) $subtotal + $ship + $box - $giam;
}

/** Ghi 1 dòng lịch sử xử lý. */
function donhang_log($d, $id_dh, $trang_thai, $ghi_chu = '')
{
    $d->reset();
    $d->setTable('#_dathang_xuly');
    return (bool) $d->insert(array(
        'id_dh'           => (int) $id_dh,
        'trang_thai_xuly' => (int) $trang_thai,
        'ghi_chu'         => $d->clear($ghi_chu),
        'ngay_xuly'       => date('Y-m-d H:i:s'),
        'nguoi_xuly'      => $d->clear($_SESSION['name'] ?? 'Admin'),
    ));
}

/** Nhãn lịch sử (gồm cả mã thanh toán legacy 5/6). */
function donhang_ls_label($code)
{
    $map = donhang_trangthai_map();
    $code = (int) $code;
    if (isset($map[$code])) {
        return $map[$code]['label'];
    }
    if ($code === 5) {
        return 'Đã thanh toán';
    }
    if ($code === 6) {
        return 'Chưa thanh toán';
    }
    return '#' . $code;
}
/* ------------------------------------------------------------------ */
/* List / detail load                                                 */
/* ------------------------------------------------------------------ */

function showdulieu()
{
    global $d, $items, $total_page, $limit, $total_records, $current_page, $per_page;
    global $filter_status, $filter_paid, $filter_from, $filter_to, $order_stats;
    global $order, $order_lines, $order_logs, $order_subtotal, $order_grand;

    // ---- Chi tiết 1 đơn ----
    if (($_REQUEST['a'] ?? '') === 'edit') {
        $id = (int) ($_REQUEST['id'] ?? 0);
        $order = $id > 0
            ? $d->simple_fetch('SELECT * FROM #_dathang WHERE id = ' . $id)
            : null;
        if (empty($order)) {
            $order = null;
            $order_lines = array();
            $order_logs = array();
            $order_subtotal = 0;
            $order_grand = 0;
            $items = array();
            return;
        }

        $order_lines = $d->o_fet(
            'SELECT * FROM #_dathang_chitiet WHERE id_dh = ' . $id . ' ORDER BY id ASC'
        );
        if (!is_array($order_lines)) {
            $order_lines = array();
        }

        $order_logs = $d->o_fet(
            'SELECT * FROM #_dathang_xuly WHERE id_dh = ' . $id . ' ORDER BY id DESC'
        );
        if (!is_array($order_logs)) {
            $order_logs = array();
        }

        $order_subtotal = 0;
        foreach ($order_lines as $ln) {
            $order_subtotal += ((float) $ln['gia_ban']) * ((int) $ln['so_luong']);
        }
        $order_grand = donhang_grand_total($order, $order_subtotal);
        $items = array($order);
        return;
    }

    // ---- Danh sách ----
    $where = array('1=1');

    $col = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
    $key = isset($_GET['key']) ? trim((string) $_GET['key']) : '';
    $st  = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
    $paid = isset($_GET['paid']) ? trim((string) $_GET['paid']) : '';
    $from = isset($_GET['from']) ? trim((string) $_GET['from']) : '';
    $to   = isset($_GET['to']) ? trim((string) $_GET['to']) : '';

    if ($st !== '' && ctype_digit($st) && (int) $st >= 0 && (int) $st <= 4) {
        $where[] = 'trangthai_xuly = ' . (int) $st;
        $filter_status = (string) (int) $st;
    } else {
        $filter_status = '';
    }

    if ($paid === '0' || $paid === '1') {
        $where[] = 'trangthai_thanhtoan = ' . (int) $paid;
        $filter_paid = $paid;
    } else {
        $filter_paid = '';
    }

    // YYYY-MM-DD
    if ($from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $where[] = "ngay_dathang >= '" . $d->clear($from) . "'";
        $filter_from = $from;
    } else {
        $filter_from = '';
    }
    if ($to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $where[] = "ngay_dathang <= '" . $d->clear($to) . "'";
        $filter_to = $to;
    } else {
        $filter_to = '';
    }

    $allowed = array('ma_dh', 'ho_ten', 'dien_thoai', 'email', 'dia_chi');
    if ($key !== '') {
        $keySql = $d->clear($key);
        if ($col !== '' && in_array($col, $allowed, true)) {
            $where[] = $col . " LIKE '%" . $keySql . "%'";
        } else {
            $where[] = "(ma_dh LIKE '%" . $keySql . "%' OR ho_ten LIKE '%" . $keySql . "%'"
                . " OR dien_thoai LIKE '%" . $keySql . "%' OR email LIKE '%" . $keySql . "%'"
                . " OR dia_chi LIKE '%" . $keySql . "%')";
        }
    }

    $whereSql = implode(' AND ', $where);

    $limit  = 14;
    $page   = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $base = 'SELECT * FROM #_dathang WHERE ' . $whereSql
        . ' ORDER BY trangthai_xuly ASC, id DESC';

    $items = $d->o_fet($base . ' LIMIT ' . (int) $offset . ', ' . (int) $limit);
    if (!is_array($items)) {
        $items = array();
    }

    $countRow = $d->simple_fetch('SELECT COUNT(*) AS c FROM #_dathang WHERE ' . $whereSql);
    $total_records = (int) ($countRow['c'] ?? 0);
    $total_page    = (int) ceil($total_records / max(1, $limit));
    $current_page  = $page;
    $per_page      = $limit;

    // Stats: grand total theo cùng công thức chi tiết (toàn DB, không theo filter list)
    $order_stats = array('cho' => 0, 'thu' => 0, 'huy' => 0, 'ctv' => 0, 'count_new' => 0);

    $sumSql = 'SELECT d.id, d.trangthai_xuly, d.phi_vanchuyen, d.has_box, d.phi_thung, d.so_tien_giam,'
        . ' COALESCE(SUM(c.gia_ban * c.so_luong), 0) AS subtotal'
        . ' FROM #_dathang d'
        . ' LEFT JOIN #_dathang_chitiet c ON c.id_dh = d.id'
        . ' GROUP BY d.id, d.trangthai_xuly, d.phi_vanchuyen, d.has_box, d.phi_thung, d.so_tien_giam';
    $sumRows = $d->o_fet($sumSql);
    if (is_array($sumRows)) {
        foreach ($sumRows as $r) {
            $tt   = (int) ($r['trangthai_xuly'] ?? 0);
            $tien = donhang_grand_total($r, (float) ($r['subtotal'] ?? 0));
            if ($tt === 0) {
                $order_stats['count_new']++;
            }
            if ($tt < 3) {
                $order_stats['cho'] += $tien;
            } elseif ($tt === 3) {
                $order_stats['thu'] += $tien;
            } else {
                $order_stats['huy'] += $tien;
            }
        }
    }

    $ctv = $d->simple_fetch(
        'SELECT COALESCE(SUM((gia_ban - gia_goc) * so_luong),0) AS ck'
        . ' FROM #_dathang_chitiet WHERE id_ctv > 0'
    );
    $order_stats['ctv'] = (float) ($ctv['ck'] ?? 0);
}
/* ------------------------------------------------------------------ */
/* Actions                                                            */
/* ------------------------------------------------------------------ */

/** User bấm "Tiếp nhận" đơn mới (0 → 1), có ghi log. */
function tiepnhan_don($id_module)
{
    global $d;

    $id = (int) ($_GET['id'] ?? 0);
    if ($d->checkPermission_edit($id_module) != 1 || $id <= 0) {
        donhang_flash('error', 'Không có quyền hoặc đơn không hợp lệ.');
        $d->redirect(donhang_back_man());
        return;
    }

    $row = $d->simple_fetch('SELECT id, trangthai_xuly FROM #_dathang WHERE id = ' . $id);
    if (empty($row)) {
        donhang_flash('error', 'Không tìm thấy đơn hàng.');
        $d->redirect(donhang_back_man());
        return;
    }

    if ((int) $row['trangthai_xuly'] !== 0) {
        donhang_flash('warning', 'Đơn này đã được tiếp nhận trước đó.');
        $d->redirect(donhang_back_edit($id));
        return;
    }

    $d->o_que(
        "UPDATE #_dathang SET trangthai_xuly = 1, ngay_capnhat = '"
        . date('Y-m-d') . "' WHERE id = " . $id
    );
    donhang_log($d, $id, 1, 'Tiếp nhận đơn hàng');
    donhang_flash('success', 'Đã tiếp nhận đơn hàng.');
    $d->redirect(donhang_back_edit($id));
}

/**
 * Cập nhật xử lý / thanh toán (POST a=save).
 * - trangthai: 0–4 pipeline giao hàng
 * - trangthai_thanhtoan: 0|1 (tách riêng, không còn mã 5/6 trong select)
 */
function luu_xuly($id_module)
{
    global $d;

    $id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($d->checkPermission_edit($id_module) != 1 || $id <= 0) {
        donhang_flash('error', 'Không có quyền chỉnh sửa.');
        $d->redirect(donhang_back_man());
        return;
    }

    $row = $d->simple_fetch('SELECT * FROM #_dathang WHERE id = ' . $id);
    if (empty($row)) {
        donhang_flash('error', 'Không tìm thấy đơn hàng.');
        $d->redirect(donhang_back_man());
        return;
    }

    $st   = (int) ($_POST['trangthai'] ?? -1);
    $paid = isset($_POST['trangthai_thanhtoan']) ? (int) $_POST['trangthai_thanhtoan'] : -1;
    $note = trim((string) ($_POST['ghichu'] ?? ''));
    $now  = date('Y-m-d');

    $map = donhang_trangthai_map();
    $ok  = false;
    $msg = array();

    // Cập nhật pipeline giao hàng
    if (isset($map[$st]) && $st !== (int) $row['trangthai_xuly']) {
        $d->o_que(
            "UPDATE #_dathang SET trangthai_xuly = {$st}, ngay_capnhat = '{$now}' WHERE id = " . $id
        );
        donhang_log($d, $id, $st, $note !== '' ? $note : ('Cập nhật: ' . $map[$st]['label']));
        $ok = true;
        $msg[] = 'trạng thái xử lý';
    }

    // Cập nhật thanh toán (tách riêng)
    if ($paid === 0 || $paid === 1) {
        $curPaid = (int) ($row['trangthai_thanhtoan'] ?? 0);
        if ($paid !== $curPaid) {
            $paySql = "UPDATE #_dathang SET trangthai_thanhtoan = {$paid}, ngay_capnhat = '{$now}'";
            if ($paid === 1) {
                $paySql .= ", ngay_thanhtoan = '{$now}'";
            }
            $paySql .= ' WHERE id = ' . $id;
            $d->o_que($paySql);
            // log dùng mã 5/6 để tương thích lịch sử cũ
            donhang_log(
                $d,
                $id,
                $paid === 1 ? 5 : 6,
                $note !== '' ? $note : ($paid === 1 ? 'Đánh dấu đã thanh toán' : 'Đánh dấu chưa thanh toán')
            );
            $ok = true;
            $msg[] = 'thanh toán';
        }
    }

    // Chỉ ghi chú (không đổi status) → vẫn log nếu có nội dung
    if (!$ok && $note !== '') {
        donhang_log($d, $id, (int) $row['trangthai_xuly'], $note);
        $ok = true;
        $msg[] = 'ghi chú';
    }

    if ($ok) {
        donhang_flash('success', 'Đã cập nhật ' . implode(', ', $msg) . '.');
    } else {
        donhang_flash('warning', 'Không có thay đổi nào được lưu.');
    }
    $d->redirect(donhang_back_edit($id));
}

function xoadulieu($id_module)
{
    global $d;
    $back = donhang_back_man();
    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        donhang_flash('error', 'Không có quyền xóa.');
        $d->redirect($back);
        return;
    }
    $id = (int) $_GET['id'];
    if ($id <= 0) {
        $d->redirect($back);
        return;
    }
    $d->o_que('DELETE FROM #_dathang_chitiet WHERE id_dh = ' . $id);
    $d->o_que('DELETE FROM #_dathang_xuly WHERE id_dh = ' . $id);
    $d->reset();
    $d->setTable('#_dathang');
    $d->setWhere('id', $id);
    $d->delete();
    donhang_flash('success', 'Đã xóa đơn hàng.');
    $d->redirect($back);
}

function xoadulieu_mang($id_module)
{
    global $d;
    $back = donhang_back_man();
    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        donhang_flash('error', 'Không có quyền xóa hoặc chưa chọn đơn.');
        $d->redirect($back);
        return;
    }
    $ids = array_values(array_filter(array_map('intval', (array) $_POST['chk_child'])));
    if (empty($ids)) {
        $d->redirect($back);
        return;
    }
    $chuoi = implode(',', $ids);
    $d->o_que('DELETE FROM #_dathang_chitiet WHERE id_dh IN (' . $chuoi . ')');
    $d->o_que('DELETE FROM #_dathang_xuly WHERE id_dh IN (' . $chuoi . ')');
    $d->o_que('DELETE FROM #_dathang WHERE id IN (' . $chuoi . ')');
    donhang_flash('success', 'Đã xóa ' . count($ids) . ' đơn hàng.');
    $d->redirect($back);
}
