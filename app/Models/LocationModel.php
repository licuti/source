<?php

namespace App\Models;

class LocationModel extends \App\Core\Database\Model
{
    public $table = 'db_locations';
    
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    public function parent()
    {
        return $this->belongsTo(LocationModel::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(LocationModel::class, 'parent_id', 'id');
    }
}
