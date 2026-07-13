<?php
namespace App\Models;

class GalleryModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    public $table = '#_galleries';
    public $primaryKey = 'id';
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    /**
     * Query scope: Áp dụng cấu hình mặc định cho Admin (tắt use_lang)
     */
    public static function adminQuery() {
        $query = static::query();
        $query->withoutGlobalScope('lang');
        return $query;
    }
}
