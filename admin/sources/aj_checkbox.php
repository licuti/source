<?php
	define('_lib','../lib/');
	include _lib."config.php";
	include_once _lib."class.php";
	$d = new func_index($config['database']);
        
	$do = $_REQUEST['do'];
        if($do=='checkbox'){
            $trangthai = addslashes($_POST['trangthai']);
            $bang= addslashes($_POST['bang']);
            $cot= addslashes($_POST['cot']);
            $id= addslashes($_POST['id']);
            if($bang == '#_module_admin' or $bang =='#_thanhvien' or $bang == '#_flash_sale'or $bang == '#_binhluan' or $bang == '#_category_noidung'){
                if($d->o_que("update ".$bang." set ".$cot." = ".$trangthai." where id = ".$id)){
                    echo '1';
                }else{
                    echo '0';
                }
            }else{
                if($d->o_que("update ".$bang." set ".$cot." = ".$trangthai." where id_code = ".$id)){
                    echo '1';
                }else{
                    echo '0';
                }
            }
            
        }elseif ($do=='xoaimg') {
            $bang= addslashes($_POST['bang']);
            $cot= addslashes($_POST['cot']);
            $id= addslashes($_POST['id']);
            $folder = addslashes($_POST['folder']);
            //echo "select ".$cot." from #".$bang." where id_code = '".$id."'";
            $hinh_anh = $d->simple_fetch("select ".$cot." from #".$bang." where id_code = '".$id."'");
            if($folder==''){
                @unlink('../../uploads/images/'.$hinh_anh[$cot]);
                @unlink('../../uploads/images/largethumb/'.$hinh_anh[$cot]);
                @unlink('../../uploads/images/smallthumb/'.$hinh_anh[$cot]);
                @unlink('../../uploads/images/thumb/'.$hinh_anh[$cot]);
                if($d->o_que("update #".$bang." set ".$cot." = '' where id_code = ".$id)){
                    echo '1';
                }else{
                    echo '0';
                }
            }else{
                @unlink('../../uploads/images/'.$folder.'/'.$hinh_anh[$cot]);
                echo '1';
            }
        }

?>