<?php
namespace App\Models;

class AttributeValueModel extends \Model {
    public $table = '#_attribute_values';

    public function attribute() {
        return $this->belongsTo(AttributeModel::class, 'attribute_id', 'id_code');
    }
}
