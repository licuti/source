<?php
namespace App\Models;

use App\Core\Database\Model;

class UserModel extends Model {
    public $table = '#_users';
    
    public bool $timestamps = false;
    protected array $hidden = ['password', 'token'];

    public function role() {
        return $this->belongsTo(RoleModel::class, 'role_id');
    }
}
