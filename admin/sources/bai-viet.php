<?php

if (!defined('_source')) die("Error");

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';
$link_option = '';
foreach (['search', 'key', 'page'] as $k) {
    if (isset($_GET[$k])) {
        $link_option .= '&' . $k . '=' . addslashes($_GET[$k]);
    }
}

$row_setting = $d->simple_fetch("select setting from #_module where id = 2 ");
$arrr_setting = json_decode($row_setting['setting'], true);

switch ($a) {
    case 'man':
    case 'add':
    case 'edit':
        showdulieu();
        $template = @$_REQUEST['p'] . '/' . ($a == 'man' ? 'hienthi' : 'them');
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

function showdulieu() {
    global $d, $items, $loai, $total_page, $limit, $total_records, $where_search;

    $loai = $d->array_category(0, '', '', 3);
    $where_search = '';

    if ($_REQUEST['a'] == 'man') {
        if (!empty($_GET['search']) && !empty($_GET['key'])) {
            if ($_GET['search'] == 'loai') {
                $id_code = $_GET['key'];
                $list_id = $id_code . $d->getIdsub($id_code);
                $where_search = " and id_loai in ($list_id)";
                $loai = $d->array_category(0, '', $id_code, 3);
            } else {$allowedCols = ['ten', 'id_code', 'alias', 'title', 'keyword', 'des'];
                $colName = in_array($_GET['search'], $allowedCols, true)
                    ? $_GET['search']
                    : 'ten';
                $where_search = " and $colName like '%" . addslashes($_GET['key']) . "%' ";
            }
        }$limit  = 20;
        $lang   = LANG;
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $base = "select * from #_tintuc where lang='$lang' $where_search order by so_thu_tu asc, cap_nhat desc, id desc";
        $items = $d->o_fet("$base limit $offset, $limit");

        $total_records = $d->num_rows(str_replace('select *', 'select id', $base));
        $total_page    = (int) ceil($total_records / $limit);
        $current_page  = $page;
        $per_page      = $limit;

    } elseif (isset($_REQUEST['id'])) {
        $id = addslashes($_REQUEST['id']);
        $items = $d->o_fet("select * from #_tintuc where id_code='$id' and lang='" . LANG . "'");
        if (!empty($items[0]['id_loai'])) {
            $loai = $d->array_category(0, '', $items[0]['id_loai'], 3);
        }
    }
}

function luudulieu($id_module) {
    global $d, $link_option, $arrr_setting;

    if ($d->checkPermission_edit($id_module) != 1) {
        $d->redirect("index.php?p=bai-viet&a=man" . $link_option);
        return;
    }

    $w = $h = 300;
    $option_resize = 'auto';
    if (!empty($arrr_setting['size_img'])) {
        $size = explode('x', $arrr_setting['size_img']);
        $w = (int) $size[0];
        $h = (int) $size[1];
    }
    if (!empty($arrr_setting['option_resize'])) {
        $option_resize = $arrr_setting['option_resize'];
    }

    $id = isset($_REQUEST['id']) ? addslashes($_REQUEST['id']) : '';
    $isEdit = !empty($id);$ten0 = trim($_POST['ten'][0] ?? '');
    $alias0In = trim($_POST['alias'][0] ?? '');
    $alias0 = $alias0In !== '' ? $alias0In : $ten0;
    $alias0 = $d->createAlias(slugify(addslashes($alias0)));
    if ($alias0 === '') {
        $d->alert('Vui lòng nhập tên bài viết để tạo đường dẫn.');
        $d->redirect('index.php?p=bai-viet&a=man' . $link_option);
        return;
    }
    if ($d->checkLink($alias0, $isEdit ? $id : null) == 0) {
        $alias0 .= '-' . ($isEdit ? rand(0, 9) : rand(10, 999));
    }

    $data0 = ['ten' => addslashes($ten0)];
    $d->reset();
    $d->setTable('cf_parent');

    if ($isEdit) {
        $d->setWhere('id', $id);
        $success = $d->update($data0);
        $id_code = $id;
    } else {
        $id_code = $d->insert($data0);
        $success = ($id_code > 0);
    }

    if ($success) {
        foreach (get_json('lang') as $key => $value) {
            $langCode = $value['code'];
            $rowId = $isEdit ? $_POST['id_row'][$key] : null;

            $alias = $d->createAlias(slugify($d->clear(addslashes($_POST['alias'][$key]))));
            if ($d->checkLink($alias, $isEdit ? $rowId : null) == 0) {
                $alias .= '-' . ($isEdit ? rand(0, 9) : rand(10, 999));
            }

            $tags = addslashes($_POST['tags_hienthi'][$key]);
            $id_tag = '';
            foreach (explode(',', $tags) as $vt) {
                $vt = trim($vt);
                if ($vt === '') continue;
                $kt = $d->safeFetchOne(
                    'select id, ten from #_tags where ten = ? and lang = ?',
                    [$vt, $langCode]
                );
                if (!$kt) {
                    $di = ['hien_thi' => 1, 'ten' => $vt, 'alias' => slugify($vt), 'lang' => $langCode];
                    $d->reset();
                    $d->setTable('#_tags');
                    if ($tId = $d->insert($di)) {
                        $id_tag .= "$tId,";
                    }
                } else {
                    $id_tag .= $kt['id'] . ',';
                }
            }
            $data = [
                'id_loai'       => (int)addslashes($_POST['id_loai']),
                'hinh_anh'      => addslashes($_POST['hinh_anh']),
                'ten'           => $d->clear(addslashes($_POST['ten'][$key])),
                'slug'          => $d->createAlias(slugify(addslashes($_POST['slug'][$key]))),
                'alias'         => $alias,
                'mo_ta'         => $d->clear(addslashes($_POST['mo_ta'][$key])),
                'noi_dung'      => $d->clear(addslashes($_POST['noi_dung'][$key])),
                'title'         => $d->clear(addslashes($_POST['title'][$key])),
                'keyword'       => $d->clear(addslashes($_POST['keyword'][$key])),
                'des'           => addslashes($_POST['des'][$key]),
                'seo_head'      => addslashes($_POST['seo_head'][$key]),
                'seo_body'      => addslashes($_POST['seo_body'][$key]),
                'tags_hienthi'  => addslashes($_POST['tags_hienthi'][$key]),
                'tags'          => trim($id_tag, ','),
                'nofollow'      => isset($_POST['nofollow']) ? 1 : 0,
                'noindex'       => isset($_POST['noindex']) ? 1 : 0,
                'hien_thi'      => isset($_POST['hien_thi']) ? 1 : 0,
                'so_thu_tu'     => $_POST['so_thu_tu'] ?: 0,
                'cap_nhat'      => time(),
                'id_user'       => (int)($_POST['id_user'] ?? ($_SESSION['id_user'] ?? 0)),
            ];

            if (!$isEdit) {
                $data['ngay_dang'] = time();
                $data['id_code']   = $id_code;
                $data['lang']      = $langCode;
            } else {$raw = $_POST['ngay_dang'] ?? '';
                if ($raw !== '') {
                    $ts = strtotime($raw);
                    $data['ngay_dang'] = $ts !== false ? $ts : ($row['ngay_dang'] ?? time());
                }
            }

            $d->reset();
            $d->setTable('#_tintuc');
            if ($isEdit) {
                $d->setWhere('id', $rowId);
                $d->update($data);
                if (!empty($rowId) && !empty($alias)) {
                    sync_menu_item_url('tintuc', (int) $rowId, $alias);
                }
            } else {
                $d->insert($data);
            }
        }

        $redirectUrl = ($_POST['capnhat'] == 'capnhat_sua')
            ? "index.php?p=bai-viet&a=edit&id=$id_code$link_option"
            : "index.php?p=bai-viet&a=man$link_option";
        $d->redirect($redirectUrl);

    } else {
        $msg = $isEdit ? 'Cập nhật dữ liệu bị lỗi!' : 'Thêm dữ liệu bị lỗi!';
        $d->alert($msg);
        $d->redirect($msg, "index.php?p=bai-viet&a=man" . $link_option);
    }
}

function xoadulieu($id_module) {
    global $d, $link_option;

    if ($d->checkPermission_dele($id_module) != 1 || !isset($_GET['id'])) {
        $d->redirect("index.php?p=bai-viet&a=man" . $link_option);
        return;
    }

    $id = addslashes($_GET['id']);
    $img = $d->o_fet("select hinh_anh from #_tintuc where id_code='$id' limit 1");
    if (!$img) {
        $d->alert('Không nhận được dữ liệu!');
        $d->redirect('Xóa dữ liệu bị lỗi', "index.php?p=bai-viet&a=man" . $link_option);
        return;
    }

    @unlink("../uploads/images/{$img[0]['hinh_anh']}");
    @unlink("../uploads/images/thumb/{$img[0]['hinh_anh']}");

    $d->reset();
    $d->setTable('#_tintuc');
    $d->setWhere('id_code', $id);

    if ($d->delete()) {
        $d->o_que("delete from cf_parent where id=$id");
        $d->redirect("index.php?p=bai-viet&a=man" . $link_option);
    } else {
        $d->alert('Xóa dữ liệu bị lỗi!');
        $d->redirect('Xóa dữ liệu bị lỗi', "index.php?p=bai-viet&a=man" . $link_option);
    }
}

function xoadulieu_mang($id_module) {
    global $d, $link_option;

    if ($d->checkPermission_dele($id_module) != 1 || empty($_POST['chk_child'])) {
        $d->redirect("index.php?p=bai-viet&a=man" . $link_option);
        return;
    }

    $ids = array_map('addslashes', $_POST['chk_child']);
    $chuoi = implode(',', $ids);
    $lang = LANG;

    $imgs = $d->o_fet("select hinh_anh from #_tintuc where id_code in ($chuoi) and lang='$lang'");

    if ($d->o_que("delete from #_tintuc where id_code in ($chuoi)")) {
        $d->o_que("delete from cf_parent where id in ($chuoi)");
        foreach ($imgs as $im) {
            if (!empty($im['hinh_anh'])) {
                @unlink("../uploads/images/{$im['hinh_anh']}");
                @unlink("../uploads/images/thumb/{$im['hinh_anh']}");
            }
        }
        $d->redirect("index.php?p=bai-viet&a=man" . $link_option);
    } else {
        $d->alert('Không nhận được dữ liệu!');
        $d->redirect('Xóa dữ liệu bị lỗi', "index.php?p=bai-viet&a=man" . $link_option);
    }
}
