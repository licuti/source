<?php

namespace App\Models;

use App\Core\Model;

class ShippingMethodModel extends Model
{
    protected $table = 'db_shipping_methods';
    protected $primaryKey = 'id';
    
    // Thuộc tính cast JSON
    protected $casts = [
        'api_config' => 'json'
    ];
}
