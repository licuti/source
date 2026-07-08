<?php

namespace App\Models;

/**
 * PageModel
 * Quản lý các trang tĩnh (Giới thiệu, Liên hệ, Chính sách...)
 */
class PageModel extends \App\Core\Model {
    public $table = '#_page'; // Tự động thay thế #_ thành db_
    
    // Các thuộc tính mặc định nếu cần
    public bool $use_lang = true;
    public bool $timestamps = true;
    protected string $createdAt = 'ngay_dang';
    protected string $updatedAt = 'cap_nhat';

    /**
     * Lấy trang theo alias
     */
    public static function findByAlias(string $alias) {
        return self::where('alias', $alias)->where('hien_thi', 1)->first();
    }
}
