<?php
namespace App\Models;

class PostModel extends \App\Core\Database\Model {
    use \App\Traits\Translatable;
    
    public $table = '#_posts';

    // db_posts dùng chuẩn tên tiếng Anh
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    // Khai báo các field được phép mass assign (chỉ chứa các field trong bảng gốc)
    protected array $fillable = [
        'noindex', 'nofollow', 'seo_head', 'seo_body', 'sort_order', 'status', 
        'is_featured', 'view', 'created_by', 'updated_by', 'created_at', 'updated_at'
    ];

    // Khai báo các thuộc tính phụ thuộc ngôn ngữ
    protected array $translatedAttributes = [
        'title', 'slug', 'description', 'content', 
        'seo_title', 'seo_description', 'keyword', 'tags'
    ];

    /**
     * Quan hệ Many-to-Many với CategoryModel
     */
    public function categories() {
        return $this->belongsToMany(CategoryModel::class, 'post_category', 'post_id', 'category_id');
    }

    /**
     * Query scope: Lọc dữ liệu theo quyền sở hữu của user
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
        if (!empty($this->attributes['slug']) && !empty($this->id)) {
            $menuItemModel = new \App\Models\MenuItemModel();
            $menuItemModel->where('object_type', 'post')
                          ->where('object_id', $this->id)
                          ->update(['url' => $this->attributes['slug']]);
        }
    }
}
?>
