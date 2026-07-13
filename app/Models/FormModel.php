<?php
namespace App\Models;

class FormModel extends \App\Core\Database\Model {
    public $table = 'db_forms';
    
    public bool $timestamps = true;
    
    public function getFields() {
        return FormFieldModel::where('form_id', $this->id)->orderBy('sort_order', 'ASC')->get();
    }
}
