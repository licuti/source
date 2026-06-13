<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Validator;
use App\Services\MenuService;

class MenuController extends BaseAdminController {
    
    private MenuService $menuService;

    public function __construct() {
        parent::__construct();
        $this->menuService = new MenuService();
    }

    public function index(Request $request) {
        $menus = $this->menuService->getAllMenus();
        $menu_location = $this->menuService->getAllMenuLocations();

        $current_menu_id = 0;
        $current_menu = null;
        $current_menu_items_json = '[]';
        $saved_locations_for_current_menu = [];

        if ($request->has('menu')) {
            $current_menu_id = (int)$request->input('menu');
        } else if (!empty($menus)) {
            $current_menu_id = $menus[0]->id;
        }

        if ($current_menu_id > 0) {
            $details = $this->menuService->getMenuDetails($current_menu_id);
            $current_menu = $details['menu'];
            $current_menu_items_json = $details['items_json'];
            $saved_locations_for_current_menu = $details['locations'];
        }

        return $this->render('admin.menu.index', compact(
            'menus', 
            'menu_location', 
            'current_menu_id', 
            'current_menu', 
            'current_menu_items_json',
            'saved_locations_for_current_menu'
        ));
    }

    /**
     * API TÌm kiếm nguồn dữ liệu cho Menu (AJAX)
     */
    public function ajaxSearchSource(Request $request) {
        $type = $request->input('type', '');
        $q = trim($request->input('q', ''));
        $lang = $request->input('lang', 'all');

        if (empty($type)) {
            return $this->json(['status' => 'error', 'message' => 'Loại dữ liệu không hợp lệ.']);
        }

        $items = $this->menuService->searchSourceItems($type, $q, $lang);

        return $this->json([
            'status' => 'success',
            'data' => $items
        ]);
    }

    public function ajaxCreate(Request $request) {
        $validator = new Validator($request->all(), [
            'name' => 'required|max:255'
        ], [
            'name.required' => 'Vui lòng nhập tên menu.',
            'name.max'      => 'Tên menu không được vượt quá 255 ký tự.'
        ]);

        if ($validator->fails()) {
            return $this->json(['status' => 'error', 'message' => $validator->firstError()]);
        }

        $name = trim($request->input('name'));
        $id = $this->menuService->createMenu($name);

        if ($id) {
            return $this->json(['status' => 'success', 'menu_id' => $id]);
        }

        return $this->json(['status' => 'error', 'message' => 'Có lỗi xảy ra khi tạo menu.']);
    }

    public function ajaxDelete(Request $request) {
        $menu_id = (int)$request->input('menu_id');
        if ($menu_id <= 0) {
            return $this->json(['status' => 'error', 'message' => 'ID menu không hợp lệ.']);
        }

        $deleted = $this->menuService->deleteMenu($menu_id);

        if ($deleted) {
            return $this->json(['status' => 'success']);
        }

        return $this->json(['status' => 'error', 'message' => 'Có lỗi xảy ra khi xóa menu.']);
    }

    public function ajaxSave(Request $request) {
        $json_data = $request->input('json_data');
        if (empty($json_data)) {
            return $this->json(['status' => 'error', 'message' => 'Không có dữ liệu gửi lên.']);
        }

        $data = json_decode($json_data, true);
        if (!is_array($data)) {
            return $this->json(['status' => 'error', 'message' => 'Dữ liệu không đúng định dạng.']);
        }

        $validator = new Validator($data, [
            'id' => 'required',
            'name' => 'required|max:255'
        ], [
            'id.required'   => 'Không xác định được Menu cần lưu.',
            'name.required' => 'Tên menu không được để trống.',
            'name.max'      => 'Tên menu quá dài.'
        ]);

        if ($validator->fails()) {
            return $this->json(['status' => 'error', 'message' => $validator->firstError()]);
        }

        $menu_id = (int)$data['id'];
        $menu_name = trim($data['name']);
        $locations = isset($data['locations']) && is_array($data['locations']) ? $data['locations'] : [];
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

        $saved = $this->menuService->saveMenuData($menu_id, $menu_name, $locations, $items);

        if ($saved) {
            return $this->json(['status' => 'success', 'message' => 'Menu đã được lưu thành công!']);
        }

        return $this->json(['status' => 'error', 'message' => 'Lỗi cập nhật CSDL.']);
    }
}
