<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\ModuleAdminModel;

class RoleAdminController extends BaseAdminController {
    
    public function index(Request $request) {
        $keyword = $request->input('keyword');
        $query = RoleModel::query();
        
        if (!empty($keyword)) {
            $query->where('name', 'LIKE', "%{$keyword}%");
        }
        
        $roles = $query->paginate(20);
        return $this->render('admin.role.index', compact('roles', 'keyword'));
    }

    public function create() {
        $modules = ModuleAdminModel::where('parent', 0)->orderBy('sort_order', 'ASC')->get();
        return $this->render('admin.role.form', compact('modules'));
    }

    public function store(Request $request) {
        $name = $request->input('name');
        $description = $request->input('description');
        $is_active = $request->input('is_active', 1);
        
        $role_id = RoleModel::insert([
            'name' => $name,
            'description' => $description,
            'is_active' => $is_active,
            'created_at' => time(),
            'updated_at' => time()
        ]);

        if ($role_id) {
            $this->savePermissions($request, $role_id);
        }

        if ($request->input('save_action') === 'continue') {
            return $this->redirect(route('admin.role.edit', ['id' => $role_id]))->with('success', 'Thêm nhóm quyền thành công!');
        }
        return $this->redirect(route('admin.role.index'))->with('success', 'Thêm nhóm quyền thành công!');
    }

    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $role = RoleModel::findOrFail($id);
        $modules = ModuleAdminModel::where('parent', 0)->orderBy('sort_order', 'ASC')->get();
        
        // Load existing permissions
        $permissionsList = RolePermissionModel::where('role_id', $id)->get();
        $permissions = [];
        foreach ($permissionsList as $p) {
            $permissions[$p->module_id] = [
                'can_view' => $p->can_view,
                'can_add' => $p->can_add,
                'can_edit' => $p->can_edit,
                'can_delete' => $p->can_delete
            ];
        }

        return $this->render('admin.role.form', compact('role', 'modules', 'permissions'));
    }

    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $role = RoleModel::findOrFail($id);
        $role->update([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'is_active' => $request->input('is_active', 0),
            'updated_at' => time()
        ]);

        $this->savePermissions($request, $id);

        if ($request->input('save_action') === 'continue') {
            return $this->redirect(route('admin.role.edit', ['id' => $id]))->with('success', 'Cập nhật nhóm quyền thành công!');
        }
        return $this->redirect(route('admin.role.index'))->with('success', 'Cập nhật nhóm quyền thành công!');
    }

    private function savePermissions(Request $request, $role_id) {
        RolePermissionModel::where('role_id', $role_id)->delete();
        
        $permsInput = $request->input('permissions', []);
        foreach ($permsInput as $module_id => $actions) {
            // Check if at least one permission is granted
            if (isset($actions['can_view']) || isset($actions['can_add']) || isset($actions['can_edit']) || isset($actions['can_delete'])) {
                RolePermissionModel::insert([
                    'role_id' => $role_id,
                    'module_id' => $module_id,
                    'can_view' => isset($actions['can_view']) ? 1 : 0,
                    'can_add' => isset($actions['can_add']) ? 1 : 0,
                    'can_edit' => isset($actions['can_edit']) ? 1 : 0,
                    'can_delete' => isset($actions['can_delete']) ? 1 : 0
                ]);
            }
        }
    }

    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $role = RoleModel::findOrFail($id);
        
        if ($role->is_system == 1) {
            return $this->redirect(route('admin.role.index'))->with('error', 'Không thể xóa nhóm quyền hệ thống!');
        }
        
        // Kiểm tra xem có user nào đang dùng không
        $usersCount = \App\Models\UserModel::where('role_id', $id)->count();
        if ($usersCount > 0) {
            return $this->redirect(route('admin.role.index'))->with('error', 'Không thể xóa nhóm quyền đang có người dùng!');
        }
        
        RolePermissionModel::where('role_id', $id)->delete();
        RoleModel::where('id', $id)->delete();
        return $this->redirect(route('admin.role.index'))->with('success', 'Xóa nhóm quyền thành công!');
    }

    public function updateStatusAjax(Request $request) {
        $id = $request->input('id');
        $field = $request->input('field', 'is_active');
        $value = $request->input('value', 0);
        
        $role = RoleModel::findOrFail($id);
        if ($role->is_system == 1 && $field === 'is_active' && $value == 0) {
            return $this->json(['success' => false, 'message' => 'Không thể vô hiệu hóa nhóm quyền hệ thống!']);
        }
        if ($field === 'is_active') {
            $role->update(['is_active' => $value]);
            return $this->json(['success' => true, 'message' => 'Đã cập nhật trạng thái!']);
        }
        return $this->json(['success' => false, 'message' => 'Trường dữ liệu không hợp lệ!']);
    }
}
