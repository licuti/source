<?php
require 'bootstrap/app.php';
$cats = App\Models\CategoryModel::getAllForAdminByModule(4);
print_r($cats);
