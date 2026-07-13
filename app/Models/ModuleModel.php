<?php

namespace App\Models;

use App\Core\Database\Model;

class ModuleModel extends Model {
    public $table = '#_module';
    
    public bool $timestamps = false;
}
