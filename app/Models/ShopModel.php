<?php
namespace App\Models;

class ShopModel extends \Model {
    public $table = '#_shops';
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    protected array $casts = [
        'status'     => 'int',
        'sort_order'    => 'int',
        'user_id'       => 'int',
        'id_code'       => 'int',
    ];
}
