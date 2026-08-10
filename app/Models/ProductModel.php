<?php
namespace App\Models;

use App\Models\CategoryModel;

class ProductModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    public $table = '#_products';
    public bool $timestamps = false;

    // ============================================================
    //  ĐỊNH NGHĨA QUAN HỆ (RELATIONSHIPS)
    // ============================================================

    /**
     * Mối quan hệ Nhiều-1 với Danh mục (Cũ - Giữ lại để tương thích ngược)
     */
    public function category() {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'id');
    }

    /**
     * Mối quan hệ Nhiều-Nhiều với Danh mục (Mới)
     */
    public function categories() {
        return $this->belongsToMany(CategoryModel::class, 'product_category', 'product_id', 'category_id');
    }

    /**
     * Helper lấy danh sách Category IDs của sản phẩm hiện tại
     */
    public function getCategoryIds(): array {
        if (empty($this->id)) return [];
        $catIds = \App\Core\Database\DB::select("SELECT category_id FROM db_product_category WHERE product_id = ?", [$this->id]);
        return array_column($catIds, 'category_id');
    }

    /**
     * Mối quan hệ 1-Nhiều với các Biến thể sản phẩm
     */
    public function variants() {
        return $this->hasMany(ProductVariantModel::class, 'product_id', 'id_code'); // Variants haven't been migrated yet, they might still use id_code
    }

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
        INNER JOIN db_product_category pc ON pc.product_id = sp.id
        LEFT JOIN (
            SELECT product_id,
                   MIN(IF(promotional_price > 0, promotional_price, price)) AS min_v,
                   MAX(IF(promotional_price > 0, promotional_price, price)) AS max_v
            FROM $tableVariant
            GROUP BY product_id
        ) v ON v.product_id = sp.id_code
        WHERE pc.category_id IN ($idList) AND sp.status = 1 $langWhere";

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'min' => (float)($row['abs_min'] ?? 0),
            'max' => (float)($row['abs_max'] ?? 50000000),
        ];
    }

    /**
     * Query scope: Lọc sản phẩm theo trạng thái tồn kho
     */
    public function scopeInStock($query) {
        return $query->whereRaw('(stock_quantity > low_stock_amount)');
    }

    public function scopeLowStock($query) {
        return $query->whereRaw('(stock_quantity > 0 AND stock_quantity <= low_stock_amount)');
    }

    public function scopeOutOfStock($query) {
        return $query->whereRaw('(stock_status = "out_of_stock" OR stock_quantity <= 0)');
    }

    /**
     * Query scope: Gom nhóm bộ lọc (trạng thái, danh mục, từ khóa, tồn kho)
     */
    public function scopeFilter($query, array $filters) {
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['category_id']) && (int)$filters['category_id'] > 0) {
            $catId = (int)$filters['category_id'];
            $query->whereRaw("EXISTS (SELECT 1 FROM db_product_category pc WHERE pc.product_id = #_products.id AND pc.category_id = {$catId})");
        }
        if (!empty($filters['keyword'])) {
            $query->whereLike('title', trim($filters['keyword']));
        }

        $stockFilter = $filters['stock_filter'] ?? 'all';
        if ($stockFilter === 'in_stock') {
            $this->scopeInStock($query);
        } elseif ($stockFilter === 'low_stock') {
            $this->scopeLowStock($query);
        } elseif ($stockFilter === 'out_of_stock') {
            $this->scopeOutOfStock($query);
        }

        return $query;
    }

    /**
     * Tự động cập nhật đường dẫn (URL) trong Menu nếu Sản phẩm thay đổi slug
     */
    public function saved() {
        if (!empty($this->attributes['slug']) && !empty($this->id)) {
            $menuItemModel = new \App\Models\MenuItemModel();
            $menuItemModel->where('object_type', 'product')
                          ->where('object_id', $this->id)
                          ->update(['url' => $this->attributes['slug']]);
        }
    }
}
