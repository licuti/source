<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use ModuleAdminModel;

class MenuAdminController extends BaseAdminController {
    
    /**
     * Hiển thị giao diện quản lý Menu (Drag & Drop)
     */
    public function index(Request $request) {
        // Lấy tất cả menu sắp xếp theo sort_order
        $menus = \ModuleAdminModel::orderBy('sort_order', 'ASC')->get();
        
        // Build cấu trúc cây để Nestable dễ render
        $tree = $this->buildTree($menus);
        
        // Lấy danh sách menu cấp 1 để làm option cho select box (nếu cần ở form tạo nhanh)
        $parentOptions = \ModuleAdminModel::where('parent', 0)->orderBy('sort_order', 'ASC')->get();
        
        return $this->render('admin.system_menu.index', compact('tree', 'parentOptions', 'menus'));
    }

    /**
     * Hàm phụ trợ đệ quy tạo cây menu
     */
    private function buildTree($elements, $parentId = 0) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element->parent == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }

    /**
     * Lưu menu mới tạo từ form
     */
    public function store(Request $request) {
        $data = [
            'name'             => $request->input('name', ''),
            'alias'            => $request->input('alias', ''),
            'route_name'       => $request->input('route_name', ''),
            'icon'             => $request->input('icon', 'fa-circle'),
            'parent'           => (int)$request->input('parent', 0),
            'permission_level' => (int)$request->input('permission_level', 1),
            'is_active'        => $request->input('is_active') !== null ? 1 : 0,
            'badge_query'      => $request->input('badge_query', ''),
            'badge_color'      => $request->input('badge_color', 'danger'),
            'sort_order'       => 0 // Mặc định là 0, sẽ được sort lại sau
        ];

        \ModuleAdminModel::insert($data);
        return $this->redirect(route('admin.system_menu.index'));
    }

    /**
     * Lấy dữ liệu 1 menu trả về form sửa qua AJAX
     */
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        $menu = \ModuleAdminModel::where('id', $id)->first();
        if ($menu) {
            return $this->json(['success' => true, 'data' => $menu]);
        }
        return $this->json(['success' => false, 'message' => 'Không tìm thấy menu']);
    }

    /**
     * Cập nhật thông tin menu
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        $data = [
            'name'             => $request->input('name', ''),
            'alias'            => $request->input('alias', ''),
            'route_name'       => $request->input('route_name', ''),
            'icon'             => $request->input('icon', 'fa-circle'),
            'parent'           => (int)$request->input('parent', 0),
            'permission_level' => (int)$request->input('permission_level', 1),
            'badge_query'      => $request->input('badge_query', ''),
            'badge_color'      => $request->input('badge_color', 'danger'),
            'is_active'        => $request->input('is_active') !== null ? 1 : 0
        ];

        // Ngăn chống loop cha con
        if ($id == $data['parent']) {
            $data['parent'] = 0;
        }

        \ModuleAdminModel::where('id', $id)->update($data);
        return $this->redirect(route('admin.system_menu.index'));
    }

    /**
     * Xóa menu và các menu con của nó
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? $id[1] ?? 0) : $id;
        
        // Xóa menu hiện tại
        \ModuleAdminModel::where('id', $id)->delete();
        // Xóa luôn các menu con
        \ModuleAdminModel::where('parent', $id)->delete();
        
        return $this->redirect(route('admin.system_menu.index'));
    }

    /**
     * Nhận JSON từ Nestable kéo thả và cập nhật lại parent, sort_order
     */
    public function updateSortAjax(Request $request) {
        $jsonString = $request->input('data');
        $items = json_decode($jsonString, true);

        if (is_array($items)) {
            $this->updateTreeSort($items, 0);
            return $this->json(['success' => true, 'message' => 'Đã lưu cấu trúc menu!']);
        }

        return $this->json(['success' => false, 'message' => 'Dữ liệu không hợp lệ!']);
    }

    /**
     * Hàm đệ quy cập nhật parent & sort
     */
    private function updateTreeSort($items, $parentId = 0) {
        $sortOrder = 1;
        foreach ($items as $item) {
            $id = (int)$item['id'];
            if ($id > 0) {
                \ModuleAdminModel::where('id', $id)->update([
                    'parent' => $parentId,
                    'sort_order' => $sortOrder
                ]);
            }
            // Đệ quy nếu có children
            if (!empty($item['children']) && is_array($item['children'])) {
                $this->updateTreeSort($item['children'], $id);
            }
            $sortOrder++;
        }
    }
}
