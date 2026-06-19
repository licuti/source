<?php

namespace App\Models;

class ShippingMethodModel extends \Model
{
    public $table = 'db_shipping_methods';
    public bool $use_lang = false;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';
    // Thuộc tính cast JSON
    protected array $casts = [
        'api_config' => 'json'
    ];
}
