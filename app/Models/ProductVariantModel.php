<?php
namespace App\Models;

class ProductVariantModel extends \Model {
    public $table = '#_product_variants';
    public bool $use_lang = false;

    /**
     * Mối quan hệ 1-Nhiều với bảng trung gian thuộc tính
     * Dùng tên 'thuoctinh' thay vì 'attributes' để tránh trùng với property $attributes của base Model.
     */
    public function thuoctinh() {
        return $this->hasMany(ProductVariantAttributeModel::class, 'variant_id', 'id');
    }

    /**
     * Eager load đệ quy cho mảng các biến thể (Tránh N+1 query lồng).
     */
    public static function loadNestedAttributes(array &$variants) {
        if (empty($variants)) return;

        $dummyVariant = new self();
        // 1. Nạp bảng trung gian thuoctinh vào relations
        $dummyVariant->loadRelation($variants, ProductVariantAttributeModel::tableName(), 'id', 'variant_id', 'thuoctinh', true);

        $allThuoctinhs = [];
        foreach ($variants as $v) {
            $raw = $v->getRelation('thuoctinh', []);
            if (!empty($raw)) {
                $objs = array_map(
                    fn($t) => is_array($t) ? new ProductVariantAttributeModel($t) : $t,
                    $raw
                );
                $v->setRelation('thuoctinh', $objs);
                $allThuoctinhs = array_merge($allThuoctinhs, $objs);
            }
        }

        // 2. Nạp quan hệ attribute và value cho từng thuoctinh
        if (!empty($allThuoctinhs)) {
            $dummyAttr = new ProductVariantAttributeModel();
            $dummyAttr->loadRelation(
                $allThuoctinhs,
                AttributeModel::tableName(),
                'attribute_id', 'id_code',
                'attribute', false,
                "AND lang = '" . LANG . "'"
            );
            $dummyAttr->loadRelation(
                $allThuoctinhs,
                AttributeValueModel::tableName(),
                'attribute_value_id', 'id_code',
                'value', false,
                "AND lang = '" . LANG . "'"
            );

            foreach ($allThuoctinhs as $t) {
                $attr = $t->getRelation('attribute');
                if (is_array($attr)) $t->setRelation('attribute', new AttributeModel($attr));

                $val = $t->getRelation('value');
                if (is_array($val)) $t->setRelation('value', new AttributeValueModel($val));
            }
        }
    }
}
