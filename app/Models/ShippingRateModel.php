<?php

namespace App\Models;

class ShippingRateModel extends \Model
{
    public $table = 'db_shipping_rates';
    public bool $use_lang = false;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';
}
