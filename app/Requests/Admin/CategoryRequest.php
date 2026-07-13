<?php

namespace App\Requests\Admin;

use App\Core\FormRequest;
use App\Models\CategoryModel;

class CategoryRequest extends FormRequest {
    
    /**
     * Xác định rule kiểm tra dữ liệu
     */
    public function rules(): array {
        $langs = config('language_presets'); // Hoặc lấy từ $_SESSION['app_locale'] tuỳ cấu trúc hiện tại
        // Đơn giản hóa: Chúng ta lấy danh sách ngôn ngữ đang được hỗ trợ
        $appLangs = array_keys(config('lang', ['vi' => 'Tiếng Việt', 'en' => 'English']));
        
        $rules = [];
        
        // title của ít nhất một ngôn ngữ (mặc định là vi) phải có
        $rules['title.vi'] = 'required|max:255';
        
        $rules['parent_id'] = 'numeric';
        $rules['sort_order'] = 'numeric';
        
        return $rules;
    }

    /**
     * Tùy chỉnh thông báo lỗi
     */
    public function messages(): array {
        return [
            'title.vi.required' => 'Tên danh mục (Tiếng Việt) không được để trống',
            'title.vi.max'      => 'Tên danh mục không được vượt quá 255 ký tự',
            'parent_id.numeric' => 'Danh mục cha không hợp lệ',
            'sort_order.numeric'=> 'Số thứ tự phải là số',
        ];
    }
    
    /**
     * Chạy logic validate bổ sung (nếu cần)
     */
    public function validateResolved() {
        // Chạy validate rules() trước
        parent::validateResolved();
        
        // Validate chống loop parent_id
        $id = (int) $this->input('id', 0);
        $parentId = (int) $this->input('parent_id', 0);
        
        if ($id > 0 && $id === $parentId) {
            $this->addError('parent_id', 'Danh mục cha không thể là chính nó!');
        }
    }
}
