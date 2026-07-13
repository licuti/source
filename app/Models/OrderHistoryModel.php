<?php

namespace App\Models;

class OrderHistoryModel extends \App\Core\Database\Model
{
    public $table = 'db_order_history';
    public $primaryKey = 'id';
    
    public bool $timestamps = false; // Only created_at is handled manually or by DB default
    
    protected array $fillable = [
        'order_id',
        'status_from',
        'status_to',
        'note',
        'created_by',
        'created_at'
    ];

    public function order()
    {
        return $this->belongsTo(OrderModel::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(UserModel::class, 'created_by');
    }
}
