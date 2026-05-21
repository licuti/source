<?php
class ProductVariantAttributeModel extends Model {
    public $table = '#_sanpham_bienthe_thuoctinh';
    public bool $use_lang = false;

    // Quan hệ với bảng Thuộc tính gốc (Màu sắc, Kích thước...)
    public function attribute() {
        return $this->belongsTo(AttributeModel::class, 'id_thuoctinh', 'id_code');
    }

    // Quan hệ với bảng Giá trị thuộc tính (Đỏ, Xanh, XL, XXL...)
    public function value() {
        return $this->belongsTo(AttributeValueModel::class, 'id_thuoctinh_giatri', 'id_code');
    }
}
