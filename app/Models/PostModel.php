<?php
namespace App\Models;

class PostModel extends \Model {
    public $table = '#_posts';

    // db_posts dùng chuẩn tên tiếng Anh
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    // Khai báo các field được phép mass assign
    protected array $fillable = [
        'id_code', 'category_id', 'lang', 'title', 'alias', 'description', 
        'content', 'image', 'seo_title', 'seo_description', 'keyword', 'tags', 
        'noindex', 'nofollow', 'seo_head', 'seo_body', 'sort_order', 'status', 
        'is_featured', 'view', 'created_by', 'updated_by', 'created_at', 'updated_at'
    ];

    /**
     * Query scope: Áp dụng cấu hình mặc định cho Admin (tắt use_lang)
     */
    public static function adminQuery() {
        $query = static::query();
        $query->use_lang = false;
        return $query;
    }

    /**
     * Query scope: Lọc dữ liệu theo quyền sở hữu của user
     * @param \App\Core\QueryBuilder $query
     * @param object $user
     * @return \App\Core\QueryBuilder
     */
    public function scopeOwnedByUser($query, $user) {
        if ($user->is_admin != 1) {
            $query->where('created_by', $user->id);
        }
        return $query;
    }
    /**
     * Tự động cập nhật đường dẫn (URL) trong Menu nếu Bài viết thay đổi alias
     */
    public function saved() {
        if (!empty($this->attributes['alias']) && !empty($this->id)) {
            $menuItemModel = new \App\Models\MenuItemModel();
            $menuItemModel->where('object_type', 'post')
                          ->where('object_id', $this->id)
                          ->update(['url' => $this->attributes['alias']]);
        }
    }
}
?>
