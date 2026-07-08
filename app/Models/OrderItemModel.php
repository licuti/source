<?php

namespace App\Models;

class OrderItemModel extends \App\Core\Model
{
    public $table = 'db_order_items';
    public $primaryKey = 'id';
    public bool $use_lang = false;
    
    protected array $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'product_image',
        'attributes_info',
        'quantity',
        'price',
        'total'
    ];

    public function product()
    {
        return $this->belongsTo(ProductModel::class, 'product_id');
    }

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }
}
