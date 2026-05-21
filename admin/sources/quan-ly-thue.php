<?php
if(!defined('_source')) die("Error");

$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";

switch($a){
    case "man":
        showdulieu();
        $template = @$_REQUEST['p']."/hienthi";
    break;
    case "save":
        luudulieu();
    break;
    default:
        $template = "index";
}

function showdulieu(){
    global $d, $item;
    $item = $d->simple_fetch("select * from #_thongtin where id = 1 limit 1");
}

function luudulieu(){
    global $d, $id_module;
    
    if($d->checkPermission_edit($id_module) == 1){
        $data = [];
        $data['vat_rate'] = (double)$_POST['vat_rate'];
        $data['vat_type'] = (int)$_POST['vat_type'];

        $d->reset();
        $d->setTable('#_thongtin');
        // We update all rows if there are multiple languages, 
        // as VAT is usually a business-wide setting, not localized.
        // But the previous thongtin logic updates per language.
        // Let's update it for all languages to ensure consistency.
        $d->rawQuery("UPDATE #_thongtin SET vat_rate = '".$data['vat_rate']."', vat_type = '".$data['vat_type']."'");
        
        $d->redirect("index.php?p=".$_GET['p']."&a=man");
    } else {
        $d->alert("Bạn không có quyền thực hiện thao tác này!");
        $d->redirect("index.php?p=".$_GET['p']."&a=man");
    }
}
?>
