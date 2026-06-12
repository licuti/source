<?php
require_once 'app/autoload.php';
require_once 'config/database.php';
require_once 'app/Core/Model.php';
require_once 'app/Models/CategoryModel.php';
use App\Models\CategoryModel;
$cats = CategoryModel::getTreeForAdminByModule(4);
require_once 'app/Helpers/ui.php';
renderCategoryTree($cats);
