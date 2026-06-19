<?php

namespace App\Models;

class ShippingRateModel extends \Model
{
    public $table = 'db_shipping_rates';
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';
}
