<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\PostModel;
use App\Models\MenuModel;
use App\Models\MenuItemModel;
use App\Models\MenuLocationModel;
use App\Models\LanguageModel;

class MenuController extends BaseAdminController {
    
    public function index(Request $request) {
        $categoryModel = new CategoryModel();
        $productModel = new ProductModel();
        $postModel = new PostModel();
        $menuModel = new MenuModel();
        $menuItemModel = new MenuItemModel();
        $menuLocationModel = new MenuLocationModel();
        $languageModel = new LanguageModel();

        $menu_category = $categoryModel->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();
        $menu_product = $productModel->orderBy('id', 'desc')->get();
        $menu_post = $postModel->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->get();

        $menu_sources = [
            [
                'title' => 'Danh mục',
                'items' => $this->prepareMenuSource(
                    $menu_category,
                    ['object-type' => "category", 'type' => "Danh mục"],
                    'id_code',
                    'id_loai'
                ),
            ],
            [
                'title' => 'Sản phẩm',
                'items' => $this->prepareMenuSource(
                    $menu_product,
                    ['object-type' => "sanpham", 'type' => "Sản phẩm"]
                ),
            ],
            [
                'title' => 'Bài viết',
                'items' => $this->prepareMenuSource(
                    $menu_post,
                    ['object-type' => "tintuc", 'type' => "Bài viết"]
                ),
            ],
        ];

        $menus = $menuModel->orderBy('id', 'asc')->get();
        $menu_location = $menuLocationModel->orderBy('location_name', 'asc')->get();

        $current_menu_id = 0;
        $current_menu = null;
        $items_db = [];
        $saved_locations_for_current_menu = [];

        if ($request->has('menu')) {
            $current_menu_id = (int)$request->input('menu');
        } else if (!empty($menus)) {
            $current_menu_id = $menus[0]->id;
        }

        if ($current_menu_id > 0) {
            $current_menu = $menuModel->find($current_menu_id);
            $items_db = $menuItemModel->where('menu_id', $current_menu_id)
                                      ->orderBy('parent_id', 'asc')
                                      ->orderBy('sort_order', 'asc')
                                      ->get();
            $saved_locations_for_current_menu = $menuLocationModel->where('menu_id', $current_menu_id)->get();
        }

        // Convert object array to array for buildMenuTree
        $items_db_array = array_map(function($item) {
            return $item->toArray();
        }, $items_db);

        $current_menu_items_json = json_encode($this->buildMenuTree($items_db_array));

        return $this->render('admin.menu.index', compact(
            'menu_sources', 
            'menus', 
            'menu_location', 
            'current_menu_id', 
            'current_menu', 
            'current_menu_items_json'
        ));
    }

    public function ajaxCreate(Request $request) {
        $name = trim($request->input('name'));
        if (empty($name)) {
            return $this->json(['status' => 'error', 'message' => 'Tên menu không hợp lệ']);
        }

        $menuModel = new MenuModel();
        $id = $menuModel->insert(['name' => $name]);

        return $this->json(['status' => 'success', 'menu_id' => $id]);
    }

    public function ajaxDelete(Request $request) {
        $menu_id = (int)$request->input('menu_id');
        if ($menu_id <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID menu không hợp lệ']);
        }

        $menuModel = new MenuModel();
        $menuItemModel = new MenuItemModel();
        $menuLocationModel = new MenuLocationModel();

        // Xóa menu_items
        $menuItemModel->where('menu_id', $menu_id)->delete();
        // Cập nhật lại menu_locations
        $menuLocationModel->where('menu_id', $menu_id)->update(['menu_id' => 0]);
        // Xóa menu
        $menuModel->where('id', $menu_id)->delete();

        return $this->json(['status' => 'success']);
    }

    public function ajaxSave(Request $request) {
        $json_data = $request->input('json_data');
        if (empty($json_data)) {
            return $this->json(['status' => 'error', 'message' => 'Không có dữ liệu gửi lên']);
        }

        $data = json_decode($json_data, true);
        if (!is_array($data) || !isset($data['id'])) {
            return $this->json(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
        }

        $menu_id = (int)$data['id'];
        $menu_name = trim($data['name']);

        $menuModel = new MenuModel();
        $menuItemModel = new MenuItemModel();
        $menuLocationModel = new MenuLocationModel();

        // 1. Cập nhật tên menu
        $menuModel->where('id', $menu_id)->update(['name' => $menu_name]);

        // 2. Cập nhật vị trí menu
        $menuLocationModel->where('menu_id', $menu_id)->update(['menu_id' => 0]); // Xóa liên kết cũ
        if (isset($data['locations']) && is_array($data['locations'])) {
            foreach ($data['locations'] as $loc) {
                // loc định dạng: location_name_lang
                $parts = explode('_', $loc);
                if (count($parts) >= 2) {
                    $lang = array_pop($parts);
                    $loc_name = implode('_', $parts);
                    
                    // Reset các location khác đang trỏ đến cùng loc_name và lang
                    $menuLocationModel->where('location_name', $loc_name)
                                      ->where('lang', $lang)
                                      ->update(['menu_id' => 0]);

                    $menuLocationModel->where('location_name', $loc_name)
                                      ->where('lang', $lang)
                                      ->update(['menu_id' => $menu_id]);
                }
            }
        }

        // 3. Cập nhật menu items
        $menuItemModel->where('menu_id', $menu_id)->delete();
        if (isset($data['items']) && is_array($data['items'])) {
            $this->saveMenuItemsRecursive($data['items'], $menu_id, 0);
        }

        return $this->json(['status' => 'success', 'message' => 'Menu đã được lưu thành công!']);
    }

    private function saveMenuItemsRecursive($items, $menu_id, $parent_id) {
        if (!is_array($items)) return;

        $menuItemModel = new MenuItemModel();
        $sort_order = 0;

        foreach ($items as $item) {
            $sort_order++;

            $itemData = [
                'menu_id'     => $menu_id,
                'parent_id'   => $parent_id,
                'label'       => $item['label'] ?? '',
                'url'         => $item['url'] ?? '',
                'class'       => $item['class'] ?? '',
                'style'       => $item['style'] ?? 'default',
                'block'       => $item['block'] ?? '',
                'target'      => $item['target'] ?? '_self',
                'image'       => $item['image'] ?? '',
                'type'        => $item['type'] ?? '',
                'object_type' => $item['object_type'] ?? '',
                'object_id'   => isset($item['object_id']) && $item['object_id'] !== '' ? $item['object_id'] : null,
                'sort_order'  => $sort_order,
            ];

            $new_item_id = $menuItemModel->insert($itemData);

            if (!empty($item['children']) && is_array($item['children'])) {
                $this->saveMenuItemsRecursive($item['children'], $menu_id, $new_item_id);
            }
        }
    }

    private function buildMenuTree($elements, $parentId = 0) {
        $branch = array();
        if (empty($elements)) {
            return $branch;
        }

        foreach ($elements as $element) {
            if ($element['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($elements, $element['id']);
                
                $item = array(
                    'id'          => $element['id'],
                    'label'       => $element['label'],
                    'url'         => $element['url'],
                    'class'       => $element['class'],
                    'style'       => $element['style'],
                    'block'       => $element['block'],
                    'target'      => $element['target'],
                    'image'       => $element['image'],
                    'type'        => $element['type'] ?? '',
                    'object_type' => $element['object_type'] ?? '',
                    'object_id'   => $element['object_id'] ?? null,
                    'lang'        => $element['lang'] ?? 'vi',
                );

                $item['children'] = $children;
                $branch[] = $item;
            }
        }
        return $branch;
    }

    private function prepareMenuSource(array $items, array $config = [], ?string $idField = null, ?string $parentField = null) {
        $itemsArray = array_map(function($item) {
            return (array)$item;
        }, $items);

        $normalized = $this->normalizeMenuItems($itemsArray, $config);

        if (!$idField || !$parentField || empty($itemsArray) || !isset($itemsArray[0][$parentField])) {
            return $normalized;
        }

        return $this->buildSourceTree($normalized, $idField, $parentField);
    }

    private function normalizeMenuItems(array $rows, array $sourceConfig = []) {
        $normalized = [];

        foreach ($rows as $r) {
            $item = [
                'id'          => isset($r['id']) ? (int)$r['id'] : 0,
                'id_code'     => isset($r['id_code']) ? $r['id_code'] : null,
                'id_loai'     => isset($r['id_loai']) ? $r['id_loai'] : 0,
                'ten'         => isset($r['ten']) ? $r['ten'] : '',
                'alias'       => isset($r['alias']) ? $r['alias'] : '',
                'lang'        => isset($r['lang']) ? $r['lang'] : 'vi',
                'object_type' => isset($r['object_type']) ? $r['object_type'] : '',
                'object_id'   => isset($r['id']) ? $r['id'] : null,
            ];
            foreach ($sourceConfig as $k => $v) {
                $item[$k] = $v;
            }

            $normalized[] = $item;
        }

        return $normalized;
    }

    private function buildSourceTree(array $items, $idField = 'id', $parentField = 'parent_id', $parentId = 0, $langField = 'lang') {
        $tree = [];
        $grouped = [];
        
        foreach ($items as $item) {
            $lang = isset($item[$langField]) ? $item[$langField] : 'vi';
            $parentKey = (isset($item[$parentField]) ? $item[$parentField] : 0) . '_' . $lang;
            $grouped[$parentKey][] = $item;
        }

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

        foreach ($items as $item) {
            if ((isset($item[$parentField]) ? $item[$parentField] : 0) == $parentId) {
                $lang = isset($item[$langField]) ? $item[$langField] : 'vi';
                $item['children'] = $build($item[$idField], $lang);
                $tree[] = $item;
            }
        }

        return $tree;
    }
}
