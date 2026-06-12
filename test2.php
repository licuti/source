<?php
require_once 'bootstrap/app.php';
$cats = App\Models\CategoryModel::getAllForAdminByModule(4);
$cat = $cats[0] ?? null;
if ($cat) {
    file_put_contents('test_log.txt', print_r($cat->attributes, true));
} else {
    file_put_contents('test_log.txt', "No categories found");
}
