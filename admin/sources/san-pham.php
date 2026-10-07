<?php
if (!defined('_source')) die("Error");

require_once __DIR__ . '/services/VariantService.php';
require_once __DIR__ . '/services/ProductRepository.php';
require_once __DIR__ . '/services/ProductService.php';

$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";
$link_option = '';
if (isset($_GET['search'])) {
    $link_option .= '&search=' . addslashes($_GET['search']);
}
if (isset($_GET['key'])) {
    $link_option .= '&key=' . addslashes($_GET['key']);
}
if (isset($_GET['page'])) {
    $link_option .= '&page=' . addslashes($_GET['page']);
}
$row_setting = $d->simple_fetch("select setting from #_module where id = 3 ");
$setting = $row_setting['setting'];
$arrr_setting = json_decode($setting, true);

switch ($a) {
    case "man":
        showdulieu();
        $template = @$_REQUEST['p'] . "/hienthi";
        break;
    case "variation":
        show_variation();
        $template = @$_REQUEST['p'] . "/variation";
        break;
    case "variants":
        show_variants();
        $template = @$_REQUEST['p'] . "/variants";
        break;
    case "add":
        showdulieu();
        $template = @$_REQUEST['p'] . "/them";
        break;
    case "edit":
        showdulieu();
        $template = @$_REQUEST['p'] . "/them";
        break;
    case "save":
        $productService = new ProductService($d);
        $productService->save($id_module, $_POST, $_FILES, $_REQUEST, $link_option, $arrr_setting);
        break;
    case "delete":
        xoadulieu($id_module);
        break;
    case "delete_all":
        xoadulieu_mang($id_module);
        break;
    default:
        $template = "index";
}

function show_variation()
{
    global $d, $data, $data_type_variation, $data_type_sort;
    $data = $d->o_fet("select * from #_thuoctinh where lang='".LANG."' order by id ASC");
    $data_type_variation = [
        'select' => 'Lựa chọn',
        'color' => 'Color',
        'image' => 'Image',
        'label' => 'Label',
    ];
    $data_type_sort = [
        'id' => 'ID',
        'ten' => 'Tên'
    ];
}

function show_variants()
{
    global $d, $variation, $data;
    $variation = $d->simple_fetch("select * from #_thuoctinh where id_code=" . $_GET['variation'] . " and lang='".LANG."' order by id ASC");
    $data = $d->o_fet("select * from #_thuoctinh_giatri where id_thuoctinh=" . $_GET['variation'] . " and lang='".LANG."' order by id ASC");
}

function showdulieu()
{
    global $d, $items, $limit, $loai, $total_page, $total_records, $current_page, $where_search;

    $loai = $d->array_category(0, '', $_GET['loaitin'] ?? 0, 4);

    if ($_REQUEST['a'] == 'man') {
        $where_search = '';
        if (isset($_GET['search']) and $_GET['key'] != '' and $_GET['search'] != '') {
            if ($_GET['search'] == 'loai') {
                $id_code = $_GET['key'];
                $list_id = $id_code . $d->getIdsub($id_code);
                $where_search = " and id_loai in ($list_id)";
                $loai = $d->array_category(0, '', $id_code, 4);
            } else {
                $col = addslashes($_GET['search']);
                $value = addslashes($_GET['key']);
                $where_search = " and $col like '%" . $value . "%' ";
            }
        }

        $limit        = 12;
        $current_page = max(1, (int) ($_GET['page'] ?? 1));
        $start        = ($current_page - 1) * $limit;

        $repo = new ProductRepository($d);
        $items = $repo->getList($where_search, $start, $limit);
        $total_records = $repo->count($where_search);
        $total_page = (int) ceil($total_records / $limit);
    } else {
        if (isset($_REQUEST['id'])) {
            $id = addslashes($_REQUEST['id']);
            $repo = new ProductRepository($d);
            $items = [$repo->findByIdAndLang($id, LANG)];
            $loai = $d->array_category(0, '', $items[0]['id_loai'], 4);
            if (!empty($items[0]['product_attributes'])) {
                $product_attributes = json_decode($items[0]['product_attributes'], true);
            } else {
                $product_attributes = [];
            }
        }
    }
}
function xoadulieu($id_module)
{
    global $d, $link_option;

    if ($d->checkPermission_dele($id_module) == 1) {
        if (!empty($_GET['id'])) {
            $id = (int)$_GET['id'];

            $productService = new ProductService($d);
            if ($productService->delete($id)) {
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            } else {
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            }
        } else {
            $d->alert("Không nhận được dữ liệu!");
            $d->redirect("index.php?p=san-pham&a=man" . $link_option);
        }
    } else {
        $d->redirect("index.php?p=san-pham&a=man" . $link_option);
    }
}

function xoadulieu_mang($id_module)
{
    global $d, $link_option;

    if ($d->checkPermission_dele($id_module) == 1) {
        if (isset($_POST['chk_child']) && !empty($_POST['chk_child'])) {
            $ids = array_map('intval', $_POST['chk_child']);
            $productService = new ProductService($d);
            if ($productService->deleteMany($ids)) {
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            } else {
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("index.php?p=san-pham&a=man" . $link_option);
            }
        } else {
            $d->alert("Không nhận được dữ liệu!");
            $d->redirect("index.php?p=san-pham&a=man" . $link_option);
        }
    } else {
        $d->redirect("index.php?p=san-pham&a=man" . $link_option);
    }
}
?>