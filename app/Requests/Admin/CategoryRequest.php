<?php

namespace App\Requests\Admin;

use App\Core\FormRequest;
use App\Models\CategoryModel;

class CategoryRequest extends FormRequest {
    
    /**
     * Xác định rule kiểm tra dữ liệu
     */
    public function rules(): array {
        $rules = [];
        
        $rules['title'] = 'required|max:255';
        $rules['parent_id'] = 'numeric';
        $rules['sort_order'] = 'numeric';
        $rules['image'] = '';
        $rules['banner'] = '';
        $rules['module'] = '';
        $rules['status'] = '';
        $rules['is_featured'] = '';
        $rules['slug'] = '';
        $rules['description'] = '';
        $rules['content'] = '';
        $rules['seo_title'] = '';
        $rules['keyword'] = '';
        $rules['seo_description'] = '';
        $rules['seo_head'] = '';
        $rules['seo_body'] = '';
        $rules['seo_schema'] = '';
        $rules['seo_canonical'] = '';
        $rules['lang'] = '';
        $rules['id'] = '';
        $rules['created_at'] = '';
        $rules['save_action'] = '';
        
        return $rules;
    }

    /**
     * Tùy chỉnh thông báo lỗi
     */
    public function messages(): array {
        return [
            'title.required'    => 'Tên danh mục không được để trống',
            'title.max'         => 'Tên danh mục không được vượt quá 255 ký tự',
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
