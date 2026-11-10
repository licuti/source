<?php
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
        showdulieu();
        $template = $_REQUEST['p'] . '/hienthi';
        break;
    case 'add':
    case 'edit':
        showdulieu();
        $template = $_REQUEST['p'] . '/them';
        break;
    case 'save':
        luudulieu($id_module);
        break;
    case 'save_group':
        luunhom($id_module);
        break;
    case 'delete':
        xoadulieu($id_module);
        break;
    case 'delete_group':
        xoanhom($id_module);
        break;
    case 'delete_all':
        xoadulieu_mang($id_module);
        break;
    default:
        $template = 'index';
}

function noi_dung_flash($type, $message)
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array(
        'type'    => $type,
        'message' => $message,
    );
}

/** Ve danh sach man (co the kem parent / id_loai sua nhom). */
function noi_dung_back($parent = '', $editGroupId = 0)
{
    global $d;
    if ((int) $editGroupId > 0) {
        $d->redirect('index.php?p=noi-dung&a=man&id_loai=' . (int) $editGroupId . '&scroll_group=' . (int) $editGroupId);
        return;
    }
    $url = 'index.php?p=noi-dung&a=man';
    if ($parent !== '' && $parent !== null) {
        $url .= '&id_loai=' . urlencode($parent);
    }
    $d->redirect($url);
}

/**
 * Sau luu ban ghi #_content: Lưu | Lưu và Sửa | Lưu và Thêm mới.
 *
 * @param string     $cap      capnhat|capnhat_sua|capnhat_them
 * @param string|int $parent
 * @param int        $savedId  id_code vua luu
 */
function noi_dung_redirect_after_save($cap, $parent, $savedId = 0)
{
    global $d;
    $parent = (int) $parent;
    $savedId = (int) $savedId;
    if ($cap === 'capnhat_sua' && $savedId > 0) {
        $d->redirect('index.php?p=noi-dung&a=edit&id=' . $savedId . '&parent=' . $parent);
        return;
    }
    if ($cap === 'capnhat_them') {
        $d->redirect('index.php?p=noi-dung&a=add&parent=' . $parent);
        return;
    }
    $url = 'index.php?p=noi-dung&a=man';
    if ($parent > 0) {
        $url .= '&id_loai=' . $parent;
    }
    if ($savedId > 0) {
        $url .= '&scroll_item=' . $savedId;
    }
    $d->redirect($url);
}

function showdulieu()
{
    global $d, $items, $nd_groups, $nd_group_options, $nd_edit_group;
    global $rowByLang, $main, $groupOpts, $isEdit, $editId, $parent;

    $nd_groups = $d->o_fet(
        "select * from #_category_noidung where lang='" . LANG . "' order by id ASC"
    ) ?: array();

    // 1 query tat ca content, group theo id_loai (tranh N+1)
    $nd_group_options = array();
    foreach ($nd_groups as $g) {
        $nd_group_options[(int) $g['id_code']] = array();
    }
    $allContent = $d->o_fet(
        "select * from #_content where lang='" . LANG . "' order by so_thu_tu ASC, id ASC"
    ) ?: array();
    foreach ($allContent as $row) {
        $gid = (int) ($row['id_loai'] ?? 0);
        if (!isset($nd_group_options[$gid])) {
            $nd_group_options[$gid] = array();
        }
        $nd_group_options[$gid][] = $row;
    }

    $nd_edit_group = null;
    if (!empty($_GET['id_loai'])) {
        $gid = (int) $_GET['id_loai'];
        $nd_edit_group = array();
        foreach (get_json('lang') as $lang) {
            $code = $lang['code'];
            $nd_edit_group[$code] = $d->simple_fetch(
                "select * from #_category_noidung where id_code=" . $gid . " and lang='" . $code . "'"
            ) ?: array();
        }
    }

    $isEdit = !empty($_REQUEST['id']);
    $editId = $isEdit ? (int) $_REQUEST['id'] : 0;
    $parent = isset($_GET['parent']) ? (int) $_GET['parent'] : 0;
    $rowByLang = array();
    $items = array();

    foreach (get_json('lang') as $lang) {
        $code = $lang['code'];
        $rowByLang[$code] = $isEdit
            ? ($d->simple_fetch(
                "select * from #_content where id_code=" . $editId . " and lang='" . $code . "'"
            ) ?: array())
            : array();
    }
    $langs = get_json('lang');
    $firstCode = $langs[0]['code'] ?? 'vi';
    $main = $rowByLang[$firstCode] ?? array();
    if (!$parent && !empty($main['id_loai'])) {
        $parent = (int) $main['id_loai'];
    }
    if ($isEdit) {
        $items = array($main);
    }

    $groupOpts = array();
    foreach ($nd_groups as $g) {
        $groupOpts[(string) $g['id_code']] = (!empty($g['label']) && trim((string) $g['label']) !== '') ? $g['label'] : ($g['ten'] ?? '');
    }
}

function luunhom($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        noi_dung_flash('error', 'Bạn không có quyền chỉnh sửa.');
        noi_dung_back();
        return;
    }

    $id_loai    = isset($_GET['id_loai']) ? (int) $_GET['id_loai'] : 0;
    $hinh_anh   = addslashes(trim((string) ($_POST['hinh_anh'] ?? '')));
    $background = addslashes(trim((string) ($_POST['background'] ?? '')));
    $tens       = $_POST['ten'] ?? array();
    $labels     = $_POST['label'] ?? array();
    $noidungs   = $_POST['noi_dung'] ?? array();
    $links      = $_POST['link'] ?? array();
    $id_rows    = $_POST['id_row'] ?? array();
    $ten0       = trim((string) ($tens[0] ?? ''));
    $label0     = is_array($labels) ? trim((string) ($labels[0] ?? '')) : trim((string) $labels);
    $cap        = $_POST['capnhat'] ?? 'capnhat';

    if ($ten0 === '') {
        noi_dung_flash('error', 'Vui lòng nhập tên nhóm.');
        noi_dung_back('', $id_loai);
        return;
    }

    $savedGroupId = $id_loai;

    if ($id_loai > 0) {
        $d->reset();
        $d->setTable('cf_parent');
        $d->setWhere('id', $id_loai);
        if (!$d->update(array('ten' => addslashes($ten0), 'label' => addslashes($label0)))) {
            noi_dung_flash('error', 'Cập nhật nhóm thất bại.');
            noi_dung_back('', $id_loai);
            return;
        }
        foreach (get_json('lang') as $key => $value) {
            $labelVal = is_array($labels) ? trim((string) ($labels[$key] ?? '')) : trim((string) $labels);
            $data = array(
                'label'    => $d->clear(addslashes($labelVal)),
                'ten'      => $d->clear(addslashes(trim((string) ($tens[$key] ?? '')))),
                'noi_dung' => $d->clear(addslashes((string) ($noidungs[$key] ?? ''))),
                'link'     => $d->clear(addslashes((string) ($links[$key] ?? ''))),
                'nofollow' => isset($_POST['nofollow']) ? 1 : 0,
                'target'   => isset($_POST['target']) ? 1 : 0,
            );
            if ($hinh_anh !== '') {
                $data['hinh_anh'] = $hinh_anh;
            }
            if ($background !== '') {
                $data['background'] = $background;
            }
            $rid = (int) ($id_rows[$key] ?? 0);
            if ($rid > 0) {
                $d->reset();
                $d->setTable('#_category_noidung');
                $d->setWhere('id', $rid);
                $d->update($data);
            }
        }
        noi_dung_flash('success', 'Đã cập nhật nhóm nội dung.');
    } else {
        $d->reset();
        $d->setTable('cf_parent');
        $id_code = $d->insert(array('ten' => addslashes($ten0), 'label' => addslashes($label0), 'type' => 1));
        if (!$id_code) {
            noi_dung_flash('error', 'Thêm nhóm thất bại.');
            noi_dung_back();
            return;
        }
        $savedGroupId = (int) $id_code;
        foreach (get_json('lang') as $key => $value) {
            $labelVal = is_array($labels) ? trim((string) ($labels[$key] ?? '')) : trim((string) $labels);
            $d->reset();
            $d->setTable('#_category_noidung');
            $d->insert(array(
                'label'      => $d->clear(addslashes($labelVal)),
                'ten'        => $d->clear(addslashes(trim((string) ($tens[$key] ?? '')))),
                'noi_dung'   => $d->clear(addslashes((string) ($noidungs[$key] ?? ''))),
                'link'       => $d->clear(addslashes((string) ($links[$key] ?? ''))),
                'hinh_anh'   => $hinh_anh,
                'background' => $background,
                'hien_thi'   => 1,
                'id_code'    => $id_code,
                'lang'       => $value['code'],
                'nofollow'   => isset($_POST['nofollow']) ? 1 : 0,
                'target'     => isset($_POST['target']) ? 1 : 0,
            ));
        }
        noi_dung_flash('success', 'Đã thêm nhóm nội dung.');
    }

    if ($cap === 'capnhat_sua' && $savedGroupId > 0) {
        noi_dung_back('', $savedGroupId);
    } else {
        noi_dung_back();
    }
}

function xoanhom($id_module)
{
    global $d;

    if ($d->checkPermission_dele($id_module) != 1) {
        noi_dung_flash('error', 'Bạn không có quyền xóa.');
        noi_dung_back();
        return;
    }

    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        noi_dung_flash('error', 'Không nhận được dữ liệu.');
        noi_dung_back();
        return;
    }

    $sub = $d->o_fet("select id_code from #_content where id_loai='" . $id . "'") ?: array();
    foreach ($sub as $row) {
        $d->o_que('delete from cf_parent where id=' . (int) $row['id_code']);
    }
    $d->o_que('delete from #_content where id_loai=' . $id);
    $d->reset();
    $d->setTable('#_category_noidung');
    $d->setWhere('id_code', $id);
    if ($d->delete()) {
        $d->o_que('delete from cf_parent where id=' . $id);
        noi_dung_flash('success', 'Đã xóa nhóm và nội dung con.');
    } else {
        noi_dung_flash('error', 'Xóa nhóm thất bại.');
    }
    noi_dung_back();
}

/** Payload 1 ngon ngu cho #_content (khong gom id_code/lang — them o insert). */
function noi_dung_content_row($key, $id_loai, $hinh_anh, $background, $hien_thi, $so_thu_tu)
{
    global $d;
    return array(
        'id_loai'    => $id_loai,
        'hinh_anh'   => $hinh_anh,
        'background' => $background,
        'ten'        => $d->clear(addslashes($_POST['ten'][$key] ?? '')),
        'ten_phu'    => $d->clear(addslashes($_POST['ten_phu'][$key] ?? '')),
        'noi_dung'   => $d->clear(addslashes($_POST['noi_dung'][$key] ?? '')),
        'link'       => $d->clear(addslashes($_POST['link'][$key] ?? '')),
        'hien_thi'   => $hien_thi,
        'so_thu_tu'  => $so_thu_tu,
        'nofollow'   => isset($_POST['nofollow']) ? 1 : 0,
        'target'     => isset($_POST['target']) ? 1 : 0,
        // NOT NULL, không default — phải khai báo tường minh
        'ma_video'   => '',
    );
}

function luudulieu($id_module)
{
    global $d;

    $parentGet = isset($_GET['parent']) ? $_GET['parent'] : '';
    if ($d->checkPermission_edit($id_module) != 1) {
        noi_dung_flash('error', 'Bạn không có quyền chỉnh sửa.');
        noi_dung_back($parentGet);
        return;
    }

    $id         = isset($_REQUEST['id']) ? addslashes($_REQUEST['id']) : '';
    $hinh_anh   = addslashes($_POST['hinh_anh'] ?? '');
    $background = addslashes($_POST['background'] ?? '');
    $id_loai    = (int) ($_POST['id_loai'] ?? 0);
    if ($id_loai <= 0) {
        $id_loai = (int) ($_GET['parent'] ?? 0);
    }
    if ($id_loai <= 0) {
        noi_dung_flash('error', 'Vui lòng chọn nhóm nội dung.');
        noi_dung_back($parentGet);
        return;
    }
    $so_thu_tu = ($_POST['so_thu_tu'] ?? '') !== '' ? (int) $_POST['so_thu_tu'] : 0;
    $hien_thi  = isset($_POST['hien_thi']) ? 1 : 0;
    $cap       = $_POST['capnhat'] ?? 'capnhat';
    $ten0      = trim((string) ($_POST['ten'][0] ?? ''));
    $backParent = $parentGet !== '' ? $parentGet : $id_loai;

    if ($id != '') {
        $d->reset();
        $d->setTable('cf_parent');
        $d->setWhere('id', $id);
        if (!$d->update(array('ten' => addslashes($ten0)))) {
            noi_dung_flash('error', 'Cập nhật thất bại.');
            noi_dung_back($backParent);
            return;
        }
        foreach (get_json('lang') as $key => $value) {
            $rid = (int) ($_POST['id_row'][$key] ?? 0);
            if ($rid <= 0) {
                continue;
            }
            $d->reset();
            $d->setTable('#_content');
            $d->setWhere('id', $rid);
            $d->update(noi_dung_content_row($key, $id_loai, $hinh_anh, $background, $hien_thi, $so_thu_tu));
        }
        noi_dung_flash('success', 'Đã cập nhật nội dung.');
        noi_dung_redirect_after_save($cap, $backParent, (int) $id);
        return;
    }

    $d->reset();
    $d->setTable('cf_parent');
    $id_code = $d->insert(array('ten' => addslashes($ten0)));
    if (!$id_code) {
        noi_dung_flash('error', 'Thêm nội dung thất bại.');
        noi_dung_back($backParent);
        return;
    }
    foreach (get_json('lang') as $key => $value) {
        $data = noi_dung_content_row($key, $id_loai, $hinh_anh, $background, $hien_thi, $so_thu_tu);
        $data['id_code']  = $id_code;
        $data['lang']     = $value['code'];
        $data['ngay_tao'] = time();
        $d->reset();
        $d->setTable('#_content');
        $d->insert($data);
    }
    noi_dung_flash('success', 'Đã thêm nội dung.');
    noi_dung_redirect_after_save($cap, $backParent, (int) $id_code);
}

function xoadulieu($id_module)
{
    global $d;
    $parent = isset($_GET['parent']) ? $_GET['parent'] : '';
    $isAjax = !empty($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($d->checkPermission_dele($id_module) != 1 || empty($_GET['id'])) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Bạn không có quyền xóa hoặc thiếu dữ liệu.'));
            exit;
        }
        noi_dung_flash('error', 'Không xóa được.');
        noi_dung_back($parent);
        return;
    }

    $id = (int) $_GET['id'];
    $d->reset();
    $d->setTable('#_content');
    $d->setWhere('id_code', $id);
    if ($d->delete()) {
        $d->o_que('delete from cf_parent where id=' . $id);
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => true, 'message' => 'Đã xóa nội dung.', 'id' => $id));
            exit;
        }
        noi_dung_flash('success', 'Đã xóa nội dung.');
    } else {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Xóa thất bại.'));
            exit;
        }
        noi_dung_flash('error', 'Xóa thất bại.');
    }
    noi_dung_back($parent);
}

function xoadulieu_mang($id_module)
{
    global $d;
    $parent = isset($_GET['parent']) ? $_GET['parent'] : '';
    $isAjax = !empty($_REQUEST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Không có quyền xóa hoặc chưa chọn mục nào.'));
            exit;
        }
        noi_dung_back($parent);
        return;
    }

    $ids = array();
    foreach ((array) $_POST['chk_child'] as $val) {
        $v = (int) $val;
        if ($v > 0) {
            $ids[] = $v;
        }
    }
    if (!$ids) {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Chưa chọn mục hợp lệ nào.'));
            exit;
        }
        noi_dung_back($parent);
        return;
    }

    $chuoi = implode(',', $ids);
    if ($d->o_que('delete from #_content where id_code in (' . $chuoi . ')')) {
        $d->o_que('delete from cf_parent where id in (' . $chuoi . ')');
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => true, 'message' => 'Đã xóa ' . count($ids) . ' nội dung.', 'ids' => $ids));
            exit;
        }
        noi_dung_flash('success', 'Đã xóa ' . count($ids) . ' nội dung.');
    } else {
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(array('ok' => false, 'message' => 'Xóa hàng loạt thất bại.'));
            exit;
        }
        noi_dung_flash('error', 'Xóa hàng loạt thất bại.');
    }
    noi_dung_back($parent);
}
