<?php
class CategoryModel extends Model {
    public $table = '#_category';
    
    // Lưu trữ mảng Category trên RAM trong 1 vòng đời request để dùng nhiều lần mà không cần Query
    protected static $cachedCategories = null;

    /**
     * Nạp toàn bộ Category vào RAM (Chỉ chạy câu Query 1 lần duy nhất)
     */
    protected static function loadAllCategories() {
        if (self::$cachedCategories === null) {
            // Chỉ lấy các cột cần thiết để tối ưu bộ nhớ
            self::$cachedCategories = self::query()
                ->where('hien_thi', 1)
                ->get('id_code, id_loai');
        }
        return self::$cachedCategories;
    }

    /**
     * Lấy danh sách ID (chuỗi) gồm Danh mục hiện tại và tất cả các danh mục con.
     * Mặc định là Lấy cả Cha lẫn Con.
     */
    public static function getChildrenIds($parentIdCode, $includeParent = true) {
        $categories = self::loadAllCategories();
        $childIds = self::findChildrenRecursive($parentIdCode, $categories); // Sẽ trả về dạng ",101,102"
        
        if ($includeParent) {
            // Tự động bao gồm luôn thằng cha: "100,101,102"
            return $parentIdCode . $childIds; 
        }
        
        // Nếu chỉ muốn lấy con ròng (không lấy cha), bỏ dấu phẩy thừa ở đầu
        return ltrim($childIds, ',');
    }

    /**
     * Helper: Đệ quy mảng siêu nhanh trên RAM thay vì query Database
     */
    protected static function findChildrenRecursive($parentId, &$categories) {
        $childIds = '';
        foreach ($categories as $cat) {
            if ($cat->id_loai == $parentId) {
                $childIds .= ',' . $cat->id_code;
                // Gọi đệ quy tiếp tục tìm con của danh mục hiện tại
                $childIds .= self::findChildrenRecursive($cat->id_code, $categories);
            }
        }
        return $childIds;
    }
    /**
     * Lấy toàn bộ danh mục đang hiển thị
     */
    public static function getAll($parentId = null) {
        $query = self::query()->where('hien_thi', 1);
        if ($parentId !== null) {
            $query->where('id_loai', (int)$parentId);
        }
        return $query->orderBy('so_thu_tu')->orderBy('id', 'DESC')->get();
    }

    /**
     * Lấy cây danh mục (Hierarchical Tree)
     */
    public static function getTree($parentId = 0) {
        $elements = self::getAll();
        return self::buildTree($elements, $parentId);
    }

    /**
     * Lấy toàn bộ danh mục (dành cho Admin, không lọc hien_thi)
     */
    public static function getAllForAdmin($parentId = null) {
        $query = self::query();
        if ($parentId !== null) {
            $query->where('id_loai', (int)$parentId);
        }
        return $query->orderBy('so_thu_tu')->orderBy('id', 'DESC')->get();
    }

    /**
     * Lấy cây danh mục cho Admin (Bao gồm cả danh mục ẩn)
     */
    public static function getTreeForAdmin($parentId = 0) {
        $elements = self::getAllForAdmin();
        return self::buildTree($elements, $parentId);
    }

    /**
     * Hàm hỗ trợ dựng cây đệ quy
     */
    private static function buildTree($elements, $parentId = 0) {
        $branch = array();
        foreach ($elements as $element) {
            if ($element->id_loai == $parentId) {
                $children = self::buildTree($elements, $element->id_code);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }
        return $branch;
    }
}
?>
