<?php
namespace App\Models;

class AttributeModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    public $table = '#_attributes';

    public function values() {
        return $this->hasMany(AttributeValueModel::class, 'attribute_id', 'id_code');
    }
}
