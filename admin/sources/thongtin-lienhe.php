<?php
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
    case 'edit':
        showdulieu();
        $template = $_REQUEST['p'] . '/hienthi';
        break;
    case 'save':
        luudulieu($id_module);
        break;
    default:
        $template = 'index';
}

function thongtin_lienhe_flash($type, $message)
{
    if (!isset($_SESSION) || session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function showdulieu()
{
    global $d, $items, $rowByLang;

    $rowByLang = array();
    foreach (get_json('lang') as $lang) {
        $code = $lang['code'];
        $row = $d->simple_fetch("select * from #_thongtin where lang = '" . $code . "'");
        $rowByLang[$code] = is_array($row) ? $row : array();
    }
    $items = $d->o_fet('select * from #_thongtin where id = 1') ?: array();
}

function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        thongtin_lienhe_flash('error', 'Bạn không có quyền chỉnh sửa.');
        $d->redirect('index.php?p=thongtin-lienhe&a=man');
        return;
    }

    $fields = array(
        'favicon', 'icon_share', 'company', 'hotline', 'address', 'office',
        'dien_thoai', 'website', 'twitter', 'facebook', 'linkedin', 'email',
        'zalo', 'thoi_gian', 'coppy_right', 'map', 'link_map', 'messenger',
        'youtube', 'pinterest', 'instagram', 'skype', 'whatsapp', 'telegram',
        'iframe', 'tiktok', 'shoppe', 'site_key', 'secret_key',
        'pancake_api_key', 'pancake_shop_id', 'pancake_api_url', 'pancake_sync_to_crm',
        'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_secure'
    );
    $shellFields = array('map', 'link_map');

    foreach (get_json('lang') as $key => $value) {
        $code = $value['code'];
        $row  = $d->simple_fetch("select * from #_thongtin where lang = '" . $code . "'");

        $data = array('lang' => $code);
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $raw = is_array($_POST[$f]) ? (string) ($_POST[$f][$key] ?? '') : (string) $_POST[$f];
            } else {
                $raw = '';
            }
            if (in_array($f, $shellFields, true) && function_exists('check_shell')) {
                $data[$f] = check_shell(addslashes($raw));
            } else {
                $data[$f] = $d->clear(addslashes($raw));
            }
        }
        // favicon / icon_share: path file, clear nhe
        $rawFavicon = isset($_POST['favicon']) ? (is_array($_POST['favicon']) ? ($_POST['favicon'][$key] ?? '') : $_POST['favicon']) : '';
        $rawIconShare = isset($_POST['icon_share']) ? (is_array($_POST['icon_share']) ? ($_POST['icon_share'][$key] ?? '') : $_POST['icon_share']) : '';
        $data['favicon']    = addslashes((string)$rawFavicon);
        $data['icon_share'] = addslashes((string)$rawIconShare);

        $d->reset();
        $d->setTable('#_thongtin');
        if (is_array($row) && !empty($row['id'])) {
            $d->setWhere('id', $row['id']);
            $d->update($data);
        } else {
            $d->insert($data);
        }
    }

    thongtin_lienhe_flash('success', 'Đã cập nhật thông tin liên hệ.');
    $d->redirect('index.php?p=thongtin-lienhe&a=man');
}
