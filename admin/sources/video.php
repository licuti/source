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
$row_setting = $d->simple_fetch("select setting from #_module where id = 13 ");
$setting = $row_setting['setting'];
$arrr_setting = json_decode($setting, true);
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

	if ($_REQUEST['a'] == 'man') {
            $where_search = '';
            $loaiId = 0;

            // Lọc danh mục từ list_bar (name="loai")
            if (!empty($_GET['loai']) && (int) $_GET['loai'] > 0) {
                $loaiId = (int) $_GET['loai'];
                $list_id = $loaiId . $d->getIdsub($loaiId);
                $where_search .= " and id_loai in ($list_id)";
            }

            // Tìm theo cột
            if (!empty($_GET['search']) && ($_GET['key'] ?? '') !== '' && $_GET['search'] !== 'loai') {
                $col   = addslashes($_GET['search']);
                $value = addslashes($_GET['key']);
                $allowed = ['ten', 'ma_video', 'id_code', 'alias'];
                if (in_array($col, $allowed, true)) {
                    $where_search .= " and $col like '%" . $value . "%' ";
                }
            }

            // Dropdown danh mục module video = 13
            $loai = $d->array_category(0, '= ', (string) $loaiId, 13);

            $limit = 20;
            $current_page = max(1, (int) ($_GET['page'] ?? 1));
            $start = ($current_page - 1) * $limit;
            $items = $d->o_fet("select * from #_video where lang ='" . LANG . "' $where_search order by so_thu_tu asc, id desc limit $start, $limit");
            $total_records = $d->num_rows("select id from #_video where lang ='" . LANG . "' $where_search order by so_thu_tu asc, id desc");
            $total_page = ceil($total_records / $limit);
	} else {
            if (isset($_REQUEST['id'])) {
                $id = addslashes($_REQUEST['id']);
                $items = $d->o_fet("select * from #_video where id_code =  '" . $id . "' and lang = '" . LANG . "'");
                $loai = $d->array_category(0, '', $items[0]['id_loai'] ?? 0, 13);
            } else {
                $loai = $d->array_category(0, '', 0, 13);
            }
	}
}

function luudulieu($id_module){
    global $d;
    global $link_option;
    global $arrr_setting;
    
    if($arrr_setting['size_img']!=''){
        $size = explode('x', $arrr_setting['size_img']);
        $w = (int)$size[0];
        $h = (int)$size[1];
    }else{
        $w = 300;
        $h = 300;
    }
    if($arrr_setting['option_resize']!=''){
        $option_resize = $arrr_setting['option_resize'];
    }else{
        $option_resize="auto";
    }
    if($d->checkPermission_edit($id_module)==1){
	$id = (isset($_REQUEST['id'])) ? addslashes($_REQUEST['id']) : "";
	$file_name=$d->fns_Rand_digit(0,9,12);
	if($id != '')
	{
            $alias0      = addslashes($_POST['alias'][0]);
            if($d->checkLink($alias0,$id)== 0) {
                $alias0.="-".rand(0,9);
            }
            $hinh_anh = addslashes($_POST['hinh_anh']);
            
            $id_loai    =   addslashes($_POST['id_loai']);
            $so_thu_tu  =   $_POST['so_thu_tu'] !='' ? $_POST['so_thu_tu'] : 0;
            $hien_thi   =   isset($_POST['hien_thi']) ? 1 : 0;
            
            $data0['ten']   =   addslashes($_POST['ten'][0]);
            $d->reset();
            $d->setTable('cf_parent');
            $d->setWhere('id',$id);
            if($d->update($data0)) {
                foreach (get_json('lang') as $key => $value) {
                    $data['id_loai']    =   $id_loai;
                    if($hinh_anh!=''){
                        $data['hinh_anh']   =   $hinh_anh;
                    }
					$data['video']   =   addslashes($_POST['video']);
                    $data['ten']        =   $d->clear(addslashes($_POST['ten'][$key]));
                    $data['alias']          = addslashes($_POST['alias'][$key]);
                    if($d->checkLink($data['alias'], $_POST['id_row'][$key])== 0) {
                        $data['alias'].="-".rand(0,9);
                    }
                    $data['noi_dung']   =   $d->clear(addslashes($_POST['noi_dung'][$key]));
                    $data['ma_video']        = addslashes($_POST['ma_video']);
                    $data['title']      = $d->clear(addslashes($_POST['title'][$key]));
                    $data['keyword']    = $d->clear(addslashes($_POST['keyword'][$key]));
                    $data['des']        = addslashes($_POST['des'][$key]);
                    $data['hien_thi']   =   $hien_thi;
                    $data['so_thu_tu']  = $so_thu_tu;
                    $data['cap_nhat']  = time();
                    $d->reset();
                    $d->setTable('#_video');
                    $d->setWhere('id', $_POST['id_row'][$key]);
                    $d->update($data);
                }
                 if (isset($_POST['capnhat']) && $_POST['capnhat'] == 'capnhat_sua') {
                    $d->redirect("index.php?p=video&a=edit&id=" . $id . $link_option);
                } else {
                    $d->redirect("index.php?p=video&a=man" . $link_option);
                }
            }else{
                $d->alert("Cập nhật dữ liệu bị lỗi!");
                $d->redirect("Cập nhật dữ liệu bị lỗi", "index.php?p=video&a=man".$link_option);
            }
	}
	else
	{
            $alias0      = addslashes($_POST['alias'][0]);
            if($d->checkLink($alias0)== 0) {
                $alias0.="-".rand(0,9);
            }
            $hinh_anh = addslashes($_POST['hinh_anh']);
            $id_loai        =   addslashes($_POST['id_loai']);
            $so_thu_tu      =   $_POST['so_thu_tu'] !='' ? $_POST['so_thu_tu'] : 0;
            $hien_thi       =   isset($_POST['hien_thi']) ? 1 : 0;
            
            $data0['ten']   =   addslashes($_POST['ten'][0]);
            $d->reset();
            $d->setTable('cf_parent');
            if($id_code = $d->insert($data0)) {
                foreach (get_json('lang') as $key => $value) {
                    $data['id_loai']    =   $id_loai;
                    $data['hinh_anh']   =   $hinh_anh;
					$data['video']   =   addslashes($_POST['video']);
                    $data['ten']        =   $d->clear(addslashes($_POST['ten'][$key]));
                    $data['alias']          = addslashes($_POST['alias'][$key]);
                    if($d->checkLink($data['alias'])== 0) {
                        $data['alias'].="-".rand(0,9);
                    }
                    $data['noi_dung']   =   $d->clear(addslashes($_POST['noi_dung'][$key]));
                    $data['title']      = $d->clear(addslashes($_POST['title'][$key]));
                    $data['keyword']    = $d->clear(addslashes($_POST['keyword'][$key]));
                    $data['des']        = addslashes($_POST['des'][$key]);
                    $data['ma_video']        = addslashes($_POST['ma_video']);
                    $data['hien_thi']   =   $hien_thi;
                    $data['id_code']    =   $id_code;
                    $data['lang']       =   $value['code'];
                    $data['so_thu_tu']  = $so_thu_tu;
                    $data['cap_nhat']  = time();
                    $d->reset();
                    $d->setTable('#_video');
                    $d->insert($data);
                }
                if (isset($_POST['capnhat']) && $_POST['capnhat'] == 'capnhat_sua') {
                    $d->redirect("index.php?p=video&a=edit&id=" . $id_code . $link_option);
                } else {
                    $d->redirect("index.php?p=video&a=man" . $link_option);
                }
            }else{
                $d->alert("Thêm dữ liệu bị lỗi!");
                $d->redirect("Thêm dữ liệu bị lỗi", "index.php?p=video&a=man".$link_option);
            }
	}
    }else{
        $d->redirect("index.php?p=video&a=man".$link_option);
    }
}

function xoadulieu($id_module){
    global $d;
    global $link_option;
    if($d->checkPermission_dele($id_module)==1){
        if(isset($_GET['id'])){
            $id =  addslashes($_GET['id']);
            $hinhanh = $d->o_fet("select * from #_video where id_code = '".$id."'");
            @unlink('../uploads/images/'.$hinhanh[0]['hinh_anh']);
            @unlink('../uploads/images/thumb/'.$hinhanh[0]['hinh_anh']);
            $d->reset();
            $d->setTable('#_video');
            $d->setWhere('id_code',$id);
            if($d->delete()){
                $d->o_que("delete from cf_parent where id = $id ");
                $d->redirect("index.php?p=video&a=man".$link_option);
            }else{
                $d->alert("Xóa dữ liệu bị lỗi!");
                $d->redirect("Xóa dữ liệu bị lỗi", "index.php?p=video&a=man".$link_option);
            }
        }else {
            $d->alert("Không nhận được dữ liệu!");
            $d->redirect("Xóa dữ liệu bị lỗi", "index.php?p=video&a=man".$link_option);
        }
    }else{
        $d->redirect("index.php?p=video&a=man".$link_option);
    }
	
}

function xoadulieu_mang($id_module){
    global $d;
    global $link_option;
    if($d->checkPermission_dele($id_module)==1){
        if(isset($_POST['chk_child'])){
            $chuoi = "";
            foreach ($_POST['chk_child'] as $val) {
                    $chuoi .=$val.',';
            }
            $chuoi = trim($chuoi,',');
            //lay danh sách idsp theo chuoi
            $hinhanh = $d->o_fet("select * from #_video where id_code in ($chuoi) and lang ='".LANG."'");
            if($d->o_que("delete from #_video where id_code in ($chuoi)")){
                 $d->o_que("delete from cf_parent where id in ($chuoi) ");
                //xoa hình ảnh
                foreach ($hinhanh as $ha) {
                    @unlink('../uploads/images/'.$ha['hinh_anh']);
                    @unlink('../uploads/images/thumb/'.$ha['hinh_anh']);
                }
                $d->redirect("index.php?p=video&a=man".$link_option);
            }
            else{
                $d->alert("Không nhận được dữ liệu!");
                $d->redirect("Xóa dữ liệu bị lỗi", "index.php?p=video&a=man".$link_option);
            } 
        }else $d->redirect("index.php?p=video&a=man".$link_option);
    }else{
        $d->redirect("index.php?p=video&a=man".$link_option);
    }
	
	
}
?>