<?php
class ProductModel extends Model {
    public $table = '#_sanpham';

    // ============================================================
    //  ĐỊNH NGHĨA QUAN HỆ (RELATIONSHIPS)
    // ============================================================

    /**
     * Mối quan hệ Nhiều-1 với Danh mục
     */
    public function category() {
        return $this->belongsTo(CategoryModel::class, 'id_loai', 'id_code');
    }

    /**
     * Mối quan hệ 1-Nhiều với Album hình ảnh
     */
    public function albums() {
        return $this->hasMany(ProductAlbumModel::class, 'id_sp', 'id_code');
    }

    /**
     * Mối quan hệ 1-Nhiều với các Biến thể sản phẩm
     */
    public function variants() {
        return $this->hasMany(ProductVariantModel::class, 'id_sanpham', 'id_code');
    }

    // ============================================================
    //  CÁC PHƯƠNG THỨC TRUY XUẤT TIỆN ÍCH
    // ============================================================

    /**
     * Lấy sản phẩm tiêu biểu
     */
    public function getFeatured($limit = 10) {
        return self::query()
            ->where('tieu_bieu', 1)
            ->where('hien_thi', 1)
            ->latest()
            ->limit($limit)
            ->with('category', 'variants')
            ->get();
    }

    /**
     * Lấy chi tiết sản phẩm theo Alias
     */
    public function getByAlias($alias) {
        return self::query()
            ->where('alias', $alias)
            ->where('hien_thi', 1)
            ->with('category', 'variants', 'albums')
            ->first();
    }

    /**
     * Lấy chi tiết sản phẩm theo ID Code
     */
    public function getByIdCode($id_code) {
        return self::query()
            ->where('id_code', (int)$id_code)
            ->where('hien_thi', 1)
            ->with('category', 'variants', 'albums')
            ->first();
    }

    /**
     * Lấy sản phẩm mới nhất
     */
    public function getLatest($limit = 10) {
        return self::query()
            ->where('hien_thi', 1)
            ->latest()
            ->limit($limit)
            ->with('category')
            ->get();
    }

    /**
     * Lấy khoảng giá (min/max) cho danh sách danh mục
     * Giữ nguyên SQL thuần để tối ưu hiệu năng tính toán min/max từ nhiều bảng
     */
    public static function getPriceRange(array $categoryIds): array {
        if (empty($categoryIds)) return ['min' => 0, 'max' => 50000000];

        $tableName    = self::tableName();
        $tableVariant = ProductVariantModel::tableName();
        $idList       = implode(',', array_map('intval', $categoryIds));
        $langWhere    = (defined('_where_lang') && _where_lang !== '') ? _where_lang : '';

        $sql = "SELECT
            MIN(LEAST(
                IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia),
                COALESCE(v.min_v, 999999999)
            )) AS abs_min,
            MAX(GREATEST(
                IF(sp.khuyen_mai > 0, sp.khuyen_mai, sp.gia),
                COALESCE(v.max_v, 0)
            )) AS abs_max
        FROM $tableName sp
        LEFT JOIN (
            SELECT id_sanpham,
                   MIN(IF(khuyen_mai > 0, khuyen_mai, gia)) AS min_v,
                   MAX(IF(khuyen_mai > 0, khuyen_mai, gia)) AS max_v
            FROM $tableVariant
            GROUP BY id_sanpham
        ) v ON v.id_sanpham = sp.id_code
        WHERE sp.id_loai IN ($idList) AND sp.hien_thi = 1 $langWhere";

        $stmt = self::$pdo->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'min' => (float)($row['abs_min'] ?? 0),
            'max' => (float)($row['abs_max'] ?? 50000000),
        ];
    }
}
