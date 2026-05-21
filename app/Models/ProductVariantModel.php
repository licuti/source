<?php
class ProductVariantModel extends Model {
    public $table = '#_sanpham_bienthe';
    public bool $use_lang = false;

    /**
     * Mối quan hệ 1-Nhiều với bảng trung gian thuộc tính
     * Dùng tên 'thuoctinh' thay vì 'attributes' để tránh trùng với property $attributes của base Model.
     */
    public function thuoctinh() {
        return $this->hasMany(ProductVariantAttributeModel::class, 'id_bienthe', 'id');
    }
    /**
     * Eager load đệ quy cho mảng các biến thể (Tránh N+1 query lồng)
     */
    public static function loadNestedAttributes(array &$variants) {
        if (empty($variants)) return;

        $dummyVariant = new self();
        // 1. Nạp bảng trung gian thuoctinh
        $dummyVariant->loadRelation($variants, \ProductVariantAttributeModel::tableName(), 'id', 'id_bienthe', 'thuoctinh', true);
        
        $allThuoctinhs = [];
        foreach ($variants as $v) {
            if (!empty($v->thuoctinh)) {
                // Ép kiểu array thuần thành Object
                $objs = array_map(fn($t) => is_array($t) ? new \ProductVariantAttributeModel($t) : $t, $v->thuoctinh);
                $v->thuoctinh = $objs;
                $allThuoctinhs = array_merge($allThuoctinhs, $objs);
            }
        }

        // 2. Nạp quan hệ attribute và value (lọc theo ngôn ngữ hiện tại)
        if (!empty($allThuoctinhs)) {
            $dummyAttr = new \ProductVariantAttributeModel();
            $dummyAttr->loadRelation($allThuoctinhs, \AttributeModel::tableName(), 'id_thuoctinh', 'id_code', 'attribute', false, "AND lang = '".LANG."'");
            $dummyAttr->loadRelation($allThuoctinhs, \AttributeValueModel::tableName(), 'id_thuoctinh_giatri', 'id_code', 'value', false, "AND lang = '".LANG."'");
            
            // Ép kiểu các relation attribute và value thành Object luôn
            foreach ($allThuoctinhs as $t) {
                if (is_array($t->attribute)) $t->attribute = new \AttributeModel($t->attribute);
                if (is_array($t->value)) $t->value = new \AttributeValueModel($t->value);
            }
        }
    }
}
