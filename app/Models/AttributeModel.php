<?php
namespace App\Models;

class AttributeModel extends \App\Core\Model {
    public $table = '#_attributes';

    public function values() {
        return $this->hasMany(AttributeValueModel::class, 'attribute_id', 'id_code');
    }
}
