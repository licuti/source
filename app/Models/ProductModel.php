<?php
namespace App\Models;

use CategoryModel;

class ProductModel extends \Model {
    public $table = '#_products';
    public bool $timestamps = false;

    // ============================================================
    //  ĐỊNH NGHĨA QUAN HỆ (RELATIONSHIPS)
    // ============================================================

    /**
     * Mối quan hệ Nhiều-1 với Danh mục
     */
    public function category() {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id_code');
    }

    /**
     * Mối quan hệ 1-Nhiều với các Biến thể sản phẩm
     */
    public function variants() {
        return $this->hasMany(ProductVariantModel::class, 'product_id', 'id_code');
    }

    // ============================================================
    //  CÁC PHƯƠNG THỨC TRUY XUẤT TIỆN ÍCH
    // ============================================================

    /**
     * Lấy sản phẩm tiêu biểu
     */
    public function getFeatured($limit = 10) {
        return self::query()
            ->where('is_featured', 1)
            ->where('status', 1)
            ->latest()
            ->limit($limit)
            ->with('category', 'variants')
            ->get();
    }

    /**
     * Lấy chi tiết sản phẩm theo Slug
     */
    public function getBySlug($slug) {
        return self::query()
            ->where('slug', $slug)
            ->where('status', 1)
            ->with('category', 'variants')
            ->first();
    }

    /**
     * Lấy chi tiết sản phẩm theo ID Code
     */
    public function getByIdCode($id_code) {
        return self::query()
            ->where('id_code', (int)$id_code)
            ->where('status', 1)
            ->with('category', 'variants')
            ->first();
    }

    /**
     * Lấy sản phẩm mới nhất
     */
    public function getLatest($limit = 10) {
        return self::query()
            ->where('status', 1)
            ->latest()
            ->limit($limit)
            ->with('category')
            ->get();
    }

    /**
     * Lấy khoảng giá (min/max) cho danh sách danh mục
     */
    public static function getPriceRange(array $categoryIds): array {
        if (empty($categoryIds)) return ['min' => 0, 'max' => 50000000];

        $tableName    = self::tableName();
        $tableVariant = ProductVariantModel::tableName();
        $idList       = implode(',', array_map('intval', $categoryIds));
        $langWhere    = (defined('_where_lang') && _where_lang !== '') ? _where_lang : '';

        $sql = "SELECT
            MIN(LEAST(
                IF(sp.promotional_price > 0, sp.promotional_price, sp.price),
                COALESCE(v.min_v, 999999999)
            )) AS abs_min,
            MAX(GREATEST(
                IF(sp.promotional_price > 0, sp.promotional_price, sp.price),
                COALESCE(v.max_v, 0)
            )) AS abs_max
        FROM $tableName sp
        LEFT JOIN (
            SELECT product_id,
                   MIN(IF(promotional_price > 0, promotional_price, price)) AS min_v,
                   MAX(IF(promotional_price > 0, promotional_price, price)) AS max_v
            FROM $tableVariant
            GROUP BY product_id
        ) v ON v.product_id = sp.id_code
        WHERE sp.category_id IN ($idList) AND sp.status = 1 $langWhere";

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'min' => (float)($row['abs_min'] ?? 0),
            'max' => (float)($row['abs_max'] ?? 50000000),
        ];
    }
}
