<?php
if(!defined('_source')) die('Error');
$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : '';

$link_option = '';
foreach (['search', 'key', 'page'] as $k) {
    if (isset($_GET[$k])) {
        $link_option .= '&' . $k . '=' . addslashes($_GET[$k]);
    }
}

switch($a){
    case 'man':
        showdulieu();
        $template = @$_REQUEST['p'].'/hienthi';
        break;
    case 'add':
        showdulieu();
        $template = @$_REQUEST['p'].'/them';
        break;
    case 'edit':
        showdulieu();
        $template = @$_REQUEST['p'].'/them';
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

function showdulieu(){
    global $d, $items, $loai, $total_page, $limit, $total_records, $current_page, $per_page, $offset, $where_search, $products, $selectedProducts;

    $loai = $d->array_category(0,'','',2);
    $where_search = '';

    if($_REQUEST['a'] == 'man'){
        if(!empty($_GET['search']) && !empty($_GET['key'])){
            $allowedCols = ['ten'];
            $colName = in_array($_GET['search'], $allowedCols, true) ? $_GET['search'] : 'ten';
            $where_search = ' and ' . $colName . ' like \'%' . addslashes($_GET['key']) . '%\' ';
        }

        $limit = 14;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $base = 'select fs.*, (select count(*) from #_flash_sale_sp where flash_sale_id = fs.id) as sp_count from #_flash_sale fs where 1=1 ' . $where_search . ' order by fs.id desc';
        $items = $d->o_fet($base . ' limit ' . $offset . ', ' . $limit);

        $total_records = $d->num_rows(str_replace('select fs.*,', 'select fs.id,', $base));
        $total_page    = (int) ceil($total_records / $limit);
        $current_page  = $page;
        $per_page      = $limit;
    } else {
        if($_REQUEST['a'] == 'add'){
            $items = [];
            $products = [];
            $selectedProducts = [];
        } elseif(isset($_REQUEST['id'])){
            @$id = addslashes($_REQUEST['id']);
            $items = $d->simple_fetch('select * from #_flash_sale where id =  \''.$id.'\' ');
            $products = $d->o_fet('select id, id_code, ten, gia from #_sanpham where lang = \'vi\' and hien_thi = 1 order by ten asc');
            $selected = $d->o_fet('select sanpham_id, gia_flash_sale from #_flash_sale_sp where flash_sale_id = ' . (int)$id);
            $selectedProducts = [];
            foreach($selected as $s){
                $selectedProducts[(int)$s['sanpham_id']] = (int)$s['gia_flash_sale'];
            }
        }
    }
}

function luudulieu($id_module){
    global $d, $link_option, $arrr_setting;

    if($d->checkPermission_edit($id_module) != 1){
        $d->redirect('index.php?p=flash-sale&a=man&loaitin='.@$_GET['loaitin']);
        return;
    }

    $id = (isset($_REQUEST['id'])) ? addslashes($_REQUEST['id']) : '';
    $newId = null;

    $data = [
        'ten'       => $d->clear(addslashes($_POST['ten'])),
        'tu_ngay'   => $d->clear(addslashes($_POST['tu_ngay'])),
        'den_ngay'  => $d->clear(addslashes($_POST['den_ngay'])),
        'hien_thi'  => isset($_POST['hien_thi']) ? '1' : '0',
    ];

    if($id != ''){
        $d->reset();
        $d->setTable('#_flash_sale');
        $d->setWhere('id', $id);
        if($d->update($data)){
            $newId = $id;
        } else {
            $d->alert('Cập nhật dữ liệu bị lỗi!');
            $d->redirect('Cập nhật dữ liệu bị lỗi', 'index.php?p=flash-sale&a=man'.$link_option);
            return;
        }
    } else {
        $d->reset();
        $d->setTable('#_flash_sale');
        $newId = $d->insert($data);
    }

    // Lưu sản phẩm trong chương trình
    if(!empty($newId)){
        $d->safeExec('delete from #_flash_sale_sp where flash_sale_id = ?', [$newId]);
        if(!empty($_POST['sp']) && is_array($_POST['sp'])){
            foreach($_POST['sp'] as $spId => $checked){
                $spId = (int) $spId;
                if($spId <= 0 || empty($checked)){
                    continue;
                }
                $gia = isset($_POST['gia_flash_sale'][$spId]) ? (int) str_replace(',', '', $_POST['gia_flash_sale'][$spId]) : 0;
                $d->safeExec('insert into #_flash_sale_sp (flash_sale_id, sanpham_id, gia_flash_sale) values (?, ?, ?)', [$newId, $spId, $gia]);
            }
        }
    }

    $saveAction = $_POST['capnhat'] ?? 'capnhat';
    if($saveAction == 'capnhat_sua' && !empty($newId)){
        $d->redirect('index.php?p=flash-sale&a=edit&id=' . $newId . $link_option);
    } else {
        $d->redirect('index.php?p=flash-sale&a=man' . $link_option);
    }
}

function xoadulieu($id_module){
    global $d, $link_option;

    if($d->checkPermission_dele($id_module)==1){
        if(isset($_GET['id'])){
            $id = (int) $_GET['id'];
            $d->safeExec('delete from #_flash_sale_sp where flash_sale_id = ?', [$id]);
            $d->reset();
            $d->setTable('#_flash_sale');
            $d->setWhere('id', $id);
            if($d->delete()){
                $d->redirect('index.php?p=flash-sale&a=man'.$link_option);
            } else {
                $d->alert('Xóa dữ liệu bị lỗi!');
                $d->redirect('Xóa dữ liệu bị lỗi', 'index.php?p=flash-sale&a=man'.$link_option);
            }
        } else {
            $d->alert('Không nhận được dữ liệu!');
            $d->redirect('Xóa dữ liệu bị lỗi', 'index.php?p=&a=man');
        }
    } else {
        $d->redirect('index.php?p=flash-sale&a=man'.$link_option);
    }
}

function xoadulieu_mang($id_module){
    global $d, $link_option;

    if($d->checkPermission_dele($id_module)==1){
        if(isset($_POST['chk_child']) && is_array($_POST['chk_child'])){
            $ids = array_map('intval', $_POST['chk_child']);
            $chuoi = implode(',', $ids);
            if($chuoi === ''){
                $d->redirect('index.php?p=flash-sale&a=man'.$link_option);
            }
            $d->safeExec('delete from #_flash_sale_sp where flash_sale_id in ('.$chuoi.')');
            if($d->o_que('delete from #_flash_sale where id in ('.$chuoi.')')){
                $d->redirect('index.php?p=flash-sale&a=man'.$link_option);
            } else {
                $d->alert('Không nhận được dữ liệu!');
                $d->redirect('Xóa dữ liệu bị lỗi', 'index.php?p=flash-sale&a=man'.$link_option);
            }
        } else {
            $d->redirect('index.php?p=flash-sale&a=man'.$link_option);
        }
    } else {
        $d->redirect('index.php?p=flash-sale&a=man'.$link_option);
    }
}
