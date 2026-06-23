<?php 
require 'app/autoload.php'; 
loadEnv(dirname(__DIR__) . '/.env');
$data = \App\Models\OrderModel::select('SUM(grand_total) as total')->first();
print_r($data->toArray());
