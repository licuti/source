<?php
namespace App\Models;

class ProductVariantModel extends \App\Core\Database\Model {
    public $table = '#_product_variants';
    
    public bool $timestamps = false;

    /**
     * Mối quan hệ 1-Nhiều với bảng trung gian thuộc tính
     * Dùng tên 'thuoctinh' thay vì 'attributes' để tránh trùng với property $attributes của base Model.
     */
    public function thuoctinh() {
        return $this->hasMany(ProductVariantAttributeModel::class, 'variant_id', 'id');
    }
}
