<?php
namespace App\Models;

class FormModel extends \Model {
    public $table = 'db_forms';
    public bool $use_lang = false;
    public bool $timestamps = true;
    
    public function getFields() {
        return FormFieldModel::where('form_id', $this->id)->orderBy('sort_order', 'ASC')->get();
    }
}
