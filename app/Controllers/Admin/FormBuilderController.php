<?php
namespace App\Controllers\Admin;

use App\Models\FormModel;
use App\Models\FormFieldModel;
use App\Models\FormSubmissionModel;

class FormBuilderController extends BaseAdminController {
    
    public function __construct() {
        parent::__construct();
        $this->requirePermission(33); // ID của module Khách hàng liên hệ
    }

    public function index() {
        $forms = FormModel::orderBy('created_at', 'DESC')->get();
        // Count unread submissions for each form
        foreach ($forms as $form) {
            $form->unread_count = FormSubmissionModel::where('form_id', $form->id)
                                ->where('status', FormSubmissionModel::STATUS_NEW)
                                ->count();
        }
        return view('admin.form_builder.index', ['forms' => $forms]);
    }
    
    public function ajax() {
        $action = request()->post('action');
        $id = request()->post('id');
        
        try {
            switch ($action) {
                case 'create':
                    $name = request()->post('name');
                    $code = request()->post('code');
                    $email_to = request()->post('email_to');
                    
                    if (empty($name) || empty($code)) {
                        throw new \Exception('Vui lòng nhập Tên form và Mã shortcode.');
                    }
                    
                    // Check duplicate code
                    $exists = FormModel::where('code', $code)->first();
                    if ($exists) {
                        throw new \Exception('Mã shortcode này đã tồn tại, vui lòng chọn mã khác.');
                    }
                    
                    FormModel::create([
                        'name' => $name,
                        'code' => $code,
                        'email_to' => $email_to,
                        'is_active' => 1
                    ]);
                    
                    return response()->json(['success' => true, 'message' => 'Thêm form thành công.']);
                    
                case 'update':
                    $name = request()->post('name');
                    $code = request()->post('code');
                    $email_to = request()->post('email_to');
                    $success_message = request()->post('success_message');
                    $is_active = request()->post('is_active') ? 1 : 0;
                    
                    if (empty($name) || empty($code)) {
                        throw new \Exception('Vui lòng nhập Tên form và Mã shortcode.');
                    }
                    
                    // Check duplicate code
                    $exists = FormModel::where('code', $code)->where('id', '!=', $id)->first();
                    if ($exists) {
                        throw new \Exception('Mã shortcode này đã tồn tại, vui lòng chọn mã khác.');
                    }
                    
                    FormModel::update($id, [
                        'name' => $name,
                        'code' => $code,
                        'email_to' => $email_to,
                        'success_message' => $success_message,
                        'is_active' => $is_active
                    ]);
                    
                    return response()->json(['success' => true, 'message' => 'Cập nhật thành công.']);
                    
                case 'delete':
                    FormModel::delete($id);
                    return response()->json(['success' => true, 'message' => 'Xóa form thành công.']);
                    
                case 'get':
                    $form = FormModel::find($id);
                    if ($form) {
                        return response()->json(['success' => true, 'data' => $form]);
                    }
                    throw new \Exception('Không tìm thấy form.');
                    
                case 'save_builder':
                    // Lưu cấu trúc fields của form
                    $fieldsJson = request()->post('fields');
                    $fields = json_decode($fieldsJson, true);
                    
                    if (!is_array($fields)) {
                        throw new \Exception('Dữ liệu không hợp lệ.');
                    }
                    
                    // Xóa các field cũ
                    FormFieldModel::where('form_id', $id)->delete();
                    
                    // Thêm lại các field mới
                    $sortOrder = 0;
                    foreach ($fields as $field) {
                        FormFieldModel::create([
                            'form_id' => $id,
                            'type' => $field['type'] ?? 'text',
                            'name' => $field['name'] ?? 'field_' . time(),
                            'label' => $field['label'] ?? 'Trường dữ liệu',
                            'placeholder' => $field['placeholder'] ?? '',
                            'options' => !empty($field['options']) ? json_encode($field['options'], JSON_UNESCAPED_UNICODE) : null,
                            'is_required' => !empty($field['is_required']) ? 1 : 0,
                            'sort_order' => $sortOrder++
                        ]);
                    }
                    
                    return response()->json(['success' => true, 'message' => 'Lưu cấu trúc form thành công.']);
                    
                default:
                    throw new \Exception('Hành động không hợp lệ.');
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function builder($id) {
        $form = FormModel::find($id);
        if (!$form) {
            return redirect(route('admin.form.index'))->with('error', 'Không tìm thấy form.');
        }
        
        $fields = FormFieldModel::where('form_id', $id)->orderBy('sort_order', 'ASC')->get();
        return view('admin.form_builder.builder', [
            'form' => $form,
            'fields' => $fields
        ]);
    }
    
    public function submissions($id) {
        $form = FormModel::find($id);
        if (!$form) {
            return redirect(route('admin.form.index'))->with('error', 'Không tìm thấy form.');
        }
        
        $status = request()->get('status');
        
        $query = FormSubmissionModel::where('form_id', $id);
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        
        $submissions = $query->orderBy('created_at', 'DESC')->paginate(20);
        
        return view('admin.form_builder.submissions', [
            'form' => $form,
            'submissions' => $submissions
        ]);
    }
    
    public function submissionAjax() {
        $action = request()->post('action');
        $id = request()->post('id');
        
        try {
            switch ($action) {
                case 'get':
                    $sub = FormSubmissionModel::find($id);
                    if ($sub) {
                        // Đánh dấu đã đọc
                        if ($sub->status == FormSubmissionModel::STATUS_NEW) {
                            FormSubmissionModel::update($id, ['status' => FormSubmissionModel::STATUS_READ]);
                            $sub->status = FormSubmissionModel::STATUS_READ;
                        }
                        
                        // Parse JSON data
                        $sub->data_payload = json_decode($sub->data_payload, true);
                        
                        return response()->json(['success' => true, 'data' => $sub]);
                    }
                    throw new \Exception('Không tìm thấy thư liên hệ.');
                    
                case 'reply':
                    $reply_content = request()->post('reply_content');
                    if (empty($reply_content)) {
                        throw new \Exception('Vui lòng nhập nội dung phản hồi.');
                    }
                    
                    $sub = FormSubmissionModel::find($id);
                    if (!$sub) {
                        throw new \Exception('Không tìm thấy thư liên hệ.');
                    }
                    
                    // Cập nhật trạng thái và nội dung phản hồi
                    FormSubmissionModel::update($id, [
                        'status' => FormSubmissionModel::STATUS_REPLIED,
                        'reply_content' => $reply_content,
                        'replied_at' => date('Y-m-d H:i:s'),
                        'replied_by' => session('admin_id', 0)
                    ]);
                    
                    // Todo: Tích hợp Gửi Email thực tế ở đây nếu cần thiết
                    // Cần parse JSON để lấy email của khách, nếu field name có chữ "email"
                    
                    return response()->json(['success' => true, 'message' => 'Đã lưu phản hồi.']);
                    
                case 'delete':
                    FormSubmissionModel::delete($id);
                    return response()->json(['success' => true, 'message' => 'Xóa thư liên hệ thành công.']);
                    
                default:
                    throw new \Exception('Hành động không hợp lệ.');
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
