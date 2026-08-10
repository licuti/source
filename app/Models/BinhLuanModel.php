<?php
namespace App\Models;

class BinhLuanModel extends \App\Core\Database\Model {
    public $table = '#_binhluan';
    

    /**
     * Quan hệ với media (hình ảnh, video bình luận)
     */
    public function media() {
        return $this->hasMany(BinhLuanMediaModel::class, 'id_binhluan', 'id');
    }

    /**
     * Quan hệ với các câu trả lời
     */
    public function replies() {
        return $this->hasMany(BinhLuanModel::class, 'parent', 'id');
    }

    /**
     * Filter condition builder
     */
    protected function applyFilters($query, $product_id, $filters) {
        $query->where('id_sanpham', (int)$product_id)
              ->where('trang_thai', 1)
              ->where('parent', 0);

        if (!empty($filters['bl_star'])) {
            $query->where('danh_gia', (int)$filters['bl_star']);
        }
        
        // Note: EXISTS condition is a bit complex for our simple query builder right now.
        // If queryBuilder doesn't support whereExists easily, we can use whereRaw
        if (!empty($filters['bl_media'])) {
            $prefix = self::$prefix ?? '';
            $query->whereRaw("EXISTS (SELECT 1 FROM " . $prefix . "binhluan_media m WHERE m.id_binhluan = " . $prefix . "binhluan.id)");
        }
        
        return $query;
    }

    /**
     * Lấy danh sách đánh giá
     */
    public function getForProduct($product_id, $filters = [], $limit = 10, $offset = 0) {
        $q = self::query();
        $this->applyFilters($q, $product_id, $filters);
        
        if ($limit > 0) {
            $q->limit($limit, $offset);
        }

        return $q->orderBy('id', 'DESC')
                 ->withMedia()
                 ->withReplies()
                 ->get();
    }

    /**
     * Đếm tổng số đánh giá theo filter
     */
    public function countForProduct($product_id, $filters = []) {
        $q = self::query();
        $this->applyFilters($q, $product_id, $filters);
        return $q->count();
    }

    /**
     * Tổng quan đánh giá (avg, total)
     */
    public static function getSummary($product_id) {
        $sql = "SELECT COUNT(id) AS total, COALESCE(AVG(danh_gia),0) AS avg 
                FROM " . static::tableName() . " 
                WHERE id_sanpham = " . (int)$product_id . " AND trang_thai = 1 AND parent = 0 AND danh_gia > 0";
        $stmt = self::$pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'avg' => $row ? round((float)$row['avg'], 1) : 0,
            'total' => $row ? (int)$row['total'] : 0,
        ];
    }

    /**
     * Lấy số lượng đánh giá theo từng sao (1-5)
     */
    public static function getStarCounts($product_id) {
        $sql = "SELECT danh_gia, COUNT(id) AS cnt 
                FROM " . static::tableName() . " 
                WHERE id_sanpham = " . (int)$product_id . " AND trang_thai = 1 AND parent = 0 AND danh_gia BETWEEN 1 AND 5 
                GROUP BY danh_gia";
        $stmt = self::$pdo->query($sql);
        
        $counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $counts[(int)$row['danh_gia']] = (int)$row['cnt'];
        }
        return $counts;
    }

    /**
     * Đếm số đánh giá có chứa ảnh/video
     */
    public static function countMediaReviews($product_id) {
        $prefix = self::$prefix ?? '';
        $sql = "SELECT COUNT(DISTINCT bl.id) AS cnt 
                FROM " . static::tableName() . " bl
                INNER JOIN " . str_replace('#_', $prefix, '#_binhluan_media') . " m ON m.id_binhluan = bl.id
                WHERE bl.id_sanpham = " . (int)$product_id . " AND bl.trang_thai = 1 AND bl.parent = 0";
        $stmt = self::$pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['cnt'] ?? 0);
    }
}
