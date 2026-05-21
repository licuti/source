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
	global $d, $items;
	if($_REQUEST['a'] == 'man'){
        $items = $d->o_fet("select * from #_button_contact order by sort asc, id desc");
	}
}

function luudulieu($id_module){
    global $d;
    if($d->checkPermission_edit($id_module) == 1){
    	$request['id']                     = $_POST['id'];
    	$request['image']                  = $_POST['image'];
    	$request['name']                   = $_POST['name'];
    	$request['link']                   = $_POST['link'];
    	$request['target']                 = $_POST['target'];
    	$request['color_background']       = $_POST['color_background'];
    	$request['color_background_alpha'] = $_POST['color_background_alpha'];
    	$request['color_text']             = $_POST['color_text'];

    	// var_dump($request['target']);
    	// die();
    	foreach ($request['image'] as $key => $value) {
    		$data_item['image']      			 = $request['image'][$key];
    		$data_item['name']                   = $request['name'][$key];
    		$data_item['link'] 					 = $request['link'][$key];
    		$data_item['target']                 = $request['target'][$key];
    		$data_item['color_background']       = $request['color_background'][$key];
    		$data_item['color_background_alpha'] = $request['color_background_alpha'][$key];
    		$data_item['color_text'] 			 = $request['color_text'][$key];
    		$data_item['sort'] 			         = $key;
    		if ($request['id'][$key]) {
            	$d->reset();
                $d->setTable('#_button_contact');
                $d->setWhere('id', $request['id'][$key]);
                $d->update($data_item);
    		}else{
    			$d->reset();
            	$d->setTable('#_button_contact');
            	$d->insert($data_item);
    		}
    	}
    	$d->redirect("index.php?p=button-contact&a=man");
	}
}