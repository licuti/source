<?php

namespace App\Models;

class PromoCodeUsageModel extends \App\Core\Database\Model
{
    public $table = 'db_promo_code_usage';
    public $primaryKey = 'id';
    
    
    protected array $fillable = [
        'promo_code_id',
        'user_id',
        'order_id',
        'discount_applied',
        'used_at'
    ];
}
