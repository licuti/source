<?php
namespace App\Models;

class PostModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    
    public $table = '#_posts';

    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    // Đưa tất cả các cột ngôn ngữ và nội dung vào fillable
    protected array $fillable = [
        'id_code', 'lang', 'title', 'slug', 'description', 'content', 
        'seo_title', 'seo_description', 'keyword', 'tags',
        'noindex', 'nofollow', 'seo_head', 'seo_body', 'seo_schema', 'seo_canonical',
        'sort_order', 'status', 'is_featured', 'view', 'created_by', 'updated_by', 'created_at', 'updated_at'
    ];

    /**
     * Quan hệ Many-to-Many với CategoryModel
     * post_id trong bảng pivot post_category trỏ tới primary key id của post.
     */
    public function categories() {
        return $this->belongsToMany(CategoryModel::class, 'post_category', 'post_id', 'category_id');
    }

    /**
     * Helper lấy danh sách Category IDs của bài viết hiện tại
     */
    public function getCategoryIds(): array {
        if (empty($this->id)) return [];
        $catIds = \App\Core\Database\DB::select("SELECT category_id FROM db_post_category WHERE post_id = ?", [$this->id]);
        return array_column($catIds, 'category_id');
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
