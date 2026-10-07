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
    case 'save':
        luudulieu($id_module);
        break;
    case 'delete':
        xoadulieu($id_module);
        break;
    default:
        $template = 'index';
}

function text_flash($type, $message)
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $_SESSION['admin_flash'] = array('type' => $type, 'message' => $message);
}

function text_back()
{
    global $d;
    $d->redirect('index.php?p=text&a=man');
}

/** Nhóm cố định: key => nhãn admin */
function text_group_options()
{
    return array(
        'common'   => 'Chung',
        'home'     => 'Trang chủ',
        'product'  => 'Sản phẩm',
        'news'     => 'Tin tức',
        'form'     => 'Form / Liên hệ',
        'checkout' => 'Giỏ hàng / Đặt hàng',
        'account'  => 'Tài khoản',
        'footer'   => 'Footer',
        'button'   => 'Nút / CTA',
        'other'    => 'Khác',
    );
}

function text_normalize_code($code)
{
    $code = strtolower(trim((string) $code));
    $code = preg_replace('/\s+/', '_', $code);
    $code = preg_replace('/[^a-z0-9_.\-]/', '', $code);
    return $code;
}

function text_normalize_group($group)
{
    $group = strtolower(trim((string) $group));
    return ($group !== '' && isset(text_group_options()[$group])) ? $group : '';
}

/** Map lang code => nội dung từ mảng POST (key = code hoặc index). */
function text_langs_from_post($map)
{
    $out = array();
    $has = false;
    if (!is_array($map)) {
        $map = array();
    }
    foreach (get_json('lang') as $i => $lang) {
        $lc = $lang['code'];
        if (array_key_exists($lc, $map)) {
            $val = trim((string) $map[$lc]);
        } elseif (array_key_exists($i, $map)) {
            $val = trim((string) $map[$i]);
        } else {
            $val = '';
        }
        $out[$lc] = $val;
        if ($val !== '') {
            $has = true;
        }
    }
    return array($out, $has);
}

function text_payload($dataText, $codeVal, $groupVal)
{
    return array(
        'text'       => json_encode($dataText, JSON_UNESCAPED_UNICODE),
        'code'       => $codeVal !== '' ? $codeVal : null,
        'group_code' => $groupVal !== '' ? $groupVal : null,
    );
}

/** true nếu code đã tồn tại (excludeId = bỏ qua khi update). */
function text_code_taken($code, $excludeId = 0)
{
    global $d;
    if ($code === '') {
        return false;
    }
    $sql = "select id from #_text where code = '" . addslashes($code) . "'";
    if ((int) $excludeId > 0) {
        $sql .= ' and id != ' . (int) $excludeId;
    }
    $sql .= ' limit 1';
    $row = $d->simple_fetch($sql);
    return is_array($row) && !empty($row['id']) ? (int) $row['id'] : false;
}

function showdulieu()
{
    global $d, $items, $textGroupOptions;

    $textGroupOptions = text_group_options();
    $items = $d->o_fet('select * from #_text order by id ASC') ?: array();
}

function luudulieu($id_module)
{
    global $d;

    if ($d->checkPermission_edit($id_module) != 1) {
        text_flash('error', 'Bạn không có quyền chỉnh sửa.');
        text_back();
        return;
    }

    // —— Lưu hàng loạt từ bảng ——
    if (!empty($_POST['bulk']) && !empty($_POST['ten']) && is_array($_POST['ten'])) {
        $n = 0;
        foreach ($_POST['ten'] as $tid => $langsMap) {
            $tid = (int) $tid;
            if ($tid <= 0 || !is_array($langsMap)) {
                continue;
            }
            list($dataText,) = text_langs_from_post($langsMap);
            $codeVal  = isset($_POST['code'][$tid]) ? text_normalize_code($_POST['code'][$tid]) : '';
            $groupVal = isset($_POST['group_code'][$tid]) ? text_normalize_group($_POST['group_code'][$tid]) : '';

            $taken = text_code_taken($codeVal, $tid);
            if ($taken) {
                text_flash('error', 'Mã key "' . $codeVal . '" đã dùng cho #' . $taken . '.');
                text_back();
                return;
            }

            $d->reset();
            $d->setTable('#_text');
            $d->setWhere('id', $tid);
            if ($d->update(text_payload($dataText, $codeVal, $groupVal))) {
                $n++;
            }
        }
        text_flash('success', 'Đã lưu ' . $n . ' bản ghi text.');
        text_back();
        return;
    }

    // —— Thêm mới ——
    list($dataText, $hasAny) = text_langs_from_post(isset($_POST['ten']) ? $_POST['ten'] : array());
    if (!$hasAny) {
        text_flash('error', 'Vui lòng nhập ít nhất một bản dịch.');
        text_back();
        return;
    }

    $codeVal  = text_normalize_code($_POST['code'] ?? '');
    $groupVal = text_normalize_group($_POST['group_code'] ?? '');
    $taken = text_code_taken($codeVal);
    if ($taken) {
        text_flash('error', 'Mã key đã tồn tại (#' . $taken . ').');
        text_back();
        return;
    }

    $d->reset();
    $d->setTable('#_text');
    if ($d->insert(text_payload($dataText, $codeVal, $groupVal))) {
        text_flash('success', 'Đã thêm text mới.');
    } else {
        text_flash('error', 'Thêm text thất bại.');
    }
    text_back();
}

function xoadulieu($id_module)
{
    global $d;

    if ($d->checkPermission_dele($id_module) != 1) {
        text_flash('error', 'Bạn không có quyền xóa.');
        text_back();
        return;
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        text_flash('error', 'Không nhận được dữ liệu.');
        text_back();
        return;
    }

    if ($d->o_que('delete from #_text where id = ' . $id)) {
        text_flash('success', 'Đã xóa text #' . $id . '.');
    } else {
        text_flash('error', 'Xóa thất bại.');
    }
    text_back();
}
