<?php
namespace App\Controllers\Frontend;

use App\Models\FormModel;
use App\Models\FormFieldModel;
use App\Models\FormSubmissionModel;

class FormController extends \App\Controllers\Controller {
    
    public function submit($id) {
        $form = FormModel::find($id);
        
        if (!$form || !$form->is_active) {
            return $this->backWithError('Biểu mẫu không tồn tại hoặc đã bị tắt.');
        }
        
        // 1. Kiểm tra Honeypot (Chống Spam)
        $honeypot = request()->post('__hp_website');
        if (!empty($honeypot)) {
            // Nếu có dữ liệu trong ô honeypot -> Bot Spam -> Giả vờ thành công nhưng không lưu
            return $this->backWithSuccess($form->success_message ?: 'Cảm ơn bạn đã liên hệ.');
        }
        
        // Kiểm tra CAPTCHA nếu có kích hoạt
        $captcha = \App\Services\Captcha\CaptchaManager::getDriver();
        if ($captcha) {
            $token = request()->post('g-recaptcha-response') ?? request()->post('cf-turnstile-response') ?? '';
            if (!$captcha->verify($token, request()->ip())) {
                return $this->backWithError('Xác minh an toàn (Captcha) thất bại. Vui lòng thử lại.');
            }
        }
        
        // 2. Lấy định nghĩa các field của form
        $fields = FormFieldModel::where('form_id', $id)->get();
        $payload = [];
        
        // 3. Xử lý dữ liệu gửi lên
        foreach ($fields as $field) {
            $name = $field->name;
            $type = $field->type;
            $isRequired = $field->is_required;
            
            // Xử lý File Upload
            if ($type === 'file') {
                if (isset($_FILES[$name]) && $_FILES[$name]['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES[$name];
                    
                    // Validate Error
                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        return $this->backWithError('Có lỗi khi tải lên tệp: ' . $field->label);
                    }
                    
                    // Validate Size (Max 5MB)
                    if ($file['size'] > 5 * 1024 * 1024) {
                        return $this->backWithError('Tệp "' . $field->label . '" vượt quá dung lượng cho phép (Max 5MB).');
                    }
                    
                    // Validate Extension
                    $allowedExts = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($ext, $allowedExts)) {
                        return $this->backWithError('Tệp "' . $field->label . '" không hợp lệ. Chỉ chấp nhận ảnh, pdf, word, excel.');
                    }
                    
                    // Upload File an toàn
                    $uploadDir = ROOT_PATH . '/public/uploads/forms/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                        $payload[$name] = '/uploads/forms/' . $filename;
                    } else {
                        return $this->backWithError('Lỗi lưu tệp: ' . $field->label);
                    }
                } elseif ($isRequired) {
                    return $this->backWithError('Vui lòng chọn tệp cho trường: ' . $field->label);
                }
            } else {
                // Các trường dữ liệu thông thường
                $value = request()->post($name);
                
                // Trường hợp checkbox (có thể trả về array)
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }
                
                if ($isRequired && (trim((string)$value) === '')) {
                    return $this->backWithError('Vui lòng nhập: ' . $field->label);
                }
                
                $payload[$name] = $value;
            }
        }
        
        // 4. Lưu vào Database
        FormSubmissionModel::create([
            'form_id' => $id,
            'data_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => FormSubmissionModel::STATUS_NEW,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        // TODO: Xử lý Gửi Email (nếu form có cấu hình email_to)
        
        // 5. Trả về thông báo thành công
        return $this->backWithSuccess($form->success_message ?: 'Cảm ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất.');
    }
    
    private function backWithError($message) {
        // Tuỳ framework của bạn đang dùng cơ chế redirect nào.
        // Dựa vào các Controller trước đó, tôi dùng redirect()->back() hoặc ->with()
        if (function_exists('redirect')) {
            return redirect(request()->referer())->with('error', $message);
        }
        // Fallback
        $_SESSION['error'] = $message;
        header("Location: " . request()->referer());
        exit;
    }
    
    private function backWithSuccess($message) {
        if (function_exists('redirect')) {
            return redirect(request()->referer())->with('success', $message);
        }
        $_SESSION['success'] = $message;
        header("Location: " . request()->referer());
        exit;
    }
}
