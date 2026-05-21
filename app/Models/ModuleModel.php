<?php

namespace App\Models;

use Model;

class ModuleModel extends Model {
    public $table = '#_module';
    public bool $use_lang = false;
    public bool $timestamps = false;
}
