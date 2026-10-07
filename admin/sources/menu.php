<?php
/**
 * Module Menu (admin)
 * - Load danh sách menu + vị trí
 * - Load nguồn thêm mục (danh mục / SP / bài viết)
 * - Đưa cây item hiện tại ra JSON cho Nestable (JS)
 */
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
        menu_show();
        $template = @$_REQUEST['p'] . '/hienthi';
        break;
    default:
        $template = 'index';
}

/** Chuẩn bị dữ liệu trang quản lý menu. */
function menu_show()
{
    global $d, $menu_sources, $menu_location, $menus;
    global $current_menu_id, $current_menu, $current_menu_items_json;

    // Chỉ lấy cột cần thiết (nhẹ hơn SELECT *)
    $cols_cat  = 'id, id_code, id_loai, ten, alias, lang, so_thu_tu';
    $cols_item = 'id, ten, alias, lang, so_thu_tu';

    $menu_sources = array(
        array(
            'title' => 'Danh mục',
            'items' => menu_prepare_source(
                $d->o_fet("SELECT {$cols_cat} FROM #_category ORDER BY so_thu_tu ASC, id DESC"),
                array('object_type' => 'category', 'type' => 'Danh mục'),
                'id_code',
                'id_loai'
            ),
        ),
        array(
            'title' => 'Sản phẩm',
            'items' => menu_prepare_source(
                $d->o_fet("SELECT {$cols_item} FROM #_sanpham ORDER BY so_thu_tu ASC, id DESC"),
                array('object_type' => 'sanpham', 'type' => 'Sản phẩm')
            ),
        ),
        array(
            'title' => 'Bài viết',
            'items' => menu_prepare_source(
                $d->o_fet("SELECT {$cols_item} FROM #_tintuc ORDER BY so_thu_tu ASC, id DESC"),
                array('object_type' => 'tintuc', 'type' => 'Bài viết')
            ),
        ),
    );

    $menus = $d->o_fet('SELECT id, name FROM #_menus ORDER BY id ASC');
    $menu_location = $d->o_fet('SELECT * FROM #_menu_locations ORDER BY location_name ASC');

    $current_menu_id = 0;
    $current_menu = array('id' => 0, 'name' => '');
    $items_db = array();

    if (isset($_GET['menu'])) {
        $current_menu_id = (int) $_GET['menu'];
    } elseif (!empty($menus)) {
        $current_menu_id = (int) $menus[0]['id'];
    }

    if ($current_menu_id > 0) {
        $row = $d->simple_fetch('SELECT id, name FROM #_menus WHERE id = ' . $current_menu_id);
        if (!empty($row)) {
            $current_menu = $row;
        }
        $items_db = $d->o_fet(
            'SELECT * FROM #_menu_items WHERE menu_id = ' . $current_menu_id
            . ' ORDER BY parent_id ASC, sort_order ASC'
        );
    }

    $current_menu_items_json = json_encode(
        menu_build_tree($items_db),
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    if ($current_menu_items_json === false) {
        $current_menu_items_json = '[]';
    }
}

/** DB rows (phẳng) -> cây JSON cho Nestable. */
function menu_build_tree($elements, $parentId = 0)
{
    $branch = array();
    if (empty($elements)) {
        return $branch;
    }

    foreach ($elements as $el) {
        if ((int) $el['parent_id'] !== (int) $parentId) {
            continue;
        }

        $branch[] = array(
            'id'          => $el['id'],
            'label'       => $el['label'],
            'url'         => $el['url'],
            'class'       => $el['class'],
            'style'       => $el['style'],
            'block'       => $el['block'],
            'target'      => $el['target'],
            'image'       => $el['image'],
            'type'        => $el['type'],
            'object_type' => $el['object_type'],
            'object_id'   => $el['object_id'],
            'sub_label'   => $el['sub_label'],
            'children'    => menu_build_tree($elements, $el['id']),
        );
    }

    return $branch;
}

/** Chuẩn hoá 1 dòng nguồn về format checkbox. */
function menu_normalize_rows(array $rows, array $sourceConfig = array())
{
    $out = array();
    foreach ($rows as $r) {
        $item = array(
            'id'          => isset($r['id']) ? (int) $r['id'] : 0,
            'id_code'     => isset($r['id_code']) ? $r['id_code'] : null,
            'id_loai'     => isset($r['id_loai']) ? $r['id_loai'] : 0,
            'ten'         => isset($r['ten']) ? $r['ten'] : '',
            'alias'       => isset($r['alias']) ? $r['alias'] : '',
            'lang'        => isset($r['lang']) ? $r['lang'] : 'vi',
            'object_type' => null,
            'object_id'   => isset($r['id']) ? $r['id'] : null,
        );
        foreach ($sourceConfig as $k => $v) {
            $item[$k] = $v;
        }
        $out[] = $item;
    }
    return $out;
}

/** Dựng cây nguồn theo parent + lang. */
function menu_build_source_tree(array $items, $idField = 'id', $parentField = 'parent_id', $parentId = 0)
{
    $grouped = array();
    foreach ($items as $item) {
        $lang = isset($item['lang']) ? $item['lang'] : 'vi';
        $pid  = isset($item[$parentField]) ? $item[$parentField] : 0;
        $grouped[$pid . '_' . $lang][] = $item;
    }

    $build = function ($parentId, $lang) use (&$build, $grouped, $idField) {
        $branch = array();
        $key = $parentId . '_' . $lang;
        if (!isset($grouped[$key])) {
            return $branch;
        }
        foreach ($grouped[$key] as $item) {
            $children = $build($item[$idField], $item['lang']);
            if ($children) {
                $item['children'] = $children;
            }
            $branch[] = $item;
        }
        return $branch;
    };

    $tree = array();
    foreach ($items as $item) {
        $pid = isset($item[$parentField]) ? $item[$parentField] : 0;
        if ((string) $pid !== (string) $parentId) {
            continue;
        }
        $lang = isset($item['lang']) ? $item['lang'] : 'vi';
        $children = $build($item[$idField], $lang);
        if ($children) {
            $item['children'] = $children;
        }
        $tree[] = $item;
    }

    return $tree;
}

/** Normalize + (tuỳ chọn) build tree nếu có cột cha-con. */
function menu_prepare_source(array $items, array $config = array(), $idField = null, $parentField = null)
{
    $normalized = menu_normalize_rows($items, $config);

    if (!$idField || !$parentField || empty($items) || !isset($items[0][$parentField])) {
        return $normalized;
    }

    return menu_build_source_tree($normalized, $idField, $parentField);
}

/** HTML checkbox nguồn (đệ quy children). */
function renderMenuSourceItems($items, $level = 0, $prefix = '')
{
    $html = '';
    if ($level > 0) {
        $prefix .= '== ';
    }

    foreach ($items as $item) {
        $html .= '<div class="source-item">';
        $html .= '<label>';
        $html .= $prefix;
        $html .= '<input type="checkbox"'
            . ' value="' . htmlspecialchars($item['ten']) . '"'
            . ' data-label="' . htmlspecialchars($item['ten']) . '"'
            . ' data-url="' . htmlspecialchars($item['alias']) . '"'
            . ' data-lang="' . htmlspecialchars($item['lang']) . '"'
            . ' data-type="' . htmlspecialchars($item['type']) . '"'
            . ' data-object-type="' . htmlspecialchars($item['object_type']) . '"'
            . ' data-object-id="' . htmlspecialchars($item['object_id']) . '">';
        $html .= ' ' . htmlspecialchars($item['ten']);
        $html .= '</label></div>';

        if (!empty($item['children'])) {
            $html .= renderMenuSourceItems($item['children'], $level + 1, $prefix);
        }
    }

    return $html;
}
