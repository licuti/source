<?php
namespace App\Models;

use Model;

class RolePermissionModel extends Model {
    public $table = '#_role_permissions';
    public bool $use_lang = false;
    public bool $timestamps = false;

    public function role() {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }

    public function module() {
        return $this->belongsTo(ModuleAdminModel::class, 'module_id');
    }
}
