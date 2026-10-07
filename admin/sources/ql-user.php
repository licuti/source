<?php


if(!defined('_source')) die("Error");
$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";
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
	case "change-pass":
		showdulieu();
		$template = @$_REQUEST['p']."/doipass";
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
	case "savepass":
		savepass();
		break;
	default:
		$template = "index";
}

/**
 * Kiểm tra định dạng mật khẩu ở SERVER (đồng bộ với JS ở form).
 * 8-20 ký tự, có chữ thường, chữ HOA, số và 1 trong !@#$%^&*
 */
if (!function_exists('ql_user_password_ok')) {
	function ql_user_password_ok($password)
	{
		$password = (string) $password;
		$len = strlen($password);
		if ($len < 8 || $len > 20) {
			return false;
		}
		return (bool) preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[!@#$%^&*])(?=.*[A-Z]).{8,20}$/', $password);
	}
}

function savepass(){
	global $d;

	$id = $_SESSION["id_user"];
	$data['ho_ten'] = addslashes($_POST['ho_ten']);	

	// Validate mật khẩu ở server — không tin JS
	$newPass = isset($_POST['password']) ? (string) $_POST['password'] : '';
	if (!ql_user_password_ok($newPass)) {
		$d->transfer("Mật khẩu chưa đúng định dạng (8-20 ký tự, có chữ thường, HOA, số và ký tự đặc biệt !@#$%^&*).", "index.php?p=ql-user&a=man");
		return;
	}
	$data['pass_hash'] = $d->hashPassword($newPass);

	$d->reset();
	$d->setTable('#_user');
	$d->setWhere('id',$id);
	
	if($d->update($data))
	{
		$d->redirect("index.php?p=ql-user&a=man");
	}
	else{
		$d->transfer("Lưu dữ liệu bị lỗi", "index.php?p=ql-user&a=man");
	}
}

function showdulieu(){
	global $d, $items, $paging, $loai,$hang;
	if($_REQUEST['a'] == 'man'){
            //update cot
            $id = isset($_GET['id']) ? addslashes($_GET['id']) : "";
            if($_SESSION['quyen']==4){
                $items = $d->o_fet("select * from #_user  where id <> ".$_SESSION['id_user']." order by id desc");
            }else{
                $items = $d->o_fet("select * from #_user where id <> ".$_SESSION['id_user']." and parent=".$_SESSION['id_user']." order by id desc");
            }
            //
	}else if($_REQUEST['a'] == 'edit'){
            //lay noi dung theo id
            
            $id = isset($_GET['id']) ? addslashes($_GET['id']) : "";
            if($_SESSION['id_user'] == $id and $_SESSION['quyen'] < 4){
                $d->redirect("index.php?p=thongtin-user&a=man");
            }
            
            $items = $d->o_fet("select * from #_user where id =  '".$id."'");
            if( $_SESSION['quyen'] < 4 and $items[0]['parent']!= $_SESSION['id_user']){
                $d->redirect("index.php?p=ql-user&a=man");
            }
		
	}else if($_REQUEST['a'] == 'change-pass'){
		//lay noi dung theo id
		$id = isset($_GET['id']) ? addslashes($_GET['id']) : "";
		$items = $d->o_fet("select * from #_user where id =  '".$_SESSION['id_user']."'");
		
		
	}
}

function luudulieu(){
	global $d;

	$id = (isset($_REQUEST['id'])) ? addslashes($_REQUEST['id']) : "";
	if($id != '')
	{
            $data['tai_khoan']  = $d->clean(addslashes($_POST['tai_khoan']));
            $data['user_hash']  = sha1($data['tai_khoan']);
            $data['ho_ten']     = addslashes($_POST['ho_ten']);
            $data['email']      = addslashes($_POST['email']);
            $data['noi_dung'] = addslashes($_POST['noi_dung']);
            $data['hinh_anh'] = addslashes($_POST['hinh_anh']);
            if(isset($_POST['password']) && !empty($_POST['password'])){
                // Validate mật khẩu ở server — không tin JS
                if (!ql_user_password_ok($_POST['password'])) {
                    $d->transfer("Mật khẩu chưa đúng định dạng (8-20 ký tự, có chữ thường, HOA, số và ký tự đặc biệt !@#$%^&*).", "index.php?p=ql-user&a=man");
                    return;
                }
                $data['pass_hash'] = $d->hashPassword($_POST['password']);
            }
            $data['quyen_han'] = addslashes($_POST['quyen_han']);
            if($_POST['quyen_han'] == '4'){

                  $data['is_admin'] = 1;

            }else{

                 $data['is_admin'] = 0;
            }
            $data['hien_thi'] = isset($_POST['hien_thi']) ? 1 : 0;
            $d->setTable('#_user');
            $d->setWhere('id',$id);

            if($d->update($data)){
                $d->o_que("delete from #_user_permission_group where id_user = '".$id."'");
                if(isset($_POST['permission'])){
                    $permission = $_POST['permission'];
                    foreach ( $permission as $key => $value) {
                        $data_permission['id_user'] = $id;
                        $data_permission['id_permission'] = $value;
                        if(isset($_POST['action_'.$value])){
                            $action = implode('', $_POST['action_'.$value]);
                        }else{
                            $action=0;
                        }
                        $data_permission['action'] = $action;
                        $d->reset();
                        $d->setTable('#_user_permission_group');
                        $d->insert($data_permission);
                    }							
                }
                $d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']);
            }
            else{
                $d->alert("Cập nhật dữ liệu bị lỗi!");
                $d->redirect("Cập nhật dữ liệu bị lỗi", "index.php?p=ql-user&a=man");
            }
	}
	else{
            $data['tai_khoan']  =   $d->clean(addslashes($_POST['tai_khoan']));
            $data['user_hash']  =   sha1($data['tai_khoan']);
            $data['ho_ten']     =   addslashes($_POST['ho_ten']);
            $data['noi_dung'] = addslashes($_POST['noi_dung']);
            $data['hinh_anh'] = addslashes($_POST['hinh_anh']);

            // Bắt buộc có mật khẩu khi tạo user mới; validate ở server
            if (!isset($_POST['password']) || !ql_user_password_ok($_POST['password'])) {
                $d->transfer("Mật khẩu chưa đúng định dạng (8-20 ký tự, có chữ thường, HOA, số và ký tự đặc biệt !@#$%^&*).", "index.php?p=ql-user&a=man");
                return;
            }
            $data['pass_hash']  =   $d->hashPassword($_POST['password']);
            $data['quyen_han']  =   addslashes($_POST['quyen_han']);
            $data['parent']     = $_SESSION['id_user'];
            if($_POST['quyen_han'] == '4'){

                  $data['is_admin'] = 1;

            }else{

                 $data['is_admin'] = 0;
            }
            $data['email']      =   addslashes($_POST['email']);
            $data['hien_thi'] = isset($_POST['hien_thi']) ? 1 : 0;
            $d->reset();
            $d->setTable('#_user');
            if($id_u = $d->insert($data))
            {
                if(isset($_POST['permission'])){
                    $permission = $_POST['permission'];
                    foreach ( $permission as $key => $value) {
                        $data_permission['id_user'] = $id_u;
                        $data_permission['id_permission'] = $value;
                        if(isset($_POST['action_'.$value])){
                            $action = implode('', $_POST['action_'.$value]);
                        }else{
                            $action=0;
                        }
                        $data_permission['action'] = $action;
                        $d->reset();
                        $d->setTable('#_user_permission_group');
                        $d->insert($data_permission);
                    }							
                }
                $d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']."");
            }
            else{
                    $d->transfer("Tên tài khoản đã tồn tại!", "index.php?p=ql-user&a=man");
            }
	}
}

function xoadulieu(){
	global $d;
	if($_SESSION['is_admin'] != 1){
		$d->redirect("index.php");
	}
	if($_SESSION['quyen'] == 4){
		if(isset($_GET['id'])){
			$id =  addslashes($_GET['id']);
			$d->reset();
			$d->setTable('#_user');
			$d->setWhere('id',$id);
			$d->setWhereOrther('id','admin');
			if($d->delete()){
				$d->o_que("delete from #_user_permission_group where id_user = '".$id."'");
				@$d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']."");
			}else
				$d->transfer("Xóa dữ liệu bị lỗi", "index.php?p=ql-user&a=man");
		}else $d->transfer("Không nhận được dữ liệu", "index.php?p=ql-user&a=man");
	}else {
		$d->alert("Quản trị viên không thể thực hiện thao tác này!");
		@$d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']."");
	} 
}

function xoadulieu_mang(){
    global $d, $d;
    if($_SESSION['quyen'] == 4){
        if(isset($_POST['chk_child'])){
            $chuoi = "";
            foreach ($_POST['chk_child'] as $val) {
                    if($val <> 'admin')
                    $chuoi .=$val.",";
            }
            $chuoi = trim($chuoi,',');
            //lay danh sách idsp theo chuoi
            $hinhanh = $d->o_fet("select * from #_user where id in ($chuoi)");
            if($d->o_fet("delete from #_user where id in ($chuoi)")){
                //xoa hình ảnh
                foreach ($hinhanh as $ha) {
                    @unlink('../uploads/images/'.$ha['hinh_anh']);
                }
                $d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']."");
            }
            else $d->transfer("Xóa dữ liệu bị lỗi", "index.php?p=ql-user&a=man");
        }else $d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']."");
    }else {
            $d->alert("Quản trị viên không thể thực hiện thao tác này!");
            @$d->redirect("index.php?p=ql-user&a=man&page=".@$_REQUEST['page']."");
    } 
}
?>