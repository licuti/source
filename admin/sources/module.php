<?php
if (!defined('_source')) die("Error");

$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "man";

switch ($a) {
    case "save":
        luuModule();
        break;
    case "delete":
        xoaModule();
        break;
    case "man":
    default:
        hienThiModule();
        $template = $_REQUEST['p'] . "/hienthi";
        break;
}

/**
 * Chuẩn bị dữ liệu cho trang danh sách module.
 * Cây 2 cấp: parent = 0 là danh mục chính, còn lại là module con.
 */
function hienThiModule()
{
    global $d, $items, $row_item;

    $items = $d->o_fet("select * from #_module_admin where parent = 0 order by so_thu_tu asc, id asc");

    $row_item = [];
    if (isset($_GET['id']) && (int) $_GET['id'] > 0) {
        $id = (int) $_GET['id'];
        $row = $d->o_fet("select * from #_module_admin where id = " . $id);
        if (!empty($row)) {
            $row_item = $row[0];
        }
    }
}

/**
 * Xử lý thêm / sửa module.
 */
function luuModule()
{
    global $d, $id_module;

    if ($d->checkPermission_edit($id_module) != 1) {
        admin_flash('error', 'Bạn không có quyền thực hiện thao tác này.');
        header("location: index.php?p=module&a=man");
        exit;
    }

    if (!admin_csrf_verify()) {
        admin_flash('error', 'Phiên làm việc hết hạn, vui lòng thử lại.');
        header("location: index.php?p=module&a=man");
        exit;
    }

    if (empty($_POST['themmoi']) && empty($_POST['capnhat'])) {
        header("location: index.php?p=module&a=man");
        exit;
    }

    $data = [];
    $data['parent']    = (int) ($_POST['parent'] ?? 0);
    $data['name']      = $d->clear(addslashes($_POST['name'] ?? ''));
    $data['alias']     = $d->clear(addslashes($_POST['alias'] ?? ''));
    $data['so_thu_tu'] = (int) ($_POST['so_thu_tu'] ?? 0);

    if ($data['name'] === '' || $data['alias'] === '') {
        admin_flash('error', 'Tên module và file xử lý không được để trống.');
        header("location: index.php?p=module&a=man");
        exit;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    if ($id > 0) {
        $d->setTable('#_module_admin');
        $d->setWhere('id', $id);
        $d->update($data);
        admin_flash('success', 'Cập nhật module thành công.');
    } else {
        $data['hien_thi'] = 1;
        $d->setTable('#_module_admin');
        if ($d->insert($data)) {
            if ($data['parent'] > 0) {
                $dir = "templates/" . $data['alias'];
                if (!file_exists($dir)) {
                    mkdir($dir, 0700);
                }
            }
        }
        admin_flash('success', 'Thêm module thành công.');
    }

    header("location: index.php?p=module&a=man");
    exit;
}

/**
 * Xử lý xóa module.
 * Giữ nguyên logic cũ: nếu là module cha (parent=0) chỉ xóa bản thân,
 * nếu là module con thì xóa cả các module con của nó.
 */
function xoaModule()
{
    global $d, $id_module;

    if ($d->checkPermission_dele($id_module) != 1) {
        admin_flash('error', 'Bạn không có quyền thực hiện thao tác này.');
        header("location: index.php?p=module&a=man");
        exit;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        header("location: index.php?p=module&a=man");
        exit;
    }

    $row = $d->o_fet("select * from #_module_admin where id = " . $id);
    if (empty($row)) {
        header("location: index.php?p=module&a=man");
        exit;
    }

    $row = $row[0];

    if ($row['parent'] == 0) {
        $d->o_que("delete from #_module_admin where id = " . $id);
    } else {
        $children = $d->o_fet("select * from #_module_admin where parent = " . $id);
        $d->o_que("delete from #_module_admin where id = " . $id);
        foreach ($children as $child) {
            $d->o_que("delete from #_module_admin where id = " . (int) $child['id']);
        }
    }

    admin_flash('success', 'Xóa module thành công.');
    header("location: index.php?p=module&a=man");
    exit;
}
