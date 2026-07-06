<?php
namespace App\Models;

class RedirectModel extends \Model {
    public $table = '#_redirects';
    public bool $use_lang = false;

    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';
    
    protected array $fillable = [
        'old_url',
        'new_url',
        'status',
        'created_at',
        'updated_at'
    ];
}
