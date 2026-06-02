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
     * Eager load đệ quy cho mảng các biến thể (Tránh N+1 query lồng).
     * Cập nhật: dùng setRelation() / getRelation() thay vì dynamic property.
     */
    public static function loadNestedAttributes(array &$variants) {
        if (empty($variants)) return;

        $dummyVariant = new self();
        // 1. Nạp bảng trung gian thuoctinh vào relations
        $dummyVariant->loadRelation($variants, \ProductVariantAttributeModel::tableName(), 'id', 'id_bienthe', 'thuoctinh', true);

        $allThuoctinhs = [];
        foreach ($variants as $v) {
            // Đọc từ relations thay vì dynamic property
            $raw = $v->getRelation('thuoctinh', []);
            if (!empty($raw)) {
                // Ép kiểu array thuần thành Object nếu cần
                $objs = array_map(
                    fn($t) => is_array($t) ? new \ProductVariantAttributeModel($t) : $t,
                    $raw
                );
                // Lưu lại qua setRelation
                $v->setRelation('thuoctinh', $objs);
                $allThuoctinhs = array_merge($allThuoctinhs, $objs);
            }
        }

        // 2. Nạp quan hệ attribute và value cho từng thuoctinh
        if (!empty($allThuoctinhs)) {
            $dummyAttr = new \ProductVariantAttributeModel();
            $dummyAttr->loadRelation(
                $allThuoctinhs,
                \AttributeModel::tableName(),
                'id_thuoctinh', 'id_code',
                'attribute', false,
                "AND lang = '" . LANG . "'"
            );
            $dummyAttr->loadRelation(
                $allThuoctinhs,
                \AttributeValueModel::tableName(),
                'id_thuoctinh_giatri', 'id_code',
                'value', false,
                "AND lang = '" . LANG . "'"
            );

            // Ép kiểu các relation về Object (đã được setRelation bởi loadRelation)
            foreach ($allThuoctinhs as $t) {
                $attr = $t->getRelation('attribute');
                if (is_array($attr)) $t->setRelation('attribute', new \AttributeModel($attr));

                $val = $t->getRelation('value');
                if (is_array($val)) $t->setRelation('value', new \AttributeValueModel($val));
            }
        }
    }
}
