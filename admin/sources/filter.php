<?php
if (!defined('_source')) die('Error');

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

function filter_flash($type, $message)
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function filter_want_stay()
{
    return isset($_POST['capnhat']) && $_POST['capnhat'] === 'capnhat_sua';
}

function filter_back($parent = '', $editId = 0)
{
    global $d;
    if ($editId > 0) {
        $url = 'index.php?p=filter&a=edit&id=' . (int) $editId;
        if ($parent !== '' && $parent !== null) {
            $url .= '&parent=' . urlencode($parent);
        }
        $d->redirect($url);
        return;
    }
    $url = 'index.php?p=filter&a=man';
    if ($parent !== '' && $parent !== null) {
        $url .= '&parent=' . urlencode($parent);
    }
    $d->redirect($url);
}

function showdulieu()
{
    global $d, $items, $filter_groups, $filter_group_options, $filter_edit_group;
    global $rowByLang, $main, $groupOpts, $isEdit, $editId, $parent;

    $filter_groups = $d->o_fet(
        "select * from #_filter where lang='" . LANG . "' order by so_thu_tu ASC, id ASC"
    ) ?: array();

    $filter_group_options = array();
    foreach ($filter_groups as $g) {
        $gid = (int) $g['id_code'];
        $filter_group_options[$gid] = $d->o_fet(
            "select * from #_filter_content where id_loai=" . $gid
            . " and lang='" . LANG . "' order by so_thu_tu ASC, id ASC"
        ) ?: array();
    }

    $filter_edit_group = null;
    if (!empty($_GET['id_loai'])) {
        $gid = (int) $_GET['id_loai'];
        $filter_edit_group = array();
        foreach (get_json('lang') as $lang) {
            $code = $lang['code'];
            $filter_edit_group[$code] = $d->simple_fetch(
                "select * from #_filter where id_code=" . $gid . " and lang='" . $code . "'"
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
        if ($isEdit) {
            $rowByLang[$code] = $d->simple_fetch(
                "select * from #_filter_content where id_code=" . $editId . " and lang='" . $code . "'"
            ) ?: array();
        } else {
            $rowByLang[$code] = array();
        }
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
    foreach ($filter_groups as $g) {
        $groupOpts[(string) $g['id_code']] = $g['ten'];
    }
}

function luunhom($id_module)
{
    global $d;
    if ($d->checkPermission_edit($id_module) != 1) {
        filter_flash('error', 'Bạn không có quyền chỉnh sửa.');
        filter_back();
        return;
    }

    $id_loai   = isset($_GET['id_loai']) ? (int) $_GET['id_loai'] : 0;
    $hinh_anh  = addslashes(trim((string) ($_POST['hinh_anh'] ?? '')));
    $so_thu_tu = ($_POST['so_thu_tu'] ?? '') !== '' ? (int) $_POST['so_thu_tu'] : 0;
    $tens      = $_POST['ten'] ?? array();
    $noidungs  = $_POST['noi_dung'] ?? array();
    $id_rows   = $_POST['id_row'] ?? array();
    $ten0      = trim((string) ($tens[0] ?? ''));

    if ($ten0 === '') {
        filter_flash('error', 'Vui lòng nhập tên nhóm.');
        filter_back($id_loai ?: '');
        return;
    }

    if ($id_loai > 0) {
        $d->reset();
        $d->setTable('cf_parent');
        $d->setWhere('id', $id_loai);
        $d->update(array('ten' => addslashes($ten0)));

        foreach (get_json('lang') as $key => $value) {
            $data = array(
                'ten'       => $d->clear(addslashes(trim((string) ($tens[$key] ?? '')))),
                'noi_dung'  => $d->clear(addslashes((string) ($noidungs[$key] ?? ''))),
                'so_thu_tu' => $so_thu_tu,
            );
            if ($hinh_anh !== '') {
                $data['hinh_anh'] = $hinh_anh;
            }
            $rid = (int) ($id_rows[$key] ?? 0);
            if ($rid > 0) {
                $d->reset();
                $d->setTable('#_filter');
                $d->setWhere('id', $rid);
                $d->update($data);
            }
        }
        filter_flash('success', 'Đã cập nhật nhóm filter.');
    } else {
        $d->reset();
        $d->setTable('cf_parent');
        $id_code = $d->insert(array('ten' => addslashes($ten0), 'type' => 2));
        if (!$id_code) {
            filter_flash('error', 'Thêm nhóm thất bại.');
            filter_back();
            return;
        }
        foreach (get_json('lang') as $key => $value) {
            $d->reset();
            $d->setTable('#_filter');
            $d->insert(array(
                'ten'       => $d->clear(addslashes(trim((string) ($tens[$key] ?? '')))),
                'noi_dung'  => $d->clear(addslashes((string) ($noidungs[$key] ?? ''))),
                'hinh_anh'  => $hinh_anh,
                'hien_thi'  => 1,
                'so_thu_tu' => $so_thu_tu,
                'id_code'   => $id_code,
                'lang'      => $value['code'],
            ));
        }
        filter_flash('success', 'Đã thêm nhóm filter.');
    }
    filter_back();
}

function xoanhom($id_module)
{
    global $d;
    if ($d->checkPermission_dele($id_module) != 1) {
        filter_flash('error', 'Bạn không có quyền xóa.');
        filter_back();
        return;
    }
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        filter_back();
        return;
    }
    $sub = $d->o_fet("select id_code from #_filter_content where id_loai='" . $id . "'") ?: array();
    foreach ($sub as $row) {
        $d->o_que('delete from cf_parent where id=' . (int) $row['id_code']);
    }
    $d->o_que('delete from #_filter_content where id_loai=' . $id);
    $d->reset();
    $d->setTable('#_filter');
    $d->setWhere('id_code', $id);
    $d->delete();
    $d->o_que('delete from cf_parent where id=' . $id);
    filter_flash('success', 'Đã xóa nhóm filter.');
    filter_back();
}

function luudulieu($id_module)
{
    global $d;
    $parent = $_REQUEST['parent'] ?? '';
    if ($d->checkPermission_edit($id_module) != 1) {
        filter_flash('error', 'Bạn không có quyền chỉnh sửa.');
        filter_back($parent);
        return;
    }

    $id        = $_REQUEST['id'] ?? '';
    $hinh_anh  = addslashes(trim((string) ($_POST['hinh_anh'] ?? '')));
    $id_loai   = addslashes(trim((string) ($_POST['id_loai'] ?? '0')));
    $so_thu_tu = ($_POST['so_thu_tu'] ?? '') !== '' ? (int) $_POST['so_thu_tu'] : 0;
    $hien_thi  = isset($_POST['hien_thi']) ? 1 : 0;
    $tens      = $_POST['ten'] ?? array();
    $noidungs  = $_POST['noi_dung'] ?? array();
    $id_rows   = $_POST['id_row'] ?? array();
    $ten0      = trim((string) ($tens[0] ?? ''));

    if ($ten0 === '') {
        filter_flash('error', 'Vui lòng nhập tiêu đề.');
        filter_back($parent);
        return;
    }

    $back = $parent !== '' ? $parent : $id_loai;
    $stay = filter_want_stay();

    if ($id != '') {
        $d->reset();
        $d->setTable('cf_parent');
        $d->setWhere('id', $id);
        if (!$d->update(array('ten' => addslashes($ten0)))) {
            filter_flash('error', 'Cập nhật thất bại.');
            filter_back($back);
            return;
        }
        foreach (get_json('lang') as $key => $value) {
            $rid = (int) ($id_rows[$key] ?? 0);
            if ($rid <= 0) continue;
            $d->reset();
            $d->setTable('#_filter_content');
            $d->setWhere('id', $rid);
            $d->update(array(
                'id_loai'   => $id_loai,
                'hinh_anh'  => $hinh_anh,
                'ten'       => $d->clear(addslashes(trim((string) ($tens[$key] ?? '')))),
                'noi_dung'  => $d->clear(addslashes((string) ($noidungs[$key] ?? ''))),
                'hien_thi'  => $hien_thi,
                'so_thu_tu' => $so_thu_tu,
            ));
        }
        filter_flash('success', 'Đã cập nhật tùy chọn.');
        filter_back($back, $stay ? (int) $id : 0);
        return;
    }

    $d->reset();
    $d->setTable('cf_parent');
    $id_code = $d->insert(array('ten' => addslashes($ten0)));
    if (!$id_code) {
        filter_flash('error', 'Thêm tùy chọn thất bại.');
        filter_back($parent);
        return;
    }
    foreach (get_json('lang') as $key => $value) {
        $d->reset();
        $d->setTable('#_filter_content');
        $d->insert(array(
            'id_loai'   => $id_loai,
            'hinh_anh'  => $hinh_anh,
            'ten'       => $d->clear(addslashes(trim((string) ($tens[$key] ?? '')))),
            'noi_dung'  => $d->clear(addslashes((string) ($noidungs[$key] ?? ''))),
            'hien_thi'  => $hien_thi,
            'id_code'   => $id_code,
            'lang'      => $value['code'],
            'so_thu_tu' => $so_thu_tu,
        ));
    }
    filter_flash('success', 'Đã thêm tùy chọn.');
    filter_back($back, $stay ? (int) $id_code : 0);
}

function xoadulieu($id_module)
{
    global $d;
    $parent = $_GET['parent'] ?? '';
    if ($d->checkPermission_dele($id_module) != 1 || empty($_GET['id'])) {
        filter_flash('error', 'Không xóa được.');
        filter_back($parent);
        return;
    }
    $id = addslashes($_GET['id']);
    $d->reset();
    $d->setTable('#_filter_content');
    $d->setWhere('id_code', $id);
    if ($d->delete()) {
        $d->o_que('delete from cf_parent where id=' . (int) $id);
        filter_flash('success', 'Đã xóa tùy chọn.');
    } else {
        filter_flash('error', 'Xóa thất bại.');
    }
    filter_back($parent);
}

function xoadulieu_mang($id_module)
{
    global $d;
    $parent = $_REQUEST['parent'] ?? '';
    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        filter_back($parent);
        return;
    }
    $ids = array();
    foreach ((array) $_POST['chk_child'] as $v) {
        if ((int) $v > 0) $ids[] = (int) $v;
    }
    if ($ids) {
        $chuoi = implode(',', $ids);
        $d->o_que('delete from #_filter_content where id_code in (' . $chuoi . ')');
        $d->o_que('delete from cf_parent where id in (' . $chuoi . ')');
        filter_flash('success', 'Đã xóa ' . count($ids) . ' tùy chọn.');
    }
    filter_back($parent);
}
