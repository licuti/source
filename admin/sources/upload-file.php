<?php
if(!defined('_source')) die("Error");
$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";
$link_option ='';
if(isset($_GET['search'])){
    $link_option.='&search='.addslashes($_GET['search']);
}
if(isset($_GET['key'])){
    $link_option.='&key='.addslashes($_GET['key']);
}
if(isset($_GET['page'])){
    $link_option.='&page='.addslashes($_GET['page']);
}
switch($a){
	case "man":
		showdulieu();
		$template = @$_REQUEST['p']."/hienthi";
		break;
	case "add":
		showdulieu();
		$template = @$_REQUEST['p']."/them";
		break;
	case "edit":
		showdulieu();
		$template = @$_REQUEST['p']."/them";
		break;
	case "save":
		luudulieu($id_module);
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

function showdulieu(){
	global $d, $items, $loai, $total_page, $limit, $total_records, $where_search, $current_page;

	// db_module: id=12 title='Upload file' templates='files'
	if ($_REQUEST['a'] == 'man') {
            $where_search = '';
            $loaiId = 0;

            if (!empty($_GET['loai']) && (int) $_GET['loai'] > 0) {
                $loaiId = (int) $_GET['loai'];
                $list_id = $loaiId . $d->getIdsub($loaiId);
                $where_search .= " and id_loai in ($list_id)";
            }

            if (!empty($_GET['search']) && ($_GET['key'] ?? '') !== '' && $_GET['search'] !== 'loai') {
                $col   = addslashes($_GET['search']);
                $value = addslashes($_GET['key']);
                $allowed = ['ten', 'id_code', 'file', 'loai_file', 'link_khac'];
                if (in_array($col, $allowed, true)) {
                    $where_search .= " and $col like '%" . $value . "%' ";
                }
            }

            $loai = $d->array_category(0, '= ', (string) $loaiId, 12);

            $limit = 20;
            $current_page = max(1, (int) ($_GET['page'] ?? 1));
            $start = ($current_page - 1) * $limit;
            $items = $d->o_fet("select * from #_files where lang ='" . LANG . "' $where_search order by so_thu_tu asc, id desc limit $start, $limit");
            $total_records = $d->num_rows("select id from #_files where lang ='" . LANG . "' $where_search order by so_thu_tu asc, id desc");
            $total_page = ceil($total_records / $limit);
	} else {
            if (isset($_REQUEST['id'])) {
                $id = addslashes($_REQUEST['id']);
                $items = $d->o_fet("select * from #_files where id_code =  '" . $id . "' and lang = '" . LANG . "'");
                $loai = $d->array_category(0, '', $items[0]['id_loai'] ?? 0, 12);
            } else {
                $loai = $d->array_category(0, '', 0, 12);
            }
	}
}

function luudulieu($id_module){
    global $d;
    global $link_option;
    if($d->checkPermission_edit($id_module)==1){
	$id = (isset($_REQUEST['id'])) ? addslashes($_REQUEST['id']) : "";
	// file path from File Manager ($_POST['file'])
	if($id != '')
	{
            // File từ File Manager (hidden name=file) — không upload local nữa
            $file_name = trim((string) ($_POST['file'] ?? ''));
            $file_name = $file_name !== '' ? addslashes($file_name) : '';

            $id_loai    =   addslashes($_POST['id_loai']);
            $so_thu_tu  =   $_POST['so_thu_tu'] !='' ? $_POST['so_thu_tu'] : 0;
            $hien_thi   =   isset($_POST['hien_thi']) ? 1 : 0;
            
            $data0['ten']   =   addslashes($_POST['ten'][0]);
            $d->reset();
            $d->setTable('cf_parent');
            $d->setWhere('id',$id);
            if($d->update($data0)) {
                foreach (get_json('lang') as $key => $value) {
                    $data = array();
                    $data['id_loai']   = (int) $id_loai;
                    $data['ten']       = $d->clear(addslashes($_POST['ten'][$key] ?? ''));
                    $data['mo_ta']     = $d->clear(addslashes($_POST['mo_ta'][$key] ?? ''));
                    $alias = trim((string) ($_POST['alias'][$key] ?? ''));
                    if ($alias === '') {
                        $alias = function_exists('slugify') ? slugify($data['ten']) : preg_replace('/\s+/', '-', strtolower($data['ten']));
                    }
                    $data['alais']     = addslashes($alias);
                    $data['link_khac'] = $d->clear(addslashes($_POST['link_khac'] ?? ''));
                    $loai_post = addslashes($_POST['loai_file'] ?? '');
                    if ($file_name !== '') {
                        $data['file'] = $file_name;
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $data['loai_file'] = $ext !== '' ? $ext : ($loai_post !== '' ? $loai_post : 'file');
                    }
                    if ($data['link_khac'] !== '' && $loai_post !== '') {
                        $data['loai_file'] = $loai_post;
                    }
                    if (!isset($data['loai_file']) || $data['loai_file'] === '') {
                        $data['loai_file'] = $loai_post !== '' ? $loai_post : 'file';
                    }
                    $data['hien_thi']  = (int) $hien_thi;
                    $data['so_thu_tu'] = (int) $so_thu_tu;
                    $d->reset();
                    $d->setTable('#_files');
                    $d->setWhere('id', $_POST['id_row'][$key]);
                    $d->update($data);
                }
                if (isset($_POST['capnhat']) && $_POST['capnhat'] == 'capnhat_sua') {
                    $d->redirect("index.php?p=upload-file&a=edit&id=" . $id . $link_option);
                } else {
                    $d->redirect("index.php?p=upload-file&a=man" . $link_option);
                }
            }else{
                $d->alert("Cập nhật dữ liệu bị lỗi!");
                $d->redirect("Cập nhật dữ liệu bị lỗi", "index.php?p=upload-file&a=man".$link_option);
            }
	}
	else
	{
            // File từ File Manager (hidden name=file)
            $file_name = trim((string) ($_POST['file'] ?? ''));
            $file_name = $file_name !== '' ? addslashes($file_name) : '';

            $id_loai        =   addslashes($_POST['id_loai']);
            $so_thu_tu      =   $_POST['so_thu_tu'] !='' ? $_POST['so_thu_tu'] : 0;
            $hien_thi       =   isset($_POST['hien_thi']) ? 1 : 0;
            
            $data0['ten']   =   addslashes($_POST['ten'][0]);
            $d->reset();
            $d->setTable('cf_parent');
            if($id_code = $d->insert($data0)) {
                foreach (get_json('lang') as $key => $value) {
                    $data = array();
                    $data['id_loai']   = (int) $id_loai;
                    $data['ten']       = $d->clear(addslashes($_POST['ten'][$key] ?? ''));
                    $data['mo_ta']     = $d->clear(addslashes($_POST['mo_ta'][$key] ?? ''));
                    $alias = trim((string) ($_POST['alias'][$key] ?? ''));
                    if ($alias === '') {
                        $alias = function_exists('slugify') ? slugify($data['ten']) : preg_replace('/\s+/', '-', strtolower($data['ten']));
                    }
                    $data['alais']     = addslashes($alias);
                    $data['file']      = $file_name !== '' ? $file_name : '';
                    $data['link_khac'] = $d->clear(addslashes($_POST['link_khac'] ?? ''));
                    $loai_post = addslashes($_POST['loai_file'] ?? '');
                    if ($file_name !== '') {
                        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $data['loai_file'] = $ext !== '' ? $ext : ($loai_post !== '' ? $loai_post : 'file');
                    } elseif ($data['link_khac'] !== '') {
                        $data['loai_file'] = $loai_post !== '' ? $loai_post : 'file';
                    } else {
                        $data['loai_file'] = $loai_post !== '' ? $loai_post : 'file';
                    }
                    $data['ngay_dang'] = time();
                    $data['hien_thi']  = (int) $hien_thi;
                    $data['id_code']   = $id_code;
                    $data['lang']      = $value['code'];
                    $data['so_thu_tu'] = (int) $so_thu_tu;
                    $d->reset();
                    $d->setTable('#_files');
                    $d->insert($data);
                }
                if (isset($_POST['capnhat']) && $_POST['capnhat'] == 'capnhat_sua') {
                    $d->redirect("index.php?p=upload-file&a=edit&id=" . $id_code . $link_option);
                } else {
                    $d->redirect("index.php?p=upload-file&a=man" . $link_option);
                }
            }else{
                $d->alert("Thêm dữ liệu bị lỗi!");
                $d->redirect("Thêm dữ liệu bị lỗi", "index.php?p=upload-file&a=man".$link_option);
            }
	}
    }else{
        $d->redirect("index.php?p=upload-file&a=man&loaitin=".@$_GET['loaitin']);
    }
}

function xoadulieu($id_module){
    global $link_option;
    global $d;
    if($d->checkPermission_dele($id_module)==1){
        if(isset($_GET['id'])){
            $id =  addslashes($_GET['id']);
            $hinhanh = $d->o_fet("select * from #_files where id_code = '".$id."'");
            //@unlink('../uploads/images/'.$hinhanh[0]['hinh_anh']);
            $d->reset();
            $d->setTable('#_files');
            $d->setWhere('id_code',$id);
            if($d->delete()){
                $d->o_que("delete from cf_parent where id = $id ");
                $d->redirect("index.php?p=upload-file&a=man&page=".@$_REQUEST['page']."&loaitin=".@$_GET['loaitin']);
            }else{
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("Xóa dữ liệu bị lỗi", "index.php?p=upload-file&a=man".$link_option);
            }
        }else {
            $d->alert("Không nhận được dữ liệu!");
            $d->redirect("Xóa dữ liệu bị lỗi", "index.php?p=upload-file&a=man".$link_option);
        }
    }else{
        $d->redirect("index.php?p=upload-file&a=man".$link_option);
    }
	
}

function xoadulieu_mang($id_module){
    global $d;
    if($d->checkPermission_dele($id_module)==1){
        if(isset($_POST['chk_child'])){
            $chuoi = "";
            foreach ($_POST['chk_child'] as $val) {
                    $chuoi .=$val.',';
            }
            $chuoi = trim($chuoi,',');
            //lay danh sách idsp theo chuoi
            $hinhanh = $d->o_fet("select * from #_files where id_code in ($chuoi) and lang ='".LANG."'");
            if($d->o_que("delete from #_files where id_code in ($chuoi)")){
                 $d->o_que("delete from cf_parent where id in ($chuoi) ");
                //xoa hình ảnh
                foreach ($hinhanh as $ha) {
                    //@unlink('../uploads/images/'.$ha['hinh_anh']);
                }
                $d->redirect("index.php?p=upload-file&a=man&page=".@$_REQUEST['page']."&loaitin=".@$_GET['loaitin']);
            }
            else{
                $d->alert("Không nhận được dữ liệu!");
                $d->redirect("Xóa dữ liệu bị lỗi", "index.php?p=upload-file&a=man".$link_option);
            } 
        }else $d->redirect("index.php?p=upload-file&a=man".$link_option);
    }else{
        $d->redirect("index.php?p=upload-file&a=man".$link_option);
    }
	
	
}
?>