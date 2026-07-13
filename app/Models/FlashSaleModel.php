<?php
namespace App\Models;

class FlashSaleModel extends \App\Core\Database\Model {
    use \App\Traits\HasLanguage;
    public $table = 'db_flash_sales';
    public $primaryKey = 'id';
}
