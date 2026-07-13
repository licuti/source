<?php
namespace App\Models;

class RedirectModel extends \App\Core\Database\Model {
    public $table = '#_redirects';
    
    

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
