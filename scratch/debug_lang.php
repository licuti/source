<?php
require 'app/autoload.php';
$config = include 'config/database.php';
\Model::boot($config);
\Model::setGlobalConstraint("AND lang='vi'");

$valModel = \AttributeValueModel::where('id_code', 735)->first();
var_dump($valModel ? $valModel->attributes : 'Not Found');
