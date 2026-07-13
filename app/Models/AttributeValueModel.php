<?php
namespace App\Models;

class AttributeValueModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    public $table = '#_attribute_values';

    public function attribute() {
        return $this->belongsTo(AttributeModel::class, 'attribute_id', 'id_code');
    }
}
