<?php
namespace App\Models;

class FormFieldModel extends \App\Core\Database\Model {
    public $table = 'db_form_fields';
    
    public bool $timestamps = false;
}
