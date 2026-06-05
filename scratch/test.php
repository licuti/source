<?php
define('DEBUG_ROUTE', true);
require __DIR__ . '/../app/autoload.php';
$dbConfig = require __DIR__ . '/../config/database.php';
require __DIR__ . '/../admin/lib/class.php';
\func_index::$shared_config = $dbConfig;
\Model::boot($dbConfig);
require __DIR__ . '/../app/Models/UserModel.php';

$user = \UserModel::where('user_hash', '123')->first();
var_dump($user);
