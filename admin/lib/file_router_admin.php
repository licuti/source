<?php
	$p = (isset($_REQUEST['p'])) ? addslashes($_REQUEST['p']) : "";
        $row_module = $d->simple_fetch("select * from #_module_admin where alias='$p'");
        $id_module = $row_module['id'];
	if($p == ''){
		$source = "";
		$template = "index";
	}else if($p=='out'){
            session_destroy();
            $d->cookie_destroy();
            $d->redirect("login.php");
	}
	else{
            $source = $p;
	}
	if(!empty($p) and $d->checkPermission($_SESSION['id_user'],$id_module)==0 and $p!='thongtin-user'){
            $d->redirect("index.php");
	}
        //echo $source;
	if($source!="") @include "sources/".$source.".php";
?>