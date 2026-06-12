<?php
require_once 'app/autoload.php';
require_once 'config/database.php';
$db = new Database(); // Assuming this sets up Model::$pdo
$db->connect();
require_once 'app/Models/ProductModel.php';
require_once 'app/Models/ProductVariantModel.php';
require_once 'app/Services/InventoryService.php';

App\Services\InventoryService::syncProductStock(187);
echo "Synced.\n";
