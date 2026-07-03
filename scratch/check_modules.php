<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$models = \App\Models\ModuleAdminModel::where('alias', 'LIKE', '%post%')
    ->orWhere('route_name', 'LIKE', '%post%')
    ->get();

foreach ($models as $m) {
    echo "ID: {$m->id}, Name: {$m->name}, Alias: {$m->alias}, Route: {$m->route_name}\n";
}
