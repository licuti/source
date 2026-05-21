<?php

if(!defined('_source')) die("Error");
$a = (isset($_REQUEST['a'])) ? addslashes($_REQUEST['a']) : "";
switch($a){
	case "man":
		showdulieu();
		$template = @$_REQUEST['p']."/hienthi";
		break;
	default:
		$template = "index";
}

function showdulieu(){
	global $d, $menu_sources, $menu_location, $menus;
	global $current_menu_id;
	global $current_menu;
	global $saved_locations_for_current_menu;
	global $current_menu_items_json;
	
	// Thay đổi select * 
	$menu_category = $d->o_fet("select * from #_category order by so_thu_tu asc, id desc");
	$menu_product = $d->o_fet("select * from #_sanpham order by so_thu_tu asc, id desc");
	$menu_post = $d->o_fet("select * from #_tintuc order by so_thu_tu asc, id desc");

	$menu_sources = [
	    [
	        'title' => 'Danh mục',
	        'items'         => prepareMenuSource(
	        	$menu_category,
	        	['object-type' => "category", 'type' => "Danh mục"],
	        	'id_code',
	        	'id_loai',
	        ),
	    ],
	    [
	        'title' => 'Sản phẩm',
	        'items' => prepareMenuSource(
                $menu_product,
                ['object-type' => "sanpham", 'type' => "Sản phẩm"]),
	    ],
	    [
	        'title' => 'Bài viết',
	        'items' => prepareMenuSource(
                $menu_post,
                ['object-type' => "tintuc", 'type' => "Bài viết"]),
	    ],
	];


	$menus = $d->o_fet("select * from #_menus order by id asc");
	$menu_location = $d->o_fet("select * from #_menu_locations order by location_name asc");

	$current_menu_id = 0;
	$current_menu = null;
	$items_db = array();
	$saved_locations_for_current_menu = array();

	if (isset($_GET['menu'])) {
	    $current_menu_id = (int)$_GET['menu'];
	} else if (!empty($menus)) {
	    $current_menu_id = $menus[0]['id'];
	}

	if ($current_menu_id > 0) {
	    $current_menu = $d->simple_fetch("select * from #_menus where id = " . $current_menu_id);	
        $items_db = $d->o_fet("select * from #_menu_items where menu_id=".$current_menu_id." order by parent_id asc, sort_order asc");
        $saved_locations_for_current_menu = $d->o_fet("select * from #_menu_locations where menu_id=".$current_menu_id." ");
	}

    // Chuyển danh sách item sang JSON để JS đọc
    $current_menu_items_json = json_encode(buildMenuTree($items_db));

	// echo "<pre>";
	// print_r($current_menu);
	// print_r($locations_db);
	// print_r($menu_sources);
	// print_r($saved_locations_for_current_menu);
	// echo "</pre>";
}

function buildMenuTree($elements, $parentId = 0) {
    $branch = array();
    if (empty($elements)) {
        return $branch;
    }

    foreach ($elements as $element) {
        if ($element['parent_id'] == $parentId) {
            $children = buildMenuTree($elements, $element['id']);
            
            // Ánh xạ CSDL sang JSON
            $item = array(
                'id'       => $element['id'],
                'label'    => $element['label'],
                'url'      => $element['url'],
                'class'    => $element['class'],
                'style'    => $element['style'],
                'block'    => $element['block'],
                'target'   => $element['target'],
                'image'    => $element['image'],
                'type'    => $element['type'],
                'object_type'=> $element['object_type'],
                'object_id'  => $element['object_id'],
            );

            $item['children'] = $children;
            if (!empty($children)) {
            }

            $branch[] = $item;
        }
    }
    return $branch;
}


function normalizeMenuItems(array $rows, array $sourceConfig = []) {
    $normalized = [];

    foreach ($rows as $r) {
        $item = [
            'id'        => isset($r['id']) ? (int)$r['id'] : 0,
            'id_code'   => isset($r['id_code']) ? $r['id_code'] : null,
            'id_loai'   => isset($r['id_loai']) ? $r['id_loai'] : 0,
            'ten'       => isset($r['ten']) ? $r['ten'] : '',
            'alias'     => isset($r['alias']) ? $r['alias'] : '',
            'lang'      => isset($r['lang']) ? $r['lang'] : 'vi',
            'object_type' => isset($r['object_type']) ? $r['object_type'] : '',
            'object_id'   => isset($r['id']) ? $r['id'] : null,
            // 'children'  => []
        ];
        foreach ($sourceConfig as $k => $v) {
            $item[$k] = $v;
        }

        $normalized[] = $item;
    }

    return $normalized;
}

function buildSourceTree(array $items, $idField = 'id', $parentField = 'parent_id', $parentId = 0, $langField = 'lang') {
    $tree = [];

    // Gom nhóm các item theo parent_id + lang để đảm bảo cha-con cùng ngôn ngữ
    $grouped = [];
    foreach ($items as $item) {
        $lang = isset($item[$langField]) ? $item[$langField] : 'vi';
        $parentKey = (isset($item[$parentField]) ? $item[$parentField] : 0) . '_' . $lang;
        $grouped[$parentKey][] = $item;
    }

    // Hàm đệ quy
    $build = function($parentId, $lang) use (&$build, $grouped, $idField, $langField) {
        $branch = [];
        $key = $parentId . '_' . $lang;

        if (!isset($grouped[$key])) return $branch;

        foreach ($grouped[$key] as $item) {
            $children = $build($item[$idField], $item[$langField]);
            if ($children) $item['children'] = $children;
            $branch[] = $item;
        }

        return $branch;
    };

    // Duyệt từng item gốc theo lang
    foreach ($items as $item) {
        if (
            (isset($item[$parentField]) ? $item[$parentField] : 0) == $parentId
        ) {
            $lang = isset($item[$langField]) ? $item[$langField] : 'vi';
            $item['children'] = $build($item[$idField], $lang);
            $tree[] = $item;
        }
    }

    return $tree;
}

function prepareMenuSource(array $items, array $config = [], ?string $idField = null, ?string $parentField = null) {
    $normalized = normalizeMenuItems($items, $config);

    // Nếu không có cột cha-con hoặc $idField/$parentField không được truyền → trả danh sách phẳng
    if (!$idField || !$parentField || empty($items) || !isset($items[0][$parentField])) {
        return $normalized;
    }

    return buildSourceTree($normalized, $idField, $parentField);
}


function renderMenuSourceItems($items, $level = 0, $char = '') {
    $html = '';

    $padding_style = 'style="padding-left: ' . ($level * 0) . 'px;"';
    if ($level > 0) {
        $char .= '== ';
    }

    foreach ($items as $item) {
        $html .= '<div class="source-item" ' . $padding_style . '>';
        $html .= '<label>';
        $html .= $char;
        $html .= '<input type="checkbox"
                  value="' . htmlspecialchars($item['ten']) . '"
                  data-label="' . htmlspecialchars($item['ten']) . '"
                  data-url="' . htmlspecialchars($item['alias']) . '"
                  data-lang="' . htmlspecialchars($item['lang']) . '"
                  data-type="' . htmlspecialchars($item['type']) . '"
                  data-object-type="' . htmlspecialchars($item['object-type']) . '"
                  data-object-id="' . htmlspecialchars($item['object_id']) . '">';
        $html .= ' '. htmlspecialchars($item['ten']);
        $html .= '</label>';
        $html .= '</div>';
        
        if (!empty($item['children'])) {
            $html .= renderMenuSourceItems($item['children'], $level + 1, $char);
        }
    }
    
    return $html;
}