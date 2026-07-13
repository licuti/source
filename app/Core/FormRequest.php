<?php

namespace App\Core;

use App\Exceptions\ValidationException;

/**
 * Lớp trừu tượng cho FormRequest
 * Kế thừa Request thông thường nhưng có thêm tính năng tự động Validate
 */
abstract class FormRequest extends Request {
    
    /**
     * Khởi tạo FormRequest, kế thừa URI và Params từ Request gốc
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Hàm được gọi bởi Container sau khi khởi tạo thành công class này
     */
    public function validateResolved() {
        $rules = $this->rules();
        $messages = $this->messages();

        $validator = new Validator($this->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Nơi định nghĩa các rule validate (Lớp con phải tự implement)
     */
    abstract public function rules(): array;

    /**
     * Nơi định nghĩa tin nhắn lỗi custom (Lớp con có thể override)
     */
    public function messages(): array {
        return [];
    }

    /**
     * Tùy chọn: Có thể thêm hàm `authorize()` để check quyền (nếu làm full chuẩn Laravel)
     */
    public function authorize(): bool {
        return true;
    }
}
