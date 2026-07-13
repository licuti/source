<?php

namespace App\Controllers\Admin;

use App\Models\CustomerModel;
use App\Models\LocationModel;
use App\Core\Request;

class CustomerController extends BaseAdminController
{
    public function index(Request $request)
    {
        $query = CustomerModel::query();
        
        $keyword = $request->input('keyword');
        if (!empty($keyword)) {
            $kw = '%' . $keyword . '%';
            $query->whereRaw('(fullname LIKE ? OR email LIKE ? OR phone LIKE ? OR code LIKE ?)', [$kw, $kw, $kw, $kw]);
        }
        
        $hien_thi = $request->input('hien_thi');
        if ($hien_thi !== null && $hien_thi !== '') {
            $query->where('status', $hien_thi);
        }

        $items = $query->orderBy('id', 'DESC')->paginate(20);

        return $this->render('admin.customer.index', [
            'items' => $items,
            'title' => 'Quản lý Thành viên',
            'keyword' => $keyword,
            'hien_thi' => $hien_thi
        ]);
    }

    public function create()
    {
        $countries = LocationModel::where('type', 'country')->orderBy('name', 'ASC')->get();
        return $this->render('admin.customer.form', [
            'title' => 'Thêm Khách hàng mới',
            'item' => [],
            'countries' => $countries,
            'provinces' => [],
            'districts' => [],
            'wards' => []
        ]);
    }

    public function edit(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        $item = CustomerModel::find($id);
        
        if (!$item) {
            return $this->redirect(route('admin.customer.index'))->with('error', 'Không tìm thấy khách hàng!');
        }

        $countries = LocationModel::where('type', 'country')->orderBy('name', 'ASC')->get();
        $provinces = $item->country_id ? LocationModel::where('type', 'province')->where('parent_id', $item->country_id)->orderBy('name', 'ASC')->get() : [];
        $districts = $item->province_id ? LocationModel::where('type', 'district')->where('parent_id', $item->province_id)->orderBy('name', 'ASC')->get() : [];
        $wards = $item->district_id ? LocationModel::where('type', 'ward')->where('parent_id', $item->district_id)->orderBy('name', 'ASC')->get() : [];

        return $this->render('admin.customer.form', [
            'title' => 'Sửa thông tin Khách hàng: ' . $item->fullname,
            'item' => $item,
            'countries' => $countries,
            'provinces' => $provinces,
            'districts' => $districts,
            'wards' => $wards
        ]);
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        return $this->save($request, $id);
    }

    private function save(Request $request, $id = null)
    {
        $email = trim($request->input('email'));
        $fullname = trim($request->input('fullname'));
        
        if (empty($email) || empty($fullname)) {
            return $this->back()->with('error', 'Tên và Email không được để trống!');
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->back()->with('error', 'Định dạng Email không hợp lệ!');
        }

        // Check duplicate email
        $checkQuery = CustomerModel::where('email', $email);
        if ($id) {
            $checkQuery->where('id', '!=', $id);
        }
        if ($checkQuery->exists()) {
            return $this->back()->with('error', 'Email này đã được sử dụng bởi khách hàng khác!');
        }

        $data = [
            'fullname' => $fullname,
            'phone' => trim($request->input('phone', '')),
            'avatar' => trim($request->input('avatar', '')),
            'email' => $email,
            'gender' => intval($request->input('gender', 0)),
            'address' => trim($request->input('address', '')),
            'country_id' => intval($request->input('country_id', 0)),
            'province_id' => intval($request->input('province_id', 0)),
            'district_id' => intval($request->input('district_id', 0)),
            'ward_id' => intval($request->input('ward_id', 0)),
            'status' => $request->input('status') !== null ? 1 : 0
        ];

        // Code auto generation if empty
        $code = trim($request->input('code', ''));
        if (empty($code) && !$id) {
            // Generate basic code on create
            $data['code'] = 'KH-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } elseif (!empty($code)) {
            $data['code'] = $code;
        }

        // Handle Birthday
        $birthday = $request->input('birthday');
        if (!empty($birthday)) {
            $data['birthday'] = $birthday;
        }

        // Handle Password
        $password = $request->input('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        if ($id) {
            CustomerModel::where('id', $id)->update($data);
            $msg = 'Cập nhật khách hàng thành công!';
        } else {
            // On create, if no password provided, generate random
            if (empty($password)) {
                $data['password'] = password_hash(substr(md5(mt_rand()), 0, 8), PASSWORD_BCRYPT);
            }
            $id = CustomerModel::insertGetId($data);
            $msg = 'Thêm khách hàng mới thành công!';
        }

        $saveAction = $request->input('save_action', 'exit');
        if ($saveAction === 'continue') {
            return $this->redirect(route('admin.customer.edit', ['id' => $id]))->with('success', $msg);
        }
        
        return $this->redirect(route('admin.customer.index'))->with('success', $msg);
    }

    public function updateStatusAjax(Request $request)
    {
        $id = $request->input('id');
        $field = $request->input('field', 'status');
        $value = $request->input('value', 0);
        
        if ($field !== 'status') {
            return $this->json(['success' => false, 'message' => 'Trường không hợp lệ!']);
        }
        
        $item = CustomerModel::find($id);
        if ($item) {
            CustomerModel::where('id', $id)->update([$field => $value]);
            return $this->json(['success' => true, 'message' => 'Cập nhật trạng thái thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Không tìm thấy dữ liệu!']);
    }

    public function destroy(Request $request, $params = [])
    {
        $id = is_array($params) ? ($params['id'] ?? 0) : $params;
        
        // TODO: Check if customer has orders before deleting.
        // For now, allow deletion.
        if (CustomerModel::where('id', $id)->delete()) {
            return $this->json(['success' => true, 'message' => 'Xóa khách hàng thành công!']);
        }
        
        return $this->json(['success' => false, 'message' => 'Có lỗi xảy ra, không thể xóa!']);
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids) || !is_array($ids)) {
            return $this->json(['success' => false, 'message' => 'Không có mục nào được chọn!']);
        }
        
        // TODO: Skip customers with orders.
        $successCount = CustomerModel::whereIn('id', $ids)->delete();
        
        if ($successCount > 0) {
            return $this->json(['success' => true, 'message' => "Đã xóa thành công {$successCount} khách hàng."]);
        }
        
        return $this->json(['success' => false, 'message' => 'Không thể xóa các khách hàng đã chọn!']);
    }
}
