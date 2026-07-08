<?php
namespace App\Models;

class FormFieldModel extends \App\Core\Model {
    public $table = 'db_form_fields';
    public bool $use_lang = false;
    public bool $timestamps = false;
}
