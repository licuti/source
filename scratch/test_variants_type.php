<?php
require_once 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();

$row = ProductModel::where('hien_thi', 1)->first();
if ($row) {
    $row = ProductModel::where('id_code', $row->id_code)->with('variants', 'albums')->first();
    echo "variants type: " . gettype($row->variants) . "\n";
    if (!empty($row->variants)) {
        foreach ($row->variants as $v) {
            echo "variant type: " . gettype($v) . "\n";
            if (is_object($v)) {
                echo "variant class: " . get_class($v) . "\n";
            }
            break;
        }
    }
}
