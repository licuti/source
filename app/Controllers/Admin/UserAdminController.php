<?php
namespace App\Controllers\Admin;

use App\Core\Request;
use App\Models\UserModel;
use App\Models\RoleModel;

class UserAdminController extends BaseAdminController {
    
    /**
     * Danh sách người dùng
     */
    public function index(Request $request) {
        // Query users, eager load role
        $query = UserModel::with('role')->latest('id');
        
        // Tìm kiếm
        if ($keyword = $request->input('keyword')) {
            $query->whereRaw("fullname LIKE ? OR username LIKE ? OR email LIKE ?", ["%$keyword%", "%$keyword%", "%$keyword%"]);
        }
        
        // Phân trang
        $users = $query->paginate(20);
        
        return $this->render('admin.user.index', compact('users'));
    }

    /**
     * Giao diện thêm mới
     */
    public function create(Request $request) {
        $roles = RoleModel::where('is_active', 1)->get();
        $user = new UserModel(); // Dùng để đổ dữ liệu rỗng cho Form dùng chung
        return $this->render('admin.user.form', compact('user', 'roles'));
    }

    /**
     * Xử lý lưu mới
     */
    public function store(Request $request) {
        $data = $this->validateAndPrepareData($request);
        
        // Kiểm tra tài khoản trùng
        if (UserModel::where('username', $data['username'])->exists()) {
            return $this->redirect(route('admin.user.create'))->with('error', 'Tên đăng nhập đã tồn tại!');
        }

        // Tạo mật khẩu băm mới
        $password = $request->input('password');
        if (empty($password)) {
            return $this->redirect(route('admin.user.create'))->with('error', 'Mật khẩu không được để trống!');
        }
        
        // Dùng chuẩn sha1 như AuthController hiện tại (Nếu nâng cấp, có thể đổi sang password_hash)
        $data['password'] = sha1($password);
        $data['created_at'] = time();

        $id = UserModel::insertGetId($data);
        
        if ($request->input('submit_action') == 'save_and_edit') {
            return $this->redirect(route('admin.user.edit', ['id' => $id]))->with('success', 'Đã thêm tài khoản thành công!');
        }
        return $this->redirect(route('admin.user.index'))->with('success', 'Đã thêm tài khoản thành công!');
    }

    /**
     * Giao diện chỉnh sửa
     */
    public function edit(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $user = UserModel::findOrFail($id);
        $roles = RoleModel::where('is_active', 1)->get();
        
        return $this->render('admin.user.form', compact('user', 'roles'));
    }

    /**
     * Xử lý cập nhật
     */
    public function update(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $user = UserModel::findOrFail($id);
        
        $data = $this->validateAndPrepareData($request);
        
        // Đổi mật khẩu nếu có nhập
        $password = $request->input('password');
        if (!empty($password)) {
            $data['password'] = sha1($password);
        }

        // Super Admin gốc (ví dụ id = 1 hoặc is_admin = 1) bảo vệ quyền
        if ($user->is_admin == 1 && session('is_admin') != 1) {
            return $this->redirect(route('admin.user.index'))->with('error', 'Chỉ Super Admin mới được sửa quyền Super Admin!');
        }

        $user->update($data);
        
        if ($request->input('submit_action') == 'save_and_edit') {
            return $this->redirect(route('admin.user.edit', ['id' => $id]))->with('success', 'Đã cập nhật thông tin thành công!');
        }
        return $this->redirect(route('admin.user.index'))->with('success', 'Đã cập nhật thông tin thành công!');
    }

    /**
     * Xóa người dùng
     */
    public function destroy(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $user = UserModel::findOrFail($id);

        if ($user->is_admin == 1) {
            return $this->redirect(route('admin.user.index'))->with('error', 'Không thể xóa tài khoản Super Admin!');
        }
        
        if ($user->id == session('id_user')) {
            return $this->redirect(route('admin.user.index'))->with('error', 'Không thể tự xóa chính mình!');
        }

        UserModel::where('id', $id)->delete();

        return $this->redirect(route('admin.user.index'))->with('success', 'Đã xóa tài khoản thành công!');
    }
    
    /**
     * Cập nhật trạng thái nhanh (hiển thị/khóa)
     */
    public function updateStatusAjax(Request $request) {
        $id = $request->input('id');
        $field = $request->input('field', 'is_active');
        $value = $request->input('value', 0);
        
        $user = UserModel::findOrFail($id);
        
        if ($user->is_admin == 1 && session('is_admin') != 1) {
            return $this->json(['success' => false, 'message' => 'Không thể khóa tài khoản Super Admin!']);
        }
        
        // Chỉ cho phép update is_active
        if ($field === 'is_active') {
            $user->update(['is_active' => $value]);
            return $this->json(['success' => true, 'message' => 'Đã cập nhật trạng thái!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Trường dữ liệu không hợp lệ!']);
    }

    private function validateAndPrepareData(Request $request) {
        return [
            'fullname'   => $request->input('fullname', ''),
            'username'   => $request->input('username', ''),
            'email'      => $request->input('email', ''),
            'phone'      => $request->input('phone', ''),
            'address'    => $request->input('address', ''),
            'avatar'     => $request->input('avatar', ''),
            'birthday'   => $request->input('birthday') ?: null,
            'gender'     => (int)$request->input('gender', 0),
            'role_id'    => (int)$request->input('role_id', 0),
            'is_active'  => $request->input('is_active') !== null ? 1 : 0,
            // 'is_admin' => 0 // Chỉ update qua DB thủ công hoặc logic riêng
        ];
    }
}
