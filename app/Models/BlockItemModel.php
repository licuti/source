<?php
namespace App\Models;

class BlockItemModel extends \Model {
    public $table = '#_block_items';
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    protected array $casts = [
        'data_payload'  => 'json',
        'is_active'     => 'bool',
        'block_id'      => 'int',
        'sort_order'    => 'int',
    ];
}
