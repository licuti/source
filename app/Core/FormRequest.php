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
     * Lấy các dữ liệu đã được validate (chỉ lấy các key có trong rules)
     */
    public function validated(): array {
        $rules = $this->rules();
        $keys = array_keys($rules);
        $allData = $this->all();
        
        $validatedData = [];
        foreach ($keys as $key) {
            // Hỗ trợ dot notation đơn giản nếu cần, ở đây chỉ xử lý level 1
            if (array_key_exists($key, $allData)) {
                $validatedData[$key] = $allData[$key];
            }
        }
        return $validatedData;
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
