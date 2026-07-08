<?php

namespace App\Models;

class PromoCodeModel extends \App\Core\Model
{
    public $table = 'db_promo_codes';
    public $primaryKey = 'id';
    public bool $use_lang = false;
    
    protected array $fillable = [
        'shop_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'max_discount_amount',
        'min_order_amount',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_per_user',
        'apply_to',
        'is_active'
    ];

    public function generateUniqueCode($prefix = 'PROMO', $length = 8)
    {
        do {
            $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
        } while (self::where('code', $code)->exists());
        
        return $code;
    }
}
