<?php

namespace App\Models;

class ShippingRateModel extends \App\Core\Database\Model
{
    public $table = 'db_shipping_rates';
    
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    public function country() {
        return $this->belongsTo(LocationModel::class, 'country_id', 'id');
    }

    public function province() {
        return $this->belongsTo(LocationModel::class, 'province_id', 'id');
    }

    public function district() {
        return $this->belongsTo(LocationModel::class, 'district_id', 'id');
    }

    public function ward() {
        return $this->belongsTo(LocationModel::class, 'ward_id', 'id');
    }
}
