<?php

namespace App\Requests\Admin;

use App\Core\FormRequest;

class PostRequest extends FormRequest {
    
    /**
     * Xác định rule kiểm tra dữ liệu
     */
    public function rules(): array {
        $rules = [];
        
        $rules['title'] = 'required|max:255';
        $rules['slug'] = '';
        $rules['description'] = '';
        $rules['content'] = '';
        $rules['seo_title'] = '';
        $rules['seo_description'] = '';
        $rules['keyword'] = '';
        $rules['tags'] = '';
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
            'title.required' => 'Vui lòng nhập Tiêu đề bài viết.',
            'title.max'      => 'Tiêu đề bài viết không được vượt quá 255 ký tự.',
        ];
    }
}
