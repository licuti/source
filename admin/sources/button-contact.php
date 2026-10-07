<?php
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
    case 'add':
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

function button_contact_flash($type, $message)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function button_contact_back()
{
    global $d;
    $d->redirect('index.php?p=button-contact&a=man');
}

function showdulieu()
{
    global $d, $items;
    $items = $d->o_fet('select * from #_button_contact order by sort ASC, id ASC') ?: array();
}

function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        button_contact_flash('error', 'Bạn không có quyền chỉnh sửa.');
        button_contact_back();
        return;
    }

    $images = isset($_POST['image']) && is_array($_POST['image']) ? $_POST['image'] : array();
    $ids    = isset($_POST['id']) && is_array($_POST['id']) ? $_POST['id'] : array();
    $names  = isset($_POST['name']) && is_array($_POST['name']) ? $_POST['name'] : array();
    $links  = isset($_POST['link']) && is_array($_POST['link']) ? $_POST['link'] : array();
    $targets = isset($_POST['target']) && is_array($_POST['target']) ? $_POST['target'] : array();
    $bg     = isset($_POST['color_background']) && is_array($_POST['color_background']) ? $_POST['color_background'] : array();
    $bga    = isset($_POST['color_background_alpha']) && is_array($_POST['color_background_alpha']) ? $_POST['color_background_alpha'] : array();
    $fg     = isset($_POST['color_text']) && is_array($_POST['color_text']) ? $_POST['color_text'] : array();

    $n = 0;
    $sort = 0;
    foreach ($images as $key => $image) {
        $name = isset($names[$key]) ? trim((string) $names[$key]) : '';
        $link = isset($links[$key]) ? trim((string) $links[$key]) : '';
        // bo dong trong hoan toan
        if ($name === '' && $link === '' && trim((string) $image) === '') {
            continue;
        }

        $rowId = isset($ids[$key]) ? (int) $ids[$key] : 0;
        $target = isset($targets[$key]) && $targets[$key] === '_blank' ? '_blank' : '_self';

        $data = array(
            'image'                  => addslashes(trim((string) $image)),
            'name'                   => $d->clear(addslashes($name)),
            'link'                   => $d->clear(addslashes($link)),
            'target'                 => $target,
            'color_background'       => addslashes(trim((string) (isset($bg[$key]) ? $bg[$key] : ''))),
            'color_background_alpha' => addslashes(trim((string) (isset($bga[$key]) ? $bga[$key] : ''))),
            'color_text'             => addslashes(trim((string) (isset($fg[$key]) ? $fg[$key] : ''))),
            'sort'                   => $sort,
        );
        $sort++;

        $d->reset();
        $d->setTable('#_button_contact');
        if ($rowId > 0) {
            $d->setWhere('id', $rowId);
            if ($d->update($data)) {
                $n++;
            }
        } else {
            if ($d->insert($data)) {
                $n++;
            }
        }
    }

    button_contact_flash('success', 'Đã lưu ' . $n . ' nút liên hệ.');
    button_contact_back();
}
