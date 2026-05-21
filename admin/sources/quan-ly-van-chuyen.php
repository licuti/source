<?php
if (!defined('_source')) die("Error");
$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";

switch ($a) {
    case "man":
        showdulieu();
        $template = "quan-ly-van-chuyen/hienthi";
        break;
    case "add":
        showdulieu();
        $template = "quan-ly-van-chuyen/them";
        break;
    case "edit":
        showdulieu();
        $template = "quan-ly-van-chuyen/them";
        break;
    case "save":
        luudulieu();
        break;
    case "delete":
        xoadulieu();
        break;
    case "save_settings":
        luu_settings_ship();
        break;
    default:
        $template = "index";
}

function showdulieu()
{
    global $d, $items, $item, $tinhs, $huyens, $xas, $shipping_settings;
    if ($_REQUEST['a'] == 'man') {
        // Lấy cấu hình chung từ db_thongtin
        $shipping_settings = $d->simple_fetch("SELECT free_ship_threshold, default_ship_phi, ship_base_weight, ship_rounding FROM #_thongtin WHERE lang = '".LANG."' LIMIT 1");

        $items = $d->o_fet("SELECT s.*, t.ten as ten_tinh, h.ten as ten_huyen, x.ten as ten_xa 
                            FROM #_ship s 
                            LEFT JOIN #_thanhpho t ON s.id_tinh = t.code 
                            LEFT JOIN #_huyen h ON (s.id_huyen = h.code AND s.id_tinh = h.code_tinh AND s.id_huyen <> '')
                            LEFT JOIN #_xa x ON (s.id_xa = x.code AND s.id_huyen = x.code_huyen AND s.id_xa <> '')
                            ORDER BY s.so_thu_tu ASC, s.id DESC");
    } else {
        if (isset($_REQUEST['id'])) {
            $item = $d->simple_fetch("SELECT * FROM #_ship WHERE id = " . (int)$_REQUEST['id']);
        }
        $tinhs = $d->getTinh();
        if (isset($item['id_tinh'])) {
            $huyens = $d->getHuyen($item['id_tinh']);
        }
        if (isset($item['id_huyen'])) {
            $xas = $d->getXa($item['id_huyen']);
        }
    }
}

function luudulieu()
{
    global $d;
    $id = (int)$_REQUEST['id'];
    $data = [
        'id_tinh'   => addslashes($_POST['id_tinh']),
        'id_huyen'  => addslashes($_POST['id_huyen']),
        'id_xa'     => addslashes($_POST['id_xa']),
        'phi_ship'  => (float)str_replace(',', '', $_POST['phi_ship']),
        'phi_extra_kg' => (float)str_replace(',', '', $_POST['phi_extra_kg']),
        'free_weight'  => (float)$_POST['free_weight'],
        'ghi_chu'   => addslashes($_POST['ghi_chu']),
        'hien_thi'  => isset($_POST['hien_thi']) ? 1 : 0,
        'so_thu_tu' => (int)$_POST['so_thu_tu']
    ];

    $d->reset();
    $d->setTable('#_ship');
    if ($id > 0) {
        $d->setWhere('id', $id);
        if ($d->update($data)) {
            $d->location("index.php?p=quan-ly-van-chuyen&a=man");
        } else {
            $d->alert("Cập nhật thất bại!");
        }
    } else {
        if ($d->insert($data)) {
            $d->location("index.php?p=quan-ly-van-chuyen&a=man");
        } else {
            $d->alert("Thêm mới thất bại!");
        }
    }
}

function xoadulieu()
{
    global $d;
    $id = (int)$_REQUEST['id'];
    if ($id > 0) {
        $d->reset();
        $d->setTable('#_ship');
        $d->setWhere('id', $id);
        $d->delete();
    }
    $d->location("index.php?p=quan-ly-van-chuyen&a=man");
}

function luu_settings_ship()
{
    global $d;
    $data_settings = [
        'free_ship_threshold' => (float)str_replace(',', '', $_POST['free_ship_threshold']),
        'default_ship_phi'    => (float)str_replace(',', '', $_POST['default_ship_phi']),
        'ship_base_weight'    => (float)$_POST['ship_base_weight'],
        'ship_rounding'       => (int)$_POST['ship_rounding']
    ];

    $d->reset();
    $d->setTable('#_thongtin');
    $d->setWhere('lang', LANG);
    if ($d->update($data_settings)) {
        $d->location("index.php?p=quan-ly-van-chuyen&a=man");
    } else {
        $d->alert("Cập nhật cấu hình thất bại!");
        $d->location("index.php?p=quan-ly-van-chuyen&a=man");
    }
}
?>
