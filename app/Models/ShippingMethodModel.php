<?php

namespace App\Models;

class ShippingMethodModel extends \Model
{
    public $table = 'db_shipping_methods';
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';
    // Thuộc tính cast JSON
    protected array $casts = [
        'api_config' => 'json'
    ];
}
