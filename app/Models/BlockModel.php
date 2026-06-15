<?php
namespace App\Models;

class BlockModel extends \Model {
    public $table = '#_blocks';
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    protected array $casts = [
        'schema_config' => 'json',
        'is_active'     => 'bool',
        'sort_order'    => 'int',
    ];
}
