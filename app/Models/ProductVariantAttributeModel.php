<?php
namespace App\Models;

class ProductVariantAttributeModel extends \Model {
    public $table = '#_product_variant_attributes';
    public bool $use_lang = false;

    /**
     * Thuộc tính (Màu sắc, Size...)
     */
    public function attribute() {
        return $this->belongsTo(AttributeModel::class, 'attribute_id', 'id_code');
    }

    /**
     * Giá trị thuộc tính (Đỏ, Xanh, XL, XXL...)
     */
    public function value() {
        return $this->belongsTo(AttributeValueModel::class, 'attribute_value_id', 'id_code');
    }
}
