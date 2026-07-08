<?php
namespace App\Models;

use Model;

class RoleModel extends Model {
    public $table = '#_roles';
    public bool $use_lang = false;
    public bool $use_lang = false;
    public bool $timestamps = true;
    protected string $createdAt = 'created_at';
    protected string $updatedAt = 'updated_at';

    public function permissions() {
        return $this->hasMany(RolePermissionModel::class, 'role_id');
    }
}
