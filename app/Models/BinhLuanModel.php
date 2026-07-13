<?php
namespace App\Models;

class BinhLuanModel extends \App\Core\Database\Model {
    public $table = '#_binhluan';
    

    /**
     * Eager load media for reviews
     */
    public function withMedia(&$data = null) {
        if ($data === null) {
            $this->qb_with[] = __FUNCTION__;
            return $this;
        }
        $table = str_replace('#_', self::$prefix, '#_binhluan_media');
        $this->loadRelation($data, $table, 'id', 'id_binhluan', 'media', true);
        
        // Cast raw arrays to objects
        if (is_array($data)) {
            foreach ($data as &$item) {
                if (!empty($item->media)) {
                    $item->media = array_map(fn($m) => is_array($m) ? (object)$m : $m, $item->media);
                }
            }
        } elseif (is_object($data) && !empty($data->media)) {
            $data->media = array_map(fn($m) => is_array($m) ? (object)$m : $m, $data->media);
        }
        return $data;
    }

    /**
     * Eager load replies for reviews
     */
    public function withReplies(&$data = null) {
        if ($data === null) {
            $this->qb_with[] = __FUNCTION__;
            return $this;
        }
        // Thêm điều kiện trang_thai = 1 cho replies
        $instance = new static();
        // Cần custom logic cho loadRelation có điều kiện, hoặc tạm dùng loadRelation cơ bản
        // Tốt nhất là thêm callback vào loadRelation trong tương lai.
        // Hiện tại loadRelation chưa hỗ trợ condition phụ, ta lấy hết rồi filter hoặc query riêng.
        // Dùng loadRelation chuẩn:
        $data = $this->loadRelation($data, static::tableName(), 'id', 'parent', 'replies', true);
        
        // Filter replies with trang_thai = 1 and cast to Object
        if (is_array($data)) {
            foreach ($data as &$item) {
                if (isset($item->replies) && is_array($item->replies)) {
                    $filtered = array_filter($item->replies, function($reply) {
                        $stt = is_array($reply) ? ($reply['trang_thai'] ?? 0) : ($reply->trang_thai ?? 0);
                        return (int)$stt === 1;
                    });
                    $item->replies = array_values(array_map(fn($r) => is_array($r) ? new static($r) : $r, $filtered));
                }
            }
        } elseif (is_object($data)) {
            if (isset($data->replies) && is_array($data->replies)) {
                $filtered = array_filter($data->replies, function($reply) {
                    $stt = is_array($reply) ? ($reply['trang_thai'] ?? 0) : ($reply->trang_thai ?? 0);
                    return (int)$stt === 1;
                });
                $data->replies = array_values(array_map(fn($r) => is_array($r) ? new static($r) : $r, $filtered));
            }
        }
        
        return $data;
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
