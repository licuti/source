<?php
namespace App\Controllers\Admin;

use App\Core\Request;

use App\Models\FormModel;
use App\Models\FormFieldModel;
use App\Models\FormSubmissionModel;

class FormBuilderController extends BaseAdminController {
    

    public function index() {
        $forms = FormModel::orderBy('created_at', 'DESC')->get();
        // Count unread submissions for each form
        foreach ($forms as $form) {
            $form->unread_count = FormSubmissionModel::where('form_id', $form->id)
                                ->where('status', FormSubmissionModel::STATUS_NEW)
                                ->count();
        }
        return $this->render('admin.form_builder.index', ['forms' => $forms]);
    }
    
    public function ajax(Request $request) {
        $action = $request->input('action');
        $id = $request->input('id');
        
        try {
            switch ($action) {
                case 'create':
                    $name = $request->input('name');
                    $code = $request->input('code');
                    $email_to = $request->input('email_to');
                    
                    if (empty($name) || empty($code)) {
                        throw new \Exception('Vui lòng nhập Tên form và Mã shortcode.');
                    }
                    
                    // Check duplicate code
                    $exists = FormModel::where('code', $code)->first();
                    if ($exists) {
                        throw new \Exception('Mã shortcode này đã tồn tại, vui lòng chọn mã khác.');
                    }
                    
                    $mail_settings = [
                        'admin' => [
                            'enable' => $request->input('admin_mail_enable') ? true : false,
                            'subject' => $request->input('admin_mail_subject'),
                            'body' => $request->input('admin_mail_body')
                        ],
                        'customer' => [
                            'enable' => $request->input('customer_mail_enable') ? true : false,
                            'field' => $request->input('customer_mail_field'),
                            'subject' => $request->input('customer_mail_subject'),
                            'body' => $request->input('customer_mail_body')
                        ]
                    ];
                    
                    FormModel::create([
                        'name' => $name,
                        'code' => $code,
                        'email_to' => $email_to,
                        'mail_settings' => json_encode($mail_settings, JSON_UNESCAPED_UNICODE),
                        'is_active' => 1
                    ]);
                    
                    return $this->json(['success' => true, 'message' => 'Thêm form thành công.']);
                    
                case 'update':
                    $name = $request->input('name');
                    $code = $request->input('code');
                    $email_to = $request->input('email_to');
                    $success_message = $request->input('success_message');
                    $is_active = $request->input('is_active') ? 1 : 0;
                    
                    if (empty($name) || empty($code)) {
                        throw new \Exception('Vui lòng nhập Tên form và Mã shortcode.');
                    }
                    
                    // Check duplicate code
                    $exists = FormModel::where('code', $code)->where('id', '!=', $id)->first();
                    if ($exists) {
                        throw new \Exception('Mã shortcode này đã tồn tại, vui lòng chọn mã khác.');
                    }
                    
                    $mail_settings = [
                        'admin' => [
                            'enable' => $request->input('admin_mail_enable') ? true : false,
                            'subject' => $request->input('admin_mail_subject'),
                            'body' => $request->input('admin_mail_body')
                        ],
                        'customer' => [
                            'enable' => $request->input('customer_mail_enable') ? true : false,
                            'field' => $request->input('customer_mail_field'),
                            'subject' => $request->input('customer_mail_subject'),
                            'body' => $request->input('customer_mail_body')
                        ]
                    ];
                    
                    FormModel::update($id, [
                        'name' => $name,
                        'code' => $code,
                        'email_to' => $email_to,
                        'success_message' => $success_message,
                        'mail_settings' => json_encode($mail_settings, JSON_UNESCAPED_UNICODE),
                        'is_active' => $is_active
                    ]);
                    
                    return $this->json(['success' => true, 'message' => 'Cập nhật thành công.']);
                    
                case 'delete':
                    FormModel::delete($id);
                    return $this->json(['success' => true, 'message' => 'Xóa form thành công.']);
                    
                case 'get':
                    $form = FormModel::find($id);
                    if ($form) {
                        $fields = FormFieldModel::where('form_id', $id)->orderBy('sort_order', 'ASC')->get();
                        $form->fields = $fields;
                        return $this->json(['success' => true, 'data' => $form]);
                    }
                    throw new \Exception('Không tìm thấy form.');
                    
                case 'save_builder':
                    // Lưu cấu trúc fields của form
                    $fieldsJson = $request->input('fields');
                    $fields = json_decode($fieldsJson, true);
                    
                    if (!is_array($fields)) {
                        throw new \Exception('Dữ liệu không hợp lệ.');
                    }
                    
                    // Xóa các field cũ
                    FormFieldModel::where('form_id', $id)->delete();
                    
                    // Thêm lại các field mới
                    $sort = 0;
                    foreach ($fields as $f) {
                        FormFieldModel::insert([
                            'form_id' => $id,
                            'type' => $f['type'],
                            'name' => $f['name'],
                            'label' => $f['label'],
                            'placeholder' => $f['placeholder'] ?? '',
                            'options' => json_encode($f['options'] ?? [], JSON_UNESCAPED_UNICODE),
                            'advanced_settings' => !empty($f['advanced_settings']) ? json_encode($f['advanced_settings'], JSON_UNESCAPED_UNICODE) : null,
                            'is_required' => $f['is_required'] ? 1 : 0,
                            'col_width' => $f['col_width'] ?? 'col-md-12',
                            'sort_order' => $sort++
                        ]);
                    }
                    
                    return $this->json(['success' => true, 'message' => 'Lưu cấu trúc form thành công.']);
                    
                default:
                    throw new \Exception('Hành động không hợp lệ.');
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    public function builder(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $form = FormModel::find($id);
        if (!$form) {
            return redirect(route('admin.form.index'))->with('error', 'Không tìm thấy form.');
        }
        
        $fields = FormFieldModel::where('form_id', $id)->orderBy('sort_order', 'ASC')->get();
        return $this->render('admin.form_builder.builder', [
            'form' => $form,
            'fields' => $fields
        ]);
    }
    
    public function preview(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $form = FormModel::find($id);
        if (!$form) {
            return redirect(route('admin.form.index'))->with('error', 'Không tìm thấy form.');
        }
        
        $fields = FormFieldModel::where('form_id', $id)->orderBy('sort_order', 'ASC')->get();
        return $this->render('admin.form_builder.preview', [
            'form' => $form,
            'fields' => $fields
        ]);
    }
    
    public function submissions(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $form = FormModel::find($id);
        if (!$form) {
            return redirect(route('admin.form.index'))->with('error', 'Không tìm thấy form.');
        }
        
        $status = $request->input('status');
        
        $query = FormSubmissionModel::where('form_id', $id);
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }
        
        $submissions = $query->orderBy('created_at', 'DESC')->paginate(20);
        
        return $this->render('admin.form_builder.submissions', [
            'form' => $form,
            'submissions' => $submissions
        ]);
    }
    
    public function export(Request $request, $id) {
        $id = is_array($id) ? ($id['id'] ?? 0) : $id;
        $form = FormModel::find($id);
        if (!$form) {
            return redirect(route('admin.form.index'))->with('error', 'Không tìm thấy form.');
        }
        
        $submissions = FormSubmissionModel::where('form_id', $id)->orderBy('created_at', 'DESC')->get();
        
        // Prepare CSV Output
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=form_' . $form->code . '_export_' . date('Ymd_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fputs($output, "\xEF\xBB\xBF");
        
        // Find all possible keys in JSON
        $headers = ['ID', 'Trạng thái', 'IP', 'Thời gian gửi', 'Ghi chú / Phản hồi'];
        $jsonKeys = [];
        
        foreach ($submissions as $sub) {
            $data = json_decode($sub->data_payload, true) ?? [];
            foreach ($data as $key => $val) {
                if (!in_array($key, $jsonKeys)) {
                    $jsonKeys[] = $key;
                }
            }
        }
        
        // Append dynamic JSON keys to headers
        $headers = array_merge($headers, $jsonKeys);
        fputcsv($output, $headers);
        
        // Output rows
        foreach ($submissions as $sub) {
            $statusText = 'Mới';
            if ($sub->status == FormSubmissionModel::STATUS_READ) $statusText = 'Đã đọc';
            if ($sub->status == FormSubmissionModel::STATUS_REPLIED) $statusText = 'Đã phản hồi';
            
            $row = [
                $sub->id,
                $statusText,
                $sub->ip_address,
                $sub->created_at,
                $sub->reply_content
            ];
            
            $data = json_decode($sub->data_payload, true) ?? [];
            foreach ($jsonKeys as $key) {
                $row[] = isset($data[$key]) ? $data[$key] : '';
            }
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    public function submissionAjax(Request $request) {
        $action = $request->input('action');
        $id = $request->input('id');
        
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
                        
                        return $this->json(['success' => true, 'data' => $sub]);
                    }
                    throw new \Exception('Không tìm thấy thư liên hệ.');
                    
                case 'reply':
                    $reply_content = $request->input('reply_content');
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
                    
                    return $this->json(['success' => true, 'message' => 'Phản hồi đã được gửi.']);
                    
                case 'delete':
                    FormSubmissionModel::delete($id);
                    return $this->json(['success' => true, 'message' => 'Xóa thư liên hệ thành công.']);
                    
                default:
                    throw new \Exception('Hành động không hợp lệ.');
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
