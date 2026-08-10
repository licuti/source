<?php

namespace App\Requests\Admin;

use App\Core\FormRequest;
use App\Models\LanguageModel;

class ProductRequest extends FormRequest {
    
    /**
     * Xác định rule kiểm tra dữ liệu
     */
    public function rules(): array {
        $defaultLang = LanguageModel::getDefault();
        $primaryLang = $defaultLang ? $defaultLang->code : 'vi';

        $rules = [];
        $rules["title.{$primaryLang}"] = 'required|max:255';
        
        // Cấu hình các trường khác để đi qua Validator nếu cần
        $rules['product_type'] = '';
        $rules['sku'] = '';
        $rules['price'] = '';
        $rules['promotional_price'] = '';
        $rules['flash_sale_price'] = '';
        $rules['flash_sale'] = '';
        $rules['flash_sale_start'] = '';
        $rules['flash_sale_end'] = '';
        $rules['status'] = '';
        $rules['is_featured'] = '';
        $rules['is_new'] = '';
        $rules['is_hot'] = '';
        $rules['is_sale'] = '';
        $rules['stock_status'] = '';
        $rules['stock_quantity'] = '';
        $rules['low_stock_amount'] = '';
        $rules['weight'] = '';
        $rules['category_ids'] = '';
        $rules['image'] = '';
        $rules['thumbnail'] = '';
        $rules['gallery'] = '';
        $rules['title'] = '';
        $rules['slug'] = '';
        $rules['description'] = '';
        $rules['content'] = '';
        $rules['specifications'] = '';
        $rules['seo_title'] = '';
        $rules['seo_description'] = '';
        $rules['keyword'] = '';
        $rules['tags'] = '';
        $rules['seo_head'] = '';
        $rules['seo_body'] = '';
        $rules['seo_schema'] = '';
        $rules['seo_canonical'] = '';
        $rules['noindex'] = '';
        $rules['nofollow'] = '';
        $rules['unit'] = '';
        $rules['variants'] = '';
        $rules['product_attributes'] = '';
        $rules['save_action'] = '';

        return $rules;
    }

    /**
     * Tùy chỉnh thông báo lỗi
     */
    public function messages(): array {
        $defaultLang = LanguageModel::getDefault();
        $primaryLang = $defaultLang ? $defaultLang->code : 'vi';

        return [
            "title.{$primaryLang}.required" => 'Tên sản phẩm không được để trống.',
            "title.{$primaryLang}.max"      => 'Tên sản phẩm không được vượt quá 255 ký tự.'
        ];
    }
}
