<?php

namespace App\Models;

class ShippingRateModel extends \Model
{
    public $table = 'db_shipping_rates';
    public bool $use_lang = false;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    public function province() {
        return $this->belongsTo(ProvinceModel::class, 'province_code', 'code');
    }

    public function district() {
        return $this->belongsTo(DistrictModel::class, 'district_code', 'code');
    }

    public function ward() {
        return $this->belongsTo(WardModel::class, 'ward_code', 'code');
    }
}
