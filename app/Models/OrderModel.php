<?php

namespace App\Models;

class OrderModel extends \Model
{
    public $table = 'db_orders';
    public $primaryKey = 'id';
    public bool $use_lang = false;
    
    protected array $fillable = [
        'order_code',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'province_id',
        'district_id',
        'ward_id',
        'subtotal',
        'shipping_fee',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'payment_method_id',
        'shipping_method_id',
        'promo_code_id',
        'payment_status',
        'order_status',
        'customer_note',
        'shop_note'
    ];

    public function items()
    {
        return $this->hasMany(OrderItemModel::class, 'order_id');
    }

    public function history()
    {
        return $this->hasMany(OrderHistoryModel::class, 'order_id')->orderBy('created_at', 'DESC');
    }

    public function customer()
    {
        return $this->belongsTo(CustomerModel::class, 'customer_id');
    }
}
