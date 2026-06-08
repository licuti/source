<?php
class PostModel extends Model {
    public $table = '#_posts';

    // db_posts dùng chuẩn tên tiếng Anh
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    // TODO: Phát triển các hàm xử lý Tin tức/Bài viết
}
?>
