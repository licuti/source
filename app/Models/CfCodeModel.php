<?php

class CfCodeModel extends Model {
    public $table = 'cf_code';
    // Bỏ qua kiểm tra Mass Assignment (tương thích hệ thống cũ)
    protected array $guarded = [];
    
    // Tắt tự động thêm timestamp vì bảng cf_code cũ không có created_at/updated_at
    public bool $timestamps = false;
    
    // Tắt tự động thêm điều kiện lọc ngôn ngữ vì bảng cf_code không có cột lang
    public bool $use_lang = false;
}
