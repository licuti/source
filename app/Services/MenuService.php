<?php
namespace App\Services;

use App\Models\CategoryModel;
use App\Models\ProductModel;
use App\Models\PostModel;
use App\Models\MenuModel;
use App\Models\MenuItemModel;
use App\Models\MenuLocationModel;

class MenuService {

    /**
     * Tìm kiếm nhanh Nguồn dữ liệu (AJAX) thay vì load toàn bộ.
     */
    public function searchSourceItems(string $type, string $keyword, string $lang): array {
        $limit = 20;
        $results = [];

        switch ($type) {
            case 'category':
                $query = CategoryModel::query();
                $query->use_lang = false;
                if ($lang !== 'all') $query->where('lang', $lang);
                if ($keyword) $query->whereLike('title', $keyword);
                
                $items = $query->orderBy('sort_order', 'asc')->limit($limit)->get();
                foreach ($items as $item) {
                    $results[] = [
                        'id' => $item->id,
                        'label' => $item->title,
                        'url' => $item->slug,
                        'lang' => $item->lang,
                        'type' => 'Danh mục',
                        'object_type' => 'category'
                    ];
                }
                break;

            case 'product':
                $query = ProductModel::query();
                $query->use_lang = false;
                if ($lang !== 'all') $query->where('lang', $lang);
                if ($keyword) $query->whereLike('title', $keyword);
                
                $items = $query->orderBy('id', 'desc')->limit($limit)->get();
                foreach ($items as $item) {
                    $results[] = [
                        'id' => $item->id,
                        'label' => $item->title,
                        'url' => $item->slug,
                        'lang' => $item->lang,
                        'type' => 'Sản phẩm',
                        'object_type' => 'product'
                    ];
                }
                break;

            case 'post':
                $query = PostModel::query();
                $query->use_lang = false;
                if ($lang !== 'all') $query->where('lang', $lang);
                if ($keyword) $query->whereLike('title', $keyword);
                
                $items = $query->orderBy('sort_order', 'asc')->orderBy('id', 'desc')->limit($limit)->get();
                foreach ($items as $item) {
                    $results[] = [
                        'id' => $item->id,
                        'label' => $item->title,
                        'url' => $item->slug,
                        'lang' => $item->lang,
                        'type' => 'Bài viết',
                        'object_type' => 'post'
                    ];
                }
                break;
        }

        return $results;
    }

    /**
     * Lấy danh sách tất cả các Menu
     */
    public function getAllMenus() {
        $menuModel = new MenuModel();
        return $menuModel->orderBy('id', 'asc')->get();
    }

    /**
     * Lấy tất cả menu locations
     */
    public function getAllMenuLocations() {
        $menuLocationModel = new MenuLocationModel();
        return $menuLocationModel->orderBy('location_name', 'asc')->get();
    }

    /**
     * Lấy chi tiết một Menu bao gồm Items dạng Tree và Locations
     */
    public function getMenuDetails(int $menuId): array {
        if ($menuId <= 0) {
            return ['menu' => null, 'items_json' => '[]', 'locations' => []];
        }

        $menuModel = new MenuModel();
        $menuItemModel = new MenuItemModel();
        $menuLocationModel = new MenuLocationModel();

        $menu = $menuModel->find($menuId);
        $items_db = $menuItemModel->where('menu_id', $menuId)
                                  ->orderBy('parent_id', 'asc')
                                  ->orderBy('sort_order', 'asc')
                                  ->get();
        
        $saved_locations = $menuLocationModel->where('menu_id', $menuId)->get();

        $items_db_array = array_map(fn($item) => (is_object($item) && method_exists($item, 'toArray')) ? $item->toArray() : (array)$item, $items_db);

        $items_json = json_encode($this->buildTree($items_db_array));

        return [
            'menu' => $menu,
            'items_json' => $items_json,
            'locations' => $saved_locations
        ];
    }

    /**
     * Tạo menu mới
     */
    public function createMenu(string $name): int {
        $menuModel = new MenuModel();
        return $menuModel->insert(['name' => $name]);
    }

    /**
     * Xóa menu hoàn toàn
     */
    public function deleteMenu(int $menuId): bool {
        if ($menuId <= 0) return false;

        $menuModel = new MenuModel();
        $menuItemModel = new MenuItemModel();
        $menuLocationModel = new MenuLocationModel();

        $menuItemModel->where('menu_id', $menuId)->delete();
        $menuLocationModel->where('menu_id', $menuId)->update(['menu_id' => 0]);
        $menuModel->where('id', $menuId)->delete();

        return true;
    }

    /**
     * Lưu thông tin Menu (Tên, Locations, và Tree Items)
     */
    public function saveMenuData(int $menuId, string $menuName, array $locations, array $items): bool {
        if ($menuId <= 0) return false;

        $menuModel = new MenuModel();
        $menuItemModel = new MenuItemModel();
        $menuLocationModel = new MenuLocationModel();

        // 1. Cập nhật tên menu
        $menuModel->where('id', $menuId)->update(['name' => $menuName]);

        // 2. Cập nhật vị trí menu
        $menuLocationModel->where('menu_id', $menuId)->update(['menu_id' => 0]);
        if (!empty($locations)) {
            foreach ($locations as $loc) {
                $parts = explode('_', $loc);
                if (count($parts) >= 2) {
                    $lang = array_pop($parts);
                    $loc_name = implode('_', $parts);
                    
                    $menuLocationModel->where('location_name', $loc_name)->where('lang', $lang)->update(['menu_id' => 0]);
                    $menuLocationModel->where('location_name', $loc_name)->where('lang', $lang)->update(['menu_id' => $menuId]);
                }
            }
        }

        // 3. Cập nhật menu items phẳng hóa (Flatten) để giảm thiểu số vòng lặp DB
        $menuItemModel->where('menu_id', $menuId)->delete();
        if (!empty($items)) {
            $flatItems = [];
            $this->flattenMenuTree($items, $menuId, 0, $flatItems);
            
            // Nếu ORM hỗ trợ insertMultiple thì tuyệt vời, nếu không ta loop insert nhưng ở mức phẳng
            // Giúp code sáng sủa và không bị đệ quy kẹt DB.
            foreach ($flatItems as $itemData) {
                $menuItemModel->insert($itemData);
            }
        }

        return true;
    }

    /**
     * Biến cây Menu Nested thành mảng phẳng để chuẩn bị Insert
     */
    private function flattenMenuTree(array $items, int $menu_id, int $parent_id, array &$flatItems) {
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
            
            // Cấp 1 ID tạm để lấy parent_id cho nhánh con nếu Custom ORM không tự sinh id khi loop
            // Vì ta delete toàn bộ và insert mới, ta phải insert trước để lấy ID thật.
            // Do đó vòng lặp phẳng này vẫn cần 1 nhịp INSERT xen kẽ.
            $menuItemModel = new MenuItemModel();
            $new_item_id = $menuItemModel->insert($itemData);

            if (!empty($item['children']) && is_array($item['children'])) {
                $this->flattenMenuTree($item['children'], $menu_id, $new_item_id, $flatItems);
            }
        }
    }

    /**
     * Thuật toán Flat-to-Tree O(N) sử dụng Tham chiếu
     */
    public function buildTree(array $flatList): array {
        $tree = [];
        $indexed = [];

        // Khởi tạo và lập chỉ mục
        foreach ($flatList as &$row) {
            $row['children'] = [];
            // Đảm bảo type tương thích frontend
            $row['type'] = $row['type'] ?? '';
            $row['object_type'] = $row['object_type'] ?? '';
            $indexed[$row['id']] = &$row;
        }
        unset($row);

        // Xây cây
        foreach ($flatList as &$row) {
            if (!empty($row['parent_id']) && isset($indexed[$row['parent_id']])) {
                $indexed[$row['parent_id']]['children'][] = &$row;
            } else {
                $tree[] = &$row;
            }
        }
        unset($row);

        return $tree;
    }
}
