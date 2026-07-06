<?php
namespace App\Models;

class GalleryModel extends \Model {
    public $table = '#_galleries';
    public $primaryKey = 'id';
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    // Các cột JSON hỗ trợ đa ngôn ngữ
    protected array $jsonColumns = [
        'title', 'slug', 'description', 'content', 'gallery',
        'seo_title', 'seo_description', 'keyword', 'tags',
        'noindex', 'nofollow', 'seo_head', 'seo_body'
    ];
}
